<?php

namespace Tests\Feature;

use Illuminate\Http\Response;

trait BulkStoreBaseTestTrait
{
    protected array $post;

    protected function postValidationError(array $post=null, $error_code = null): void
    {
        if ($error_code == null) $error_code = Response::HTTP_UNPROCESSABLE_ENTITY;
        $this->postForError($post);
        $this->assertApiValidationError($error_code);
    }

    protected function postError(array $post=null, $error_code = null): void
    {
        if ($error_code == null) $error_code = Response::HTTP_UNPROCESSABLE_ENTITY;
        $this->postForError($post, $error_code);
        $this->assertApiError($error_code);
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


    public function _testSetSymbol($key, $value, $errorCode, $errThreashold=0)
    {
        $this->createSampleReq(1, 0);
        foreach ($this->post['symbols'] as &$symbol) {
            $symbol[$key] = $value;
        }

        if ($errorCode == 200) {
            $this->postAPI();
            $asset = $this->getAsset($this->post['symbols'][0], $this->source);
            $child = $this->getChildren($asset, $this->source)->first();
            if ($errThreashold == 0) {
                $this->assertEquals($value, $child->$key);
            } else {
//                if ($this->verbose)
//                    print_r("values: " . json_encode([$child->$key, $value, $errThreashold]) . "\n");
                $this->assertTrue(abs($child->$key - $value) < $errThreashold);
            }
        } else {
            $this->postValidationError($this->post, $errorCode);
        }
    }

    public function _testUnsetSymbol($key, $errorCode)
    {
        $this->createSampleReq();
        foreach ($this->post['symbols'] as &$symbol) {
            unset($symbol[$key]);
        }

        $this->postValidationError($this->post, $errorCode);
    }

    public function _testUnset($key, $errorCode)
    {
        $this->createSampleReq();
        unset($this->post[$key]);

        $this->postValidationError($this->post, $errorCode);
    }

    protected function makeSymbol($name=null, $type=null): array
    {
        $values = [];
        if ($name) $values['name'] = $name;
        if ($type) $values['type'] = $type;
        return $this->symbolFactory->make($values)->toArray();
    }

    protected function postForError(?array $post)
    {
        if ($post == null) $post = $this->post;
        if ($this->verbose) print_r("*** postError " . $this->api . ": " . json_encode($post) . "\n");
        $this->response = $this->json('POST', $this->api, $post);
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
