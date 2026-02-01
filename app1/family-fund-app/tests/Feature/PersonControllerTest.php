<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for PersonController
 * Target: Push from 50% to 70%+
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

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('people.create'));

        $response->assertStatus(200);
        $response->assertViewIs('people.create');
    }

    public function test_edit_displays_form()
    {
        $person = Person::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('people.edit', $person->id));

        $response->assertStatus(200);
        $response->assertViewIs('people.edit');
        $response->assertViewHas('person');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('people.edit', 99999));

        $response->assertRedirect(route('people.index'));
        $response->assertSessionHas('flash_notification');
    }

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

    // ==================== Additional Tests for 70% Coverage ====================

    public function test_index_displays_multiple_people()
    {
        \App\Models\Person::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get(route('people.index'));

        $response->assertStatus(200);
        $people = $response->viewData('people');
        $this->assertGreaterThanOrEqual(5, $people->count());
    }

    public function test_show_displays_correct_person_data()
    {
        $person = \App\Models\Person::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('people.show', $person->id));

        $viewPerson = $response->viewData('person');
        $this->assertEquals($person->id, $viewPerson->id);
        $this->assertEquals($person->first_name, $viewPerson->first_name);
    }

    public function test_edit_displays_correct_person_data()
    {
        $person = \App\Models\Person::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('people.edit', $person->id));

        $viewPerson = $response->viewData('person');
        $this->assertEquals($person->id, $viewPerson->id);
    }

    public function test_create_displays_view()
    {
        $response = $this->actingAs($this->user)
            ->get(route('people.create'));

        $response->assertStatus(200);
        $response->assertViewIs('people.create');
    }

    public function test_show_has_person_data_in_view()
    {
        $person = \App\Models\Person::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('people.show', $person->id));

        $response->assertSee($person->first_name);
        $response->assertSee($person->last_name);
    }

    public function test_edit_has_person_data_in_view()
    {
        $person = \App\Models\Person::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('people.edit', $person->id));

        $response->assertSee($person->first_name);
    }
}
