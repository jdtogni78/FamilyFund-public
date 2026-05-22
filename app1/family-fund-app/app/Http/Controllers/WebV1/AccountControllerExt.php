<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\AccountPDF;
use App\Http\Controllers\Traits\AccountTrait;
use App\Http\Controllers\Traits\ChartBaseTrait;
use App\Http\Controllers\Traits\PerformanceTrait;
use App\Http\Requests\CreateAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\AccountExt;
use App\Models\FundExt;
use App\Models\ScheduledJobExt;
use App\Models\UserExt;
use App\Repositories\AccountRepository;
use Illuminate\Http\Request;
use Flash;
use Response;


class AccountControllerExt extends AppBaseController
{
    use ChartBaseTrait;
    use AccountTrait, PerformanceTrait;

    /** @var  AccountRepository */
    protected $accountRepository;

    public function __construct(AccountRepository $accountRepo)
    {
        $this->accountRepository = $accountRepo;
    }

    /**
     * Display the specified Fund.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $now = date('Y-m-d');
        return $this->showAsOf($id, $now);
    }

    /**
     * Display the specified Account.
     *
     * @param int $id
     *
     * @return Response
     */
    public function showAsOf($id, $asOf)
    {
        $account = $this->accountRepository->withAuthorization()->find($id);

        if (empty($account)) {
            Flash::error('Account not found');
            return redirect(route('accounts.index'));
        }

        $this->authorize('view', $account);

        $arr = $this->createAccountViewData($asOf, $account);

        // Get scheduled transaction jobs for this account
        $scheduledTransactionJobs = ScheduledJobExt::where('entity_descr', 'transaction')
            ->whereHas('transactionTemplate', function($q) use ($account) {
                $q->where('account_id', $account->id);
            })
            ->with(['transactionTemplate', 'schedule'])
            ->where('end_dt', '>=', now())
            ->get();

        return view('accounts.show_ext')
            ->with('api', $arr)
            ->with('account', $account)
            ->with('scheduledTransactionJobs', $scheduledTransactionJobs);
    }

    public function showPdfAsOf($id, $asOf)
    {
        $account = $this->accountRepository->withAuthorization()->find($id);

        if (empty($account)) {
            Flash::error('Account not found');
            return redirect(route('accounts.index'));
        }

        $this->authorize('view', $account);

        $arr = $this->createAccountViewData($asOf, $account);

        // Add scheduled transaction jobs for this account
        $arr['scheduledTransactionJobs'] = ScheduledJobExt::where('entity_descr', 'transaction')
            ->whereHas('transactionTemplate', function($q) use ($account) {
                $q->where('account_id', $account->id);
            })
            ->with(['transactionTemplate', 'schedule'])
            ->where('end_dt', '>=', now())
            ->get();

        $pdf = new AccountPDF($arr, false);

        return $pdf->inline('account.pdf');
    }

    // --- inlined from former base ---

    public function index(Request $request)
    {
        $this->authorize('viewAny', AccountExt::class);

        $accounts = $this->accountRepository->withAuthorization()->all();

        return view('accounts.index')
            ->with('accounts', $accounts);
    }

    public function create()
    {
        $this->authorize('create', AccountExt::class);

        $api = [
            'userMap' => UserExt::userMap(),
            'fundMap' => FundExt::fundMap(),
        ];
        return view('accounts.create')
            ->with('api', $api);
    }

    public function store(CreateAccountRequest $request)
    {
        $this->authorize('create', AccountExt::class);

        $input = $request->all();

        $account = $this->accountRepository->create($input);

        Flash::success('Account saved successfully.');

        return redirect(route('accounts.index'));
    }

    public function edit($id)
    {
        $account = $this->accountRepository->withAuthorization()->find($id);

        if (empty($account)) {
            Flash::error('Account not found');

            return redirect(route('accounts.index'));
        }

        $this->authorize('update', $account);

        $api = [
            'userMap' => UserExt::userMap(),
            'fundMap' => FundExt::fundMap(),
        ];

        return view('accounts.edit')
            ->with('account', $account)
            ->with('api', $api);
    }

    public function update($id, UpdateAccountRequest $request)
    {
        $account = $this->accountRepository->withAuthorization()->find($id);

        if (empty($account)) {
            Flash::error('Account not found');

            return redirect(route('accounts.index'));
        }

        $this->authorize('update', $account);

        $account = $this->accountRepository->update($request->all(), $id);

        Flash::success('Account updated successfully.');

        return redirect(route('accounts.index'));
    }

    public function destroy($id)
    {
        $account = $this->accountRepository->withAuthorization()->find($id);

        if (empty($account)) {
            Flash::error('Account not found');

            return redirect(route('accounts.index'));
        }

        $this->authorize('delete', $account);

        // UC-47: refuse closure while the account still has active loan shares.
        // The caller must cancel or pay them off first so the receivable on the
        // fund's books has a defined disposition.
        if (method_exists($account, 'creditLines')) {
            $activeLines = $account->creditLines()
                ->where('status', 'active')
                ->get(['id', 'descr']);
            if ($activeLines->isNotEmpty()) {
                $list = $activeLines
                    ->map(fn ($l) => '#' . $l->id . ($l->descr ? ' (' . $l->descr . ')' : ''))
                    ->implode(', ');
                Flash::error(
                    'Cannot close account: ' . $activeLines->count() .
                    ' active loan share(s) remain — ' . $list .
                    '. Cancel or pay them off first.'
                );

                return redirect(route('accounts.show', $account->id));
            }
        }

        $this->accountRepository->delete($id);

        Flash::success('Account deleted successfully.');

        return redirect(route('accounts.index'));
    }

}
