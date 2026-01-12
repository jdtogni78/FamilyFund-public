<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class PriceUpdateApiExtTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions, BulkStoreTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verbose = false;
        $this->setupBulkTest('price', '/api/asset_prices_bulk_update', '2000-01-01', 1.0);
    }

    public function getChildren($asset, $source) {
        return $asset->assetPrices()->get();
    }

    public function testCashErrors()
    {
        $this->createSampleReq();
        $this->post['symbols'][] = $this->makeSymbol('CASH');
        $this->postValidationError();

        $this->createSampleReq();
        $this->post['symbols'][] = $this->makeSymbol(null, 'CSH');
        $this->postValidationError();
    }

    public function testCornerCases()
    {
//        $table->decimal('price', 13, 2);
        $this->_testSetSymbol($this->field, "99999999999999999999.9", 422);
        $this->_testSetSymbol($this->field, "9999999999999.99999999", 422);

        $err = 0.01;
        $this->_testSetSymbol($this->field, "12345678901.45678901", 200, $err);
        $this->_testSetSymbol($this->field, "12345678901.456", 200, $err);
        $this->_testSetSymbol($this->field, "99999999999.98", 200);
        $this->_testSetSymbol($this->field, "99999999999.984", 200, $err);
        $this->_testSetSymbol($this->field, "99999999999.999", 422);
        $this->_testSetSymbol($this->field, "9.99999999999999999999", 200, $err);
        $this->_testSetSymbol($this->field, "0.01", 200);
        $this->_testSetSymbol($this->field, 0.01, 200);
        $this->_testSetSymbol($this->field, 0.001, 200, $err);
        $this->_testSetSymbol($this->field, "0.001", 200, $err);
        $this->_testSetSymbol($this->field, 1.2, 200);
    }

}
