<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\App;
use Mockery\Exception;
use Spatie\TemporaryDirectory\TemporaryDirectory;

trait BasePDFTrait
{
    use ChartBaseTrait;

    public $pdf;
    private $tempDir;

    public function constructPDF()
    {
        $this->pdf = App::make('snappy.pdf.wrapper')
            ->setOption("enable-local-file-access", true)
            ->setOption("print-media-type", true);
        $this->tempDir = (new TemporaryDirectory())->force();
        try {
            $this->tempDir->create();
        } catch (Exception $e) {
            print_r($e);
        }
    }

    public function file() {
        return $this->files['main'];
    }

    public function destroy() {
        $this->tempDir->delete();
    }

    protected function createAndSavePDF(string $view, array $arr, $tempDir, string $pdfFile): void
    {
        $this->pdf->loadView($view, [
            'api' => $arr,
            'files' => $this->files
        ]);

        $this->files['main'] = $file = $tempDir->path($pdfFile);
        $this->pdf->save($file);
    }

    public function inline($file) {
        return $this->pdf->inline($file);
    }

    protected function debugHTML(bool $debugHtml, string $view, array $arr, $tempDir): void
    {
        if ($debugHtml) {
            $html = view($view)
                ->with('api', $arr)
                ->with('files', $this->files)
                ->render();

            $myFile = fopen($tempDir->path('main.html'), "w") or die("Unable to open file!");
            fwrite($myFile, $html);
        }
    }
}
