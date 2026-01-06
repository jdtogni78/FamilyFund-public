<?php

namespace App\Services;

use Knp\Snappy\Pdf;

class SnappyPdfWrapper
{
    protected Pdf $snappy;
    protected ?string $html = null;

    public function __construct(Pdf $snappy)
    {
        $this->snappy = $snappy;
    }

    /**
     * Set a PDF generation option
     */
    public function setOption(string $name, $value): self
    {
        $this->snappy->setOption($name, $value);
        return $this;
    }

    /**
     * Set multiple options
     */
    public function setOptions(array $options): self
    {
        $this->snappy->setOptions($options);
        return $this;
    }

    /**
     * Load a Blade view and render it as HTML
     */
    public function loadView(string $view, array $data = []): self
    {
        $this->html = view($view, $data)->render();
        return $this;
    }

    /**
     * Load raw HTML content
     */
    public function loadHTML(string $html): self
    {
        $this->html = $html;
        return $this;
    }

    /**
     * Save the PDF to a file
     */
    public function save(string $filename): self
    {
        if ($this->html === null) {
            throw new \RuntimeException('No HTML content loaded. Call loadView() or loadHTML() first.');
        }

        $this->snappy->generateFromHtml($this->html, $filename, [], true);
        return $this;
    }

    /**
     * Return the PDF for inline display
     */
    public function inline(string $filename = 'document.pdf'): \Illuminate\Http\Response
    {
        if ($this->html === null) {
            throw new \RuntimeException('No HTML content loaded. Call loadView() or loadHTML() first.');
        }

        $output = $this->snappy->getOutputFromHtml($this->html);

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Return the PDF for download
     */
    public function download(string $filename = 'document.pdf'): \Illuminate\Http\Response
    {
        if ($this->html === null) {
            throw new \RuntimeException('No HTML content loaded. Call loadView() or loadHTML() first.');
        }

        $output = $this->snappy->getOutputFromHtml($this->html);

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get the raw PDF output
     */
    public function output(): string
    {
        if ($this->html === null) {
            throw new \RuntimeException('No HTML content loaded. Call loadView() or loadHTML() first.');
        }

        return $this->snappy->getOutputFromHtml($this->html);
    }

    /**
     * Get the underlying Snappy instance
     */
    public function getSnappy(): Pdf
    {
        return $this->snappy;
    }
}
