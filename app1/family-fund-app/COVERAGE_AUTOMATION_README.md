# Coverage Improvement Automation

## Quick Start

### 1. Manual Improvement (Single File)

```bash
# Read the generic instructions
cat COVERAGE_IMPROVEMENT_PROMPT.md

# Apply to your file manually
# Follow step-by-step process in the prompt
```

### 2. Automated Improvement (Batch Processing)

```bash
# Improve multiple files at once
./improve_coverage_batch.sh UserController AccountController FundPDF

# Or use a file list
./improve_coverage_batch.sh @coverage_targets_example.txt
```

## File Structure

```
├── COVERAGE_TODO.md                    # Current status & todo list
├── COVERAGE_STATUS.md                  # Quick reference guide
├── COVERAGE_IMPROVEMENT_PROMPT.md      # Generic improvement instructions
├── improve_coverage_batch.sh           # Batch automation script
├── coverage_targets_example.txt        # Example file list
└── COVERAGE_AUTOMATION_README.md       # This file
```

## Usage Examples

### Example 1: Single File via CLI

```bash
./improve_coverage_batch.sh UserController
```

**What happens:**
1. Script reads COVERAGE_IMPROVEMENT_PROMPT.md
2. Invokes Claude with instructions for UserController
3. Claude analyzes, writes tests, runs tests, commits
4. Logs output to `coverage_improvement_UserController_<timestamp>.log`

### Example 2: Multiple Files

```bash
./improve_coverage_batch.sh UserController AccountController TransactionController
```

**What happens:**
1. Processes each file sequentially
2. 5-second pause between files (rate limiting)
3. Creates separate log for each file
4. Summary report at end

### Example 3: File List

Create `my_targets.txt`:
```
# Controllers needing improvement
UserController
AccountController

# PDF classes
ReportPDF
InvoicePDF
```

Run:
```bash
./improve_coverage_batch.sh @my_targets.txt
```

## How It Works

### The Generic Prompt (COVERAGE_IMPROVEMENT_PROMPT.md)

This file contains:
- Step-by-step instructions for any file
- Complete test patterns for controllers
- Edge case handling
- Common issues and solutions
- Quality guidelines

### The Batch Script (improve_coverage_batch.sh)

Features:
- ✅ File list or direct arguments
- ✅ Non-interactive Claude invocation
- ✅ Logging per file
- ✅ Rate limiting between files
- ✅ Success/failure tracking
- ✅ Summary reporting

### Test Patterns Provided

**For Controllers (18 tests):**
1. Index - list display
2. Index - multiple records
3. Create - form display
4. Store - save record
5. Store - validation
6. Store - flash message
7. Show - display record
8. Show - view data verification
9. Show - invalid ID
10. Edit - form display
11. Edit - view data verification
12. Edit - invalid ID
13. Update - save changes
14. Update - flash message
15. Update - invalid ID
16. Destroy - delete record
17. Destroy - flash message
18. Destroy - invalid ID

**For PDF/Service Classes:**
- Output generation
- File validation
- Edge cases (minimal data, empty inputs)
- Destroy/cleanup methods

## Claude CLI Requirements

### Installation

```bash
# Install claude-cli
npm install -g @anthropic-ai/claude-cli

# Or using pip
pip install claude-cli

# Verify installation
claude --version
```

### Configuration

```bash
# Set API key (if needed)
export ANTHROPIC_API_KEY="your-api-key"

# Or configure via claude-cli
claude configure
```

## Best Practices

### Before Running

1. **Check current coverage:**
   ```bash
   docker exec familyfund php artisan test --filter=<File>Test
   ```

2. **Ensure tests pass:**
   ```bash
   docker exec familyfund php artisan test
   ```

3. **Commit current work:**
   ```bash
   git add -A && git commit -m "Before coverage improvement"
   ```

### During Execution

- **Monitor logs** in real-time:
  ```bash
  tail -f coverage_improvement_*.log
  ```

- **Check progress:**
  ```bash
  git status  # See what's been modified
  git log -1  # See last commit
  ```

### After Completion

1. **Verify all tests pass:**
   ```bash
   docker exec familyfund php artisan test
   ```

