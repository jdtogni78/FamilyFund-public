<?php namespace Tests\Repositories;

use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class TransactionRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var TransactionRepository
     */
    protected $transactionRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->transactionRepo = \App::make(TransactionRepository::class);
    }
    public function test_create_transaction()
    {
        $transaction = Transaction::factory()->make()->toArray();

        $createdTransaction = $this->transactionRepo->create($transaction);

        $createdTransaction = $createdTransaction->toArray();
        $this->assertArrayHasKey('id', $createdTransaction);
        $this->assertNotNull($createdTransaction['id'], 'Created Transaction must have id specified');
        $this->assertNotNull(Transaction::find($createdTransaction['id']), 'Transaction with given id must be in DB');
        $this->assertModelData($transaction, $createdTransaction);
    }
    public function test_read_transaction()
    {
        $transaction = Transaction::factory()->create();

        $dbTransaction = $this->transactionRepo->find($transaction->id);

        // The TransactionExt observer eager-loads the `account` relation for
        // credit-line transaction types (PUR/BOR/REP), which the factory picks at
        // random. That would leak `account` into the expected toArray() and make
        // this assertion order/faker-dependent, so compare attributes only.
        $transaction->setRelations([]);

        $dbTransaction = $dbTransaction->toArray();
        $this->assertModelData($transaction->toArray(), $dbTransaction);
    }
    public function test_update_transaction()
    {
        $transaction = Transaction::factory()->create();
        $fakeTransaction = Transaction::factory()->make()->toArray();

        $updatedTransaction = $this->transactionRepo->update($fakeTransaction, $transaction->id);

        $this->assertModelData($fakeTransaction, $updatedTransaction->toArray());
        $dbTransaction = $this->transactionRepo->find($transaction->id);
        $this->assertModelData($fakeTransaction, $dbTransaction->toArray());
    }
    public function test_delete_transaction()
    {
        $transaction = Transaction::factory()->create();

        $resp = $this->transactionRepo->delete($transaction->id);

        $this->assertTrue($resp);
        $this->assertNull(Transaction::find($transaction->id), 'Transaction should not exist in DB');
    }
}
