<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Account;
use App\Models\Fund;
use Tests\DataFactory;
use App\Http\Resources\AccountResource;

use PHPUnit\Framework\Attributes\Test;
class AccountApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_create_account()
    {
        $fund = Fund::factory()->create();
        $account = Account::factory()->make(
            ['fund_id' => $fund->id])->toArray();

        $this->response = $this->json(
            'POST',
            '/api/accounts', $account
        );

        $this->assertApiResponse($account, ['id']);
    }

    public function createAccount()
    {
        $factory = new DataFactory();
        $factory->createFund();
        $account = $factory->fundAccount;
        return $account;
    }
    #[Test]
    public function test_read_account()
    {
        $account = $this->createAccount();

        $this->response = $this->json(
            'GET',
            '/api/accounts/'.$account->id
        );

        $this->assertApiResponse((new AccountResource($account))->toArray(null));
    }

    #[Test]
    public function test_update_account()
    {
        $account = $this->createAccount();
        $fund = $account->fund()->first();
        $editedAccount = Account::factory()->make(
            ['fund_id' => $fund->id])->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/accounts/'.$account->id,
            $editedAccount
        );

        $editedAccount['id'] = $account->id;
        $this->assertApiResponse($editedAccount);
    }

    #[Test]
    public function test_delete_account()
    {
        $account = $this->createAccount();

        $this->response = $this->json(
            'DELETE',
             '/api/accounts/'.$account->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/accounts/'.$account->id
        );

        $this->response->assertStatus(404);
    }
}
