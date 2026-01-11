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
use App\Http\Controllers\Traits\AccountSelectorTrait;

class DepositRequestControllerExt extends DepositRequestController
{
    use AccountSelectorTrait;

    public function create()
    {
        $api = $this->getAccountSelectorData();
        return view('deposit_requests.create')->with('api', $api);
    }

    public function edit($id)
    {
        $api = $this->getAccountSelectorData();
        return parent::edit($id)->with('api', $api);
    }
}
