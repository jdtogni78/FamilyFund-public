<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Requests\CreateDepositRequestRequest;
use App\Http\Requests\UpdateDepositRequestRequest;
use App\Repositories\DepositRequestRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;
use App\Models\DepositRequestExt;
use App\Http\Controllers\DepositRequestController;
use App\Models\AccountExt;

class DepositRequestControllerExt extends DepositRequestController
{
    public function create()
    {
        $api = $this->getApi();
        return view('deposit_requests.create')->with('api', $api);
    }

    private function getApi()
    {
        $statusMap = DepositRequestExt::statusMap();
        $accountMap = AccountExt::accountMap();
        return [
            'statusMap' => $statusMap,
            'accountMap' => $accountMap,
        ];
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
}
