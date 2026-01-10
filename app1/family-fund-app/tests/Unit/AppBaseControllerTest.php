<?php

namespace Tests\Unit;

use App\Http\Controllers\AppBaseController;
use Tests\TestCase;

/**
 * Unit tests for AppBaseController
 */
class AppBaseControllerTest extends TestCase
{
    private AppBaseController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AppBaseController();
    }

    public function test_send_response_returns_success_json()
    {
        $result = ['id' => 1, 'name' => 'Test'];
        $message = 'Data retrieved successfully';

        $response = $this->controller->sendResponse($result, $message);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals($result, $data['data']);
        $this->assertEquals($message, $data['message']);
    }

    public function test_send_response_with_empty_result()
    {
        $result = [];
        $message = 'No data found';

        $response = $this->controller->sendResponse($result, $message);

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals([], $data['data']);
    }

    public function test_send_error_returns_error_json_with_default_code()
    {
        $error = 'Resource not found';

        $response = $this->controller->sendError($error);

        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals($error, $data['message']);
    }

    public function test_send_error_with_custom_code()
    {
        $error = 'Validation failed';

        $response = $this->controller->sendError($error, 422);

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals($error, $data['message']);
    }

    public function test_send_error_with_server_error_code()
    {
        $error = 'Internal server error';

        $response = $this->controller->sendError($error, 500);

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function test_send_success_returns_success_json()
    {
        $message = 'Operation completed successfully';

        $response = $this->controller->sendSuccess($message);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals($message, $data['message']);
    }

    public function test_send_success_does_not_include_data_key()
    {
        $response = $this->controller->sendSuccess('Success');

        $data = json_decode($response->getContent(), true);
        $this->assertArrayNotHasKey('data', $data);
    }
}
