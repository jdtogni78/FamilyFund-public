<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Traits\AccountPDF;
use App\Http\Controllers\Traits\AccountTrait;
use App\Http\Controllers\Traits\ChartBaseTrait;
use App\Http\Controllers\Traits\PerformanceTrait;
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

        return view('accounts.show_ext')->with('api', $arr);
    }

    public function showPdfAsOf($id, $asOf)
    {
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            Flash::error('Account not found');
            return redirect(route('accounts.index'));
        }

        $arr = $this->createAccountViewData($asOf, $account);
        $pdf = new AccountPDF($arr, false);

        return $pdf->inline('account.pdf');
    }

}
