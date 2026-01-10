<?php

namespace Tests\Unit;

use App\Models\Person;
use App\Models\PersonExt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Unit tests for PersonExt model
 */
class PersonExtTest extends TestCase
{
    use DatabaseTransactions;

    public function test_legal_guardians_map_returns_array_with_select_option()
    {
        $result = PersonExt::legalGuardiansMap();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(null, $result);
        $this->assertEquals('Select one', $result[null]);
    }

    public function test_legal_guardians_map_includes_persons_without_guardian()
    {
        // Create a person without a legal guardian (i.e., a legal guardian themselves)
        $person = Person::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Guardian',
            'legal_guardian_id' => null,
        ]);

        $result = PersonExt::legalGuardiansMap();

        $this->assertArrayHasKey($person->id, $result);
        $this->assertEquals('Test Guardian', $result[$person->id]);
    }

    public function test_legal_guardians_map_excludes_persons_with_guardian()
    {
        // Create a legal guardian
        $guardian = Person::factory()->create([
            'first_name' => 'Parent',
            'last_name' => 'User',
            'legal_guardian_id' => null,
        ]);

        // Create a person with a legal guardian (i.e., a dependent)
        $dependent = Person::factory()->create([
            'first_name' => 'Child',
            'last_name' => 'User',
            'legal_guardian_id' => $guardian->id,
        ]);

        $result = PersonExt::legalGuardiansMap();

        // Guardian should be in the map
        $this->assertArrayHasKey($guardian->id, $result);
        // Dependent should NOT be in the map
        $this->assertArrayNotHasKey($dependent->id, $result);
    }
}
