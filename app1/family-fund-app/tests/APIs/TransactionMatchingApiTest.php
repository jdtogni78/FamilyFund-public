<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\TransactionMatching;

class TransactionMatchingApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_transaction_matching()
    {
        $transactionMatching = TransactionMatching::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/transaction_matchings', $transactionMatching
        );

        $this->assertApiResponse($transactionMatching);
    }

    /**
     * @test
     */
    public function test_read_transaction_matching()
    {
        $transactionMatching = TransactionMatching::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/transaction_matchings/'.$transactionMatching->id
        );

        $this->assertApiResponse($transactionMatching->toArray());
    }

    /**
     * @test
     */
    public function test_update_transaction_matching()
    {
        $transactionMatching = TransactionMatching::factory()->create();
        $editedTransactionMatching = TransactionMatching::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/transaction_matchings/'.$transactionMatching->id,
            $editedTransactionMatching
        );

        $this->assertApiResponse($editedTransactionMatching);
    }

    /**
     * @test
     */
    public function test_delete_transaction_matching()
    {
        $transactionMatching = TransactionMatching::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/transaction_matchings/'.$transactionMatching->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/transaction_matchings/'.$transactionMatching->id
        );

        $this->response->assertStatus(404);
    }
}
