# Coverage Improvement - Generic Instructions

**Use this prompt for improving test coverage of any file to 70%+**

---

## Your Task

Improve test coverage for the specified file from its current level to **70%+**.

## Step-by-Step Process

### 1. Analyze Current Coverage

```bash
# Run tests for the file to establish baseline
docker exec familyfund php artisan test --filter=<FileNameTest>

# Check current coverage (if available)
docker exec familyfund ./vendor/bin/phpunit --filter=<FileNameTest> --coverage-text
```

### 2. Read and Understand the Code

- Read the source file completely
- Identify all public methods
- Note any dependencies (repositories, services, traits)
- Check for existing test file
- Review existing tests to understand patterns

### 3. Create or Enhance Test File

**Test file location:** `tests/Feature/<FileName>Test.php`

**Required test structure:**
```php
<?php

namespace Tests\Feature;

use App\Models\<Model>;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class <FileName>Test extends TestCase
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

    // Your tests here
}
```

### 4. Write Comprehensive Tests

**For Controllers - Standard CRUD Coverage:**

```php
// 1. Index - List display
public function test_index_displays_list()
{
    $response = $this->actingAs($this->user)
        ->get(route('<resource>.index'));

    $response->assertStatus(200);
    $response->assertViewIs('<resource>.index');
    $response->assertViewHas('<resources>');
}

// 2. Index - Multiple records
public function test_index_displays_multiple_records()
{
    Model::factory()->count(5)->create();

    $response = $this->actingAs($this->user)
        ->get(route('<resource>.index'));

    $data = $response->viewData('<resources>');
    $this->assertGreaterThanOrEqual(5, $data->count());
}

// 3. Create - Form display
public function test_create_displays_form()
{
    $response = $this->actingAs($this->user)
        ->get(route('<resource>.create'));

    $response->assertStatus(200);
    $response->assertViewIs('<resource>.create');
}

// 4. Store - Save new record
public function test_store_saves_new_record()
{
    $data = Model::factory()->make();

    $response = $this->actingAs($this->user)
        ->post(route('<resource>.store'), $data->toArray());

    $response->assertRedirect(route('<resource>.index'));
    $this->assertDatabaseHas('<table>', [
        'field' => $data->field,
    ]);
}

// 5. Store - Validation
public function test_store_validates_required_fields()
{
    $response = $this->actingAs($this->user)
        ->post(route('<resource>.store'), []);

    $response->assertSessionHasErrors(['required_field']);
}

// 6. Store - Flash message
public function test_store_shows_success_message()
{
    $data = Model::factory()->make();

    $response = $this->actingAs($this->user)
        ->post(route('<resource>.store'), $data->toArray());

    $response->assertSessionHas('flash_notification');
}

// 7. Show - Display record
public function test_show_displays_record()
{
    $record = Model::factory()->create();

    $response = $this->actingAs($this->user)
        ->get(route('<resource>.show', $record->id));

    $response->assertStatus(200);
    $response->assertViewIs('<resource>.show');
    $response->assertViewHas('<resource>');
}

// 8. Show - View data verification
public function test_show_displays_correct_data()
{
    $record = Model::factory()->create();

    $response = $this->actingAs($this->user)
        ->get(route('<resource>.show', $record->id));

    $viewData = $response->viewData('<resource>');
    $this->assertEquals($record->id, $viewData->id);
}

// 9. Show - Invalid ID
public function test_show_redirects_when_invalid_id()
{
    $response = $this->actingAs($this->user)
        ->get(route('<resource>.show', 99999));

    $response->assertRedirect(route('<resource>.index'));
}

// 10. Edit - Form display
public function test_edit_displays_form()
{
    $record = Model::factory()->create();

    $response = $this->actingAs($this->user)
        ->get(route('<resource>.edit', $record->id));

    $response->assertStatus(200);
    $response->assertViewIs('<resource>.edit');
    $response->assertViewHas('<resource>');
}

// 11. Edit - View data verification
public function test_edit_displays_correct_data()
{
    $record = Model::factory()->create();

    $response = $this->actingAs($this->user)
        ->get(route('<resource>.edit', $record->id));

    $viewData = $response->viewData('<resource>');
    $this->assertEquals($record->id, $viewData->id);
}

// 12. Edit - Invalid ID
public function test_edit_redirects_when_invalid_id()
{
    $response = $this->actingAs($this->user)
        ->get(route('<resource>.edit', 99999));

    $response->assertRedirect(route('<resource>.index'));
}

// 13. Update - Save changes
public function test_update_saves_changes()
{
    $record = Model::factory()->create();
    $updates = Model::factory()->make();

    $response = $this->actingAs($this->user)
        ->patch(route('<resource>.update', $record->id), $updates->toArray());

    $response->assertRedirect(route('<resource>.index'));
    $this->assertDatabaseHas('<table>', [
        'id' => $record->id,
        'field' => $updates->field,
    ]);
}

// 14. Update - Flash message
public function test_update_shows_success_message()
{
    $record = Model::factory()->create();
    $updates = Model::factory()->make();

    $response = $this->actingAs($this->user)
        ->patch(route('<resource>.update', $record->id), $updates->toArray());

    $response->assertSessionHas('flash_notification');
}

// 15. Update - Invalid ID
public function test_update_redirects_when_invalid_id()
{
    $updates = Model::factory()->make();

    $response = $this->actingAs($this->user)
        ->patch(route('<resource>.update', 99999), $updates->toArray());

    $response->assertRedirect(route('<resource>.index'));
}

// 16. Destroy - Delete record
public function test_destroy_deletes_record()
{
    $record = Model::factory()->create();

    $response = $this->actingAs($this->user)
        ->delete(route('<resource>.destroy', $record->id));

    $response->assertRedirect(route('<resource>.index'));
    // Use assertSoftDeleted if model uses SoftDeletes
    $this->assertDatabaseMissing('<table>', ['id' => $record->id]);
}

// 17. Destroy - Flash message
public function test_destroy_shows_success_message()
{
    $record = Model::factory()->create();

    $response = $this->actingAs($this->user)
        ->delete(route('<resource>.destroy', $record->id));

    $response->assertSessionHas('flash_notification');
}

// 18. Destroy - Invalid ID
public function test_destroy_redirects_when_invalid_id()
{
    $response = $this->actingAs($this->user)
        ->delete(route('<resource>.destroy', 99999));

    $response->assertRedirect(route('<resource>.index'));
}
```

