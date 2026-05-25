<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\AccountSelectorTrait;
use App\Http\Requests\CreateAccountGoalRequest;
use App\Http\Requests\UpdateAccountGoalRequest;
use App\Models\AccountGoal;
use App\Models\Goal;
use App\Repositories\AccountGoalRepository;
use Illuminate\Http\Request;
use Flash;
use Response;

class AccountGoalControllerExt extends AppBaseController
{
    use AccountSelectorTrait;

    /** @var AccountGoalRepository */
    protected $accountGoalRepository;

    public function __construct(AccountGoalRepository $accountGoalRepo)
    {
        $this->accountGoalRepository = $accountGoalRepo;
    }

    protected function getApi()
    {
        return array_merge(
            $this->getAccountSelectorData(),
            ['goalMap' => Goal::pluck('name', 'id')->toArray()]
        );
    }

    public function create()
    {
        $api = $this->getApi();
        return view('account_goals.create')->with('api', $api);
    }

    public function edit($id)
    {
        $accountGoal = $this->accountGoalRepository->find($id);

        if (empty($accountGoal)) {
            Flash::error('Account Goal not found');
            return redirect(route('accountGoals.index'));
        }

        $api = $this->getApi();
        return view('account_goals.edit')
            ->with('accountGoal', $accountGoal)
            ->with('api', $api);
    }

    // --- inlined from former base ---

    public function index(Request $request)
    {
        // Defense-in-depth: scope to accounts in funds the user can access
        // (or own), in addition to the fund.full route middleware. (#85)
        $accountGoals = $this->authz()
            ->scopeByAccountRelation(AccountGoal::query())
            ->get();

        return view('account_goals.index')
            ->with('accountGoals', $accountGoals);
    }

    public function store(CreateAccountGoalRequest $request)
    {
        $input = $request->all();

        $accountGoal = $this->accountGoalRepository->create($input);

        Flash::success('Account Goal saved successfully.');

        return redirect(route('accountGoals.index'));
    }

    public function show($id)
    {
        $accountGoal = $this->accountGoalRepository->find($id);

        if (empty($accountGoal)) {
            Flash::error('Account Goal not found');

            return redirect(route('accountGoals.index'));
        }

        return view('account_goals.show')->with('accountGoal', $accountGoal);
    }

    public function update($id, UpdateAccountGoalRequest $request)
    {
        $accountGoal = $this->accountGoalRepository->find($id);

        if (empty($accountGoal)) {
            Flash::error('Account Goal not found');

            return redirect(route('accountGoals.index'));
        }

        $accountGoal = $this->accountGoalRepository->update($request->all(), $id);

        Flash::success('Account Goal updated successfully.');

        return redirect(route('accountGoals.index'));
    }

    public function destroy($id)
    {
        $accountGoal = $this->accountGoalRepository->find($id);

        if (empty($accountGoal)) {
            Flash::error('Account Goal not found');

            return redirect(route('accountGoals.index'));
        }

        $this->accountGoalRepository->delete($id);

        Flash::success('Account Goal deleted successfully.');

        return redirect(route('accountGoals.index'));
    }
}
