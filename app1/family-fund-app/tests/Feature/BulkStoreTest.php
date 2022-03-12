<?php

namespace Tests\Feature;

use App\Models\AssetExt;
use Tests\DataFactory;

trait BulkStoreTest
{
    use TimestampTest;
    use BulkStoreBaseTest;

    protected $field;
    protected $symbols;
    protected $updateObject;
    private $api;
    private $symbolFactory;
    private $requestFactory;
    private $source;
    private $cashTimestamp;
    private $verbose=false;
    private $df;

    // TEST: force not create asset?
    protected function getAsset(mixed $name, $source)
    {
        return AssetExt::where('name', $name)
            ->where('source', $source)->get()->first();
    }

    protected function getAssetOrCash($name, $type)
    {
        if (AssetExt::isCashInput(['name' => $name, 'type' => $type])) {
            return AssetExt::getCashAsset();
        } else {
            return AssetExt::where('name', $name)
                ->where('type', $type)
                ->where('source', $this->post['source'])
                ->get();
        }
    }

    protected function _testBothInSymbol($value): void
    {
        $this->createSampleReq(1);
        $this->post['symbols'][0]['price'] = $value;
        $this->post['symbols'][0]['position'] = $value;
        $this->postError();
    }

    private function setupBulkTest($field, $api, $symbolFactory, $reqFactory, $cashTs=null, $cashValue=null)
    {
        $this->df = new DataFactory();
        $this->df->createFund();
        $this->source = $this->df->portfolio->source;
        if ($cashTs == null)    $cashTs    = $this->df->cashPosition->start_dt;
        if ($cashValue == null) $cashValue = $this->df->cashPosition->position;

        if ($this->verbose) print_r("SETUP ".json_encode($this->df->portfolio)."\n");
        if ($this->verbose) print_r("SETUP ".json_encode($this->df->cashPosition)."\n");

        $this->cashTimestamp = $cashTs;
        $this->cashValue = $cashValue;
        $this->setupTimestampTest("2022-01-01");
        $this->field = $field;
        $this->api = $api;
        $this->symbolFactory = $symbolFactory;
        $this->requestFactory = $reqFactory;
    }

    public function testMissingFields()
    {
        $this->_testUnset('timestamp', 422);
        $this->_testUnset('source', 422);
        $this->_testUnsetSymbol('name', 422);
        $this->_testUnsetSymbol('type', 422);
        $this->_testUnsetSymbol($this->field, 422);
    }

    public function testNegativeFields()
    {
        $this->_testSetSymbol($this->field, -1, 422);
        $this->_testSetSymbol($this->field, null, 422);
        $this->_testSetSymbol($this->field, "abc", 422);
        $this->_testSetSymbol($this->field, 0, 422);
    }

    public function testExtraFields()
    {
        $this->_testBothInSymbol(1);
        $this->_testBothInSymbol(null);
    }

    public function testBasic()
    {
        // test new asset
        $this->createSampleReq();
        $this->postAPI();
        $this->validateSampleRequest('validateUniqueHistorical');
    }

    public function testNoSymbols()
    {
        $this->createSampleReq();
        $this->post['symbols'] = [];
        $this->postError();
        $this->post['symbols'] = null;
        $this->postError();
        unset($this->post['symbols']);
        $this->postError();
    }

    public function testSameSymbolOtherSource()
    {
        $this->createSampleReq(1);
        $this->postAPI();

        $this->nextDay(3);

        $name = $this->symbols[0]['name'];
        $source1 = $this->source;
        $asset = $this->getAsset($name, $source1);
        $this->assertCount(1, $this->getChildren($asset, $source1));

        $df = new DataFactory();
        $df->createFund();
        $source = $df->portfolio->source;
        $this->post['source'] = $source;

        $this->assertFalse(AssetExt::where('name', $name)
                ->where('source', $source)->exists());

        $this->postAPI();

        $asset2 = $this->getAsset($name, $source);
        $this->assertNotNull($asset2, $source);
        $this->assertCount(1, $this->getChildren($asset2, $source));
        $this->assertCount(1, $this->getChildren($asset, $source1));
    }

