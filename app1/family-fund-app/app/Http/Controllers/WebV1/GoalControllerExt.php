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
use App\Models\Goal;
use App\Models\AccountGoal;
use App\Models\Fund;
use App\Http\Controllers\Traits\AccountSelectorTrait;
use Illuminate\Support\Facades\Log;

class GoalControllerExt extends GoalController
{
    use AccountSelectorTrait;

    public function getApi()
    {
        return array_merge(
            $this->getAccountSelectorData(),
            ['targetTypeMap' => GoalExt::targetTypeMap()]
        );
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
        $goal = Goal::find($id);
        $goal->accounts = $goal->accounts()->get();
        $api['account_ids'] = $goal->accounts->pluck('id')->toArray();
        return parent::edit($id)->with('api', $api);
    }

    public function store(CreateGoalRequest $request)
    {
        $input = $request->all();
        $goal = $this->goalRepository->create($input);

        $goal->accounts()->sync($input['account_ids']);
        Flash::success('Goal saved successfully.');

        $api = $this->getApi();
        return redirect(route('goals.index'))->with('api', $api);
    }

    public function update($id, UpdateGoalRequest $request)
    {
        $api = $this->getApi();
        $goal = Goal::find($id);

        if (empty($goal)) {
            Flash::error('Goal not found');
            return redirect(route('goals.index'));
        }

        Log::info(json_encode($request->all()));
        $goal->update($request->all());
        
        Log::info(json_encode($request->account_ids));
        $goal->accounts()->sync($request->account_ids);

        Flash::success('Goal updated successfully.');
        return redirect(route('goals.index'));
    }
}
