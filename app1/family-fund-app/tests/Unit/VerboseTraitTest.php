<?php

namespace Tests\Unit;

use App\Http\Controllers\Traits\VerboseTrait;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit tests for VerboseTrait
 */
class VerboseTraitTest extends TestCase
{
    private $traitObject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->traitObject = new class {
            use VerboseTrait;
        };
    }

    public function test_verbose_defaults_to_false()
    {
        $this->assertFalse($this->traitObject->verbose);
    }

    public function test_debug_does_not_log_when_verbose_is_false()
    {
        Log::shouldReceive('debug')->never();

        $this->traitObject->verbose = false;
        $this->traitObject->debug('Test message');
    }

    public function test_debug_logs_when_verbose_is_true()
    {
        Log::shouldReceive('debug')
            ->once()
            ->with('Test message', []);

        $this->traitObject->verbose = true;
        $this->traitObject->debug('Test message');
    }

    public function test_info_does_not_log_when_verbose_is_false()
    {
        Log::shouldReceive('info')->never();

        $this->traitObject->verbose = false;
        $this->traitObject->info('Test message');
    }

    public function test_info_logs_when_verbose_is_true()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Test message', []);

        $this->traitObject->verbose = true;
        $this->traitObject->info('Test message');
    }

    public function test_debug_passes_data_array()
    {
        Log::shouldReceive('debug')
            ->once()
            ->with('Test message', ['key' => 'value']);

        $this->traitObject->verbose = true;
        $this->traitObject->debug('Test message', ['key' => 'value']);
    }

    public function test_info_passes_data_array()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Test message', ['key' => 'value']);

        $this->traitObject->verbose = true;
        $this->traitObject->info('Test message', ['key' => 'value']);
    }
}
