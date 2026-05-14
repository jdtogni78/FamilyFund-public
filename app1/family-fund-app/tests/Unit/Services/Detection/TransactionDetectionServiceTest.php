<?php

namespace Tests\Unit\Services\Detection;

use App\Mail\CreditLine\MismatchAlertMail;
use App\Mail\CreditLine\TransactionReceivedMail;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Services\Detection\CreditLineClassifier;
use App\Services\Detection\ContributionClassifier;
use App\Services\Detection\DetectionResult;
use App\Services\Detection\EmailDedup;
use App\Services\Detection\TransactionDetectionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

class TransactionDetectionServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed CASH asset (required by TransactionExt processing; see TradePortfolioControllerExtTest:29-33)
        \App\Models\Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
    }

    // ── DetectionResult value object ─────────────────────────────────────────

    public function test_detection_result_factories(): void
    {
        $r = DetectionResult::autoMatched(42, 'test');
        $this->assertSame(DetectionResult::STATUS_AUTO_MATCHED, $r->status);
        $this->assertSame(42, $r->targetCreditLineId);
        $this->assertFalse($r->needsReview());

        $r2 = DetectionResult::ambiguous('no match');
        $this->assertSame(DetectionResult::STATUS_AMBIGUOUS, $r2->status);
        $this->assertNull($r2->targetCreditLineId);
        $this->assertTrue($r2->needsReview());

        $r3 = DetectionResult::unmatched();
        $this->assertTrue($r3->needsReview());

        $r4 = DetectionResult::notApplicable();
        $this->assertSame(DetectionResult::STATUS_NA, $r4->status);
        $this->assertFalse($r4->needsReview());
    }

    // ── EmailDedup ────────────────────────────────────────────────────────────

    public function test_email_dedup_marks_and_checks(): void
    {
        Cache::flush();
        $dedup = new EmailDedup();

        $this->assertFalse($dedup->wasSent(999, 'transaction_received'));
        $dedup->markSent(999, 'transaction_received');
        $this->assertTrue($dedup->wasSent(999, 'transaction_received'));
        // Different type is not affected
        $this->assertFalse($dedup->wasSent(999, 'mismatch_alert'));
    }

    // ── TransactionDetectionService — BOR routes to CreditLineClassifier ─────

    public function test_ingest_bor_dispatches_credit_line_classifier_and_sends_email(): void
    {
        Mail::fake();
        Cache::flush();

        $account = $this->factory->userAccount;

        // Give account an email so mail can be dispatched
        $account->email_cc = 'test@example.com';
        $account->save();

        $tran = $this->factory->createTransaction(
            100,
            $account,
            TransactionExt::TYPE_BORROW,
            TransactionExt::STATUS_PENDING
        );
        $tran = TransactionExt::find($tran->id);

        // Mock CreditLineClassifier to return auto_matched result
        $mockClassifier = \Mockery::mock(CreditLineClassifier::class);
        $mockClassifier->shouldReceive('classify')
            ->once()
            ->andReturn(DetectionResult::autoMatched(1));

        $service = new TransactionDetectionService(
            $mockClassifier,
            new ContributionClassifier(),
            new EmailDedup()
        );

        $service->ingest($tran);

        Mail::assertSent(TransactionReceivedMail::class, function ($mail) {
            return $mail->tran->type === TransactionExt::TYPE_BORROW;
        });
    }

    public function test_ingest_bor_twice_sends_only_one_email(): void
    {
        Mail::fake();
        Cache::flush();

        $account = $this->factory->userAccount;
        $account->email_cc = 'test@example.com';
        $account->save();

        $tran = $this->factory->createTransaction(
            100,
            $account,
            TransactionExt::TYPE_BORROW,
            TransactionExt::STATUS_PENDING
        );
        $tran = TransactionExt::find($tran->id);

        $mockClassifier = \Mockery::mock(CreditLineClassifier::class);
        $mockClassifier->shouldReceive('classify')
            ->twice()
            ->andReturn(DetectionResult::autoMatched(1));

        $dedup = new EmailDedup();

        $service = new TransactionDetectionService(
            $mockClassifier,
            new ContributionClassifier(),
            $dedup
        );

        $service->ingest($tran);
        $service->ingest($tran); // second call — dedup must suppress email

        Mail::assertSentCount(1);
    }

    public function test_ingest_rep_with_ambiguous_result_sends_mismatch_alert(): void
    {
        Mail::fake();
        Cache::flush();

        $account = $this->factory->userAccount;
        $account->email_cc = 'test@example.com';
        $account->save();

        $tran = $this->factory->createTransaction(
            100,
            $account,
            TransactionExt::TYPE_REPAY,
            TransactionExt::STATUS_PENDING
        );
        $tran = TransactionExt::find($tran->id);

        $mockClassifier = \Mockery::mock(CreditLineClassifier::class);
        $mockClassifier->shouldReceive('classify')
            ->once()
            ->andReturn(DetectionResult::ambiguous('matches 2 lines'));

        $service = new TransactionDetectionService(
            $mockClassifier,
            new ContributionClassifier(),
            new EmailDedup()
        );

        $service->ingest($tran);

        Mail::assertSent(MismatchAlertMail::class);
    }

    /**
     * UC-37: contribution classifier reports legacy TransactionMatching rows
     * (no duplication — `processPending`/`createMatching` is the writer).
     */
    public function test_contribution_classifier_round_trip_reports_existing_matches(): void
    {
        $account = $this->factory->userAccount;

        // Attach a matching rule (createFund alone doesn't create one).
        $this->factory->createMatching(100, 50);

        // Create a PUR (no matchings written yet).
        $pur = $this->factory->createTransaction(
            100,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING
        );
        $pur = TransactionExt::find($pur->id);

        $classifier = new ContributionClassifier();

        // Step 1: no matchings yet → n/a (acceptable PUR state — see classifier doc).
        $result1 = $classifier->classify($pur);
        $this->assertSame(DetectionResult::STATUS_NA, $result1->status);

        // Step 2: simulate the legacy createMatching writer by writing a
        // TransactionMatching row pointing at this PUR.
        $accountMatchingRule = $account->accountMatchingRules()->first();
        $this->assertNotNull($accountMatchingRule, 'createMatching should attach an AccountMatchingRule to the user account');

        $matchTran = $this->factory->createTransaction(
            50,
            $account,
            TransactionExt::TYPE_MATCHING,
            TransactionExt::STATUS_CLEARED
        );
        $this->factory->createTransactionMatching($accountMatchingRule, $matchTran, $pur);

        // Step 3: classifier now reports auto_matched.
        $result2 = $classifier->classify($pur);
        $this->assertSame(DetectionResult::STATUS_AUTO_MATCHED, $result2->status);
        $this->assertFalse($result2->needsReview());
    }

    public function test_ingest_pur_calls_contribution_classifier_but_no_credit_line_email(): void
    {
        Mail::fake();
        Cache::flush();

        $account = $this->factory->userAccount;
        $account->email_cc = 'test@example.com';
        $account->save();

        $tran = $this->factory->createTransaction(
            100,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING
        );
        $tran = TransactionExt::find($tran->id);

        $mockContrib = \Mockery::mock(ContributionClassifier::class);
        $mockContrib->shouldReceive('classify')
            ->once()
            ->andReturn(DetectionResult::notApplicable('stub'));

        $service = new TransactionDetectionService(
            new CreditLineClassifier(),
            $mockContrib,
            new EmailDedup()
        );

        $service->ingest($tran);

        // No credit-line emails for PUR
        Mail::assertNotSent(TransactionReceivedMail::class);
        Mail::assertNotSent(MismatchAlertMail::class);
    }
}