    public function testRemoveSymbol() {
        $this->createSampleReq(2);
        $this->postAPI();

        $symbol2 = $this->post['symbols'][1];
        $asset = $this->getAsset($symbol2['name'], $this->source);

        $this->assertCount(1, $this->getChildren($asset, $this->source));
        $this->assertInfinity($this->getChildren($asset, $this->source)->first()->end_dt);
        $this->nextDay(3);
        unset($this->post['symbols'][2]);
        unset($this->post['symbols'][1]);
        $this->postAPI();
        $this->assertCount(1, $this->getChildren($asset, $this->source));
        if ($this->field == 'position') {
            $this->assertDate($this->timestamp(), $this->getChildren($asset, $this->source)->first()->end_dt);
        } else {
            $this->assertInfinity($this->getChildren($asset, $this->source)->first()->end_dt);
        }
    }

    public function testAddBefore() {
        $this->createSampleReq(1);
        $this->postAPI();

        $name = $this->symbols[0]['name'];
        $asset = $this->getAsset($name, $this->source);
        $children = $this->getChildren($asset, $this->source);
        $child = $children->first();
        $value = $child->{$this->field};
        if ($this->verbose) print_r("child: " . json_encode($child) . "\n");

        $this->assertCount(1, $children);
        $this->assertEquals($value, $child->{$this->field});

        $this->setupTimestampTest($child->start_dt);
        $this->prevDay();
        $this->createSampleReq(1);
        $this->postAPI();
        $children = $this->getChildren($asset, $this->source);

        $child = $children->first();
        if ($this->verbose) print_r("child: " . json_encode($child) . "\n");
        $this->assertCount(1, $children);
        $this->assertEquals($value, $child->{$this->field});
    }

    public function testHistory()
    {
        $this->createSampleReq();
        $this->postAPI();

        $ts1 = $this->timestamp();
        $p1 = $this->post['symbols'][0];
        $p2 = $this->post['symbols'][1];

        $pCash = null;
        if ($this->field == 'position')
            $pCash = $this->post['symbols'][2];

        $this->nextDay(4);
        $this->postAPI();
        $this->validateSampleRequest('validateUniqueHistorical', $ts1);

        $this->nextDay(1);
        $ts2 = $this->timestamp();
        unset($this->post['symbols'][1]);
        $value1 = $this->post['symbols'][0][$this->field];
        $value2 = $this->post['symbols'][0][$this->field] = 11.11;
        $this->postAPI();

        $this->validateSampleRequest('validate2Historical', $ts1, $value1);

        $this->post['symbols'] = [$p2];
        $this->validateSampleRequest('validateUniqueHistorical', $ts1,
            $this->field=='position'?"NOTINF":null, $ts2);

        $this->prevDay();
        $this->prevDay();
        $this->prevDay();
        unset($this->post['symbols'][0]);
        $this->post['symbols'] = [$p1];

        if ($this->field == 'position')
            $this->post['symbols'][] = $pCash;

        $this->post['symbols'][0][$this->field] = 55.55;
        $this->postAPI();

        $this->validateSampleRequest('validate3Historical', $ts1, $value1, $ts2, $value2);
    }

    protected function createSampleReq($count=2, $cashValue=1000): array
    {
        $this->symbols = $this->symbolFactory->count($count)->make();
        if ($this->field == 'position' && $cashValue > 0) {
            $this->symbols->add(
                $this->symbolFactory->make([
                    'name' => 'CASH',
                    'type' => 'CSH',
                    $this->field => $cashValue,
                ]));
        }
        $this->updateObject = $this->requestFactory->make([
            'timestamp' => $this->timestamp(),
            'source'    => $this->source,
        ]);

        $assetsArr = $this->updateObject->toArray();
        $assetsArr['symbols'] = $this->symbols->toArray();
        $this->post = $assetsArr;
        return $assetsArr;
    }