**For PDF/Service Classes:**

```php
public function test_class_method_generates_output()
{
    $input = Factory::create();

    $instance = new ClassName($input);
    $result = $instance->method();

    $this->assertNotNull($result);
    $this->assertFileExists($result);
}

public function test_class_handles_edge_case()
{
    $input = ['minimal' => 'data'];

    $instance = new ClassName($input);
    // Should not throw exception
    $this->assertTrue(true);
}
```

### 5. Handle Common Issues

**Table name mismatches:**
- Check the model's `$table` property
- Use correct table name in assertions
- Example: `iddocuments` not `id_documents`

**Soft Deletes:**
```php
$this->assertSoftDeleted('table', ['id' => $id]);
```

**Route naming:**
- Use snake_case: `route('resource_name.action')`
- Verify in `routes/web.php`

**View syntax errors:**
- Check view files render without errors
- Fix Blade syntax before testing create/edit forms

**Validation requirements:**
- Check Request classes for rules
- Ensure factories provide valid data
- Test validation with empty data

### 6. Run and Verify Tests

```bash
# Run specific test file
docker exec familyfund php artisan test --filter=<FileName>Test

# Verify all tests pass
# Target: 100% success rate

# Check improved coverage
docker exec familyfund ./vendor/bin/phpunit --filter=<FileName>Test --coverage-text
```

### 7. Commit Changes

```bash
git add tests/Feature/<FileName>Test.php
git commit -m "Improve <FileName> coverage to 70%+

- Added X comprehensive tests (Y total, all passing)
- Coverage: [list key areas tested]
- Expected coverage: [old]% → 70%+

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

## Success Criteria

✅ **All tests must pass** (100% success rate)
✅ **Coverage must reach 70%+**
✅ **Tests must be meaningful** (not just status code checks)
✅ **Follow existing patterns** in the codebase
✅ **Commit with co-authorship**

## Quality Guidelines

**DO:**
- Test both happy and error paths
- Verify view data, not just responses
- Test validation and flash messages
- Use factories for test data
- Follow DatabaseTransactions pattern
- Clean up in tearDown()

**DON'T:**
- Mock unnecessarily (test real behavior)
- Skip edge cases
- Test only status codes
- Leave failing tests
- Create brittle tests tied to implementation details

## Testing Philosophy

**When to refactor vs test:**
- If code is hard to test, consider refactoring first
- Extract business logic from I/O operations
- Thin wrappers around external services can have lower coverage
- Document rationale for accepting <70% coverage

**For complex integration code:**
See `COVERAGE_NEXT_STEPS.md` for refactoring strategies.

## Reference Files

- Project testing patterns: `tests/Feature/IdDocumentControllerTest.php`
- PDF testing patterns: `tests/Feature/PDFTest.php`
- Testing philosophy: `COVERAGE_NEXT_STEPS.md`
- Coverage status: `COVERAGE_STATUS.md`
