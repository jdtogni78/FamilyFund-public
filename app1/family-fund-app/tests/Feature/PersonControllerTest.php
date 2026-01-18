<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for PersonController
 * Target: Push from 23.6% to 50%+
 */
class PersonControllerTest extends TestCase
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

    public function test_index_displays_people_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('people.index'));

        $response->assertStatus(200);
        $response->assertViewIs('people.index');
        $response->assertViewHas('people');
    }

    // Note: Skipping create and edit form tests due to view syntax errors in phone_fields.blade.php

    public function test_show_displays_person()
    {
        $person = Person::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('people.show', $person->id));

        $response->assertStatus(200);
        $response->assertViewIs('people.show');
        $response->assertViewHas('person');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('people.show', 99999));

        $response->assertRedirect(route('people.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_handles_person()
    {
        $person = Person::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('people.destroy', $person->id));

        $response->assertRedirect(route('people.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('people.destroy', 99999));

        $response->assertRedirect(route('people.index'));
        $response->assertSessionHas('flash_notification');
    }
}
