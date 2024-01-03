<?php

namespace App\Http\Controllers\Traits;

use App\Http\Controllers\APIv1\PortfolioAPIControllerExt;
use App\Http\Resources\FundResource;
use App\Jobs\SendAccountReport;
use App\Jobs\SendFundReport;
use App\Mail\FundQuarterlyReport;
use App\Models\AccountReport;
use App\Models\FundExt;
use App\Models\FundReportExt;
use App\Models\User;
use App\Models\Utils;
use App\Repositories\PortfolioRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

Trait FundTrait
{
    use PerformanceTrait;
    protected $err = [];
    protected $msgs = [];
    private $noEmailMessage = "The following accounts have no email: ";

    public function createAccountBalancesResponse(FundExt $fund, $asOf)
    {
        $bals = array();
        $sharePrice = $fund->shareValueAsOf($asOf);
        foreach ($fund->accountBalancesAsOf($asOf) as $balance) {
            $account = $balance->account()->first();
            $user = $account->user()->first();

            $bal = array();
            if ($user) {
                $bal['user'] = [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            } else {
                continue;
                // $bal['user'] = [
                //     'id' => 0,
                //     'name' => 'N/A',
                // ];
            }
            $bal['account_id'] = $account->id;
            $bal['nickname'] = $account->nickname;
            $bal['type'] = $balance->type;
            $bal['shares'] = Utils::shares($balance->shares);
            $bal['value'] = Utils::currency($sharePrice * $balance->shares);
            $bals[] = $bal;
        }
        return $bals;
    }

    public function createFundArray($fund, $asOf)
    {
        $this->setPerfObject($fund);

        $arr = array();
        $arr['id'] = $fund->id;
        $arr['name'] = $fund->name;
        $arr['as_of'] = $asOf;

        return $arr;
    }

    public function createFundResponse($fund, $asOf)
    {
        $this->setPerfObject($fund);
        $rss = new FundResource($fund);
        $ret = $rss->toArray(NULL);

        $arr = array();
        $arr['value'] = Utils::currency($value = $fund->valueAsOf($asOf));
        $arr['shares'] = Utils::shares($shares = $fund->sharesAsOf($asOf));
        $arr['unallocated_shares'] = Utils::shares($unallocated = $fund->unallocatedShares($asOf));
        $arr['unallocated_shares_percent'] = Utils::percent($shares ? $unallocated / $shares : 0);
        $arr['allocated_shares'] = Utils::shares($allocated = $shares - $unallocated);
        $arr['allocated_shares_percent'] = Utils::percent($shares ? $allocated / $shares : 0);
        $arr['share_value'] = Utils::currency($shares ? $value / $shares : 0);

        $prevYearAsOf = Utils::asOfAddYear($asOf, -1);
        $arr['max_cash_value'] = $fund->portfolio()->maxCashBetween($prevYearAsOf, $asOf);

        $ret['summary'] = $arr;
        $ret['as_of'] = $asOf;

        return $ret;
    }

    public function isAdmin() {
        /** @var User $user */
        $user = Auth::user();
        if ($user != null) {
            return $user->email == "admin@dev.familyfund.local";
        }
        return false;
    }

    public function createFullFundResponse($fund, $asOf, $isAdmin = false) {
        if ($asOf == null) $asOf = date('Y-m-d');
        $fund = FundExt::find($fund->id);

        $api = $this;
        $arr = $api->createFundResponse($fund, $asOf);
        $arr['monthly_performance'] = $api->createMonthlyPerformanceResponse($asOf);
        $arr['yearly_performance'] = $api->createYearlyPerformanceResponse($asOf);
        if ($isAdmin) {
            $arr['admin'] = true;
            $arr['balances'] = $api->createAccountBalancesResponse($fund, $asOf);
        }
//        $arr['transactions'] = $api->createTransactionsResponse($account, $asOf);

        $portController = new PortfolioAPIControllerExt(\App::make(PortfolioRepository::class));
        $portfolio = $fund->portfolios()->first();
        $arr['portfolio'] = $portController->createPortfolioResponse($portfolio, $asOf);

        return $arr;
    }

    public function sendFundReport($fundReport)
    {
        $this->createAccountReports($fundReport);

        $fund = $fundReport->fund()->first();
        $asOf = $fundReport->as_of->format('Y-m-d');
        $isAdmin = $fundReport->isAdmin();

        $arr = $this->createFullFundResponse($fund, $asOf, $isAdmin);
        $pdf = new FundPDF($arr, $isAdmin);

        $this->fundEmailReport($fundReport, $pdf);

        return $fundReport;
    }

    protected function createAccountReports($fundReport)
    {
        $fund = $fundReport->fund()->first();
        Log::info("sending report to all ".$fundReport->type);
        if ($fundReport->type === 'ALL') {
            $accounts = $fund->accounts()->get();
            foreach ($accounts as $account) {
                $users = $account->user()->get();
                Log::info("* sending report to acct ".$account->nickname);
                if (count($users) == 1) {
                    $accountReport = AccountReport::create([
                        'account_id' => $account->id,
                        'type' => $fundReport->type,
                        'as_of' => $fundReport->as_of
                    ]);
                    SendAccountReport::dispatch($accountReport);
                }
            }
        }
    }

    protected function reportUsers($fund, bool $isAdmin): array
    {
        $ret = [];
        $accounts = $fund->accounts()->get();
        foreach ($accounts as $account) {
            $users = $account->user()->get();
            if (($isAdmin && count($users) == 0) ||
                (!$isAdmin && count($users) == 1)
            ) {
                $ret[] = $account;
            }
        }
        return $ret;
    }

    /**
     * @throws Exception
     */
    public function validateReportEmails($fundReport)
    {
        $fund = $fundReport->fund()->first();
        $isAdmin = $fundReport->isAdmin();
        $noEmail = [];
        $accounts = $this->reportUsers($fund, $isAdmin);
        foreach ($accounts as $account) {
            $err = $this->validateHasEmail($account);
            if ($err) $noEmail[] = $err;
        }
        if (count($noEmail) > 0)
            throw new Exception($this->noEmailMessage . implode(", ", $noEmail));
    }

    protected function fundEmailReport($fundReport, FundPDF $pdf): void
    {
        $fund = $fundReport->fund()->first();
        $asOf = $fundReport->as_of->format('Y-m-d');
        $isAdmin = $fundReport->isAdmin();

        $errs = [];
        $noEmail = [];
        $msgs = [];
        $sendCount = 0;
        $accounts = $this->reportUsers($fund, $isAdmin);
        foreach ($accounts as $account) {
            $err = $this->validateHasEmail($account);
            if ($err != null) {
                $noEmail[] = $err;
            } else {
                $sendCount++;
                $msg = "Sending email to " . $account->email_cc;
                Log::info($msg);
                $msgs[] = $msg;
                $pdfFile = $pdf->file();
                if ($this->verbose) Log::debug("pdfFile: " . json_encode($pdfFile) . "\n");
                if ($this->verbose) Log::debug("fund: " . json_encode($fund) . "\n");
                $user = $account->user()->first();
                $reportData = new FundQuarterlyReport($fund, $user, $asOf, $pdfFile);

                $emails = explode(",", $account->email_cc);
                $to = array_shift($emails);
                Mail::to($to)->cc($emails)->send($reportData);
            }
        }
        if (count($noEmail)) {
            $errs[] = $this->noEmailMessage . implode(", ", $noEmail);
        }
        if ($sendCount == 0) {
            $msg = "No emails sent";
            Log::error($msg);
            $errs[] = $msg;
        }
        $this->err = $errs;
        $this->msgs = $msgs;
    }

    protected function validateHasEmail(mixed $account): ?string
    {
        if (empty($account->email_cc)) {
            return $account->nickname;
        }
        return null;
    }

    protected function createFundReport(array $input)
    {
        $fundReport = FundReportExt::create($input);
        $this->validateReportEmails($fundReport);
        $fundReport->save();
        SendFundReport::dispatch($fundReport);
        return $fundReport;
    }
}
