<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserExt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Unit tests for UserExt model
 */
class UserExtTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_map_returns_array_with_null_key()
    {
        $result = UserExt::userMap();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(null, $result);
        $this->assertEquals('Select User', $result[null]);
    }

    public function test_user_map_includes_existing_users()
    {
        // Create a test user using User factory
        $user = User::factory()->create(['name' => 'Test User ' . uniqid()]);

        $result = UserExt::userMap();

        $this->assertArrayHasKey($user->id, $result);
        $this->assertEquals($user->name, $result[$user->id]);
    }

    public function test_user_ext_uses_users_table()
    {
        $user = new UserExt();
        $this->assertEquals('users', $user->getTable());
    }
}
