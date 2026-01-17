<?php

namespace Tests\Feature;

use App\Models\Phone;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PhoneControllerTest extends TestCase
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

    public function test_destroy_deletes_phone()
    {
        $phone = Phone::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('phones.destroy', $phone->id));

        $response->assertRedirect(route('phones.index'));
        $this->assertDatabaseMissing('phones', ['id' => $phone->id]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('phones.destroy', 99999));

        $response->assertRedirect(route('phones.index'));
        $response->assertSessionHas('flash_notification');
    }
}
