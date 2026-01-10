<?php namespace Tests\Repositories;

use App\Models\Phone;
use App\Repositories\PhoneRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

use PHPUnit\Framework\Attributes\Test;
class PhoneRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var PhoneRepository
     */
    protected $phoneRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->phoneRepo = \App::make(PhoneRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_phone()
    {
        $phone = Phone::factory()->make()->toArray();

        $createdPhone = $this->phoneRepo->create($phone);

        $createdPhone = $createdPhone->toArray();
        $this->assertArrayHasKey('id', $createdPhone);
        $this->assertNotNull($createdPhone['id'], 'Created Phone must have id specified');
        $this->assertNotNull(Phone::find($createdPhone['id']), 'Phone with given id must be in DB');
        $this->assertModelData($phone, $createdPhone);
    }

    /**
     * @test read
     */
    public function test_read_phone()
    {
        $phone = Phone::factory()->create();

        $dbPhone = $this->phoneRepo->find($phone->id);

        $dbPhone = $dbPhone->toArray();
        $this->assertModelData($phone->toArray(), $dbPhone);
    }

    /**
     * @test update
     */
    public function test_update_phone()
    {
        $phone = Phone::factory()->create();
        $fakePhone = Phone::factory()->make()->toArray();

        $updatedPhone = $this->phoneRepo->update($fakePhone, $phone->id);

        $this->assertModelData($fakePhone, $updatedPhone->toArray());
        $dbPhone = $this->phoneRepo->find($phone->id);
        $this->assertModelData($fakePhone, $dbPhone->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_phone()
    {
        $phone = Phone::factory()->create();

        $resp = $this->phoneRepo->delete($phone->id);

        $this->assertTrue($resp);
        $this->assertNull(Phone::find($phone->id), 'Phone should not exist in DB');
    }
}
