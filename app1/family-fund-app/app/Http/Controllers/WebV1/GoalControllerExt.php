<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Requests\CreateGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Repositories\GoalRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;
use App\Models\AccountExt;
use App\Models\GoalExt;
use App\Models\Goal;
use App\Models\AccountGoal;
use App\Models\Fund;
use App\Http\Controllers\Traits\AccountSelectorTrait;
use Illuminate\Support\Facades\Log;

class GoalControllerExt extends AppBaseController
{
    use AccountSelectorTrait;

    /** @var GoalRepository $goalRepository*/
    public $goalRepository;

    public function __construct(GoalRepository $goalRepo)
    {
        $this->goalRepository = $goalRepo;
    }

    public function getApi()
    {
        return array_merge(
            $this->getAccountSelectorData(),
            ['targetTypeMap' => GoalExt::targetTypeMap()]
        );
    }

    public function index(Request $request)
    {
        // Goals are shared templates (no fund/account column to scope on);
        // re-assert the fund.full management capability as defense-in-depth. (#85)
        $this->requireFullFundAccessSurface();

        $api = $this->getApi();
        $goals = $this->goalRepository->all();

        return view('goals.index')
            ->with('goals', $goals)
            ->with('api', $api);
    }

    public function create()
    {
        $api = $this->getApi();
        // Pre-select account if passed via query parameter
        if (request()->has('account_id')) {
            $api['account_ids'] = [(int) request()->get('account_id')];
        }
        return view('goals.create')->with('api', $api);
    }

    public function show($id)
    {
        $api = $this->getApi();
        $goal = $this->goalRepository->find($id);

        if (empty($goal)) {
            Flash::error('Goal not found');

            return redirect(route('goals.index'));
        }

        return view('goals.show')
            ->with('goal', $goal)
            ->with('api', $api);
    }

    public function edit($id)
    {
        $api = $this->getApi();
        $goal = Goal::find($id);
        $goal->accounts = $goal->accounts()->get();
        $api['account_ids'] = $goal->accounts->pluck('id')->toArray();

        // (was: parent::edit($id))
        $goalFromRepo = $this->goalRepository->find($id);

        if (empty($goalFromRepo)) {
            Flash::error('Goal not found');

            return redirect(route('goals.index'))->with('api', $api);
        }

        return view('goals.edit')
            ->with('goal', $goalFromRepo)
            ->with('api', $api);
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

    // --- inlined from former base ---

    public function destroy($id)
    {
        $goal = $this->goalRepository->find($id);

        if (empty($goal)) {
            Flash::error('Goal not found');

            return redirect(route('goals.index'));
        }

        $this->goalRepository->delete($id);

        Flash::success('Goal deleted successfully.');

        return redirect(route('goals.index'));
    }
}
