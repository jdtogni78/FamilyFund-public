<?php namespace Tests\Repositories;

use App\Models\Person;
use App\Repositories\PersonRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class PersonRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var PersonRepository
     */
    protected $personRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->personRepo = \App::make(PersonRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_person()
    {
        $person = Person::factory()->make()->toArray();

        $createdPerson = $this->personRepo->create($person);

        $createdPerson = $createdPerson->toArray();
        $this->assertArrayHasKey('id', $createdPerson);
        $this->assertNotNull($createdPerson['id'], 'Created Person must have id specified');
        $this->assertNotNull(Person::find($createdPerson['id']), 'Person with given id must be in DB');
        $this->assertModelData($person, $createdPerson);
    }

    /**
     * @test read
     */
    public function test_read_person()
    {
        $person = Person::factory()->create();

        $dbPerson = $this->personRepo->find($person->id);

        $dbPerson = $dbPerson->toArray();
        $this->assertModelData($person->toArray(), $dbPerson);
    }

    /**
     * @test update
     */
    public function test_update_person()
    {
        $person = Person::factory()->create();
        $fakePerson = Person::factory()->make()->toArray();

        $updatedPerson = $this->personRepo->update($fakePerson, $person->id);

        $this->assertModelData($fakePerson, $updatedPerson->toArray());
        $dbPerson = $this->personRepo->find($person->id);
        $this->assertModelData($fakePerson, $dbPerson->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_person()
    {
        $person = Person::factory()->create();

        $resp = $this->personRepo->delete($person->id);

        $this->assertTrue($resp);
        $this->assertNull(Person::find($person->id), 'Person should not exist in DB');
    }
}
