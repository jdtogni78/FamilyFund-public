<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAccountGoalRequest;
use App\Http\Requests\UpdateAccountGoalRequest;
use App\Repositories\AccountGoalRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class AccountGoalController extends AppBaseController
{
    /** @var AccountGoalRepository $accountGoalRepository*/
    private $accountGoalRepository;

    public function __construct(AccountGoalRepository $accountGoalRepo)
    {
        $this->accountGoalRepository = $accountGoalRepo;
    }

    /**
     * Display a listing of the AccountGoal.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $accountGoals = $this->accountGoalRepository->all();

        return view('account_goals.index')
            ->with('accountGoals', $accountGoals);
    }

    /**
     * Show the form for creating a new AccountGoal.
     *
     * @return Response
     */
    public function create()
    {
        return view('account_goals.create');
    }

    /**
     * Store a newly created AccountGoal in storage.
     *
     * @param CreateAccountGoalRequest $request
     *
     * @return Response
     */
    public function store(CreateAccountGoalRequest $request)
    {
        $input = $request->all();

        $accountGoal = $this->accountGoalRepository->create($input);

        Flash::success('Account Goal saved successfully.');

        return redirect(route('accountGoals.index'));
    }

    /**
     * Display the specified AccountGoal.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $accountGoal = $this->accountGoalRepository->find($id);

        if (empty($accountGoal)) {
            Flash::error('Account Goal not found');

            return redirect(route('accountGoals.index'));
        }

        return view('account_goals.show')->with('accountGoal', $accountGoal);
    }

    /**
     * Show the form for editing the specified AccountGoal.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $accountGoal = $this->accountGoalRepository->find($id);

        if (empty($accountGoal)) {
            Flash::error('Account Goal not found');

            return redirect(route('accountGoals.index'));
        }

        return view('account_goals.edit')->with('accountGoal', $accountGoal);
    }

    /**
     * Update the specified AccountGoal in storage.
     *
     * @param int $id
     * @param UpdateAccountGoalRequest $request
     *
     * @return Response
     */
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

    /**
     * Remove the specified AccountGoal from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
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
