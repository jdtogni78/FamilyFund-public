<?php

namespace App\Jobs;

use App\Http\Controllers\Traits\FundTrait;
use App\Http\Controllers\Traits\VerboseTrait;
use App\Models\FundReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\Traits\IBFlexQueriesTrait;
use App\Http\Controllers\Traits\CashDepositTrait;
use Illuminate\Support\Facades\Mail;
use App\Mail\CashDepositMail;
use App\Mail\CashDepositErrorMail;
use App\Http\Controllers\Traits\MailTrait;

class FetchDeposits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use IBFlexQueriesTrait, CashDepositTrait, MailTrait;

    const TOKEN = '787126795637155913197548';
    const QUERY_ID = '1127265';
    const URL = 'https://ndcdyn.interactivebrokers.com/AccountManagement/FlexWebService/SendRequest?t=TTT&q=QQQ&v=3';

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $content = $this->getCashDeposits();
        list($successes, $errors, $data) = $this->parseCashDepositString($content);
        foreach ($data as $item) {
            $this->sendCashDepositMail($item);
        }
        foreach ($errors as $error) {
            $this->sendCashDepositErrorMail($error);
        }
    }

    public function sendCashDepositMail($data) {
        $mail = new CashDepositMail($data);
        Mail::to($data['to'])->send($mail);
    }

    public function sendCashDepositErrorMail($error) {
        $mail = new CashDepositErrorMail($error);
        Mail::to($error['to'])->send($mail);
    }

    public function getCashDeposits() {
        $response = $this->getIBFlexQuery(self::QUERY_ID, self::TOKEN, self::URL);
        $content = $response->body();
        
        // save response to file, under /storage/app/cash_deposits
        $filename = 'cash_deposits_' . date('Y-m-d_H-i-s') . '.txt';
        file_put_contents(storage_path('app/cash_deposits/' . $filename), $content); 

        // delete file after 3 months
        $this->deleteFileAfter($filename, 3 * 30 * 24 * 60 * 60);
        return $content;
    }

    public function deleteFileAfter($filename, $delta = 3 * 30 * 24 * 60 * 60) {
        $path = storage_path('app/cash_deposits/' . $filename);
        if (file_exists($path) && time() - filemtime($path) > $delta) {
            unlink($path);
        }
    }   
}
