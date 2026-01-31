<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Requests\CreateCashDepositRequest;
use App\Http\Requests\UpdateCashDepositRequest;
use App\Repositories\CashDepositRepository;
use Illuminate\Http\Request;
use Flash;
use Response;
use App\Http\Controllers\CashDepositController;
use App\Models\CashDepositExt;
use App\Models\AccountExt;
use App\Http\Requests\AssignDepositRequestsRequest;
use App\Models\CashDeposit;
use App\Models\DepositRequestExt;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Traits\CashDepositTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\MessageBag;
use App\Mail\CashDepositMail;
use App\Mail\DepositAllocationMail;
use App\Http\Controllers\Traits\AccountSelectorTrait;

class CashDepositControllerExt extends CashDepositController
{
    use CashDepositTrait;
    use AccountSelectorTrait;

    public function create()
    {
        $api = $this->getApi();
        return parent::create($api)->with('api', $api);
    }

    public function getApi()
    {
        return array_merge(
            $this->getAccountSelectorData(),
            [
                'fundAccountMap' => AccountExt::fundAccountMap(),
                'statusMap' => CashDepositExt::statusMap(),
            ]
        );
    }

    public function show($id)
    {
        $api = $this->getApi();
        return parent::show($id)->with('api', $api);
    }

    public function edit($id)
    {
        $api = $this->getApi();
        return parent::edit($id)->with('api', $api);
    }

    public function index(Request $request)
    {
        $query = CashDeposit::with(['account.fund']);

        // Apply filters
        $filters = [];

        if ($request->filled('fund_id')) {
            $filters['fund_id'] = $request->fund_id;
            $query->whereHas('account', function($q) use ($request) {
                $q->where('fund_id', $request->fund_id);
            });
        }

        if ($request->filled('account_id')) {
            $filters['account_id'] = $request->account_id;
            $query->where('account_id', $request->account_id);
        }

        $cashDeposits = $query->orderByDesc('id')->get();

        $api = array_merge(
            $this->getApi(),
            ['filters' => $filters]
        );

        return view('cash_deposits.index')
            ->with('cashDeposits', $cashDeposits)
            ->with('api', $api)
            ->with('filters', $filters);
    }

    public function assign($id)
    {
        $api = $this->getApi();
        $cashDeposit = CashDepositExt::find($id);
        
        $depositRequests = DepositRequestExt::whereNull('cash_deposit_id')
            ->where('status', DepositRequestExt::STATUS_PENDING)
            ->get();
        $api['depositRequests'] = $depositRequests;

        return view('cash_deposits.assign')
            ->with('api', $api)
            ->with('cashDeposit', $cashDeposit);
    }

    public function doAssign($id, AssignDepositRequestsRequest $request)
    {
        try {
            $this->assignCashDeposit($id, $request);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->route('cashDeposits.assign', $id)
                ->withErrors(new MessageBag(['error' => $e->getMessage()]));
        }

        // Send allocation emails to each account
        $this->sendAllocationEmails($id);

        return redirect()->route('cashDeposits.index');
    }

    protected function sendAllocationEmails($cashDepositId)
    {
        $cashDeposit = CashDepositExt::with('depositRequests.account')->find($cashDepositId);

        foreach ($cashDeposit->depositRequests as $depositRequest) {
            $emailTo = $depositRequest->account->email_cc;
            if (empty($emailTo)) {
                Log::info("Skipping allocation email for deposit request {$depositRequest->id} - no email configured");
                continue;
            }

            try {
                $mail = new DepositAllocationMail($depositRequest);
                Mail::to($emailTo)->send($mail);
                Log::info("Sent allocation email for deposit request {$depositRequest->id} to {$emailTo}");
            } catch (\Exception $e) {
                Log::error("Failed to send allocation email for deposit request {$depositRequest->id}: " . $e->getMessage());
            }
        }
    }

    public function resendEmail($id)
    {
        $cashDeposit = CashDepositExt::find($id);

        if (empty($cashDeposit)) {
            Flash::error('Cash deposit not found');
            return redirect(route('cashDeposits.index'));
        }

        $emailTo = $cashDeposit->account->email_cc;
        if (empty($emailTo)) {
            Flash::error('No email address configured for this account.');
            return redirect(route('cashDeposits.show', $id));
        }

        $data = ['cash_deposit' => $cashDeposit];
        $mail = new CashDepositMail($data);
        Mail::to($emailTo)->send($mail);

        Flash::success('Cash deposit email sent to ' . $emailTo);
        return redirect(route('cashDeposits.show', $id));
    }
}

