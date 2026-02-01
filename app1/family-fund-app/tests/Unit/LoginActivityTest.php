<?php

namespace Tests\Unit;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Unit tests for LoginActivity model
 */
class LoginActivityTest extends TestCase
{
    use DatabaseTransactions;

    // =========================================================================
    // Constants Tests
    // =========================================================================

    public function test_status_constants_are_defined()
    {
        $this->assertEquals('success', LoginActivity::STATUS_SUCCESS);
        $this->assertEquals('failed', LoginActivity::STATUS_FAILED);
        $this->assertEquals('two_factor_pending', LoginActivity::STATUS_TWO_FACTOR_PENDING);
        $this->assertEquals('two_factor_failed', LoginActivity::STATUS_TWO_FACTOR_FAILED);
    }

    // =========================================================================
    // Fillable Tests
    // =========================================================================

    public function test_login_activity_has_correct_fillable_attributes()
    {
        $activity = new LoginActivity();
        $fillable = $activity->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('ip_address', $fillable);
        $this->assertContains('user_agent', $fillable);
        $this->assertContains('browser', $fillable);
        $this->assertContains('browser_version', $fillable);
        $this->assertContains('platform', $fillable);
        $this->assertContains('device', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('location', $fillable);
        $this->assertContains('login_at', $fillable);
    }

    // =========================================================================
    // Cast Tests
    // =========================================================================

    public function test_login_at_is_cast_to_datetime()
    {
        $user = User::factory()->create();

        $activity = LoginActivity::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'status' => LoginActivity::STATUS_SUCCESS,
            'login_at' => '2022-06-15 10:30:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $activity->login_at);
        $this->assertEquals('2022-06-15', $activity->login_at->format('Y-m-d'));
    }

    // =========================================================================
    // Relationship Tests
    // =========================================================================

    public function test_user_relationship_returns_user()
    {
        $user = User::factory()->create();

        $activity = LoginActivity::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'status' => LoginActivity::STATUS_SUCCESS,
            'login_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $activity->user);
        $this->assertEquals($user->id, $activity->user->id);
    }

    public function test_user_relationship_returns_null_for_failed_login_without_user()
    {
        $activity = LoginActivity::create([
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'status' => LoginActivity::STATUS_FAILED,
            'login_at' => now(),
        ]);

        $this->assertNull($activity->user);
    }

    // =========================================================================
    // record() Method Tests
    // =========================================================================

    public function test_record_creates_login_activity()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)');

        $activity = LoginActivity::record($user, LoginActivity::STATUS_SUCCESS, $request);

