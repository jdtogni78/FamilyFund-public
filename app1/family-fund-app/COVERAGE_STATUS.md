# Coverage Status - Quick Reference

**Overall Coverage:** ~71-72%
**Last Updated:** 2026-01-18
**Goal:** 70%+ per file (50%+ acceptable)

## Files Under 50% (Sorted by Coverage %)

| Priority | File | Coverage | Issue | Effort |
|----------|------|----------|-------|--------|
| 🔴 HIGH | TradeBandReportTrait | 9.1% | Complex dependencies | High |
| ~~🟡 MED~~ | ~~IdDocumentController~~ | ~~15%~~ → ✅ **50%+** | ~~Route/view mismatch~~ FIXED | ~~Low~~ DONE |
| ~~🟡 MED~~ | ~~PersonController~~ | ~~40%~~ → ✅ **50%+** | ~~View syntax error~~ FIXED | ~~Low~~ DONE |
| ~~🟢 LOW~~ | ~~AccountPDF~~ | ~~41.2%~~ → ✅ **50%+** | ~~Needs more tests~~ FIXED | ~~Low~~ DONE |
| 🟢 LOW | FundPDF | 44.2% | Needs more tests | Med |

## Recently Completed

1. ✅ **IdDocumentController** → 50%+ (2026-01-18)
   - Fixed route/view mismatch (idDocuments.* → id_documents.*)
   - Created missing Request classes
   - Added 9 new tests (10 total, all passing)
   - Commit: 5029c90

2. ✅ **PersonController** → 50%+ (2026-01-18)
   - Fixed syntax errors in phone_fields.blade.php and address_fields.blade.php
   - Added 3 new tests (8 total, all passing)
   - Commit: 76197b0

3. ✅ **AccountPDF** → 50%+ (2026-01-18)
   - Added 3 new edge case tests (no goals, no portfolios, comparison graph)
   - Added to existing PDFTest.php (25 total PDF tests, all passing)
   - Commit: b0d075d

## Remaining Under 50%

1. **TradeBandReportTrait** (9.1%)
   - Complex: requires mocking email, PDF, jobs, scheduling
   - High effort - consider accepting <50% for this trait
   - **Recommendation**: Consider refactoring to extract testable logic from I/O operations

2. **FundPDF** (44.2%)
   - Already has 20+ tests in PDFTest.php
   - May already be at 50%+ after AccountPDF tests run

## Testing Philosophy

**For difficult-to-test files:** Consider refactoring for testability before accepting low coverage.

Common refactoring patterns:
- Extract business logic from I/O operations (email, file, database)
- Use dependency injection for external services
- Separate data transformation from side effects
- Break complex methods into smaller, testable units

Example: TradeBandReportTrait could be split into:
- Pure functions for data preparation (easy to test)
- Thin wrappers for email/PDF/jobs (accept lower coverage or use integration tests)
   - Fix phone_fields.blade.php syntax
   - Add create/edit tests
   - 30 min fix + 30 min tests

3. **AccountPDF** → 50%+
   - Add 2-3 PDF generation tests
   - 1 hour

**Total: 3 hours → +2-3% coverage**

## Commands

```bash
# Run tests
docker exec familyfund php artisan test

# Run coverage
docker exec familyfund ./vendor/bin/phpunit --coverage-text
```

## See Also
- `COVERAGE_NEXT_STEPS.md` - Detailed action plan
- `TEST_QUICK_REF.md` - Recent test additions
