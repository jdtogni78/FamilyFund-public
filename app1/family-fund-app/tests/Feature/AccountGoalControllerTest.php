<?php

namespace Tests\Feature;

use App\Models\AccountGoal;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for AccountGoalController
 * Target: Push from 36% to 70%+
 */
class AccountGoalControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected DataFactory $df;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();
        $this->user = $this->df->user;
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    /**
     * Helper to create a goal for testing
     */
    protected function createGoal(): Goal
    {
        return Goal::factory()->create([
            'name' => 'Test Goal',
            'description' => 'Test Description',
            'start_dt' => '2024-01-01',
            'end_dt' => '2024-12-31',
            'target_type' => 'TOTAL',
            'target_amount' => 10000,
            'target_pct' => 0.04,
        ]);
    }

    // ==================== Index Tests ====================

    public function test_index_displays_account_goals_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountGoals.index'));

        $response->assertStatus(200);
        $response->assertViewIs('account_goals.index');
        $response->assertViewHas('accountGoals');
    }

    public function test_index_with_existing_account_goals()
    {
        $goal = $this->createGoal();
        AccountGoal::create([
            'account_id' => $this->df->userAccount->id,
            'goal_id' => $goal->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accountGoals.index'));

        $response->assertStatus(200);
        $response->assertViewIs('account_goals.index');
    }

    // ==================== Create Tests ====================

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountGoals.create'));

        $response->assertStatus(200);
        $response->assertViewIs('account_goals.create');
        $response->assertViewHas('api');
    }

    // ==================== Store Tests ====================

    public function test_store_creates_account_goal()
    {
        $goal = $this->createGoal();

        $response = $this->actingAs($this->user)
            ->post(route('accountGoals.store'), [
                'account_id' => $this->df->userAccount->id,
                'goal_id' => $goal->id,
            ]);

        $response->assertRedirect(route('accountGoals.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('account_goals', [
            'account_id' => $this->df->userAccount->id,
            'goal_id' => $goal->id,
        ]);
    }

    public function test_store_validates_required_account_id()
    {
        $goal = $this->createGoal();

        $response = $this->actingAs($this->user)
            ->post(route('accountGoals.store'), [
                'goal_id' => $goal->id,
            ]);

        $response->assertSessionHasErrors(['account_id']);
    }

    public function test_store_validates_required_goal_id()
    {
        $response = $this->actingAs($this->user)
            ->post(route('accountGoals.store'), [
                'account_id' => $this->df->userAccount->id,
            ]);

        $response->assertSessionHasErrors(['goal_id']);
    }

    public function test_store_validates_both_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->post(route('accountGoals.store'), []);

        $response->assertSessionHasErrors(['account_id', 'goal_id']);
    }

    // ==================== Show Tests ====================

    public function test_show_displays_account_goal()
    {
        $goal = $this->createGoal();
        $accountGoal = AccountGoal::create([
            'account_id' => $this->df->userAccount->id,
            'goal_id' => $goal->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accountGoals.show', $accountGoal->id));

        $response->assertStatus(200);
        $response->assertViewIs('account_goals.show');
        $response->assertViewHas('accountGoal');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountGoals.show', 99999));

        $response->assertRedirect(route('accountGoals.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Edit Tests ====================

    public function test_edit_displays_form()
    {
        $goal = $this->createGoal();
        $accountGoal = AccountGoal::create([
            'account_id' => $this->df->userAccount->id,
            'goal_id' => $goal->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accountGoals.edit', $accountGoal->id));

        $response->assertStatus(200);
        $response->assertViewIs('account_goals.edit');
        $response->assertViewHas('accountGoal');
        $response->assertViewHas('api');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountGoals.edit', 99999));

        $response->assertRedirect(route('accountGoals.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Update Tests ====================

    public function test_update_modifies_account_goal()
    {
        $goal1 = $this->createGoal();
        $goal2 = Goal::factory()->create([
            'name' => 'Second Goal',
            'description' => 'Second Description',
            'start_dt' => '2024-01-01',
            'end_dt' => '2024-12-31',
            'target_type' => 'TOTAL',
            'target_amount' => 20000,
            'target_pct' => 0.05,
        ]);

        $accountGoal = AccountGoal::create([
            'account_id' => $this->df->userAccount->id,
            'goal_id' => $goal1->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('accountGoals.update', $accountGoal->id), [
                'account_id' => $this->df->userAccount->id,
                'goal_id' => $goal2->id,
            ]);

        $response->assertRedirect(route('accountGoals.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('account_goals', [
            'id' => $accountGoal->id,
            'goal_id' => $goal2->id,
        ]);
    }

    public function test_update_redirects_for_invalid_id()
    {
        $goal = $this->createGoal();

        $response = $this->actingAs($this->user)
            ->put(route('accountGoals.update', 99999), [
                'account_id' => $this->df->userAccount->id,
                'goal_id' => $goal->id,
            ]);

        $response->assertRedirect(route('accountGoals.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_update_validates_required_fields()
    {
        $goal = $this->createGoal();
        $accountGoal = AccountGoal::create([
            'account_id' => $this->df->userAccount->id,
            'goal_id' => $goal->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('accountGoals.update', $accountGoal->id), []);

        $response->assertSessionHasErrors(['account_id', 'goal_id']);
    }

    // ==================== Destroy Tests ====================

    public function test_destroy_deletes_account_goal()
    {
        $goal = $this->createGoal();
        $accountGoal = AccountGoal::create([
            'account_id' => $this->df->userAccount->id,
            'goal_id' => $goal->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('accountGoals.destroy', $accountGoal->id));

        $response->assertRedirect(route('accountGoals.index'));
        $response->assertSessionHas('flash_notification');

        // Verify soft delete
        $this->assertSoftDeleted('account_goals', [
            'id' => $accountGoal->id,
        ]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('accountGoals.destroy', 99999));

        $response->assertRedirect(route('accountGoals.index'));
        $response->assertSessionHas('flash_notification');
    }
}
