<?php

namespace App\Http\Controllers\Traits;

use Log;

trait VerboseTrait
{
    public $verbose = false;

    public function debug($message, $data = []) {
        if ($this->verbose) Log::debug($message, $data);
    }
    public function info($message, $data = []) {
        if ($this->verbose) Log::info($message, $data);
    }
}
