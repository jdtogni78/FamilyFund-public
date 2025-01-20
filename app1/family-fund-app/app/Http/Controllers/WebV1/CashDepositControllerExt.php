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
use Illuminate\Support\MessageBag;

class CashDepositControllerExt extends CashDepositController
{
    use CashDepositTrait;

    public function create()
    {
        $api = $this->getApi();
        return parent::create($api)->with('api', $api);
    }

    public function getApi()
    {
        $api = [];
        $api['fundAccountMap'] = AccountExt::fundAccountMap();
        $api['accountMap'] = AccountExt::accountMap();
        $api['statusMap'] = CashDepositExt::statusMap();
        return $api;
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
        $api = $this->getApi();
        return parent::index($request)->with('api', $api);
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
        
        return redirect()->route('cashDeposits.index');
    }
}

