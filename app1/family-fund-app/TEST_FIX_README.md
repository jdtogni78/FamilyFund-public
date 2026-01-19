# Test Fix Automation

## Quick Start

### 1. Manual Fix (Single Test)

```bash
# Read the generic instructions
cat TEST_FIX_PROMPT.md

# Apply to your test manually
# Follow step-by-step process in the prompt
```

### 2. Automated Fix (Batch Processing)

```bash
# Fix multiple tests at once
./fix_tests_batch.sh AssetPriceControllerExtTest ScheduledJobControllerExtTest

# Or use a file list
./fix_tests_batch.sh @test_failures_list.txt
```

## File Structure

```
├── TEST_FIX_LOG.md                  # Batch run history & results
├── TEST_FIX_PROMPT.md               # Generic test fix instructions
├── fix_tests_batch.sh               # Batch automation script
├── test_failures_list.txt           # Example/current failures list
└── TEST_FIX_README.md               # This file
```

## Usage Examples

### Example 1: Single Test via CLI

```bash
./fix_tests_batch.sh AssetPriceControllerExtTest
```

**What happens:**
1. Script reads TEST_FIX_PROMPT.md
2. Invokes Claude with instructions for AssetPriceControllerExtTest
3. Claude runs test, analyzes error, fixes, verifies, commits
4. Logs output to `test_fix_AssetPriceControllerExtTest_<timestamp>.log`

### Example 2: Multiple Tests

```bash
./fix_tests_batch.sh AssetPriceControllerExtTest ScheduledJobControllerExtTest FundSetupTest
```

**What happens:**
1. Processes each test sequentially
2. 5-second pause between tests (rate limiting)
3. Creates separate log for each test
4. Summary report at end

### Example 3: File List

Create `my_failures.txt`:
```
# Tests to fix
AssetPriceControllerExtTest
ScheduledJobControllerExtTest

# Fund setup tests (may need refactoring)
FundSetupTest
FundSetupAPITest
```

Run:
```bash
./fix_tests_batch.sh @my_failures.txt
```

## How It Works

### The Generic Prompt (TEST_FIX_PROMPT.md)

This file contains:
- Step-by-step debugging process
- Common error patterns and solutions
- Code fix examples
- Quality guidelines
- When to fix test vs code vs refactor

### The Batch Script (fix_tests_batch.sh)

Features:
- ✅ File list or direct arguments
- ✅ Non-interactive Claude invocation
- ✅ Logging per test
- ✅ Rate limiting between tests
- ✅ Success/failure tracking
- ✅ Summary reporting

### Common Test Failures

**ErrorException: Undefined array key**
- Missing data in test setup
- Add required keys to factory/test data

**RouteNotFoundException**
- Route name mismatch
- Check routes/web.php for correct name

**SMTP/Mail Errors**
- Missing Mail::fake()
- Add at start of test

**Database Constraint Violations**
- Missing factory relationships
- Create parent records first

## Best Practices

### Before Running

1. **Check current test status:**
   ```bash
   docker exec familyfund php artisan test --filter=<TestName>
   ```

2. **Ensure working directory is clean:**
   ```bash
   git status
   git stash  # if needed
   ```

3. **Run full test suite to get baseline:**
   ```bash
   docker exec familyfund php artisan test --testsuite=Feature
   ```

### During Execution

- **Monitor logs** in real-time:
  ```bash
  tail -f test_fix_*.log
  ```

- **Check progress:**
  ```bash
  git status  # See what's been modified
  git log -1  # See last commit
  ```

### After Completion

1. **Verify tests pass:**
   ```bash
   docker exec familyfund php artisan test --testsuite=Feature
   ```

2. **Check for regressions:**
   ```bash
   docker exec familyfund php artisan test
   ```

3. **Review commits:**
   ```bash
   git log --oneline -5
   ```

4. **Update test failures list:**
   - Remove fixed tests from test_failures_list.txt
   - Document any tests that couldn't be fixed

## Troubleshooting

### Script fails with "claude: command not found"

```bash
# Check if claude-cli is installed
which claude

# Install if missing
npm install -g @anthropic-ai/claude-cli
```

### Test still fails after fix attempt

```bash
# Check the log file
cat test_fix_<TestName>_<timestamp>.log

# Review what was changed
git diff tests/Feature/<TestFile>.php

# Run test manually to debug
docker exec familyfund php artisan test --filter=<TestName> --stop-on-failure
```

### Rate limiting errors

The script includes 5-second pauses between tests. If you still hit limits:

1. Increase pause in `fix_tests_batch.sh`:
   ```bash
   # Change from:
   sleep 5
   # To:
   sleep 10
   ```

2. Process tests in smaller batches

### Test can't be fixed automatically

Some tests may require:
1. **Refactoring** (code too complex/coupled)
2. **Design changes** (architecture issues)
3. **External dependencies** (missing services)
4. **Investigation** (unclear requirements)

