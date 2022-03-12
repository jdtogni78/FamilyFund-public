<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAccountBalanceRequest;
use App\Http\Requests\UpdateAccountBalanceRequest;
use App\Repositories\AccountBalanceRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class AccountBalanceController extends AppBaseController
{
    /** @var  AccountBalanceRepository */
    protected $accountBalanceRepository;

    public function __construct(AccountBalanceRepository $accountBalanceRepo)
    {
        $this->accountBalanceRepository = $accountBalanceRepo;
    }

    /**
     * Display a listing of the AccountBalance.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $accountBalances = $this->accountBalanceRepository->all();

        return view('account_balances.index')
            ->with('accountBalances', $accountBalances);
    }

    /**
     * Show the form for creating a new AccountBalance.
     *
     * @return Response
     */
    public function create()
    {
        return view('account_balances.create');
    }

    /**
     * Store a newly created AccountBalance in storage.
     *
     * @param CreateAccountBalanceRequest $request
     *
     * @return Response
     */
    public function store(CreateAccountBalanceRequest $request)
    {
        $input = $request->all();

        $accountBalance = $this->accountBalanceRepository->create($input);

        Flash::success('Account Balance saved successfully.');

        return redirect(route('accountBalances.index'));
    }

    /**
     * Display the specified AccountBalance.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $accountBalance = $this->accountBalanceRepository->find($id);

        if (empty($accountBalance)) {
            Flash::error('Account Balance not found');

            return redirect(route('accountBalances.index'));
        }

        return view('account_balances.show')->with('accountBalance', $accountBalance);
    }

    /**
     * Show the form for editing the specified AccountBalance.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $accountBalance = $this->accountBalanceRepository->find($id);

        if (empty($accountBalance)) {
            Flash::error('Account Balance not found');

            return redirect(route('accountBalances.index'));
        }

        return view('account_balances.edit')->with('accountBalance', $accountBalance);
    }

    /**
     * Update the specified AccountBalance in storage.
     *
     * @param int $id
     * @param UpdateAccountBalanceRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAccountBalanceRequest $request)
    {
        $accountBalance = $this->accountBalanceRepository->find($id);

        if (empty($accountBalance)) {
            Flash::error('Account Balance not found');

            return redirect(route('accountBalances.index'));
        }

        $accountBalance = $this->accountBalanceRepository->update($request->all(), $id);

        Flash::success('Account Balance updated successfully.');

        return redirect(route('accountBalances.index'));
    }

    /**
     * Remove the specified AccountBalance from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $accountBalance = $this->accountBalanceRepository->find($id);

        if (empty($accountBalance)) {
            Flash::error('Account Balance not found');

            return redirect(route('accountBalances.index'));
        }

        $this->accountBalanceRepository->delete($id);

        Flash::success('Account Balance deleted successfully.');

        return redirect(route('accountBalances.index'));
    }
}
