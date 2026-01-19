# Test Coverage - Current Status & Next Steps

**Current Coverage:** ~71-72% overall
**Date:** 2026-01-18

## Current Status

### ✅ Controllers Above 50% Coverage
All controllers in the 44-49% range have been successfully pushed over 50%.

### ⚠️ Controllers Still Under 50%

Based on previous tracking, the following controllers remain below 50% coverage:

#### Priority 1: Lowest Coverage (Under 15%)
1. **TradeBandReportTrait** - 9.1%
   - Complex trait with email/PDF/job dependencies
   - Requires specialized test approach
   - 50 uncovered lines

2. **IdDocumentController** - ~15%
   - Route/view name mismatch issue
   - Views use 'idDocuments.*' but routes are 'id_documents.*'
   - Need to fix views or add route aliases
   - 29 uncovered lines

#### Priority 2: Medium Coverage (15-40%)
3. **PersonController** - ~40%
   - View syntax errors in phone_fields.blade.php prevent full testing
   - Create/edit tests skipped
   - Need to fix view before adding more tests

4. **FundPDF** - 44.21%
   - PDF generation class
   - Some methods tested, more coverage possible
   - 174 total lines

5. **AccountPDF** - 41.2%
   - PDF generation class
   - 20 uncovered lines

## Recommended Next Actions

### Immediate (Quick Wins)
1. **Fix IdDocument routes**
   - Option A: Update views to use 'id_documents.*' route names
   - Option B: Add route aliases for 'idDocuments.*'
   - Then add remaining 7 tests (create, edit, etc.)

2. **Fix PersonController views**
   - Fix syntax error in phone_fields.blade.php
   - Add create/edit form tests
   - Estimated: +3-4 tests to reach 50%

### Medium Effort
3. **AccountPDF** - Add 2-3 more PDF generation tests
   - Test additional PDF methods
   - Should easily push over 50%

4. **FundPDF** - Continue from 44% → 50%
   - Add tests for remaining PDF generation methods
   - Estimated: 3-5 more tests needed

### Complex (Lower Priority)
5. **TradeBandReportTrait**
   - Requires mocking email/PDF/job systems
   - Consider refactoring into smaller, testable units
   - Or accept lower coverage due to integration complexity

## Testing Strategy

### For Controllers with View Issues
```bash
# Fix the view files first
1. Check syntax in resources/views/people/phone_fields.blade.php
2. Update route names in resources/views/id_documents/*.blade.php
3. Then add tests
```

### For PDF Classes
```php
// Test pattern for PDF methods
public function test_pdf_method_name()
{
    // Setup test data
    $model = Model::factory()->create();
    
    // Call PDF method
    $pdf = new ClassPDF();
    $result = $pdf->methodName($model);
    
    // Assert result
    $this->assertNotNull($result);
    // Add specific assertions for PDF content
}
```

### For Complex Traits
- Consider integration tests instead of unit tests
- Mock external dependencies (email, jobs)
- Or refactor to make more testable

## Coverage Goals

**Target:** Maintain 50%+ coverage for all testable controllers

**Realistic Exceptions:**
- Complex traits with heavy dependencies (accept 30-40%)
- Pure integration classes (accept 40-50%)

## Quick Commands

```bash
# Run all tests
docker exec familyfund php artisan test

# Run specific controller test
docker exec familyfund php artisan test --filter=ControllerNameTest

# Get coverage for specific file
docker exec familyfund ./vendor/bin/phpunit --coverage-text --filter=ClassName
```

## Files Needing Attention

1. `resources/views/people/phone_fields.blade.php` - Syntax error
2. `resources/views/id_documents/*.blade.php` - Route name mismatch
3. `app/Models/TradeBandReportTrait.php` - Complex trait
4. `app/Models/AccountPDF.php` - Add 2-3 tests
5. `app/Models/FundPDF.php` - Add 3-5 tests

## Estimated Effort to Reach 75% Overall Coverage

- Fix view issues: 2-4 hours
- Add IdDocument tests: 1 hour
- Add PersonController tests: 1 hour  
- Add PDF tests: 2-3 hours
- **Total: 6-12 hours**

## Next Session Priority

1. Fix IdDocument view/route mismatch (30 min)
2. Add IdDocument tests (30 min)
3. Fix PersonController view syntax (30 min)
4. Add PersonController tests (30 min)
5. Add AccountPDF tests (1 hour)

**Expected gain:** +2-3% coverage
**Time:** ~3 hours
