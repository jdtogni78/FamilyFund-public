# Test Fix - Generic Instructions

**Use this prompt for fixing any failing test to pass**

---

## Your Task

Fix the specified failing test to make it pass reliably.

## Step-by-Step Process

### 1. Identify the Failure

```bash
# Run the specific failing test
docker exec familyfund php artisan test --filter=<TestClassName>

# Get detailed error output
docker exec familyfund php artisan test --filter=<TestClassName> --stop-on-failure
```

### 2. Analyze the Error

Common error patterns and their typical causes:

**ErrorException: Undefined array key**
- Missing data in test setup
- Missing factory relationships
- Missing configuration/constants
- Check model relationships and ensure test creates all required data

**RouteNotFoundException**
- Route not defined in routes/web.php or routes/api.php
- Route name mismatch (check controller vs route file)
- Missing route group or middleware

**Failed asserting that X matches expected Y**
- Business logic bug in source code
- Test expectations incorrect/outdated
- Data setup incomplete or incorrect

**SMTP/Email errors**
- Use Mail::fake() at start of test
- Tests should not send real emails
- Check if test is missing mail mocking

**Database constraint violations**
- Missing required relationships in factory
- Foreign key violations
- Use DatabaseTransactions trait

### 3. Read Relevant Code

- Read the test file completely
- Read the source file being tested (controller/model/service)
- Check related factories for data setup patterns
- Review route definitions if RouteNotFoundException
- Look at similar passing tests for patterns

### 4. Fix the Test

**Priority Order:**
1. **Fix test setup** (most common issue)
   - Add missing factory relationships
   - Mock external dependencies (Mail, Queue, Http)
   - Create required database records

2. **Fix source code bugs** (if test expectations are correct)
   - Only if test is correctly written but code is broken
   - Ensure fix doesn't break other tests

3. **Update test expectations** (least common, only if specs changed)
   - Only if source code is correct but test is outdated

**Common Fixes:**

```php
// Add Mail::fake() for email tests
use Illuminate\Support\Facades\Mail;
Mail::fake();

// Add missing relationships in test setup
$fund = Fund::factory()->create();
$account = Account::factory()->create(['fund_id' => $fund->id]);
$portfolio = Portfolio::factory()->create();

// Fix route names (check routes/web.php for correct name)
// If route is: Route::resource('scheduled-jobs', ScheduledJobControllerExt::class);
// Then use: route('scheduled-jobs.index') NOT route('scheduledJobs.index')

// Add missing config/constants
config(['app.some_setting' => 'value']);

// Mock external HTTP calls
use Illuminate\Support\Facades\Http;
Http::fake([
    'api.example.com/*' => Http::response(['data' => 'test'], 200)
]);
```

### 5. Verify the Fix

```bash
# Run the specific test
docker exec familyfund php artisan test --filter=<TestClassName>

# Ensure it passes consistently (run 3 times)
docker exec familyfund php artisan test --filter=<TestClassName>
docker exec familyfund php artisan test --filter=<TestClassName>
docker exec familyfund php artisan test --filter=<TestClassName>

# Run all tests to ensure no regressions
docker exec familyfund php artisan test --testsuite=Feature
```

### 6. Commit Changes

```bash
git add tests/Feature/<TestFile>.php [app/...]
git commit -m "Fix failing test: <TestClassName>

- Issue: <brief description of error>
- Fix: <what was changed>
- Result: Test now passes consistently

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>"
```

## Common Issues and Solutions

### Issue: Undefined array key in model

**Symptom:** `ErrorException: Undefined array key "XYZ"`

**Solution:**
1. Find where the key is accessed in source code
2. Add default value or check if key exists
3. OR ensure test creates data with that key

```php
// Source code fix:
$value = $array['key'] ?? 'default';
// OR
if (isset($array['key'])) {
    $value = $array['key'];
}

// Test fix: ensure key exists in test data
$data = ['key' => 'value', /* ... */];
```

### Issue: Route not defined

**Symptom:** `RouteNotFoundException: Route [name] not defined`

**Solution:**
1. Check routes/web.php for actual route name
2. Update test to use correct route name
3. If route is missing, add it (only if intentional)

```php
// Check what routes exist:
// Look at routes/web.php

// If route is: Route::resource('items', ItemController::class);
// Then use: route('items.index'), route('items.create'), etc.
```

### Issue: Mail/Queue not mocked

**Symptom:** SMTP authentication errors, Queue connection errors

**Solution:**
```php
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

public function test_something()
{
    Mail::fake();
    Queue::fake();

    // ... rest of test

    // Optional: assert emails were sent
    Mail::assertSent(SomeMailClass::class);
}
```

### Issue: Missing factory relationships

**Symptom:** Database constraint violations, foreign key errors

**Solution:**
```php
// Instead of:
$model = Model::factory()->create();

// Use:
$parent = Parent::factory()->create();
$model = Model::factory()->create(['parent_id' => $parent->id]);

// Or configure factory with relationship:
$model = Model::factory()
    ->for($parent)
    ->create();
```

### Issue: Test passes locally but fails in suite

**Symptom:** Test passes when run alone but fails with others

**Solution:**
1. Ensure test uses DatabaseTransactions trait
2. Clear any static/cached data in setUp()
3. Don't rely on test execution order
4. Mock all external dependencies

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SomeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Reset any static state
        SomeClass::$staticVar = null;
    }
}
```

## Quality Guidelines

**DO:**
- Fix the root cause, not just the symptom
- Ensure test is meaningful and tests real behavior
- Add Mail::fake(), Queue::fake(), Http::fake() for external dependencies
- Use DatabaseTransactions to isolate tests
- Verify fix doesn't break other tests
- Run the test multiple times to ensure consistency

**DON'T:**
- Skip/ignore tests (mark as incomplete only as last resort)
- Add overly broad try/catch to hide errors
- Mock internal application logic unnecessarily
- Make tests dependent on each other
- Leave commented-out code
- Fix tests without understanding why they failed

## Success Criteria

✅ **Test passes consistently** (run it 3+ times)
✅ **All other tests still pass** (no regressions)
✅ **Fix addresses root cause** (not just symptoms)
✅ **Test is meaningful** (actually tests real behavior)
✅ **Changes are committed** with clear message

## Testing Philosophy

**When to fix the test vs the code:**

1. **Fix the test** if:
   - Test setup is incomplete (missing data, mocks)
   - Test expectations are outdated
   - Test is brittle (depends on execution order, external state)

2. **Fix the code** if:
   - Test expectations are correct but code is broken
   - Code has actual bugs
   - Code doesn't handle edge cases properly

3. **Refactor both** if:
   - Code is hard to test (too coupled, complex)
   - Test and code are both unclear
   - Better patterns exist

## Reference Files

- Similar passing tests in same directory
- Factory files: `database/factories/`
- Route definitions: `routes/web.php`, `routes/api.php`
- Model relationships: `app/Models/`
- Controllers: `app/Http/Controllers/`

## When to Ask for Help

If after thorough investigation you still can't fix the test:
1. Document what you tried
2. Document the exact error
3. Note any patterns or observations
4. Mark test as incomplete with clear comment explaining why
5. Create TODO for follow-up

```php
/**
 * @group incomplete
 * TODO: Fix this test
 * Issue: Undefined array key "Test Job" - need to investigate ScheduledJobExt model
 * Attempted: Added factory data, checked routes, reviewed model code
 * Next step: Need to understand job configuration structure
 */
public function test_something()
{
    $this->markTestIncomplete('Needs investigation - see TODO comment');
}
```
