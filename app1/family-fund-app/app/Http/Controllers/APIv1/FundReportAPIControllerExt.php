<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\WebV1\FundControllerExt;
use App\Http\Controllers\WebV1\FundPDF;
use App\Http\Requests\API\CreateFundReportAPIRequest;
use App\Http\Resources\FundReportResource;
use App\Mail\FundQuarterlyReport;
use App\Repositories\FundReportRepository;
use App\Http\Controllers\API\FundReportAPIController;
use App\Repositories\FundRepository;
use Illuminate\Support\Facades\Mail;
use Response;
use App\Models\PerformanceTrait;

/**
 * Class FundReportAPIControllerExt
 * @package App\Http\Controllers\API
 */

class FundReportAPIControllerExt extends FundReportAPIController
{
    use PerformanceTrait;
    public $err = [];
    public $msgs = [];
    public $verbose = true;

    public function __construct(FundReportRepository $fundReportRepo)
    {
        parent::__construct($fundReportRepo);
    }

    public function store(CreateFundReportAPIRequest $request)
    {
        $input = $request->all();

        $fundReport = $this->createAndSendFundReport($input);

        if (count($this->err) == 0) {
            $result = new FundReportResource($fundReport);
            print_r("result: " . json_encode($result->toArray($request)) . "\n");
            return $this->sendResponse($result, 'Fund Report saved successfully'."\n".implode($this->msgs));
        } else {
            return $this->sendError(implode(",", $this->err), 415);
        }
    }

    public function createAndSendFundReport($input)
    {
        $fundReport = $this->fundReportRepository->create($input);
        $fund = $fundReport->fund()->first();
        $asOf = $fundReport->as_of;
        $isAdmin = 'ADM' === $fundReport->type;

        $fundController = new FundControllerExt(\App::make(FundRepository::class));
        $arr = $fundController->createFundViewData($fund, $asOf, $isAdmin);
        $pdf = new FundPDF($arr, $isAdmin);

        $err = [];
        $sendCount = 0;
        $accounts = $fund->accounts()->get();
        foreach ($accounts as $account) {
            $user = $account->user()->get();
            if (
                ($isAdmin && count($user) == 0) ||
                (!$isAdmin && count($user) == 1)
            ) {
                if (empty($account->email_cc)) {
                    $err[] = "Account " . $account->nickname . " has no email configured";
                } else {
                    $sendCount++;
                    $msgs[] = "Sending email to ".$account->email_cc;
                    $pdfFile = $pdf->file();
                    if ($this->verbose) print_r("pdfFile: " . json_encode($pdfFile) . "\n");
                    if ($this->verbose) print_r("fund: " . json_encode($fund) . "\n");
                    Mail::to($account->email_cc)->send(new FundQuarterlyReport($fund, $pdfFile));
                }
            }
        }
        if ($sendCount == 0) {
            $err[] = "No emails sent";
        }
        if (count($err) > 0) {
//            throw new \Exception(implode(",", $err));
            $this->sendError(implode(",", $err), 415);
        }
        $this->err = $err;
        $this->msgs = $msgs;
        return $fundReport;
    }
}
