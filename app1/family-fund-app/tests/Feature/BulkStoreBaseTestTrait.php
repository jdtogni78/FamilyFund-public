<?php

namespace Tests\Feature;

trait BulkStoreBaseTestTrait
{
    protected array $post;

    protected function postError(array $post=null, $error_code = 422): void
    {
        if ($post==null) $post = $this->post;
        if ($this->verbose) print_r("*** postError ".$this->api.": " . json_encode($post)."\n");
        $this->response = $this->json('POST', $this->api, $post);
        $this->assertApiValidationError($error_code);
    }

    protected function postAPI(array $post=null): void
    {
        if ($post==null) $post = $this->post;
        if ($this->verbose) print_r("*** postAPI ".$this->api.": " . json_encode($post)."\n");
        $this->response = $this->json('POST', $this->api, $post);
        $this->assertEmptyData();
        $this->assertApiSuccess();
    }


    protected function assertEmptyData()
    {
        if ($this->verbose)
            print_r($this->response->content()."\n");
        $res = json_decode($this->response->content());
        $this->assertEmpty($res->data);
    }


    public function _testSetSymbol($key, $value, $errorCode, $err=0)
    {
        $this->createSampleReq(1, 0);
        foreach ($this->post['symbols'] as &$symbol) {
            $symbol[$key] = $value;
        }

        if ($errorCode == 200) {
            $this->postAPI();
            $asset = $this->getAsset($this->post['symbols'][0], $this->source);
            $child = $this->getChildren($asset, $this->source)->first();
            if ($err == 0) {
                $this->assertEquals($value, $child->$key);
            } else {
                $this->assertTrue(abs($child->$key - $value) < $err);
            }
        } else {
            $this->postError($this->post, $errorCode);
        }
    }

    public function _testUnsetSymbol($key, $errorCode)
    {
        $this->createSampleReq();
        foreach ($this->post['symbols'] as &$symbol) {
            unset($symbol[$key]);
        }

        $this->postError($this->post, $errorCode);
    }

    public function _testUnset($key, $errorCode)
    {
        $this->createSampleReq();
        unset($this->post[$key]);

        $this->postError($this->post, $errorCode);
    }

    protected function makeSymbol($name=null, $type=null): array
    {
        $values = [];
        if ($name) $values['name'] = $name;
        if ($type) $values['type'] = $type;
        return $this->symbolFactory->make($values)->toArray();
    }

    private function assertAssetSymbol($asset, mixed $symbol)
    {
        if (!$asset->isCash()) {
            $this->assertEquals($asset->source, $symbol['source']);
            if (array_key_exists('created_at', $symbol))  $this->assertDate($asset->created_at,  $symbol['created_at']);
            if (array_key_exists('updated_at', $symbol))  $this->assertDate($asset->updated_at,  $symbol['updated_at']);
        }
        $this->assertEquals($asset->name, $symbol['name']);
        $this->assertEquals($asset->type, $symbol['type']);
    }

}
