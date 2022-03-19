<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\APIv1\AccountAPIControllerExt;
use App\Http\Controllers\Traits\ChartBaseTrait;
use App\Repositories\AccountRepository;
use Flash;
use Exception;
use Response;
use Spatie\TemporaryDirectory\TemporaryDirectory;


class AccountControllerExt extends AccountController
{
    use ChartBaseTrait;

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
        return $this->showAsOf($id, null);
    }

    /**
     * Display the specified Account.
     *
     * @param int $id
     *
     * @return Response
     */
    public function showAsOf($id, $asOf=null)
    {
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            Flash::error('Account not found');
            return redirect(route('accounts.index'));
        }

        $arr = $this->createAssetViewData($asOf, $account);

        return view('accounts.show_ext')->with('api', $arr);
    }

    public function showPdfAsOf($id, $asOf=null)
    {
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            Flash::error('Account not found');
            return redirect(route('accounts.index'));
        }

        $arr = $this->createAssetViewData($asOf, $account);

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

        return $pdf->inline('account.pdf');    }

    /**
     * @param mixed $asOf
     * @param $account
     * @return array
     */
    protected function createAssetViewData(mixed $asOf, $account): array
    {
        if ($asOf == null) $asOf = date('Y-m-d');

        $api = new AccountAPIControllerExt($this->accountRepository);
        $arr = $api->createAccountResponse($account, $asOf);
        $arr['monthly_performance'] = $api->createMonthlyPerformanceResponse($asOf);
        $arr['yearly_performance'] = $api->createYearlyPerformanceResponse($asOf);
        $arr['transactions'] = $api->createTransactionsResponse($account, $asOf);
        $arr['as_of'] = $asOf;
        return $arr;
    }

    private function createAssetsLineChart(array $api, TemporaryDirectory $tempDir, array &$files)
    {
        $name = 'shares.png';
        $arr = $api['transactions'];
        $data = [];
        foreach ($arr as $v) {
            $data[substr($v['timestamp'], 0,10)] = $v['balances']['OWN'] * $v['share_price'];
        };
        asort($data);
        Log::debug("data");
        Log::debug($data);

        $files[$name] = $file = $tempDir->path($name);
        $labels1 = array_keys($data);
        $this->createLineChart(array_values($data), $labels1, $file, "Shares");
    }
}
