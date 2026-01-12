<?php

namespace Tests\Feature;

use App\Http\Controllers\Traits\VerboseTrait;
use App\Models\AssetExt;
use Log;
use Ramsey\Collection\Collection;
use Symfony\Component\HttpFoundation\Response;
use Tests\DataFactory;

trait BulkStoreTestTrait
{
    use TimestampTestTrait, VerboseTrait;
    use BulkStoreBaseTestTrait;

    protected $field;
    protected $symbols;
    protected $updateObject;
    private $api;
    private $source;
    private $cashTimestamp;
    private $df;
    private static $symbolCounter = 0;

    // TEST: force not create asset?
    private int $validationError = Response::HTTP_UNPROCESSABLE_ENTITY;

    protected function getAsset(mixed $name, $source)
    {
        return AssetExt::where('name', $name)
            ->where('source', $source)->get()->first();
    }

    protected function getAssetOrCash($name, $type)
    {
        if (AssetExt::isCashInput(['name' => $name, 'type' => $type])) {
            $c = new Collection();
            $c->add(AssetExt::getCashAsset());
            return $c;
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
        $this->postValidationError();
    }

    private function setupBulkTest($field, $api, $cashTs=null, $cashValue=null)
    {
        $this->df = new DataFactory();
        $this->df->createFund();
        $this->source = $this->df->portfolio->source;
        if ($cashTs == null)    $cashTs    = $this->df->cashPosition->start_dt;
        if ($cashValue == null) $cashValue = $this->df->cashPosition->position;

        $this->debug("SETUP ".json_encode($this->df->portfolio));
        $this->debug("SETUP ".json_encode($this->df->cashPosition));

        $this->cashTimestamp = $cashTs;
        $this->cashValue = $cashValue;
        $this->setupTimestampTest("2022-01-01");
        $this->field = $field;
        $this->api = $api;
    }

    private function generateSymbol(array $overrides = []): array
    {
        $types = ['STK', 'ETF', 'BND', 'CRY'];
        self::$symbolCounter++;
        $symbol = [
            'name' => 'symbol_' . self::$symbolCounter . '_' . rand(10000, 99999),
            'type' => $types[array_rand($types)],
        ];
        if ($this->field === 'position') {
            $symbol['position'] = round(rand(1, 10000) / 100, 4);
        } else {
            $symbol['price'] = round(rand(1, 100000) / 100, 2);
        }
        return array_merge($symbol, $overrides);
    }

    private function generateSymbols(int $count): array
    {
        $symbols = [];
        for ($i = 0; $i < $count; $i++) {
            $symbols[] = $this->generateSymbol();
        }
        return $symbols;
    }

    public function testMissingFields()
    {
        $this->_testUnset('timestamp', $this->validationError);
        $this->_testUnset('source', $this->validationError);
        $this->_testUnsetSymbol('name', $this->validationError);
        $this->_testUnsetSymbol('type', $this->validationError);
        $this->_testUnsetSymbol($this->field, $this->validationError);
    }

    public function testNegativeFields()
    {
        $this->_testSetSymbol($this->field, -1, $this->validationError);
        $this->_testSetSymbol($this->field, null, $this->validationError);
        $this->_testSetSymbol($this->field, "abc", $this->validationError);
        $this->_testSetSymbol($this->field, 0, $this->validationError);
    }

    public function testExtraFields()
    {
        $this->_testBothInSymbol(1);
        $this->_testBothInSymbol(null);
    }

    public function testBasic()
    {
        // test new asset
        // $this->verbose = true;
        $this->createSampleReq();
        $this->postBulkAPI();
        $this->validateSampleRequest('validateUniqueHistorical');

        $oldVal = $this->post['symbols'][1][$this->field];
        $this->post['symbols'][1][$this->field] = 44.11;
        $ap = \App\Models\AssetPrice::where('asset_id', 202)->get();
        Log::debug("ap: " . json_encode($ap));
        $this->postError();

        $this->post['symbols'][1][$this->field] = $oldVal;
        $this->validateSampleRequest('validateUniqueHistorical');

        $this->post['symbols'][0]['name'] = $this->post['symbols'][0]['name']."_2";
        $this->post['symbols'][1][$this->field] = 44.11;
        $this->postError();

        $symbol = $this->post['symbols'][0];
        $res = $this->getAssetOrCash($symbol['name'], $symbol['type']);
        $this->assertCount(0, $res->toArray());
    }

    public function testNoSymbols()
    {
        $this->createSampleReq();
        $this->post['symbols'] = [];
        $this->postValidationError();
        $this->post['symbols'] = null;
        $this->postValidationError();
        unset($this->post['symbols']);
        $this->postValidationError();
    }

    public function testSameSymbolOtherSource()
    {
        $this->createSampleReq(1);
        $this->postBulkAPI();

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

        $this->postBulkAPI();

        $asset2 = $this->getAsset($name, $source);
        $this->assertNotNull($asset2, $source);
        $this->assertCount(1, $this->getChildren($asset2, $source));
        $this->assertCount(1, $this->getChildren($asset, $source1));
    }

    public function testRemoveSymbol() {
        $this->createSampleReq(2);
        $this->postBulkAPI();

        $symbol2 = $this->post['symbols'][1];
        $asset = $this->getAsset($symbol2['name'], $this->source);

        $this->assertCount(1, $this->getChildren($asset, $this->source));
        $this->assertInfinity($this->getChildren($asset, $this->source)->first()->end_dt);
        $this->nextDay(3);
        unset($this->post['symbols'][2]);
        unset($this->post['symbols'][1]);
        $this->postBulkAPI();
        $this->assertCount(1, $this->getChildren($asset, $this->source));
        if ($this->field == 'position') {
            $this->assertDate($this->timestamp(), $this->getChildren($asset, $this->source)->first()->end_dt);
        } else {
            $this->assertInfinity($this->getChildren($asset, $this->source)->first()->end_dt);
        }
    }

    public function testAddBefore() {
        $this->createSampleReq(1);
        $this->postBulkAPI();

        $name = $this->symbols[0]['name'];
        $asset = $this->getAsset($name, $this->source);
        $children = $this->getChildren($asset, $this->source);
        $child = $children->first();
        $value = $child->{$this->field};
        $this->debug("child: " . json_encode($child));

        $this->assertCount(1, $children);
        $this->assertEquals($value, $child->{$this->field});

        $this->setupTimestampTest($child->start_dt);
        $this->prevDay();
        $this->createSampleReq(1);
        $this->postBulkAPI();
        $children = $this->getChildren($asset, $this->source);

        $child = $children->first();
        $this->debug("child: " . json_encode($child));
        $this->assertCount(1, $children);
        $this->assertEquals($value, $child->{$this->field});
    }

    public function testHistory()
    {
        $this->createSampleReq();
        $this->postBulkAPI();

        $ts1 = $this->timestamp();
        $p1 = $this->post['symbols'][0];
        $p2 = $this->post['symbols'][1];

        $pCash = null;
        if ($this->field == 'position')
            $pCash = $this->post['symbols'][2];

        $this->nextDay(4);
        $this->postBulkAPI();
        $this->validateSampleRequest('validateUniqueHistorical', $ts1);

        $this->nextDay(1);
        $ts2 = $this->timestamp();
        unset($this->post['symbols'][1]);
        $value1 = $this->post['symbols'][0][$this->field];
        $value2 = $this->post['symbols'][0][$this->field] = 11.11;
        $this->postBulkAPI();
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
        $this->postBulkAPI();

        $this->validateSampleRequest('validate3Historical', $ts1, $value1, $ts2, $value2);
    }

    protected function createSampleReq($count=2, $cashValue=1000): array
    {
        $this->symbols = collect($this->generateSymbols($count));
        if ($this->field == 'position' && $cashValue > 0) {
            $this->symbols->add([
                'name' => 'CASH',
                'type' => 'CSH',
                $this->field => $cashValue,
            ]);
        }

        $this->post = [
            'timestamp' => $this->timestamp(),
            'source'    => $this->source,
            'symbols'   => $this->symbols->toArray(),
        ];
        $this->debug("createSampleReq: " . json_encode($this->post));
        return $this->post;
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
            $this->debug(json_encode($asset));

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
        $this->debug(json_encode([$name, $ts1, $expectedValue, $ts2, $value2])."\n");
        $this->assertEquals(3, count($collection));
        foreach ($collection as $obj) {
            $this->debug(json_encode($obj)."\n");
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
        $this->debug(json_encode([$name, $oldTs, $expectedValue])."\n");
        $this->assertEquals(2, count($collection));
        foreach ($collection as $obj) {
            $this->debug(json_encode($obj)."\n");
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
        $this->debug("coll: " . json_encode($collection->toArray()));
        $count = 0;
        foreach ($collection as $obj) {
            $this->debug(json_encode($obj));
            if ($this->compareTimestamp($timestamp, $obj->start_dt)) {
                $this->assertEquals($expectedValue, $obj->{$field});
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