    protected function validateSampleRequest($func, $ts1=null, $value1=null, $ts2=null, $value2=null): void
    {
        if ($ts1 == null) $ts1 = $this->post['timestamp'];

        foreach ($this->post['symbols'] as $symbol) {
            if (AssetExt::isCashInput($symbol))
                continue;

            $symbol['source'] = $this->post['source'];

            $res = $this->getAssetOrCash($symbol['name'], $symbol['type']);

            $this->assertCount(1, $res->toArray());
            $asset = $res->first();
            if ($this->verbose) print_r(json_encode($asset) . "\n");

            $symbol['updated_at'] = $symbol['created_at'] = date('Y-m-d');
            $this->assertAssetSymbol($asset, $symbol);

            $aps = $this->getChildren($asset, $this->source);
            $this->$func($aps, $this->field, $symbol['name'], $symbol[$this->field], $ts1, $value1, $ts2, $value2);
        }
        $this->validateNoChangeCash($this->cashTimestamp);
    }

    public function validateNoChangeCash($ts): void
    {
        $asset = AssetExt::where('name', 'CASH')->get()->first();
        $aps = $this->getChildren($asset, $this->source);
        $this->validateUniqueHistorical($aps, $this->field, 'CASH', $this->cashValue, $ts);
    }


    protected function validate3Historical($collection, $field, $name, $expectedValue, $ts1, $value1, $ts2, $value2)
    {
        if ($this->verbose) print_r(json_encode([$name, $ts1, $expectedValue, $ts2, $value2])."\n");
        $this->assertEquals(3, count($collection));
        foreach ($collection as $obj) {
            if ($this->verbose) print_r(json_encode($obj)."\n");
            if ($this->compareTimestamp($obj->start_dt, $this->post['timestamp'])) { // middle record
                $this->assertTrue($this->compareTimestamp($obj->end_dt, $ts2));
                $this->assertEquals($expectedValue, $obj[$field]);
            } else if ($this->compareTimestamp($obj->start_dt, $ts1)) {
                $this->assertTrue($this->compareTimestamp($obj->end_dt, $this->post['timestamp']));
                $this->assertEquals($value1, $obj->$field);
            } else {
                $this->assertTrue($this->compareTimestamp($obj->start_dt, $ts2));
                $this->assertTrue($this->isInfinity($obj->end_dt));
                $this->assertEquals($value2, $obj->$field);
            }
        }
    }
    protected function validate2Historical($collection, $field, $name, $expectedValue, $oldTs, $oldExpectedValue, $ts2=null, $value2=null)
    {
        if ($this->verbose) print_r(json_encode([$name, $oldTs, $expectedValue])."\n");
        $this->assertEquals(2, count($collection));
        foreach ($collection as $obj) {
            if ($this->verbose) print_r(json_encode($obj)."\n");
            if ($this->compareTimestamp($obj->start_dt, $this->post['timestamp'])) {
                $this->assertEquals($expectedValue, $obj->$field);
                $this->assertTrue($this->isInfinity($obj->end_dt));
            } else {
                $this->assertEquals($oldExpectedValue, $obj->$field);
                $this->assertTrue($this->compareTimestamp($obj->start_dt, $oldTs));
                $this->assertTrue($this->compareTimestamp($obj->end_dt,   $this->post['timestamp']));
            }
        }
    }

    public function validateUniqueHistorical($collection, $field, $name, $expectedValue, $timestamp, $value1=null, $ts2=null, $value2=null): void
    {
        if ($this->verbose) print_r("coll: " . json_encode($collection->toArray()) . "\n");
        $count = 0;
        foreach ($collection as $obj) {
            if ($this->verbose) print_r(json_encode($obj)."\n");
            if ($this->compareTimestamp($obj->start_dt, $timestamp)) {
                $this->assertEquals($obj->{$field}, $expectedValue);
                if ($value1 == "NOTINF") {
                    $this->assertDate($ts2, $obj->end_dt);
                } else {
                    $this->assertInfinity($obj->end_dt);
                }
                $count++;
            }
        }
        $this->assertEquals(1, $count);
        $count = count($collection);
        $this->assertEquals(1, $count);
    }


}

