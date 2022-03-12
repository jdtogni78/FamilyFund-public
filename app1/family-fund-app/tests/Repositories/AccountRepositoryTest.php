<?php namespace Tests\Repositories;

use App\Models\Account;
use App\Repositories\AccountRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class AccountRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var AccountRepository
     */
    protected $accountRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->accountRepo = \App::make(AccountRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_account()
    {
        $account = Account::factory()->make()->toArray();

        $createdAccount = $this->accountRepo->create($account);

        $createdAccount = $createdAccount->toArray();
        $this->assertArrayHasKey('id', $createdAccount);
        $this->assertNotNull($createdAccount['id'], 'Created Account must have id specified');
        $this->assertNotNull(Account::find($createdAccount['id']), 'Account with given id must be in DB');
        $this->assertModelData($account, $createdAccount);
    }

    /**
     * @test read
     */
    public function test_read_account()
    {
        $account = Account::factory()->create();

        $dbAccount = $this->accountRepo->find($account->id);

        $dbAccount = $dbAccount->toArray();
        $this->assertModelData($account->toArray(), $dbAccount);
    }

    /**
     * @test update
     */
    public function test_update_account()
    {
        $account = Account::factory()->create();
        $fakeAccount = Account::factory()->make()->toArray();

        $updatedAccount = $this->accountRepo->update($fakeAccount, $account->id);

        $this->assertModelData($fakeAccount, $updatedAccount->toArray());
        $dbAccount = $this->accountRepo->find($account->id);
        $this->assertModelData($fakeAccount, $dbAccount->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_account()
    {
        $account = Account::factory()->create();

        $resp = $this->accountRepo->delete($account->id);

        $this->assertTrue($resp);
        $this->assertNull(Account::find($account->id), 'Account should not exist in DB');
    }
}
