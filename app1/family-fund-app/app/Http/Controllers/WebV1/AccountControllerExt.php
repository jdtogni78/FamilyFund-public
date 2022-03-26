<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\APIv1\AccountAPIControllerExt;
use App\Http\Controllers\Traits\AccountTrait;
use App\Http\Controllers\Traits\ChartBaseTrait;
use App\Http\Controllers\Traits\PerformanceTrait;
use App\Repositories\AccountRepository;
use Flash;
use Exception;
use Response;
use Spatie\TemporaryDirectory\TemporaryDirectory;


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

        $pdf = App::make('snappy.pdf.wrapper')
            ->setOption("enable-local-file-access", true)
            ->setOption("print-media-type", true)
        ;
        $files = [];
        $tempDir = (new TemporaryDirectory())->force();

        try {
            $tempDir->create();
        } catch (Exception $e) {
            print_r($e);
        }
        $this->createAssetsLineChart($arr, $tempDir, $files);
        $this->createYearlyPerformanceGraph($arr, $tempDir, $files);
        $this->createMonthlyPerformanceGraph($arr, $tempDir, $files);
        $html = view('accounts.show_pdf')
            ->with('api', $arr)
            ->with('files', $files)
            ->render()
        ;
        $myfile = fopen($tempDir->path('account.html'), "w") or die("Unable to open file!");
        fwrite($myfile, $html);

        $pdf->loadView('accounts.show_pdf', [
            'api' => $arr,
            'files' => $files
        ]);
        $pdf->save($tempDir->path('account.pdf'));

        return $pdf->inline('account.pdf');
    }

}
