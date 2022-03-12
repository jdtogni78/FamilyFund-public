<?php namespace Tests\Repositories;

use App\Models\AccountBalance;
use App\Repositories\AccountBalanceRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class AccountBalanceRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var AccountBalanceRepository
     */
    protected $accountBalanceRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->accountBalanceRepo = \App::make(AccountBalanceRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_account_balance()
    {
        $accountBalance = AccountBalance::factory()->make()->toArray();

        $createdAccountBalance = $this->accountBalanceRepo->create($accountBalance);

        $createdAccountBalance = $createdAccountBalance->toArray();
        $this->assertArrayHasKey('id', $createdAccountBalance);
        $this->assertNotNull($createdAccountBalance['id'], 'Created AccountBalance must have id specified');
        $this->assertNotNull(AccountBalance::find($createdAccountBalance['id']), 'AccountBalance with given id must be in DB');
        $this->assertModelData($accountBalance, $createdAccountBalance);
    }

    /**
     * @test read
     */
    public function test_read_account_balance()
    {
        $accountBalance = AccountBalance::factory()->create();

        $dbAccountBalance = $this->accountBalanceRepo->find($accountBalance->id);

        $dbAccountBalance = $dbAccountBalance->toArray();
        $this->assertModelData($accountBalance->toArray(), $dbAccountBalance);
    }

    /**
     * @test update
     */
    public function test_update_account_balance()
    {
        $accountBalance = AccountBalance::factory()->create();
        $fakeAccountBalance = AccountBalance::factory()->make()->toArray();

        $updatedAccountBalance = $this->accountBalanceRepo->update($fakeAccountBalance, $accountBalance->id);

        $this->assertModelData($fakeAccountBalance, $updatedAccountBalance->toArray());
        $dbAccountBalance = $this->accountBalanceRepo->find($accountBalance->id);
        $this->assertModelData($fakeAccountBalance, $dbAccountBalance->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_account_balance()
    {
        $accountBalance = AccountBalance::factory()->create();

        $resp = $this->accountBalanceRepo->delete($accountBalance->id);

        $this->assertTrue($resp);
        $this->assertNull(AccountBalance::find($accountBalance->id), 'AccountBalance should not exist in DB');
    }
}
