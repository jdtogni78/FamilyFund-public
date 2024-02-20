<?php namespace Tests;

use App\Http\Controllers\Traits\VerboseTrait;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

trait ApiTestTrait
{
    use VerboseTrait;

    private $response;
    protected $success;
    protected $message;
    protected $data;

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
            Log::debug("IGNORE: ".json_encode($ignoredKeys));
            Log::debug("EXPECT: ".json_encode($expectedData));
        }

        $this->assertApiSuccess();

        $response = json_decode($this->response->getContent(), true);
        $actualData = $response['data'];

        if ($this->verbose)
            Log::debug(json_encode($actualData));

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
        Log::debug($this->response->getContent());
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
                Log::debug($actualData[$key]);
                $this->fail("Unnexpected key on actual response: $p");
            }
            $value = $actualData[$key];
            if (in_array($key, ['created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            if ($this->verbose) Log::debug("** assert model: " . json_encode([$p, $expectedData[$key], $actualData[$key]]));
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
    public function setResponse($response): void
    {
        $this->response = $response;

        $response = json_decode($this->response->getContent(), true);
        $this->data = null;
        $this->success = null;
        if (array_key_exists('success', $response)) {
            $this->success = $response['success'];
            if ($this->verbose) Log::debug("SUCCESS: " . ($this->success?"TRUE":"false") . "\n");
        }
        if (array_key_exists('message', $response)) {
            $this->message = $response['message'];
            if ($this->verbose) Log::debug("MESSAGE: " . $this->message . "\n");
        }
        if (array_key_exists('data', $response)) {
            $this->data = $response['data'];
        }
        if ($this->verbose) Log::debug("response: " . $this->response->getStatusCode() . " " . json_encode($response) . "\n");
    }

    public function postAPI(string $api, array $data) {
        if ($this->verbose) Log::debug("*** POST API: $api\n*** DATA:" . json_encode($data) );
        $this->setResponse($this->json('POST', $api, $data));
    }

    public function getAPI(string $api): void
    {
        if ($this->verbose) Log::debug("*** GET API: $api\n");
        $this->setResponse($this->json('GET', $api));
    }

}
