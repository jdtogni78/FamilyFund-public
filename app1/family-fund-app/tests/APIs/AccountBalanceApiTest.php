<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\AccountBalance;

use PHPUnit\Framework\Attributes\Test;
class AccountBalanceApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_create_account_balance()
    {
        $accountBalance = AccountBalance::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/account_balances', $accountBalance
        );

        $this->assertApiResponse($accountBalance);
    }

    #[Test]
    public function test_read_account_balance()
    {
        $accountBalance = AccountBalance::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/account_balances/'.$accountBalance->id
        );

        $this->assertApiResponse($accountBalance->toArray());
    }

    #[Test]
    public function test_update_account_balance()
    {
        $accountBalance = AccountBalance::factory()->create();
        $editedAccountBalance = AccountBalance::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/account_balances/'.$accountBalance->id,
            $editedAccountBalance
        );

        $this->assertApiResponse($editedAccountBalance);
    }

    #[Test]
    public function test_delete_account_balance()
    {
        $accountBalance = AccountBalance::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/account_balances/'.$accountBalance->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/account_balances/'.$accountBalance->id
        );

        $this->response->assertStatus(404);
    }
}