        $this->assertNotNull($activity->id);
        $this->assertEquals($user->id, $activity->user_id);
        $this->assertEquals(LoginActivity::STATUS_SUCCESS, $activity->status);
        $this->assertNotNull($activity->login_at);
    }

    public function test_record_captures_ip_address()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');
        $request->server->set('REMOTE_ADDR', '10.20.30.40');

        $activity = LoginActivity::record($user, LoginActivity::STATUS_SUCCESS, $request);

        $this->assertEquals('10.20.30.40', $activity->ip_address);
    }

    public function test_record_captures_user_agent()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        $request->headers->set('User-Agent', $userAgent);

        $activity = LoginActivity::record($user, LoginActivity::STATUS_SUCCESS, $request);

        $this->assertEquals($userAgent, $activity->user_agent);
    }

    public function test_record_without_user()
    {
        $request = Request::create('/login', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $activity = LoginActivity::record(null, LoginActivity::STATUS_FAILED, $request);

        $this->assertNotNull($activity->id);
        $this->assertNull($activity->user_id);
        $this->assertEquals(LoginActivity::STATUS_FAILED, $activity->status);
    }

    // =========================================================================
    // Convenience Record Methods Tests
    // =========================================================================

    public function test_record_success_creates_success_activity()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');

        $activity = LoginActivity::recordSuccess($user, $request);

        $this->assertEquals(LoginActivity::STATUS_SUCCESS, $activity->status);
        $this->assertEquals($user->id, $activity->user_id);
    }

    public function test_record_failed_creates_failed_activity()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');

        $activity = LoginActivity::recordFailed($user, $request);

        $this->assertEquals(LoginActivity::STATUS_FAILED, $activity->status);
    }

    public function test_record_failed_without_user()
    {
        $request = Request::create('/login', 'POST');

        $activity = LoginActivity::recordFailed(null, $request);

        $this->assertEquals(LoginActivity::STATUS_FAILED, $activity->status);
        $this->assertNull($activity->user_id);
    }

    public function test_record_two_factor_pending()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');

        $activity = LoginActivity::recordTwoFactorPending($user, $request);

        $this->assertEquals(LoginActivity::STATUS_TWO_FACTOR_PENDING, $activity->status);
        $this->assertEquals($user->id, $activity->user_id);
    }

    public function test_record_two_factor_failed()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');

        $activity = LoginActivity::recordTwoFactorFailed($user, $request);

        $this->assertEquals(LoginActivity::STATUS_TWO_FACTOR_FAILED, $activity->status);
        $this->assertEquals($user->id, $activity->user_id);
    }

    // =========================================================================
    // getDeviceDescriptionAttribute Tests
    // =========================================================================

    public function test_device_description_with_browser_and_platform()
    {
        $activity = new LoginActivity();
        $activity->browser = 'Chrome';
        $activity->browser_version = '119.0';
        $activity->platform = 'Windows 10';
        $activity->device = 'Desktop';

        $result = $activity->device_description;

        $this->assertEquals('Chrome 119.0 on Windows 10', $result);
    }

    public function test_device_description_with_mobile_device()
    {
        $activity = new LoginActivity();
        $activity->browser = 'Safari';
        $activity->browser_version = '17.0';
        $activity->platform = 'iOS';
        $activity->device = 'iPhone';

        $result = $activity->device_description;

        $this->assertEquals('Safari 17.0 on iOS (iPhone)', $result);
    }

    public function test_device_description_with_only_browser()
    {
        $activity = new LoginActivity();
        $activity->browser = 'Firefox';
        $activity->browser_version = null;
        $activity->platform = null;
        $activity->device = null;

        $result = $activity->device_description;

        $this->assertEquals('Firefox', $result);
    }

    public function test_device_description_returns_unknown_when_empty()
    {
        $activity = new LoginActivity();
        $activity->browser = null;
        $activity->browser_version = null;
        $activity->platform = null;
        $activity->device = null;

        $result = $activity->device_description;

        $this->assertEquals('Unknown device', $result);
    }

    public function test_device_description_excludes_desktop_device()
    {
        $activity = new LoginActivity();
        $activity->browser = 'Chrome';
        $activity->browser_version = '120.0';
        $activity->platform = 'macOS';
        $activity->device = 'Desktop';

        $result = $activity->device_description;

        // Desktop is not appended
        $this->assertEquals('Chrome 120.0 on macOS', $result);
    }

    // =========================================================================
    // isSuccessful() Method Tests
    // =========================================================================

    public function test_is_successful_returns_true_for_success_status()
    {
        $activity = new LoginActivity();
        $activity->status = LoginActivity::STATUS_SUCCESS;

        $this->assertTrue($activity->isSuccessful());
    }

    public function test_is_successful_returns_false_for_failed_status()
    {
        $activity = new LoginActivity();
        $activity->status = LoginActivity::STATUS_FAILED;

        $this->assertFalse($activity->isSuccessful());
    }

    public function test_is_successful_returns_false_for_two_factor_pending()
    {
        $activity = new LoginActivity();
        $activity->status = LoginActivity::STATUS_TWO_FACTOR_PENDING;

        $this->assertFalse($activity->isSuccessful());
    }

    public function test_is_successful_returns_false_for_two_factor_failed()
    {
        $activity = new LoginActivity();
        $activity->status = LoginActivity::STATUS_TWO_FACTOR_FAILED;

        $this->assertFalse($activity->isSuccessful());
    }

    // =========================================================================
    // Scope Tests
    // =========================================================================

    public function test_scope_for_user_filters_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        LoginActivity::create([
            'user_id' => $user1->id,
            'ip_address' => '127.0.0.1',
            'status' => LoginActivity::STATUS_SUCCESS,
            'login_at' => now(),
        ]);

        LoginActivity::create([
            'user_id' => $user2->id,
            'ip_address' => '127.0.0.2',
            'status' => LoginActivity::STATUS_SUCCESS,
            'login_at' => now(),
        ]);

        $result = LoginActivity::forUser($user1)->get();

        $this->assertEquals(1, $result->count());
        $this->assertEquals($user1->id, $result->first()->user_id);
    }

    public function test_scope_recent_orders_by_login_at_desc()
    {
        $user = User::factory()->create();

        LoginActivity::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'status' => LoginActivity::STATUS_SUCCESS,
            'login_at' => now()->subDays(2),
        ]);

        LoginActivity::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.2',
            'status' => LoginActivity::STATUS_SUCCESS,
            'login_at' => now(),
        ]);

        LoginActivity::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.3',
            'status' => LoginActivity::STATUS_SUCCESS,
            'login_at' => now()->subDay(),
        ]);

        $result = LoginActivity::forUser($user)->recent(10)->get();

        // Should be ordered by most recent first
        $this->assertEquals('127.0.0.2', $result->first()->ip_address);
        $this->assertEquals('127.0.0.1', $result->last()->ip_address);
    }

    public function test_scope_recent_limits_results()
    {
        $user = User::factory()->create();

        // Create 5 activities
        for ($i = 0; $i < 5; $i++) {
            LoginActivity::create([
                'user_id' => $user->id,
                'ip_address' => "127.0.0.$i",
                'status' => LoginActivity::STATUS_SUCCESS,
                'login_at' => now()->subMinutes($i),
            ]);
        }

        $result = LoginActivity::forUser($user)->recent(3)->get();

        $this->assertEquals(3, $result->count());
    }

    // =========================================================================
    // Browser Detection Integration Tests
    // =========================================================================

    public function test_record_detects_chrome_browser()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');
        $request->headers->set(
            'User-Agent',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36'
        );

        $activity = LoginActivity::record($user, LoginActivity::STATUS_SUCCESS, $request);

        $this->assertEquals('Chrome', $activity->browser);
        $this->assertEquals('Windows', $activity->platform);
    }

    public function test_record_detects_safari_browser()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');
        $request->headers->set(
            'User-Agent',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15'
        );

        $activity = LoginActivity::record($user, LoginActivity::STATUS_SUCCESS, $request);

        $this->assertEquals('Safari', $activity->browser);
        $this->assertEquals('OS X', $activity->platform);
    }

    public function test_record_detects_firefox_browser()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');
        $request->headers->set(
            'User-Agent',
            'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/119.0'
        );

        $activity = LoginActivity::record($user, LoginActivity::STATUS_SUCCESS, $request);

        $this->assertEquals('Firefox', $activity->browser);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    public function test_record_handles_empty_user_agent()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');
        $request->headers->set('User-Agent', '');

        $activity = LoginActivity::record($user, LoginActivity::STATUS_SUCCESS, $request);

        $this->assertNotNull($activity->id);
        // Browser detection should handle gracefully
    }

    public function test_multiple_activities_for_same_user()
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');

        // Create multiple activities
        LoginActivity::recordSuccess($user, $request);
        LoginActivity::recordFailed($user, $request);
        LoginActivity::recordSuccess($user, $request);

        $activities = LoginActivity::forUser($user)->get();

        $this->assertEquals(3, $activities->count());
    }
}
