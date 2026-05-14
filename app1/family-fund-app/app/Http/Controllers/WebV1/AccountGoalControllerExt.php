<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AccountGoalController;
use App\Http\Controllers\Traits\AccountSelectorTrait;
use App\Models\AccountGoal;
use App\Models\Goal;
use App\Repositories\AccountGoalRepository;
use Flash;

class AccountGoalControllerExt extends AccountGoalController
{
    use AccountSelectorTrait;

    /** @var AccountGoalRepository */
    protected $accountGoalRepository;

    public function __construct(AccountGoalRepository $accountGoalRepo)
    {
        parent::__construct($accountGoalRepo);
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
}
