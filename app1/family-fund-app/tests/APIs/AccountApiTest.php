<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Account;
use App\Models\Fund;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
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
        $this->actingAsFundAdminFor($account->fund);

        $this->response = $this->json(
            'GET',
            '/api/accounts/'.$account->id
        );

        $this->assertApiResponse((new AccountResource($account))->toArray(null));
    }

    private function actingAsFundAdminFor(Fund $fund): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $role = RolesAndPermissionsSeeder::createFundRole('fund-admin', $fund->id);

        $original = getPermissionsTeamId();
        setPermissionsTeamId($fund->id);
        $user->assignRole($role);
        setPermissionsTeamId($original);

        $this->actingAs($user);
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
