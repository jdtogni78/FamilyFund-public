<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use Tests\DataFactory;
use App\Models\Transaction;
use App\Models\TransactionExt;

class TransactionApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    private $factory;
    private $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new DataFactory();
        $this->factory->createFund();
        $this->factory->createUser();
        $this->account = $this->factory->userAccount;
    }

    /**
     * @test
     */
    public function test_create_transaction()
    {
        $transaction = Transaction::factory()
            ->for($this->account, 'account')
            ->make([
                'type' => TransactionExt::TYPE_PURCHASE,
                'status' => TransactionExt::STATUS_PENDING,
                'timestamp' => now()->format('Y-m-d'),
                'flags' => null,
                'value' => 100,
            ])->toArray();

        $this->response = $this->json(
            'POST',
            '/api/transactions', $transaction
        );

        // Status changes from 'P' to 'C' after processPending() is called on create
        $transaction['status'] = TransactionExt::STATUS_CLEARED;
        $this->assertApiResponse($transaction, ['id', 'shares']);
    }

    /**
     * @test
     */
    public function test_read_transaction()
    {
        $transaction = Transaction::factory()
            ->for($this->account, 'account')
            ->create([
                'type' => TransactionExt::TYPE_PURCHASE,
                'status' => TransactionExt::STATUS_PENDING,
                'timestamp' => now()->format('Y-m-d'),
            ]);

        $this->response = $this->json(
            'GET',
            '/api/transactions/'.$transaction->id
        );

        $this->assertApiResponse($transaction->toArray());
    }

    /**
     * @test
     */
    public function test_update_transaction()
    {
        $transaction = Transaction::factory()
            ->for($this->account, 'account')
            ->create([
                'type' => TransactionExt::TYPE_PURCHASE,
                'status' => TransactionExt::STATUS_PENDING,
                'timestamp' => now()->format('Y-m-d'),
            ]);
        $editedTransaction = Transaction::factory()
            ->for($this->account, 'account')
            ->make([
                'type' => TransactionExt::TYPE_PURCHASE,
                'status' => TransactionExt::STATUS_PENDING,
                'timestamp' => now()->format('Y-m-d'),
                'flags' => null,
                'value' => 100,
            ])->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/transactions/'.$transaction->id,
            $editedTransaction
        );

        $this->assertApiResponse($editedTransaction);
    }

    /**
     * @test
     */
    public function test_delete_transaction()
    {
        $transaction = Transaction::factory()
            ->for($this->account, 'account')
            ->create([
                'type' => TransactionExt::TYPE_PURCHASE,
                'status' => TransactionExt::STATUS_PENDING,
                'timestamp' => now()->format('Y-m-d'),
            ]);

        $this->response = $this->json(
            'DELETE',
             '/api/transactions/'.$transaction->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/transactions/'.$transaction->id
        );

        $this->response->assertStatus(404);
    }
}
