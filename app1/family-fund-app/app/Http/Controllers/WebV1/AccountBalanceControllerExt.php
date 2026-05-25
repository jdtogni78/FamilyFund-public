<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\AccountSelectorTrait;
use App\Http\Requests\CreateAccountBalanceRequest;
use App\Http\Requests\UpdateAccountBalanceRequest;
use App\Models\AccountBalance;
use App\Repositories\AccountBalanceRepository;
use Illuminate\Http\Request;
use Flash;
use Response;

class AccountBalanceControllerExt extends AppBaseController
{
    use AccountSelectorTrait;

    /** @var  AccountBalanceRepository */
    protected $accountBalanceRepository;

    public function __construct(AccountBalanceRepository $accountBalanceRepo)
    {
        $this->accountBalanceRepository = $accountBalanceRepo;
    }

    /**
     * Display a listing of AccountBalances with filtering.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $query = AccountBalance::with(['account.fund']);

        // Defense-in-depth: scope to the caller's accessible accounts in
        // addition to the fund.full route middleware. (#85)
        $query = $this->authz()->scopeByAccountRelation($query);

        // Apply filters
        $filters = [];

        if ($request->filled('fund_id')) {
            $filters['fund_id'] = $request->fund_id;
            $query->whereHas('account', function($q) use ($request) {
                $q->where('fund_id', $request->fund_id);
            });
        }

        if ($request->filled('account_id')) {
            $filters['account_id'] = $request->account_id;
            $query->where('account_id', $request->account_id);
        }

        $accountBalances = $query->orderByDesc('id')->get();

        $api = array_merge(
            $this->getAccountSelectorData(),
            ['filters' => $filters]
        );

        return view('account_balances.index')
            ->with('accountBalances', $accountBalances)
            ->with('api', $api)
            ->with('filters', $filters);
    }

    /**
     * Show the form for creating a new AccountBalance.
     *
     * @return Response
     */
    public function create()
    {
        $api = $this->getAccountSelectorData();
        return view('account_balances.create')->with('api', $api);
    }

    /**
     * Show the form for editing the specified AccountBalance.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $accountBalance = $this->accountBalanceRepository->find($id);

        if (empty($accountBalance)) {
            Flash::error('Account Balance not found');
            return redirect(route('accountBalances.index'));
        }

        $api = $this->getAccountSelectorData();
        return view('account_balances.edit')
            ->with('accountBalance', $accountBalance)
            ->with('api', $api);
    }

    // --- inlined from former base ---

    public function store(CreateAccountBalanceRequest $request)
    {
        $input = $request->all();

        $accountBalance = $this->accountBalanceRepository->create($input);

        Flash::success('Account Balance saved successfully.');

        return redirect(route('accountBalances.index'));
    }

    public function show($id)
    {
        $accountBalance = $this->accountBalanceRepository->find($id);

        if (empty($accountBalance)) {
            Flash::error('Account Balance not found');

            return redirect(route('accountBalances.index'));
        }

        return view('account_balances.show')->with('accountBalance', $accountBalance);
    }

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