For these cases:
- Document findings in test comments
- Mark test with `@group incomplete`
- Create TODO/issue for follow-up
- Update test_failures_list.txt with notes

## Advanced Usage

### Custom Prompts

Create test-specific prompts:

```bash
# Create custom prompt
cat > custom_fund_setup_prompt.md <<EOF
@TEST_FIX_PROMPT.md

Additional context for FundSetup tests:
- These tests create complex multi-step fund initialization
- May require refactoring to be testable
- See app/Services/FundSetupService.php for business logic
- Consider mocking external dependencies
EOF

# Use with claude directly
echo "Fix FundSetupTest using @custom_fund_setup_prompt.md" | claude --no-interactive
```

### Parallel Processing

For faster processing with multiple API keys:

```bash
# Split file list
split -l 3 test_failures_list.txt batch_

# Run in parallel (different terminals or tmux)
./fix_tests_batch.sh @batch_aa &
./fix_tests_batch.sh @batch_ab &
./fix_tests_batch.sh @batch_ac &
```

### Filter by Error Type

```bash
# Find all tests with specific error
docker exec familyfund php artisan test --testsuite=Feature 2>&1 | \
  grep "Undefined array key" -B 5 | \
  grep "FAILED" | \
  sed 's/.*Tests\\Feature\\//' | \
  sed 's/ >.*//' | \
  sort -u > undefined_array_key_tests.txt

# Fix just those tests
./fix_tests_batch.sh @undefined_array_key_tests.txt
```

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: Fix Failing Tests

on:
  workflow_dispatch:
    inputs:
      tests:
        description: 'Tests to fix (comma-separated)'
        required: true

jobs:
  fix-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Install Claude CLI
        run: npm install -g @anthropic-ai/claude-cli

      - name: Configure Claude
        env:
          ANTHROPIC_API_KEY: ${{ secrets.ANTHROPIC_API_KEY }}
        run: echo $ANTHROPIC_API_KEY | claude configure

      - name: Fix Tests
        run: |
          cd app1/family-fund-app
          IFS=',' read -ra TESTS <<< "${{ github.event.inputs.tests }}"
          ./fix_tests_batch.sh "${TESTS[@]}"

      - name: Create PR
        uses: peter-evans/create-pull-request@v4
        with:
          title: "Test fixes: ${{ github.event.inputs.tests }}"
          branch: test-fixes-${{ github.run_id }}
```

## Monitoring & Reporting

### Generate Test Failure Report

```bash
# Full test failure list with error types
docker exec familyfund php artisan test --testsuite=Feature 2>&1 | \
  grep -E "FAILED|Error" > test_failure_report.txt

# View in browser
open test_failure_report.txt
```

### Track Progress

```bash
# Tests fixed today
git log --since="1 day ago" --oneline | grep -i "fix.*test"

# Test fix trends
git log --all --oneline --grep="Fix failing test" | wc -l
```

## Success Metrics

- ✅ Test passes consistently (3+ runs)
- ✅ No regressions (all other tests pass)
- ✅ Root cause fixed (not just symptoms)
- ✅ Commits include clear explanation
- ✅ Test is meaningful (actually tests behavior)

## Support & References

- **Testing Patterns:** See existing passing tests in tests/Feature/
- **Factory Patterns:** database/factories/
- **Route Definitions:** routes/web.php, routes/api.php
- **Controller Patterns:** app/Http/Controllers/
- **Model Patterns:** app/Models/

## Common Test Fix Workflows

### Workflow 1: Quick Individual Fix

```bash
# 1. Identify the failing test
docker exec familyfund php artisan test --testsuite=Feature | grep FAILED

# 2. Run it to see error
docker exec familyfund php artisan test --filter=AssetPriceControllerExtTest

# 3. Fix it with automation
./fix_tests_batch.sh AssetPriceControllerExtTest

# 4. Verify
docker exec familyfund php artisan test --filter=AssetPriceControllerExtTest
```

### Workflow 2: Batch Fix All Failures

```bash
# 1. Get fresh list of failures
docker exec familyfund php artisan test --testsuite=Feature 2>&1 | \
  grep "^ *FAILED" | \
  sed 's/.*Tests\\Feature\\//' | \
  sed 's/ >.*//' | \
  sort -u > current_failures.txt

# 2. Review and edit list (remove tests that need manual work)
vim current_failures.txt

# 3. Run batch fix
./fix_tests_batch.sh @current_failures.txt

# 4. Verify all pass
docker exec familyfund php artisan test --testsuite=Feature
```

### Workflow 3: Fix by Error Pattern

```bash
# 1. Find tests with specific error
docker exec familyfund php artisan test --testsuite=Feature 2>&1 | \
  grep "RouteNotFoundException" -B 3 | \
  grep FAILED > route_errors.txt

# 2. Extract test names
cat route_errors.txt | sed 's/.*Tests\\Feature\\//' | sed 's/ >.*//' > route_error_tests.txt

# 3. Fix them
./fix_tests_batch.sh @route_error_tests.txt
```

---

**Happy testing! 🧪**
