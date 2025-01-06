<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Person;

class PersonApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_person()
    {
        $person = Person::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/people', $person
        );

        $this->assertApiResponse($person);
    }

    /**
     * @test
     */
    public function test_read_person()
    {
        $person = Person::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/people/'.$person->id
        );

        $this->assertApiResponse($person->toArray());
    }

    /**
     * @test
     */
    public function test_update_person()
    {
        $person = Person::factory()->create();
        $editedPerson = Person::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/people/'.$person->id,
            $editedPerson
        );

        $this->assertApiResponse($editedPerson);
    }

    /**
     * @test
     */
    public function test_delete_person()
    {
        $person = Person::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/people/'.$person->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/people/'.$person->id
        );

        $this->response->assertStatus(404);
    }
}
