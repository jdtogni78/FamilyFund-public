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
use App\Http\Controllers\Traits\CashDepositTrait;
use Illuminate\Support\Facades\Mail;
use App\Mail\CashDepositMail;
use App\Mail\CashDepositErrorMail;
use App\Http\Controllers\Traits\MailTrait;
use App\Models\TradePortfolio;

class FetchDeposits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use CashDepositTrait, MailTrait;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $tradePortfolios = TradePortfolio::where('tws_query_id', '!=', null)
            ->where('tws_token', '!=', null)
            ->get();

        foreach ($tradePortfolios as $tradePortfolio) {
            $content = $this->getCashDeposits($tradePortfolio->tws_query_id, $tradePortfolio->tws_token);
            $result = $this->parseCashDepositString($content);
            foreach ($result['successes'] as $success) {
                $this->sendCashDepositMail($success);
            }
            foreach ($result['errors'] as $error) {
                $this->sendCashDepositErrorMail($error, $result['successes'], $result['data']);
            }
        }
    }

    public function sendCashDepositMail($data) {
        $mail = new CashDepositMail($data);
        Mail::to($data['to'])->send($mail);
    }

    public function sendCashDepositErrorMail($error, $successes, $data) {
        $mail = new CashDepositErrorMail($error, $successes, $data);
        Mail::to($error['to'])->send($mail);
    }
}
