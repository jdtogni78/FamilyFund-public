<?php

namespace Tests\Feature;

use App\Models\IdDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for IdDocumentController
 * Target: Push from 50% to 70%+
 */
class IdDocumentControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_index_displays_id_documents_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('id_documents.index'));

        $response->assertStatus(200);
        $response->assertViewIs('id_documents.index');
        $response->assertViewHas('idDocuments');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('id_documents.create'));

        $response->assertStatus(200);
        $response->assertViewIs('id_documents.create');
    }

    public function test_store_saves_new_id_document()
    {
        $idDocument = IdDocument::factory()->make();

        $response = $this->actingAs($this->user)
            ->post(route('id_documents.store'), $idDocument->toArray());

        $response->assertRedirect(route('id_documents.index'));
        $this->assertDatabaseHas('iddocuments', [
            'person_id' => $idDocument->person_id,
            'number' => $idDocument->number,
        ]);
    }

    public function test_show_displays_id_document()
    {
        $idDocument = IdDocument::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('id_documents.show', $idDocument->id));

        $response->assertStatus(200);
        $response->assertViewIs('id_documents.show');
        $response->assertViewHas('idDocument');
    }

    public function test_show_redirects_when_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('id_documents.show', 99999));

        $response->assertRedirect(route('id_documents.index'));
    }

    public function test_edit_displays_form()
    {
        $idDocument = IdDocument::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('id_documents.edit', $idDocument->id));

        $response->assertStatus(200);
        $response->assertViewIs('id_documents.edit');
        $response->assertViewHas('idDocument');
    }

    public function test_edit_redirects_when_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('id_documents.edit', 99999));

        $response->assertRedirect(route('id_documents.index'));
    }

    public function test_update_saves_changes()
    {
        $idDocument = IdDocument::factory()->create();
        $updatedData = IdDocument::factory()->make();

        $response = $this->actingAs($this->user)
            ->patch(route('id_documents.update', $idDocument->id), $updatedData->toArray());

        $response->assertRedirect(route('id_documents.index'));
        $this->assertDatabaseHas('iddocuments', [
            'id' => $idDocument->id,
            'number' => $updatedData->number,
        ]);
    }

    public function test_destroy_deletes_id_document()
    {
        $idDocument = IdDocument::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('id_documents.destroy', $idDocument->id));

        $response->assertRedirect(route('id_documents.index'));
        $this->assertSoftDeleted('iddocuments', ['id' => $idDocument->id]);
    }

    public function test_destroy_redirects_when_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('id_documents.destroy', 99999));

        $response->assertRedirect(route('id_documents.index'));
    }

    // ==================== Additional Tests for 70% Coverage ====================

    public function test_index_displays_multiple_id_documents()
    {
        IdDocument::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('id_documents.index'));

        $response->assertStatus(200);
        $idDocuments = $response->viewData('idDocuments');
        $this->assertGreaterThanOrEqual(3, $idDocuments->count());
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->post(route('id_documents.store'), []);

        $response->assertSessionHasErrors(['person_id', 'number']);
    }

    public function test_store_shows_success_flash_message()
    {
        $idDocument = IdDocument::factory()->make();

        $response = $this->actingAs($this->user)
            ->post(route('id_documents.store'), $idDocument->toArray());

        $response->assertSessionHas('flash_notification');
    }

    public function test_show_displays_correct_id_document_data()
    {
        $idDocument = IdDocument::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('id_documents.show', $idDocument->id));

        $viewIdDocument = $response->viewData('idDocument');
        $this->assertEquals($idDocument->id, $viewIdDocument->id);
        $this->assertEquals($idDocument->number, $viewIdDocument->number);
    }

    public function test_edit_displays_correct_id_document_data()
    {
        $idDocument = IdDocument::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('id_documents.edit', $idDocument->id));

        $viewIdDocument = $response->viewData('idDocument');
        $this->assertEquals($idDocument->id, $viewIdDocument->id);
    }

    public function test_update_redirects_when_invalid_id()
    {
        $updatedData = IdDocument::factory()->make();

        $response = $this->actingAs($this->user)
            ->patch(route('id_documents.update', 99999), $updatedData->toArray());

        $response->assertRedirect(route('id_documents.index'));
    }

    public function test_update_shows_success_flash_message()
    {
        $idDocument = IdDocument::factory()->create();
        $updatedData = IdDocument::factory()->make();

        $response = $this->actingAs($this->user)
            ->patch(route('id_documents.update', $idDocument->id), $updatedData->toArray());

        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_shows_success_flash_message()
    {
        $idDocument = IdDocument::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('id_documents.destroy', $idDocument->id));

        $response->assertSessionHas('flash_notification');
    }
}
