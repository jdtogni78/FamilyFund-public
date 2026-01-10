<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\IdDocument;

use PHPUnit\Framework\Attributes\Test;
class IdDocumentApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_create_id_document()
    {
        $idDocument = IdDocument::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/id_documents', $idDocument
        );

        $this->assertApiResponse($idDocument);
    }

    #[Test]
    public function test_read_id_document()
    {
        $idDocument = IdDocument::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/id_documents/'.$idDocument->id
        );

        $this->assertApiResponse($idDocument->toArray());
    }

    #[Test]
    public function test_update_id_document()
    {
        $idDocument = IdDocument::factory()->create();
        $editedIdDocument = IdDocument::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/id_documents/'.$idDocument->id,
            $editedIdDocument
        );

        $this->assertApiResponse($editedIdDocument);
    }

    #[Test]
    public function test_delete_id_document()
    {
        $idDocument = IdDocument::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/id_documents/'.$idDocument->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/id_documents/'.$idDocument->id
        );

        $this->response->assertStatus(404);
    }
}