2. **Check coverage:**
   ```bash
   docker exec familyfund ./vendor/bin/phpunit --coverage-text
   ```

3. **Review commits:**
   ```bash
   git log --oneline -5
   ```

4. **Update TODO:**
   - Mark completed files in COVERAGE_TODO.md
   - Document any issues encountered

## Troubleshooting

### Script fails with "claude: command not found"

```bash
# Check if claude-cli is installed
which claude

# Install if missing
npm install -g @anthropic-ai/claude-cli
```

### Tests fail after improvement

```bash
# Check the log file
cat coverage_improvement_<file>_<timestamp>.log

# Review generated tests
git diff tests/Feature/<File>Test.php

# Run tests manually to debug
docker exec familyfund php artisan test --filter=<File>Test
```

### Rate limiting errors

The script includes 5-second pauses between files. If you still hit limits:

1. Increase pause in `improve_coverage_batch.sh`:
   ```bash
   # Change from:
   sleep 5
   # To:
   sleep 10
   ```

2. Process files in smaller batches

### Coverage doesn't reach 70%

Some files may be difficult to test (heavy I/O, complex integrations). Options:

1. **Refactor first** (see COVERAGE_NEXT_STEPS.md)
2. **Accept lower coverage** with documented rationale
3. **Add integration tests** instead of unit tests

## Advanced Usage

### Custom Prompts

Create file-specific prompts:

```bash
# Create custom prompt
cat > custom_prompt.md <<EOF
@COVERAGE_IMPROVEMENT_PROMPT.md

Additional context for UserController:
- Uses complex auth middleware
- Requires 2FA setup in tests
- See tests/Feature/AuthControllerTest.php for patterns
EOF

# Use with claude directly
echo "Improve UserController coverage @custom_prompt.md" | claude --no-interactive
```

### Parallel Processing

For faster processing with multiple API keys:

```bash
# Split file list
split -l 5 coverage_targets.txt batch_

# Run in parallel (different terminals or tmux)
./improve_coverage_batch.sh @batch_aa &
./improve_coverage_batch.sh @batch_ab &
./improve_coverage_batch.sh @batch_ac &
```

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: Coverage Improvement

on:
  workflow_dispatch:
    inputs:
      files:
        description: 'Files to improve (comma-separated)'
        required: true

jobs:
  improve-coverage:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Install Claude CLI
        run: npm install -g @anthropic-ai/claude-cli

      - name: Configure Claude
        env:
          ANTHROPIC_API_KEY: ${{ secrets.ANTHROPIC_API_KEY }}
        run: echo $ANTHROPIC_API_KEY | claude configure

      - name: Improve Coverage
        run: |
          cd app1/family-fund-app
          IFS=',' read -ra FILES <<< "${{ github.event.inputs.files }}"
          ./improve_coverage_batch.sh "${FILES[@]}"

      - name: Create PR
        uses: peter-evans/create-pull-request@v4
        with:
          title: "Coverage improvement: ${{ github.event.inputs.files }}"
          branch: coverage-improvement-${{ github.run_id }}
```

## Monitoring & Reporting

### Generate Coverage Report

```bash
# Full report
docker exec familyfund ./vendor/bin/phpunit --coverage-html coverage_report

# View in browser
open coverage_report/index.html
```

### Track Progress

```bash
# Files improved today
git log --since="1 day ago" --oneline | grep -i coverage

# Coverage trends
git log --all --oneline --grep="coverage" | wc -l
```

## Support & References

- **Testing Philosophy:** See COVERAGE_NEXT_STEPS.md
- **Current Status:** See COVERAGE_STATUS.md
- **Todo List:** See COVERAGE_TODO.md
- **Example Tests:** See tests/Feature/IdDocumentControllerTest.php
- **PDF Tests:** See tests/Feature/PDFTest.php

## Success Metrics

- ✅ All tests passing (100% success rate)
- ✅ Coverage at 70%+ per file
- ✅ Meaningful tests (not just status checks)
- ✅ Commits include co-authorship
- ✅ Documentation updated

---

**Happy testing! 🚀**
