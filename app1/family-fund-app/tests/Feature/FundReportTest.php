<?php
namespace Tests\Feature;

use App\Mail\FundQuarterlyReport;
use App\Mail\FundReportEmail;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Log;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\ApiTestTrait;
use Tests\DataFactory;

/**
 * @group needs-data-refactor
 * Test requires complex data setup with transactions for fund reports
 */
class FundReportTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    public $asOf;
    public $fund;
    public array $post;

    public function setUp(): void
    {
        parent::setUp();
        $this->asOf   = '2022-03-01';
        $this->verbose = false;
    }

    public function testEmail()
    {
        $factory = new DataFactory();
        $factory->createFundWithMatching();
        $fund = $this->fund = $factory->fund;
        $factory->fundAccount->email_cc = $email = "admin@dev.familyfund.local";
        $factory->fundAccount->save();
        $factory->createAssetWithPrice();
        $factory->createAssetWithPrice();

        Mail::fake();
        $this->postAPI();
        Mail::assertSent(FundReportEmail::class, function ($mail) use ($email, $fund) {
            return $mail->fundReport->fund->id === $fund->id &&
                $mail->hasTo($email);
        });

        // test with no email_cc
        $factory->fundAccount->email_cc = null;
        $factory->fundAccount->save();

        Mail::fake();
        $this->postAPI('ADM', Response::HTTP_UNPROCESSABLE_ENTITY);
        Mail::assertNotSent(FundReportEmail::class);


        Mail::fake();
        $email = $factory->userAccount->email_cc;
        $factory->userAccount->email_cc = null;
        $factory->userAccount->save();
        $this->postAPI('ALL', Response::HTTP_UNPROCESSABLE_ENTITY);
        Mail::assertNotSent(FundReportEmail::class);

        Mail::fake();
        $factory->userAccount->email_cc = $email;
        $factory->userAccount->save();
        $this->postAPI('ALL');
        Mail::assertSent(FundReportEmail::class, $this->validateEmail($email, $fund));


        Mail::fake();
        $factory->createUser();
        $factory->createAccountMatching();
        $email2 = $factory->userAccount->email_cc;
        $factory->createTransactionWithMatching();
        $this->postAPI('ALL');
        Mail::assertSent(FundReportEmail::class, $this->validateEmail($email, $fund));
        Mail::assertSent(FundReportEmail::class, $this->validateEmail($email2, $fund));
    }

    protected function postAPI($type = 'ADM', $code=200): mixed
    {
        $this->post = [
            'fund_id'   => $this->fund->id,
            'type'      => $type,
            'as_of'     => $this->asOf,
        ];

        if ($this->verbose)
            Log::debug("*** POST ".json_encode($this->post));
        $this->response = $this->json(
            'POST',
            '/api/fund_reports/', $this->post
        );

        $response = json_decode($this->response->getContent(), true);
        if ($this->verbose)
            Log::debug("response: " . json_encode($response,JSON_PRETTY_PRINT));

        if ($code == 200)
            $this->assertApiSuccess();
        else
            $this->assertApiError($code);

        return $response;
    }

    public function validateEmail($email, $fund): \Closure
    {
        return function ($mail) use ($email, $fund) {
            return $mail->fundReport->fund->id === $fund->id &&
                $mail->hasTo($email);
        };
    }
}
