<?php namespace Tests\Repositories;

use App\Models\TransactionMatching;
use App\Repositories\TransactionMatchingRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

use PHPUnit\Framework\Attributes\Test;
class TransactionMatchingRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var TransactionMatchingRepository
     */
    protected $transactionMatchingRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->transactionMatchingRepo = \App::make(TransactionMatchingRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_transaction_matching()
    {
        $transactionMatching = TransactionMatching::factory()->make()->toArray();

        $createdTransactionMatching = $this->transactionMatchingRepo->create($transactionMatching);

        $createdTransactionMatching = $createdTransactionMatching->toArray();
        $this->assertArrayHasKey('id', $createdTransactionMatching);
        $this->assertNotNull($createdTransactionMatching['id'], 'Created TransactionMatching must have id specified');
        $this->assertNotNull(TransactionMatching::find($createdTransactionMatching['id']), 'TransactionMatching with given id must be in DB');
        $this->assertModelData($transactionMatching, $createdTransactionMatching);
    }

    /**
     * @test read
     */
    public function test_read_transaction_matching()
    {
        $transactionMatching = TransactionMatching::factory()->create();

        $dbTransactionMatching = $this->transactionMatchingRepo->find($transactionMatching->id);

        $dbTransactionMatching = $dbTransactionMatching->toArray();
        $this->assertModelData($transactionMatching->toArray(), $dbTransactionMatching);
    }

    /**
     * @test update
     */
    public function test_update_transaction_matching()
    {
        $transactionMatching = TransactionMatching::factory()->create();
        $fakeTransactionMatching = TransactionMatching::factory()->make()->toArray();

        $updatedTransactionMatching = $this->transactionMatchingRepo->update($fakeTransactionMatching, $transactionMatching->id);

        $this->assertModelData($fakeTransactionMatching, $updatedTransactionMatching->toArray());
        $dbTransactionMatching = $this->transactionMatchingRepo->find($transactionMatching->id);
        $this->assertModelData($fakeTransactionMatching, $dbTransactionMatching->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_transaction_matching()
    {
        $transactionMatching = TransactionMatching::factory()->create();

        $resp = $this->transactionMatchingRepo->delete($transactionMatching->id);

        $this->assertTrue($resp);
        $this->assertNull(TransactionMatching::find($transactionMatching->id), 'TransactionMatching should not exist in DB');
    }
}
