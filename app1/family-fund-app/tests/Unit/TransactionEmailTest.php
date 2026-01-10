<?php

namespace Tests\Unit;

use App\Mail\TransactionEmail;
use App\Models\TransactionExt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for TransactionEmail mailable
 */
class TransactionEmailTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
    }

    public function test_transaction_email_constructor_sets_data()
    {
        $transaction = $this->factory->createTransaction(
            500,
            $this->factory->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED
        );

        $transactionData = [
            'transaction' => $transaction,
            'amount' => 500,
        ];

        $email = new TransactionEmail($transactionData);

        $this->assertEquals($transactionData, $email->transaction_data);
    }

    public function test_transaction_email_build()
    {
        $account = $this->factory->userAccount;
        $account->email_cc = 'test@example.com';
        $account->save();

        $transaction = $this->factory->createTransaction(
            500,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED
        );
        $transaction->load('account');

        $transactionData = [
            'transaction' => $transaction,
            'amount' => 500,
        ];

        $email = new TransactionEmail($transactionData);
        $email->build();

        // After build, transaction_data should have 'to' and 'report_name'
        $this->assertEquals('test@example.com', $email->transaction_data['to']);
        $this->assertEquals('Transaction Confirmation', $email->transaction_data['report_name']);
    }

    public function test_transaction_email_has_correct_subject()
    {
        $account = $this->factory->userAccount;
        $account->email_cc = 'test@example.com';
        $account->save();

        $transaction = $this->factory->createTransaction(
            500,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED
        );
        $transaction->load('account');

        $transactionData = [
            'transaction' => $transaction,
        ];

        $email = new TransactionEmail($transactionData);
        $builtEmail = $email->build();

        $this->assertEquals('Transaction Confirmation', $builtEmail->subject);
    }

    public function test_transaction_email_can_be_queued()
    {
        Mail::fake();

        $account = $this->factory->userAccount;
        $account->email_cc = 'recipient@example.com';
        $account->save();

        $transaction = $this->factory->createTransaction(
            500,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED
        );
        $transaction->load('account');

        $transactionData = [
            'transaction' => $transaction,
        ];

        $email = new TransactionEmail($transactionData);

        Mail::to('recipient@example.com')->send($email);

        Mail::assertSent(TransactionEmail::class, function ($mail) use ($transaction) {
            return $mail->transaction_data['transaction']->id === $transaction->id;
        });
    }
}
