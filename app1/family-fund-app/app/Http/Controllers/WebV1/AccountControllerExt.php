<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Traits\AccountPDF;
use App\Http\Controllers\Traits\AccountTrait;
use App\Http\Controllers\Traits\ChartBaseTrait;
use App\Http\Controllers\Traits\PerformanceTrait;
use App\Models\ScheduledJobExt;
use App\Repositories\AccountRepository;
use Flash;
use Response;


class AccountControllerExt extends AccountController
{
    use ChartBaseTrait;
    use AccountTrait, PerformanceTrait;

    public function __construct(AccountRepository $accountRepo)
    {
        parent::__construct($accountRepo);
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
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            Flash::error('Account not found');
            return redirect(route('accounts.index'));
        }

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
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            Flash::error('Account not found');
            return redirect(route('accounts.index'));
        }

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

}
