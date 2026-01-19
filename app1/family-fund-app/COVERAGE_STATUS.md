# Coverage Status - Quick Reference

**Overall Coverage:** ~71-72%  
**Last Updated:** 2026-01-18

## Files Under 50% (Sorted by Coverage %)

| Priority | File | Coverage | Issue | Effort |
|----------|------|----------|-------|--------|
| 🔴 HIGH | TradeBandReportTrait | 9.1% | Complex dependencies | High |
| 🟡 MED | IdDocumentController | ~15% | Route/view mismatch | Low |
| 🟡 MED | PersonController | ~40% | View syntax error | Low |
| 🟢 LOW | FundPDF | 44.2% | Needs more tests | Med |
| 🟢 LOW | AccountPDF | 41.2% | Needs more tests | Low |

## Quick Wins (Next 2-3 hours)

1. **IdDocumentController** → 50%+
   - Fix route names in views
   - Add 7 more tests
   - 30 min fix + 30 min tests

2. **PersonController** → 50%+
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
