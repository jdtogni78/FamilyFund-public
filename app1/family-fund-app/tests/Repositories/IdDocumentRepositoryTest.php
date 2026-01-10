<?php namespace Tests\Repositories;

use App\Models\IdDocument;
use App\Repositories\IdDocumentRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

use PHPUnit\Framework\Attributes\Test;
class IdDocumentRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var IdDocumentRepository
     */
    protected $idDocumentRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->idDocumentRepo = \App::make(IdDocumentRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_id_document()
    {
        $idDocument = IdDocument::factory()->make()->toArray();

        $createdIdDocument = $this->idDocumentRepo->create($idDocument);

        $createdIdDocument = $createdIdDocument->toArray();
        $this->assertArrayHasKey('id', $createdIdDocument);
        $this->assertNotNull($createdIdDocument['id'], 'Created IdDocument must have id specified');
        $this->assertNotNull(IdDocument::find($createdIdDocument['id']), 'IdDocument with given id must be in DB');
        $this->assertModelData($idDocument, $createdIdDocument);
    }

    /**
     * @test read
     */
    public function test_read_id_document()
    {
        $idDocument = IdDocument::factory()->create();

        $dbIdDocument = $this->idDocumentRepo->find($idDocument->id);

        $dbIdDocument = $dbIdDocument->toArray();
        $this->assertModelData($idDocument->toArray(), $dbIdDocument);
    }

    /**
     * @test update
     */
    public function test_update_id_document()
    {
        $idDocument = IdDocument::factory()->create();
        $fakeIdDocument = IdDocument::factory()->make()->toArray();

        $updatedIdDocument = $this->idDocumentRepo->update($fakeIdDocument, $idDocument->id);

        $this->assertModelData($fakeIdDocument, $updatedIdDocument->toArray());
        $dbIdDocument = $this->idDocumentRepo->find($idDocument->id);
        $this->assertModelData($fakeIdDocument, $dbIdDocument->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_id_document()
    {
        $idDocument = IdDocument::factory()->create();

        $resp = $this->idDocumentRepo->delete($idDocument->id);

        $this->assertTrue($resp);
        $this->assertNull(IdDocument::find($idDocument->id), 'IdDocument should not exist in DB');
    }
}
