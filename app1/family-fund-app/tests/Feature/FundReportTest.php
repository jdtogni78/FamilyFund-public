<?php
namespace Tests\Feature;

use App\Mail\FundReportEmail;
use App\Models\TransactionExt;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Log;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\ApiTestTrait;
use Tests\Fixtures\TestFixtures;

class FundReportTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    public $asOf;
    public $fund;
    public array $post;
    private $factory;

    public function setUp(): void
    {
        parent::setUp();
        $this->asOf = '2022-03-01';
        $this->verbose = false;
    }

    public function testEmail()
    {
        // Use fixture for complete data setup
        $this->factory = TestFixtures::fundReportFixture();
        $fund = $this->fund = $this->factory->fund;
        $email = $this->factory->fundAccount->email_cc;

        // Test ADM report sends email
        Mail::fake();
        $this->postAPI('ADM');
        Mail::assertSent(FundReportEmail::class, function ($mail) use ($email, $fund) {
            return $mail->fundReport->fund->id === $fund->id &&
                $mail->hasTo($email);
        });

        // Test with no fund account email_cc - should fail
        $this->factory->fundAccount->email_cc = null;
        $this->factory->fundAccount->save();

        Mail::fake();
        $this->postAPI('ADM', Response::HTTP_UNPROCESSABLE_ENTITY);
        Mail::assertNotSent(FundReportEmail::class);

        // Restore fund account email
        $this->factory->fundAccount->email_cc = $email;
        $this->factory->fundAccount->save();

        // Test with no user account email_cc - ALL report should fail
        $userEmail = $this->factory->userAccount->email_cc;
        $this->factory->userAccount->email_cc = null;
        $this->factory->userAccount->save();

        Mail::fake();
        $this->postAPI('ALL', Response::HTTP_UNPROCESSABLE_ENTITY);
        Mail::assertNotSent(FundReportEmail::class);

        // Restore user account email and test ALL report
        $this->factory->userAccount->email_cc = $userEmail;
        $this->factory->userAccount->save();

        Mail::fake();
        $this->postAPI('ALL');
        Mail::assertSent(FundReportEmail::class, $this->validateEmail($userEmail, $fund));

        // Test with multiple users
        Mail::fake();
        $user2 = $this->factory->createUser();
        $this->factory->userAccount->email_cc = 'user2@test.local';
        $this->factory->userAccount->save();
        $email2 = $this->factory->userAccount->email_cc;

        $this->factory->createAccountMatching();
        $this->factory->createTransactionWithMatching();

        $this->postAPI('ALL');
        Mail::assertSent(FundReportEmail::class, $this->validateEmail($userEmail, $fund));
        Mail::assertSent(FundReportEmail::class, $this->validateEmail($email2, $fund));
    }

    protected function postAPI($type = 'ADM', $code = 200): mixed
    {
        $this->post = [
            'fund_id' => $this->fund->id,
            'type' => $type,
            'as_of' => $this->asOf,
        ];

        if ($this->verbose)
            Log::debug("*** POST " . json_encode($this->post));

        $this->response = $this->json(
            'POST',
            '/api/fund_reports/',
            $this->post
        );

        $response = json_decode($this->response->getContent(), true);
        if ($this->verbose)
            Log::debug("response: " . json_encode($response, JSON_PRETTY_PRINT));

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
