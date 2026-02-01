<?php

namespace Tests\Feature;

use App\Http\Controllers\Traits\VerboseTrait;
use App\Models\AssetPrice;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
trait BulkStoreBaseTestTrait
{
    use VerboseTrait;

    protected array $post;

    protected function postValidationError(?array $post = null, $error_code = null): void
    {
        if ($error_code == null) $error_code = Response::HTTP_UNPROCESSABLE_ENTITY;
        $this->postForError($post);
        $this->assertApiValidationError($error_code);
    }

    protected function postError(?array $post = null, $error_code = null): void
    {
        if ($error_code == null) $error_code = Response::HTTP_UNPROCESSABLE_ENTITY;
        $this->postForError($post, $error_code);
        $vals = AssetPrice::where('asset_id', '>', 202)->get();
        Log::debug("postError: " . json_encode($vals));
        $this->assertApiError($error_code);
    }

    protected function postBulkAPI(?array $post = null): void
    {
        if ($post==null) $post = $this->post;
        $this->debug("*** postBulkAPI ".$this->api.": " . json_encode($post));
        $this->response = $this->json('POST', $this->api, $post);
        $this->debug("*** response: " . json_encode($this->response));
        $this->assertEmptyData();
        $this->assertApiSuccess();
    }


    protected function assertEmptyData()
    {
        if ($this->verbose)
            Log::debug($this->response->content()."\n");
        $res = json_decode($this->response->content());
        $this->assertEmpty($res->data);
    }


    public function _testSetSymbol($key, $value, $errorCode, $errThreashold=0)
    {
        $this->createSampleReq(1, 0);
        foreach ($this->post['symbols'] as &$symbol) {
            $symbol[$key] = $value;
        }
        unset($symbol);

        if ($errorCode == 200) {
            $this->postBulkAPI();
            $asset = $this->getAsset($this->post['symbols'][0], $this->source);
            $child = $this->getChildren($asset, $this->source)->first();
            if ($errThreashold == 0) {
                $this->assertEquals($value, $child->$key);
            } else {
                $check = abs($child->$key - $value) < $errThreashold;
                if ($this->verbose)
                    Log::debug("values: " . json_encode([$child->$key, $value, $errThreashold, $check]) . "\n");
                $this->assertTrue($check);
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
        $overrides = [];
        if ($name) $overrides['name'] = $name;
        if ($type) $overrides['type'] = $type;
        return $this->generateSymbol($overrides);
    }

    protected function postForError(?array $post)
    {
        if ($post == null) $post = $this->post;
        $this->debug("*** postError " . $this->api . ": " . json_encode($post));
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
