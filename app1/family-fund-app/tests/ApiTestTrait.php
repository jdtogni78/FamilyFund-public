<?php namespace Tests;

trait ApiTestTrait
{
    private $response;
    protected $success;
    protected $message;
    protected $data;

    private $verbose = false;

    public function assertApiResponse(Array $expectedData, Array $ignoredKeys = [])
    {
        if ($this->verbose) {
            print_r(json_encode($ignoredKeys)."\n");
            print_r(json_encode($expectedData)."\n");
        }

        $this->assertApiSuccess();

        $response = json_decode($this->response->getContent(), true);
        $actualData = $response['data'];

        if ($this->verbose)
            print_r(json_encode($actualData)."\n");

        $this->assertNotEmpty($actualData['id']);
        $this->assertModelData($actualData, $expectedData, $ignoredKeys, 'data');
    }

    public function assertApiSuccess()
    {
        $this->response->assertStatus(200);
        $this->response->assertJson(['success' => true]);
    }

    public function assertApiError($code)
    {
        $this->response->assertStatus($code);
        $response = json_decode($this->response->getContent(), true);
        $this->assertArrayNotHasKey('success', $response);
        $this->assertNotNull($response['errors']);
    }

    public function assertModelData(Array $actualData, Array $expectedData, Array $ignoredKeys, $path)
    {
        $all_keys = array_diff(
            array_merge(array_keys($actualData), array_keys($expectedData)),
            $ignoredKeys);

        foreach ($all_keys as $key) {
            $p = $path.'.'.$key;
            if (!array_key_exists($key, $actualData)) {
                $this->fail("Key missing on actual response: $p");
            }
            if (!array_key_exists($key, $expectedData)) {
                $this->fail("Unnexpected key on actual response: $p");
            }
        $value = $actualData[$key];
            if (in_array($key, ['created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            if (is_array($actualData[$key])) {
                $ignored = [];
                if (array_key_exists($key, $ignoredKeys)) {
                    $ignored = $ignoredKeys[$key];
                }
                $this->assertModelData($actualData[$key], $expectedData[$key], $ignored, $p);
            } else {
                $this->assertEquals($actualData[$key], $expectedData[$key], $p);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     * @param $verbose
     */
    public function setResponse($response, $verbose=false): void
    {
        $this->response = $response;

        $response = json_decode($this->getResponse()->body(), true);
        $this->data = null;
        $this->success = null;
        if (array_key_exists('success', $response)) {
            $this->success = $response['success'];
            print_r("SUCCESS: " . $this->success . "\n");
        }
        if (array_key_exists('message', $response)) {
            $this->message = $response['message'];
            print_r("MESSAGE: " . $this->message . "\n");
        }
        if (array_key_exists('data', $response)) {
            $this->data = $response['data'];
        }
        if ($verbose) $this->p($response);
    }
}
