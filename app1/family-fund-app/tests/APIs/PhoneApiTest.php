<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Phone;

class PhoneApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_phone()
    {
        $phone = Phone::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/phones', $phone
        );

        $this->assertApiResponse($phone);
    }

    /**
     * @test
     */
    public function test_read_phone()
    {
        $phone = Phone::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/phones/'.$phone->id
        );

        $this->assertApiResponse($phone->toArray());
    }

    /**
     * @test
     */
    public function test_update_phone()
    {
        $phone = Phone::factory()->create();
        $editedPhone = Phone::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/phones/'.$phone->id,
            $editedPhone
        );

        $this->assertApiResponse($editedPhone);
    }

    /**
     * @test
     */
    public function test_delete_phone()
    {
        $phone = Phone::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/phones/'.$phone->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/phones/'.$phone->id
        );

        $this->response->assertStatus(404);
    }
}
