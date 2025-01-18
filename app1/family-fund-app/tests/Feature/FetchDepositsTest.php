<?php 

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Controllers\Traits\FetchDepositsTrait;
use App\Jobs\FetchDeposits;

class FetchDepositsTest extends TestCase
{
    public function test_fetch_deposits() {
        $fd = new FetchDeposits();
        $fd->handle();
    }
}

