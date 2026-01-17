<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\IdDocument;
use App\Models\Person;
use App\Models\Phone;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for PersonController
 * Target: Get coverage from 23% to 50%+
 */
class PersonControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Person $person;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->person = Person::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Index Tests ====================

    public function test_index_displays_people()
    {
        $response = $this->actingAs($this->user)->get(route('people.index'));

        $response->assertStatus(200);
        $response->assertViewIs('people.index');
        $response->assertViewHas('people');
    }

    public function test_index_shows_existing_people()
    {
        Person::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('people.index'));

        $response->assertStatus(200);
        $people = $response->viewData('people');
        $this->assertGreaterThanOrEqual(4, $people->count()); // 3 new + 1 from setUp
    }

    // ==================== Create Tests ====================

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)->get(route('people.create'));

        $response->assertStatus(200);
        $response->assertViewIs('people.create');
    }

    // ==================== Store Tests ====================

    public function test_store_creates_person()
    {
        $personData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('people.store'), $personData);

        $response->assertRedirect(route('people.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('people', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    public function test_store_creates_person_with_addresses()
    {
        $personData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'addresses' => [
                [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                    'state' => 'IL',
                    'zip' => '62701',
                    'is_primary' => true,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->post(route('people.store'), $personData);

        $response->assertRedirect(route('people.index'));

        $person = Person::where('first_name', 'Jane')->first();
        $this->assertNotNull($person);
        $this->assertEquals(1, $person->addresses()->count());
    }

    public function test_store_creates_person_with_phones()
    {
        $personData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phones' => [
                [
                    'number' => '555-1234',
                    'type' => 'mobile',
                    'is_primary' => true,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->post(route('people.store'), $personData);

        $response->assertRedirect(route('people.index'));

        $person = Person::where('first_name', 'Jane')->first();
        $this->assertNotNull($person);
        $this->assertEquals(1, $person->phones()->count());
    }

    public function test_store_creates_person_with_id_documents()
    {
        $personData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'id_documents' => [
                [
                    'type' => 'passport',
                    'number' => 'AB123456',
                    'is_primary' => true,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->post(route('people.store'), $personData);

        $response->assertRedirect(route('people.index'));

        $person = Person::where('first_name', 'Jane')->first();
        $this->assertNotNull($person);
        $this->assertEquals(1, $person->idDocuments()->count());
    }

    // ==================== Show Tests ====================

    public function test_show_displays_person()
    {
        $response = $this->actingAs($this->user)
            ->get(route('people.show', $this->person->id));

        $response->assertStatus(200);
        $response->assertViewIs('people.show');
        $response->assertViewHas('person');
        $response->assertSee($this->person->first_name);
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('people.show', 99999));

        $response->assertRedirect(route('people.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Edit Tests ====================

    public function test_edit_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('people.edit', $this->person->id));

        $response->assertStatus(200);
        $response->assertViewIs('people.edit');
        $response->assertViewHas('person');
        $response->assertViewHas('isEdit', true);
        $response->assertViewHas('legalGuardians');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('people.edit', 99999));

        $response->assertRedirect(route('people.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Update Tests ====================

    public function test_update_modifies_person()
    {
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('people.update', $this->person->id), $updateData);

        $response->assertRedirect(route('people.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('people', [
            'id' => $this->person->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    }

    public function test_update_redirects_for_invalid_id()
    {
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('people.update', 99999), $updateData);

        $response->assertRedirect(route('people.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_update_with_new_address()
    {
        $updateData = [
            'first_name' => $this->person->first_name,
            'last_name' => $this->person->last_name,
            'addresses' => [
                [
                    'street' => '456 Oak Ave',
                    'city' => 'Chicago',
                    'state' => 'IL',
                    'zip' => '60601',
                    'is_primary' => true,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->put(route('people.update', $this->person->id), $updateData);

        $response->assertRedirect(route('people.index'));

        $this->person->refresh();
        $this->assertEquals(1, $this->person->addresses()->count());
    }

    public function test_update_modifies_existing_address()
    {
        // Create existing address
        $address = Address::factory()->create([
            'person_id' => $this->person->id,
            'street' => '123 Old St',
            'city' => 'OldCity',
        ]);

        $updateData = [
            'first_name' => $this->person->first_name,
            'last_name' => $this->person->last_name,
            'addresses' => [
                [
                    'id' => $address->id,
                    'street' => '456 New St',
                    'city' => 'NewCity',
                    'state' => 'IL',
                    'zip' => '60601',
                    'is_primary' => true,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->put(route('people.update', $this->person->id), $updateData);

        $response->assertRedirect(route('people.index'));

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'street' => '456 New St',
            'city' => 'NewCity',
        ]);
    }

    public function test_update_deletes_removed_address()
    {
        // Create two addresses
        $address1 = Address::factory()->create(['person_id' => $this->person->id]);
        $address2 = Address::factory()->create(['person_id' => $this->person->id]);

        $updateData = [
            'first_name' => $this->person->first_name,
            'last_name' => $this->person->last_name,
            'addresses' => [
                [
                    'id' => $address1->id,
                    'street' => $address1->street,
                    'city' => $address1->city,
                    'state' => $address1->state,
                    'zip' => $address1->zip,
                    'is_primary' => true,
                ]
                // address2 is not included, so it should be deleted
            ]
        ];

        $response = $this->actingAs($this->user)
            ->put(route('people.update', $this->person->id), $updateData);

        $response->assertRedirect(route('people.index'));

        $this->assertDatabaseHas('addresses', ['id' => $address1->id]);
        $this->assertDatabaseMissing('addresses', ['id' => $address2->id]);
    }

    public function test_update_with_phones()
    {
        $phone = Phone::factory()->create(['person_id' => $this->person->id]);

        $updateData = [
            'first_name' => $this->person->first_name,
            'last_name' => $this->person->last_name,
            'phones' => [
                [
                    'id' => $phone->id,
                    'number' => '555-9999',
                    'type' => 'mobile',
                    'is_primary' => true,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->put(route('people.update', $this->person->id), $updateData);

        $response->assertRedirect(route('people.index'));

        $this->assertDatabaseHas('phones', [
            'id' => $phone->id,
            'number' => '555-9999',
        ]);
    }

    public function test_update_with_id_documents()
    {
        $idDoc = IdDocument::factory()->create(['person_id' => $this->person->id]);

        $updateData = [
            'first_name' => $this->person->first_name,
            'last_name' => $this->person->last_name,
            'id_documents' => [
                [
                    'id' => $idDoc->id,
                    'type' => 'drivers_license',
                    'number' => 'DL987654',
                    'is_primary' => true,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->put(route('people.update', $this->person->id), $updateData);

        $response->assertRedirect(route('people.index'));

        $this->assertDatabaseHas('id_documents', [
            'id' => $idDoc->id,
            'number' => 'DL987654',
        ]);
    }

    // ==================== Destroy Tests ====================

    public function test_destroy_deletes_person()
    {
        $personToDelete = Person::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('people.destroy', $personToDelete->id));

        $response->assertRedirect(route('people.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseMissing('people', ['id' => $personToDelete->id]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('people.destroy', 99999));

        $response->assertRedirect(route('people.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Negative Tests ====================

    public function test_update_sets_is_primary_false_when_null()
    {
        $updateData = [
            'first_name' => $this->person->first_name,
            'last_name' => $this->person->last_name,
            'addresses' => [
                [
                    'street' => '456 Test St',
                    'city' => 'TestCity',
                    'state' => 'IL',
                    'zip' => '60601',
                    // is_primary not set, should default to false
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->put(route('people.update', $this->person->id), $updateData);

        $response->assertRedirect(route('people.index'));

        $this->person->refresh();
        $address = $this->person->addresses()->first();
        $this->assertFalse((bool)$address->is_primary);
    }

    public function test_update_with_no_sub_entities()
    {
        // Create person with sub-entities
        Address::factory()->create(['person_id' => $this->person->id]);
        Phone::factory()->create(['person_id' => $this->person->id]);

        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Person',
            // No addresses, phones, or id_documents - they should be deleted
        ];

        $response = $this->actingAs($this->user)
            ->put(route('people.update', $this->person->id), $updateData);

        $response->assertRedirect(route('people.index'));

        $this->person->refresh();
        // Sub-entities should be deleted when not included in update
        $this->assertEquals(0, $this->person->addresses()->count());
        $this->assertEquals(0, $this->person->phones()->count());
    }
}
