# Coverage Improvement - TODO List

**Last Updated:** 2026-01-18
**Overall Project Coverage:** ~71-72%
**Goal:** 70%+ per file (50%+ acceptable minimum)

## ✅ Completed Files (70%+)

| File | Coverage | Tests | Status |
|------|----------|-------|--------|
| IdDocumentController | 70%+ | 18 passing | ✅ DONE |
| PersonController | 70%+ | 14 passing | ✅ DONE |
| AccountPDF | 70%+ | 36 PDF tests | ✅ DONE |
| FundPDF | 70%+ | 36 PDF tests | ✅ DONE |

## 📋 Accepted Exceptions

| File | Coverage | Reason | Status |
|------|----------|--------|--------|
| TradeBandReportTrait | 9.1% | I/O wrapper, controller has integration tests | ✅ ACCEPTED |

## 🎯 Target Files for Improvement

To find files needing improvement, run:
```bash
# Get coverage for all controllers
docker exec familyfund ./vendor/bin/phpunit --testsuite=Feature --coverage-text 2>&1 | grep "Controller" | grep -v "100.00%"

# Get coverage for all models
docker exec familyfund ./vendor/bin/phpunit --coverage-text 2>&1 | grep "Models" | grep -v "100.00%"
```

## 📊 Current Test Suite

- **Total Feature tests:** 68+ passing
- **Total PDF tests:** 36 passing (61 assertions)
- **Success rate:** 100%

## 🔄 Workflow for New Files

1. **Identify target file** needing coverage improvement
2. **Run coverage** to get baseline percentage
3. **Use generic prompt** (see COVERAGE_IMPROVEMENT_PROMPT.md)
4. **Execute improvement** following systematic approach
5. **Verify tests pass** (100% success required)
6. **Commit changes** with co-authorship
7. **Update this TODO** with results

## 🤖 Automation

Use the batch improvement script for multiple files:
```bash
./improve_coverage_batch.sh file1 file2 file3
```

See `COVERAGE_IMPROVEMENT_PROMPT.md` for the generic prompt template.

## 📈 Progress Tracking

### Phase 1: 50%+ (Complete)
- 4 files improved
- 26 tests added
- 7 view files fixed

### Phase 2: 70%+ (Complete)
- 4 files improved
- 21 tests added
- All targets achieved

### Phase 3: Identify Next Targets
- Run coverage analysis
- Find files under 70%
- Prioritize by importance and effort
- Use automation for batch processing

## 🎯 Next Actions

1. ✅ Run full coverage report
2. ✅ Identify files between 50-70%
3. ✅ Identify files under 50%
4. ⏭️ Use automation script for batch improvement
5. ⏭️ Maintain 70%+ threshold for new code

## 📚 Reference Documentation

- `COVERAGE_STATUS.md` - Quick reference and current status
- `COVERAGE_NEXT_STEPS.md` - Detailed action plan with testing philosophy
- `COVERAGE_IMPROVEMENT_PROMPT.md` - Generic prompt for any file
- `COVERAGE_70_PERCENT_SUMMARY.md` - Phase 2 completion summary
- `COVERAGE_SESSION_SUMMARY.md` - Phase 1 completion summary
