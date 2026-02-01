<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use Tests\DataFactory;
use App\Models\Transaction;
use App\Models\TransactionExt;

use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function test_index_transactions()
    {
        // Create multiple transactions
        $transaction1 = Transaction::factory()
            ->for($this->account, 'account')
            ->create([
                'type' => TransactionExt::TYPE_PURCHASE,
                'status' => TransactionExt::STATUS_CLEARED,
                'timestamp' => now()->format('Y-m-d'),
            ]);
        $transaction2 = Transaction::factory()
            ->for($this->account, 'account')
            ->create([
                'type' => TransactionExt::TYPE_PURCHASE,
                'status' => TransactionExt::STATUS_CLEARED,
                'timestamp' => now()->format('Y-m-d'),
            ]);

        $this->response = $this->json(
            'GET',
            '/api/transactions'
        );

        $this->response->assertStatus(200);
        $this->response->assertJson(['success' => true]);
        $responseData = $this->response->json('data');
        $this->assertIsArray($responseData);
        // At minimum should have the fund transaction plus the two we created
        $this->assertGreaterThanOrEqual(2, count($responseData));
    }

    #[Test]
    public function test_index_transactions_with_pagination()
    {
        // Create multiple transactions
        for ($i = 0; $i < 5; $i++) {
            Transaction::factory()
                ->for($this->account, 'account')
                ->create([
                    'type' => TransactionExt::TYPE_PURCHASE,
                    'status' => TransactionExt::STATUS_CLEARED,
                    'timestamp' => now()->format('Y-m-d'),
                ]);
        }

        // Test with skip and limit
        $this->response = $this->json(
            'GET',
            '/api/transactions?skip=1&limit=2'
        );

        $this->response->assertStatus(200);
        $this->response->assertJson(['success' => true]);
        $responseData = $this->response->json('data');
        $this->assertIsArray($responseData);
        $this->assertLessThanOrEqual(2, count($responseData));
    }

    #[Test]
    public function test_index_transactions_with_account_filter()
    {
        // Create a transaction for our test account
        $transaction = Transaction::factory()
            ->for($this->account, 'account')
            ->create([
                'type' => TransactionExt::TYPE_PURCHASE,
                'status' => TransactionExt::STATUS_CLEARED,
                'timestamp' => now()->format('Y-m-d'),
            ]);

        // Filter by account_id
        $this->response = $this->json(
            'GET',
            '/api/transactions?account_id=' . $this->account->id
        );

        $this->response->assertStatus(200);
        $this->response->assertJson(['success' => true]);
        $responseData = $this->response->json('data');
        $this->assertIsArray($responseData);
        $this->assertGreaterThanOrEqual(1, count($responseData));
        // Verify all returned transactions belong to the same account
        foreach ($responseData as $item) {
            $this->assertEquals($this->account->id, $item['account_id']);
        }
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function test_read_transaction_not_found()
    {
        $this->response = $this->json(
            'GET',
            '/api/transactions/999999'
        );

        $this->response->assertStatus(404);
    }

    #[Test]
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

    #[Test]
    public function test_update_transaction_not_found()
    {
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
            '/api/transactions/999999',
            $editedTransaction
        );

        $this->response->assertStatus(404);
    }

    #[Test]
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

    #[Test]
    public function test_delete_transaction_not_found()
    {
        $this->response = $this->json(
            'DELETE',
            '/api/transactions/999999'
        );

        $this->response->assertStatus(404);
    }
}
