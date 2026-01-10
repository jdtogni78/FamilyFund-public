<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\User;

use PHPUnit\Framework\Attributes\Test;
class UserApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_create_user()
    {
        $user = User::factory()->make();
        $userData = array_merge($user->toArray(), ['password' => 'password123']);

        $this->response = $this->json(
            'POST',
            '/api/users', $userData
        );

        $this->assertApiResponse($user->toArray());
    }

    #[Test]
    public function test_read_user()
    {
        $user = User::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/users/'.$user->id
        );

        $this->assertApiResponse($user->toArray());
    }

    #[Test]
    public function test_update_user()
    {
        $user = User::factory()->create();
        $editedUser = User::factory()->make();
        $editedUserData = array_merge($editedUser->toArray(), ['password' => 'password123']);

        $this->response = $this->json(
            'PUT',
            '/api/users/'.$user->id,
            $editedUserData
        );

        $this->assertApiResponse($editedUser->toArray());
    }

    #[Test]
    public function test_delete_user()
    {
        $user = User::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/users/'.$user->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/users/'.$user->id
        );

        $this->response->assertStatus(404);
    }
}
