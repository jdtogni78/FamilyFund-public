<?php

namespace App\Http\Controllers\WebV1;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class FundPDF
{
    use ChartBaseTrait;
    public $pdf;
    private $tempDir;

    public function __construct(array $arr, bool $isAdmin, bool $debugHtml = false)
    {
        $pdf = App::make('snappy.pdf.wrapper')
            ->setOption("enable-local-file-access", true)
            ->setOption("print-media-type", true);
        $tempDir = (new TemporaryDirectory())->force();
        try {
            $tempDir->create();
        } catch (Exception $e) {
            print_r($e);
        }
        if ($isAdmin) {
            $this->createSharesAllocationGraph($arr, $tempDir);
            $this->createAccountsAllocationGraph($arr, $tempDir);
        }
        $this->createAssetsAllocationGraph($arr, $tempDir);
        $this->createYearlyPerformanceGraph($arr, $tempDir);
        $this->createMonthlyPerformanceGraph($arr, $tempDir);

        if ($debugHtml) {
            $html = view('funds.show_pdf')
                ->with('api', $arr)
                ->with('files', $this->files)
                ->render();

            $myFile = fopen($tempDir->path('fund.html'), "w") or die("Unable to open file!");
            fwrite($myFile, $html);
        }

        $pdf->loadView('funds.show_pdf', [
            'api' => $arr,
            'files' => $this->files
        ]);

        $this->files['fund'] = $file = $tempDir->path('fund.pdf');
        $pdf->save($file);
        $this->pdf = $pdf;
        $this->tempDir = $tempDir;
        return $pdf;
    }

    public function file() {
        return $this->files['fund'];
    }

    public function destroy() {
        $this->tempDir->delete();
    }

    public function createSharesAllocationGraph(array $api, TemporaryDirectory $tempDir): void
    {
        $name = 'shares_allocation.png';
        $values = [$api['summary']['allocated_shares_percent'], $api['summary']['unallocated_shares_percent']];
        $labels = ['Allocated', 'Unallocated'];

        $this->files[$name] = $file = $tempDir->path($name);
        $this->createDoughnutChart($values, $labels, $file);
    }

    public function createAssetsAllocationGraph(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'assets_allocation.png';
        $arr = $api['portfolio']['assets'];
        $labels = array_map(function ($v) {
            return $v['name'];
        }, $arr);
        $values = array_map(function ($v) {
            return $v['value'];
        }, $arr);

        $this->files[$name] = $file = $tempDir->path($name);
        $this->createDoughnutChart($values, $labels, $file);
    }

    public function createAccountsAllocationGraph(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'accounts_allocation.png';
        $arr = $api['balances'];
        Log::debug($arr);
        $labels = array_map(function ($v) {
            return $v['nickname'];
        }, $arr);
        $values = array_map(function ($v) {
            return $v['shares'];
        }, $arr);

        $labels[] = 'Unallocated';
        $values[] = $api['summary']['unallocated_shares'];

        $this->files[$name] = $file = $tempDir->path($name);
        $this->createDoughnutChart($values, $labels, $file);
    }
}
