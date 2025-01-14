<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCashDepositRequest;
use App\Http\Requests\UpdateCashDepositRequest;
use App\Repositories\CashDepositRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class CashDepositController extends AppBaseController
{
    /** @var CashDepositRepository $cashDepositRepository*/
    private $cashDepositRepository;

    public function __construct(CashDepositRepository $cashDepositRepo)
    {
        $this->cashDepositRepository = $cashDepositRepo;
    }

    /**
     * Display a listing of the CashDeposit.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $cashDeposits = $this->cashDepositRepository->all();

        return view('cash_deposits.index')
            ->with('cashDeposits', $cashDeposits);
    }

    /**
     * Show the form for creating a new CashDeposit.
     *
     * @return Response
     */
    public function create()
    {
        return view('cash_deposits.create');
    }

    /**
     * Store a newly created CashDeposit in storage.
     *
     * @param CreateCashDepositRequest $request
     *
     * @return Response
     */
    public function store(CreateCashDepositRequest $request)
    {
        $input = $request->all();

        $cashDeposit = $this->cashDepositRepository->create($input);

        Flash::success('Cash Deposit saved successfully.');

        return redirect(route('cashDeposits.index'));
    }

    /**
     * Display the specified CashDeposit.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $cashDeposit = $this->cashDepositRepository->find($id);

        if (empty($cashDeposit)) {
            Flash::error('Cash Deposit not found');

            return redirect(route('cashDeposits.index'));
        }

        return view('cash_deposits.show')->with('cashDeposit', $cashDeposit);
    }

    /**
     * Show the form for editing the specified CashDeposit.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $cashDeposit = $this->cashDepositRepository->find($id);

        if (empty($cashDeposit)) {
            Flash::error('Cash Deposit not found');

            return redirect(route('cashDeposits.index'));
        }

        return view('cash_deposits.edit')->with('cashDeposit', $cashDeposit);
    }

    /**
     * Update the specified CashDeposit in storage.
     *
     * @param int $id
     * @param UpdateCashDepositRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateCashDepositRequest $request)
    {
        $cashDeposit = $this->cashDepositRepository->find($id);

        if (empty($cashDeposit)) {
            Flash::error('Cash Deposit not found');

            return redirect(route('cashDeposits.index'));
        }

        $cashDeposit = $this->cashDepositRepository->update($request->all(), $id);

        Flash::success('Cash Deposit updated successfully.');

        return redirect(route('cashDeposits.index'));
    }

    /**
     * Remove the specified CashDeposit from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $cashDeposit = $this->cashDepositRepository->find($id);

        if (empty($cashDeposit)) {
            Flash::error('Cash Deposit not found');

            return redirect(route('cashDeposits.index'));
        }

        $this->cashDepositRepository->delete($id);

        Flash::success('Cash Deposit deleted successfully.');

        return redirect(route('cashDeposits.index'));
    }
}
