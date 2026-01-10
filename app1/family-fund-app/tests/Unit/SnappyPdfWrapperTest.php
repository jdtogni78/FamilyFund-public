<?php

namespace Tests\Unit;

use App\Services\SnappyPdfWrapper;
use Knp\Snappy\Pdf;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for SnappyPdfWrapper
 */
class SnappyPdfWrapperTest extends TestCase
{
    private $mockSnappy;
    private SnappyPdfWrapper $wrapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockSnappy = Mockery::mock(Pdf::class);
        $this->wrapper = new SnappyPdfWrapper($this->mockSnappy);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_set_option_calls_snappy_set_option()
    {
        $this->mockSnappy->shouldReceive('setOption')
            ->once()
            ->with('page-size', 'A4');

        $result = $this->wrapper->setOption('page-size', 'A4');

        $this->assertInstanceOf(SnappyPdfWrapper::class, $result);
    }

    public function test_set_option_returns_self_for_chaining()
    {
        $this->mockSnappy->shouldReceive('setOption');

        $result = $this->wrapper->setOption('margin-top', '10mm');

        $this->assertSame($this->wrapper, $result);
    }

    public function test_set_options_calls_snappy_set_options()
    {
        $options = ['page-size' => 'Letter', 'orientation' => 'landscape'];

        $this->mockSnappy->shouldReceive('setOptions')
            ->once()
            ->with($options);

        $result = $this->wrapper->setOptions($options);

        $this->assertInstanceOf(SnappyPdfWrapper::class, $result);
    }

    public function test_load_html_stores_html_content()
    {
        $html = '<html><body>Test content</body></html>';

        $result = $this->wrapper->loadHTML($html);

        $this->assertInstanceOf(SnappyPdfWrapper::class, $result);
    }

    public function test_load_html_returns_self_for_chaining()
    {
        $result = $this->wrapper->loadHTML('<p>Test</p>');

        $this->assertSame($this->wrapper, $result);
    }

    public function test_save_generates_pdf_from_html()
    {
        $html = '<html><body>Test</body></html>';
        $filename = '/tmp/test.pdf';

        $this->mockSnappy->shouldReceive('generateFromHtml')
            ->once()
            ->with($html, $filename, [], true);

        $this->wrapper->loadHTML($html);
        $result = $this->wrapper->save($filename);

        $this->assertInstanceOf(SnappyPdfWrapper::class, $result);
    }

    public function test_save_throws_exception_without_html()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No HTML content loaded');

        $this->wrapper->save('/tmp/test.pdf');
    }

    public function test_output_returns_pdf_content()
    {
        $html = '<html><body>Test</body></html>';
        $pdfContent = '%PDF-1.4 test content';

        $this->mockSnappy->shouldReceive('getOutputFromHtml')
            ->once()
            ->with($html)
            ->andReturn($pdfContent);

        $this->wrapper->loadHTML($html);
        $result = $this->wrapper->output();

        $this->assertEquals($pdfContent, $result);
    }

    public function test_output_throws_exception_without_html()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No HTML content loaded');

        $this->wrapper->output();
    }

    public function test_inline_returns_response_with_pdf_headers()
    {
        $html = '<html><body>Test</body></html>';
        $pdfContent = '%PDF-1.4 inline content';

        $this->mockSnappy->shouldReceive('getOutputFromHtml')
            ->once()
            ->with($html)
            ->andReturn($pdfContent);

        $this->wrapper->loadHTML($html);
        $response = $this->wrapper->inline('report.pdf');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('report.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_inline_throws_exception_without_html()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No HTML content loaded');

        $this->wrapper->inline();
    }

    public function test_download_returns_response_with_attachment_header()
    {
        $html = '<html><body>Test</body></html>';
        $pdfContent = '%PDF-1.4 download content';

        $this->mockSnappy->shouldReceive('getOutputFromHtml')
            ->once()
            ->with($html)
            ->andReturn($pdfContent);

        $this->wrapper->loadHTML($html);
        $response = $this->wrapper->download('download.pdf');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('download.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_download_throws_exception_without_html()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No HTML content loaded');

        $this->wrapper->download();
    }

    public function test_get_snappy_returns_underlying_instance()
    {
        $result = $this->wrapper->getSnappy();

        $this->assertSame($this->mockSnappy, $result);
    }

    public function test_inline_with_default_filename()
    {
        $html = '<p>Test</p>';
        $pdfContent = '%PDF-1.4';

        $this->mockSnappy->shouldReceive('getOutputFromHtml')
            ->andReturn($pdfContent);

        $this->wrapper->loadHTML($html);
        $response = $this->wrapper->inline();

        $this->assertStringContainsString('document.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_download_with_default_filename()
    {
        $html = '<p>Test</p>';
        $pdfContent = '%PDF-1.4';

        $this->mockSnappy->shouldReceive('getOutputFromHtml')
            ->andReturn($pdfContent);

        $this->wrapper->loadHTML($html);
        $response = $this->wrapper->download();

        $this->assertStringContainsString('document.pdf', $response->headers->get('Content-Disposition'));
    }
}
