<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Requests\CreateGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Repositories\GoalRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;
use App\Http\Controllers\GoalController;
use App\Models\AccountExt;
use App\Models\GoalExt;
class GoalControllerExt extends GoalController
{
    public function getApi()
    {
        return [
            'accountMap' => AccountExt::accountMap(),
            'targetTypeMap' => GoalExt::targetTypeMap(),
        ];
    }

    public function index(Request $request)
    {
        $api = $this->getApi();
        return parent::index($request)->with('api', $api);
    }

    public function create()
    {
        $api = $this->getApi();
        return parent::create()->with('api', $api);
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
