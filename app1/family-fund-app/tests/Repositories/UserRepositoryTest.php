<?php namespace Tests\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class UserRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var UserRepository
     */
    protected $userRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->userRepo = \App::make(UserRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_user()
    {
        $user = User::factory()->make();
        $userData = array_merge($user->toArray(), ['password' => $user->password]);

        $createdUser = $this->userRepo->create($userData);

        $createdUser = $createdUser->toArray();
        $this->assertArrayHasKey('id', $createdUser);
        $this->assertNotNull($createdUser['id'], 'Created User must have id specified');
        $this->assertNotNull(User::find($createdUser['id']), 'User with given id must be in DB');
        // Exclude hidden fields (password, remember_token) from comparison
        $hiddenFields = ['password', 'remember_token'];
        $this->assertModelData($user->toArray(), $createdUser, $hiddenFields);
    }

    /**
     * @test read
     */
    public function test_read_user()
    {
        $user = User::factory()->create();

        $dbUser = $this->userRepo->find($user->id);

        $dbUser = $dbUser->toArray();
        // Exclude hidden fields (password, remember_token) from comparison
        $hiddenFields = ['password', 'remember_token'];
        $this->assertModelData($user->toArray(), $dbUser, $hiddenFields);
    }

    /**
     * @test update
     */
    public function test_update_user()
    {
        $user = User::factory()->create();
        $fakeUserModel = User::factory()->make();
        $fakeUser = array_merge($fakeUserModel->toArray(), ['password' => $fakeUserModel->password]);

        $updatedUser = $this->userRepo->update($fakeUser, $user->id);

        // Exclude hidden fields (password, remember_token) from comparison
        $hiddenFields = ['password', 'remember_token'];
        $this->assertModelData($fakeUserModel->toArray(), $updatedUser->toArray(), $hiddenFields);
        $dbUser = $this->userRepo->find($user->id);
        $this->assertModelData($fakeUserModel->toArray(), $dbUser->toArray(), $hiddenFields);
    }

    /**
     * @test delete
     */
    public function test_delete_user()
    {
        $user = User::factory()->create();

        $resp = $this->userRepo->delete($user->id);

        $this->assertTrue($resp);
        $this->assertNull(User::find($user->id), 'User should not exist in DB');
    }
}
