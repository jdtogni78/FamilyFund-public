<?php namespace Tests;

use App\Models\User;
use Mockery\Exception;

trait ApiTestTrait
{
    private $response;
    protected $success;
    protected $message;
    protected $data;

    protected $verbose = false;

    public function loginWithFakeUser($email='admin@dev.familyfund.local')
    {
        $user = new User([
            'id' => 1,
            'name' => 'yish',
            'email' => $email,
        ]);

        $this->be($user);
    }

    public function assertApiResponse($expectedData, Array $ignoredKeys = [])
    {
        if ($this->verbose) {
            print_r("IGNORE: ".json_encode($ignoredKeys)."\n");
            print_r("EXPECT: ".json_encode($expectedData)."\n");
        }

        $this->assertApiSuccess();

        $response = json_decode($this->response->getContent(), true);
        $actualData = $response['data'];

        if ($this->verbose)
            print_r(json_encode($actualData)."\n");

        $this->assertNotEmpty($actualData['id']);
        $this->assertModelData($expectedData, $actualData, $ignoredKeys, 'data');
    }

    public function assertApiSuccess()
    {
        $this->response->assertStatus(200);
        $this->response->assertJson(['success' => true]);
    }

    public function assertApiValidationError($code)
    {
        $this->response->assertStatus($code);
        $response = json_decode($this->response->getContent(), true);
        $this->assertArrayNotHasKey('success', $response);
        $this->assertNotNull($response['errors']);
    }

    public function assertApiError($code)
    {
        $this->response->assertStatus($code);
//        $response = json_decode($this->response->getContent(), true);
        $this->response->assertJson(['success' => false]);
    }

    public function assertModelData(Array $expectedData, Array $actualData, Array $ignoredKeys, $path)
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
            if ($this->verbose) print_r("** assert model: " . json_encode([$p, $expectedData[$key], $actualData[$key]]) . "\n");
            if (is_array($actualData[$key])) {
                $ignored = [];
                if (array_key_exists($key, $ignoredKeys)) {
                    $ignored = $ignoredKeys[$key];
                }
                $this->assertModelData($expectedData[$key], $actualData[$key], $ignored, $p);
            } else {
                if (preg_match('/data.transactions.*.current_performance/', $p)
                        || preg_match('/data.balances.*.value/', $p)) {
                    // TODO: use a registry of custom validations
                    $this->assertTrue(abs($expectedData[$key]/$actualData[$key] - 1) < 0.02, $p);
                } else {
                    $this->assertEquals($expectedData[$key], $actualData[$key], $p);
                }
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
        if ($verbose) print_r("response: " . json_encode($response) . "\n");
    }

    private function getAPI(string $api): void
    {
        if ($this->verbose) print("*** API: $api\n");
        $this->response = $this->json(
            'GET',
            $api
        );
        if ($this->verbose) print("RESPONSE: ".$this->response->getContent() . "\n");
    }

}
