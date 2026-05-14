<?php

namespace Tests\Unit\Services\CreditLine\Matching;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Services\CreditLine\Matching\Contracts\ScheduleAdvancer;
use App\Services\CreditLine\Matching\Exceptions\InvalidMatchResolutionException;
use App\Services\CreditLine\Matching\MatchResolutionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for MatchResolutionService (UC-31).
 */
class MatchResolutionServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private MatchResolutionService $service;
    private ScheduleAdvancer $advancerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();

        $this->advancerMock = Mockery::mock(ScheduleAdvancer::class);
        $this->advancerMock->shouldReceive('advance')->andReturnNull()->byDefault();

        $this->service = new MatchResolutionService($this->advancerMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeActiveLine(int $accountId): AccountCreditLine
    {
        return AccountCreditLine::factory()->create([
            'account_id'         => $accountId,
            'status'             => AccountCreditLineExt::STATUS_ACTIVE,
            'outstanding_shares' => 10.0,
            'principal_shares'   => 10.0,
            'term_months'        => 12,
            'payment_frequency'  => AccountCreditLineExt::FREQUENCY_MONTHLY,
            'origination_date'   => '2026-01-01',
            'maturity_date'      => '2027-01-01',
        ]);
    }

    private function makeFlaggedRepTran(int $accountId, string $matchStatus): TransactionExt
    {
        /** @var TransactionExt $tran */
        $tran = Transaction::factory()->create([
            'account_id'              => $accountId,
            'type'                    => TransactionExt::TYPE_REPAY,
            'status'                  => TransactionExt::STATUS_PENDING,
            'shares'                  => 5.0,
            'value'                   => 5.0,
            'timestamp'               => '2026-05-01',
            'credit_line_match_status' => $matchStatus,
            'account_credit_line_id'  => null,
        ]);
        return $tran;
    }

    // ── UC-31: Resolve ambiguous → sets manual + FK ───────────────────────────

    public function test_resolve_ambiguous_sets_manual_and_fk(): void
    {
        $accountId = $this->factory->userAccount->id;
        $line      = $this->makeActiveLine($accountId);
        $tran      = $this->makeFlaggedRepTran($accountId, TransactionExt::MATCH_STATUS_AMBIGUOUS);

        $this->advancerMock->shouldReceive('advance')->once()->with(
            Mockery::type(TransactionExt::class),
            Mockery::type(AccountCreditLine::class)
        );

        $this->service->resolve($tran, $line);

        $tran->refresh();

        $this->assertEquals(TransactionExt::MATCH_STATUS_MANUAL, $tran->credit_line_match_status);
        $this->assertEquals($line->id, $tran->account_credit_line_id);
    }

    // ── UC-31: Resolve unmatched → sets manual + FK ───────────────────────────

    public function test_resolve_unmatched_sets_manual_and_fk(): void
    {
        $accountId = $this->factory->userAccount->id;
        $line      = $this->makeActiveLine($accountId);
        $tran      = $this->makeFlaggedRepTran($accountId, TransactionExt::MATCH_STATUS_UNMATCHED);

        $this->service->resolve($tran, $line);

        $tran->refresh();

        $this->assertEquals(TransactionExt::MATCH_STATUS_MANUAL, $tran->credit_line_match_status);
        $this->assertEquals($line->id, $tran->account_credit_line_id);
    }

    // ── Throws when transaction is already matched ─────────────────────────────

    public function test_throws_when_already_matched(): void
    {
        $this->expectException(InvalidMatchResolutionException::class);
        $this->expectExceptionMessageMatches('/cannot be resolved.*auto_matched/i');

        $accountId = $this->factory->userAccount->id;
        $line      = $this->makeActiveLine($accountId);

        $tran = Transaction::factory()->create([
            'account_id'              => $accountId,
            'type'                    => TransactionExt::TYPE_REPAY,
            'status'                  => TransactionExt::STATUS_PENDING,
            'shares'                  => 5.0,
            'value'                   => 5.0,
            'timestamp'               => '2026-05-01',
            'credit_line_match_status' => TransactionExt::MATCH_STATUS_AUTO_MATCHED,
            'account_credit_line_id'  => $line->id,
        ]);

        $this->service->resolve($tran, $line);
    }

    // ── Throws when line belongs to a different account ───────────────────────

    public function test_throws_when_account_mismatch(): void
    {
        $this->expectException(InvalidMatchResolutionException::class);
        $this->expectExceptionMessageMatches('/account/i');

        $accountId = $this->factory->userAccount->id;

        // Create a second user and account
        $this->factory->createUser();
        $otherAccount = $this->factory->userAccount;

        $line = $this->makeActiveLine($otherAccount->id); // line belongs to OTHER account

        $tran = $this->makeFlaggedRepTran($accountId, TransactionExt::MATCH_STATUS_AMBIGUOUS);

        $this->service->resolve($tran, $line);
    }

    // ── Throws when status is null (non-credit-line transaction) ─────────────

    public function test_throws_when_status_is_null(): void
    {
        $this->expectException(InvalidMatchResolutionException::class);

        $accountId = $this->factory->userAccount->id;
        $line      = $this->makeActiveLine($accountId);

        $tran = Transaction::factory()->create([
            'account_id'              => $accountId,
            'type'                    => TransactionExt::TYPE_REPAY,
            'status'                  => TransactionExt::STATUS_PENDING,
            'shares'                  => 5.0,
            'value'                   => 5.0,
            'timestamp'               => '2026-05-01',
            'credit_line_match_status' => null,
            'account_credit_line_id'  => null,
        ]);

        $this->service->resolve($tran, $line);
    }
}
