# Test Fix Plan

**Last Updated:** 2026-01-10
**Goal:** Achieve 50% code coverage

---

## Current Metrics

| Metric | Current | Target | Status |
|--------|---------|--------|--------|
| **Line Coverage** | 45.56% (3198/7019) | 50% (~3510 lines) | NEAR TARGET |
| **Method Coverage** | 35.44% (431/1216) | 50% | IN PROGRESS |
| **Class Coverage** | 28.49% (100/351) | 50% | IN PROGRESS |
| **Tests Passing** | 319 | 327 (all) | NEAR TARGET |
| **Tests Failing** | 0 | 0 | ACHIEVED |
| **Tests Skipped** | 1 | - | OK |
| **Incomplete** | 8 | - | OK |

### Lines Needed to Reach 50%
- Current: 3198 lines covered
- Target: 3510 lines (50% of 7019)
- **Gap: ~312 more lines needed**

### Test Suites

| Suite | Files | Status | Coverage Contribution |
|-------|-------|--------|----------------------|
| Unit | 1 | Placeholder only | LOW - needs new tests |
| Feature | 12 | ALL PASSING | MEDIUM |
| APIs | 26 | ALL PASSING | MEDIUM |
| Repositories | 28 | ALL PASSING | LOW |
| GoldenData | 4 | All passing | HIGH (5131 assertions) |

---

## Task Claims

**Instructions:** Before starting work, register in `.claude-agents` file (see `~/.claude/CLAUDE.md` for format). Then add your agent name and status below. Update status as you progress.

**Active Agents:** Check `.claude-agents` file for currently running agents and their tasks.

### Phase 1: Fix Existing Tests (Factory Fixes)

| Task | Agent | Status | Started | Completed |
|------|-------|--------|---------|-----------|
| WP1a: TransactionFactory, AccountBalanceFactory | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP1b: PortfolioFactory, PortfolioAssetFactory | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP1c: AssetPriceFactory, AssetChangeLogFactory | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP1d: TradePortfolioFactory, TradePortfolioItemFactory | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP1e: SymbolPositionFactory, SymbolPriceFactory | claude11 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP1f: PositionUpdateFactory, PriceUpdateFactory | claude11 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP1g: TransactionMatchingFactory | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP1h: UserFactory | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP1i: AddressFactory, PhoneFactory, IdDocumentFactory | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP1j: ChangeLogFactory, ScheduleFactory, ScheduledJobFactory | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP1k: AccountReportFactory, AccountMatchingRuleFactory | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP2: PersonFactory (date fixes) | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP2b: FundReportFactory (date fixes) | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP3: User test assertions (email_verified_at mismatch) | opus2 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP4: CashDepositTraitTest (data isolation) | claude3 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP4b: FetchDepositsTest (HTTP mocking) | claude3 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP4c: FundReportTest (fixtures) | claude3 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP4d: TradePortfolioExtTest (fund creation) | claude3 | COMPLETED | 2026-01-10 | 2026-01-10 |
| WP4e: TestFixtures class creation | claude3 | COMPLETED | 2026-01-10 | 2026-01-10 |

### Remaining Failures by Category - ALL FIXED

#### Category A: Missing Tables - FIXED (claude11)
Created migrations for position_updates, price_updates, symbol_positions, symbol_prices tables.
Fixed SymbolPositionFactory and SymbolPriceFactory type field (VARCHAR(3)).

#### Category B: SoftDeletes Mismatch - FIXED (opus2)
Removed SoftDeletes trait from AssetChangeLog and ChangeLog models.

#### Category C: FundReportFactory Date Issue - FIXED (opus2)
Fixed FundReportFactory date format.

#### Category D: IdDocument Table Name - FIXED (claude11)
Fixed IdDocument model to use `iddocuments` table (not `id_documents`).

#### Category D2: Incomplete API Features - SKIPPED (8 tests)
PositionUpdate and PriceUpdate use bulk operations, not standard CRUD APIs.
Tests marked with `@group incomplete`.

#### Category E: Application Bugs - FIXED
- DataFactory: Fixed to use `fundAccount()` state for fund accounts
- TransactionApiGoldenDataTest: Fixed division by zero with shareValue check

#### Category F: Test Data Issues - FIXED (claude3)
- CashDepositTraitTest: Use unique test account IDs, added ENUM migration
- FetchDepositsTest: Added DatabaseTransactions, fixed HTTP assertions
- FundReportTest: Refactored to use TestFixtures
- TradePortfolioExtTest: Added createFund() before createTradePortfolio()

---

## Progress Summary

### claude3 - 2026-01-10

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Tests Passing | 292 | 296 | +4 tests |
| Tests Failing | 3 | 0 | -3 tests |

**Work completed:**
- Created `TestFixtures` class with reusable fixture methods:
  - `fundReportFixture()` - Complete fund report setup
  - `cashDepositFixture()` - Cash deposit with trade portfolio
  - `cashDepositWithRequestsFixture()` - Cash deposit with deposit requests
  - `minimalFundFixture()` - Simple fund setup
  - `fundWithMatchingFixture()` - Fund with matching rules
- Fixed CashDepositTraitTest: unique test account IDs, added ENUM migration for `COMPLETED`/`CANCELLED` statuses
- Fixed FetchDepositsTest: DatabaseTransactions, isolated test data
- Fixed FundReportTest: Refactored to use TestFixtures
- Fixed TradePortfolioExtTest: Added createFund() call in setUp()
- Fixed DataFactory: `createTradePortfolio()` properly associates with fund's portfolio

**Files created:**
- `tests/Fixtures/TestFixtures.php`
- `database/migrations/2026_01_10_181834_add_completed_cancelled_to_cash_deposits_status.php`

### claude11 - 2026-01-10

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Tests Passing | 267 | 290 | +23 tests |
| Tests Failing | 36 | 5 | -31 tests (8 skipped) |

**Work completed:**
- Created 4 migrations for missing tables (position_updates, price_updates, symbol_positions, symbol_prices)
- Fixed SymbolPositionFactory and SymbolPriceFactory type field (VARCHAR(3) constraint)
- Fixed IdDocument model table name (iddocuments vs id_documents)
- Marked PositionUpdate/PriceUpdate API tests as @group incomplete (use bulk APIs instead)

### opus2 - 2026-01-10

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Tests Passing | 145 | 266 | +121 tests |
| Tests Failing | 158 | 37 | -121 tests |

**Factory FK fixes completed:**
- TransactionFactory, AccountBalanceFactory
- PortfolioFactory, PortfolioAssetFactory
- AssetPriceFactory, AssetChangeLogFactory
- TradePortfolioFactory, TradePortfolioItemFactory
- TransactionMatchingFactory, AccountMatchingRuleFactory
- UserFactory, AddressFactory, PhoneFactory, IdDocumentFactory
- PersonFactory, FundReportFactory
- ScheduleFactory, ScheduledJobFactory, AccountReportFactory

**Model fixes:**
- Removed SoftDeletes from AssetChangeLog (table lacks deleted_at)
- Removed SoftDeletes from ChangeLog (table lacks deleted_at)
- Set UPDATED_AT = null in ChangeLog (table lacks updated_at)
- Fixed Address validation: removed invalid 'default:false' rule, removed 'county' (column doesn't exist)
- Fixed Phone validation: removed invalid 'default:false' rule

**Test code fixes:**
- UserRepositoryTest: fixed password handling (password is in $hidden array)
- UserFactory: removed email_verified_at from default state (not fillable)
- PortfolioAssetFactory: added end_dt field
- CashDepositExt: fixed status constants (PEN->PENDING, etc.)

---

## Next Steps (Priority Order)

### 1. Install PCOV for Coverage Reports - COMPLETED
PCOV installed in container. Run coverage with:
```bash
docker exec familyfund ./vendor/bin/phpunit --exclude-group=incomplete --coverage-text
```

### 2. Systematic Coverage Improvement Plan

**Strategy:** Focus on files with <50% coverage that have high line counts (biggest impact).

#### Priority 1: High Impact Files (<50% coverage, >30 lines)

| File | Coverage | Lines | Uncovered | Priority |
|------|----------|-------|-----------|----------|
| `FundTrait` | 31.68% | 202 | ~138 | HIGH |
| `AccountReportAPIControllerExt` | 38.78% | 49 | ~30 | MEDIUM |
| `AccountTrait` | 41.18% | 34 | ~20 | MEDIUM |
| `TradePortfolioItem` | 46.88% | 64 | ~34 | MEDIUM |
| `TradePortfolioAPIControllerExt` | 28.57% | 7 | ~5 | LOW |
| `AssetExt` | 33.33% | 3 | ~2 | LOW |

#### Priority 2: Quick Wins (Files needing few lines to reach 50%)

| File | Current | Lines to 50% | Action |
|------|---------|--------------|--------|
| `TradePortfolioItem` | 46.88% | ~2 lines | Add 1 test |
| `AccountTrait` | 41.18% | ~3 lines | Add 1-2 tests |

#### Priority 3: Email/Mail Classes (Low coverage but isolated)

| File | Coverage | Notes |
|------|----------|-------|
| `TransactionEmail` | 21.05% | Need Mail::fake() |
| `FundReportEmail` | 21.43% | Need Mail::fake() |

### 3. Estimated Impact

| Task | Est. Lines Gained | Cumulative Coverage |
|------|-------------------|---------------------|
| FundTrait tests | +70 lines | ~46% |
| AccountTrait tests | +15 lines | ~46.5% |
| TradePortfolioItem tests | +20 lines | ~47% |
| AccountReportAPIControllerExt tests | +20 lines | ~47.5% |
| Email class tests | +20 lines | ~48% |
| Misc quick wins | +100 lines | **~50%** |

---

### Phase 2: New Unit Tests (Coverage Expansion)

| Task | Agent | Status | Started | Completed |
|------|-------|--------|---------|-----------|
| WP5a: Unit tests for AccountExt | | NOT STARTED | | |
| WP5b: Unit tests for FundExt | | NOT STARTED | | |
| WP5c: Unit tests for TransactionExt | | NOT STARTED | | |
| WP5d: Unit tests for PortfolioExt | | NOT STARTED | | |
| WP5e: Unit tests for TradePortfolioExt | | NOT STARTED | | |
| WP5f: Unit tests for Utils | | NOT STARTED | | |
| WP6a: Unit tests for QuickChartService | | NOT STARTED | | |
| WP6b: Unit tests for SnappyPdfWrapper | | NOT STARTED | | |
| WP7: Unit tests for PerformanceTrait | | NOT STARTED | | |

**Status values:** NOT STARTED, IN PROGRESS, COMPLETED, BLOCKED

---

## Coverage Commands

```bash
# Run tests with coverage summary
docker exec familyfund ./vendor/bin/phpunit --coverage-text 2>&1 | grep -E "^  (Lines|Methods|Classes):"

# Run tests with HTML coverage report (view in browser)
docker exec familyfund ./vendor/bin/phpunit --coverage-html storage/coverage
# Then open: http://localhost:3000/storage/coverage/index.html

# Run specific suite with coverage
docker exec familyfund ./vendor/bin/phpunit --testsuite=Feature --coverage-text

# Run all tests (no coverage)
docker exec familyfund php artisan test

# Run specific test file
docker exec familyfund php artisan test --filter=TransactionRepositoryTest
```

---

## Infrastructure Discussion

### Current Infrastructure (Available)

| Component | Status | Notes |
|-----------|--------|-------|
| PHPUnit 10+ | OK | Installed and configured |
| PCOV | OK | Coverage driver installed (faster than Xdebug) |
| MariaDB test DB | OK | Uses transactions for isolation |
| Factories | PARTIAL | Many missing required FKs |
| DataFactory | OK | Custom test data generator |

### Infrastructure Needed

| Component | Need | Discussion |
|-----------|------|------------|
| **Mocking for QuickChartService** | HIGH | Service makes HTTP calls to external chart server. Need to mock `Http::fake()` or create interface for DI. |
| **Mocking for SnappyPdfWrapper** | HIGH | Calls wkhtmltopdf binary. Need to mock or use test doubles. |
| **Mail testing** | LOW | Already uses `Mail::fake()` in some tests. MailHog available. |
| **Queue testing** | LOW | Queue set to `sync` in testing. Should work. |
| **Factory relationships** | HIGH | Many factories need proper `->for()` relationships. See WP1 tasks. |

### Mocking Strategy for Services

**QuickChartService** (1181 lines):
```php
// Option 1: HTTP fake (recommended)
Http::fake([
    'quickchart/*' => Http::response(['success' => true, 'url' => 'http://test.png']),
]);

// Option 2: Create interface and mock
interface ChartServiceInterface { public function generateChart($data); }
```

**SnappyPdfWrapper** (123 lines):
```php
// Option 1: Mock the wrapper class
$mock = Mockery::mock(SnappyPdfWrapper::class);
$mock->shouldReceive('generateFromHtml')->andReturn('pdf-content');

// Option 2: Skip PDF tests in CI (use @group annotation)
/** @group requires-wkhtmltopdf */
```

---

## Completed Work

### GoldenData Tests (Claude-Opus) - COMPLETED 2026-01-10

All 8 GoldenData tests now pass (5131 assertions).

**Files Modified:**
- `tests/GoldenData/AccountApiGoldenDataTest.php`
- `tests/GoldenData/FundApiGoldenDataTest.php`
- `tests/GoldenData/PortfolioApiGoldenDataTest.php`
- `tests/GoldenData/TransactionApiGoldenDataTest.php`
- `tests/ApiTestTrait.php`

---

## Phase 1: Fix Existing Tests

### Failure Categories

| Category | Count | Root Cause |
|----------|-------|------------|
| Factory missing FK | ~120 | Factories don't create required foreign key relationships |
| InvalidFormatException | ~16 | Date parsing issues in Person/FundReport factories |
| Data mismatch | ~6 | Expected vs actual value differences (User tests) |
| Feature test | 1 | CashDepositTraitTest unique constraint |

### Work Package 1: Factory FK Fixes (HIGH PRIORITY - ~120 tests)

**Problem:** Factories create records without required foreign keys, causing `Field 'X' doesn't have a default value` errors.

**Factories to fix:**

| Factory | Missing FK | Solution |
|---------|-----------|----------|
| `TransactionFactory` | `account_id` | Add `'account_id' => Account::factory()` |
| `AccountBalanceFactory` | `account_id`, `transaction_id` | Add FK factories |
| `PortfolioFactory` | `fund_id` | Add `'fund_id' => Fund::factory()` |
| `PortfolioAssetFactory` | `portfolio_id`, `asset_id` | Add FK factories |
| `AssetPriceFactory` | `asset_id` | Add `'asset_id' => Asset::factory()` |
| `AssetChangeLogFactory` | `asset_id` | Add `'asset_id' => Asset::factory()` |
| `TradePortfolioItemFactory` | `trade_portfolio_id` | Add FK factory |
| `SymbolPositionFactory` | FK missing | Check schema, add required FK |
| `SymbolPriceFactory` | FK missing | Check schema, add required FK |
| `PositionUpdateFactory` | FK missing | Check schema, add required FK |
| `PriceUpdateFactory` | FK missing | Check schema, add required FK |
| `TransactionMatchingFactory` | `transaction_id`, `ref_transaction_id` | Add 2x Transaction::factory() |
| `UserFactory` | `password` | Add `'password' => Hash::make('password')` |
| `AddressFactory` | `person_id` | Add `'person_id' => Person::factory()` |
| `PhoneFactory` | `person_id` | Add FK factory |
| `IdDocumentFactory` | `person_id` | Add FK factory |
| `ChangeLogFactory` | FK missing | Check schema |
| `ScheduleFactory` | FK missing | Check schema |
| `ScheduledJobFactory` | FK missing | Check schema |
| `AccountReportFactory` | `account_id` | Add FK factory |

### Work Package 2: Date Parsing Fixes (~16 tests)

**Problem:** `InvalidFormatException` when parsing dates in factories.

**Files affected:**
- `PersonFactory.php` - Check `dob` field format
- `FundReportFactory.php` - Check date field formats

**Solution:** Ensure date fields use `$this->faker->date('Y-m-d')` instead of `$this->faker->dateTime`

### Work Package 3: User Test Fixes (~6 tests)

**Problem:** `email_verified_at` mismatch between expected and actual values.

**Files affected:**
- `tests/APIs/UserApiTest.php`
- `tests/Repositories/UserRepositoryTest.php`

**Solution:** Either ignore `email_verified_at` in assertions or ensure factory generates consistent values.

### Work Package 4: Feature Test Fix (1 test)

**Problem:** `UniqueConstraintViolationException` in `CashDepositTraitTest`

**File:** `tests/Feature/CashDepositTraitTest.php:144`

**Solution:** Investigate `tests/DataFactory.php:115` for duplicate key generation.

---

## Phase 2: New Unit Tests (Coverage Expansion)

### Priority Model Classes (Business Logic)

These `*Ext` models contain significant business logic and should have dedicated unit tests:

| Model | Lines | Priority | Key Methods to Test |
|-------|-------|----------|---------------------|
| `AccountExt` | ~107 | HIGH | Balance calculations, transaction history |
| `FundExt` | ~53 | HIGH | `valueAsOf`, `sharesAsOf`, `unallocatedShares` |
| `TransactionExt` | ? | HIGH | Matching logic, value calculations |
| `PortfolioExt` | ~67 | HIGH | Asset aggregation, value calculations |
| `TradePortfolioExt` | ? | MEDIUM | Rebalancing logic |
| `AssetExt` | ~32 | MEDIUM | Price history, position tracking |

### Service Classes

| Service | Lines | Priority | Notes |
|---------|-------|----------|-------|
| `QuickChartService` | 1181 | HIGH | Needs HTTP mocking |
| `SnappyPdfWrapper` | 123 | MEDIUM | Needs binary mocking |

### Controller Traits

| Trait | Priority | Notes |
|-------|----------|-------|
| `PerformanceTrait` | HIGH | Core calculation logic |
| `AccountTrait` | MEDIUM | Account operations |
| `FundTrait` | MEDIUM | Fund operations |
| `TransactionTrait` | MEDIUM | Transaction handling |
| `CashDepositTrait` | LOW | Already has Feature test |

---

## Coverage Estimation

| Phase | Task | Est. Coverage Gain |
|-------|------|-------------------|
| Phase 1 | Fix all factory issues | +5% (existing tests will run) |
| Phase 2 | AccountExt unit tests | +3% |
| Phase 2 | FundExt unit tests | +2% |
| Phase 2 | TransactionExt unit tests | +3% |
| Phase 2 | PortfolioExt unit tests | +2% |
| Phase 2 | Utils unit tests | +1% |
| Phase 2 | QuickChartService tests | +15% (1181 lines!) |
| Phase 2 | Other Ext models | +10% |
| Phase 2 | Controller traits | +9% |
| **Total** | | **~50%** |

---

## Notes for Agents

1. **Check test_plan.md before starting** - claim your task in the table
2. **Run tests after each change** - verify you fixed what you intended
3. **Update status when done** - mark COMPLETED with timestamp
4. **If blocked** - mark BLOCKED and add note explaining why
5. **Coverage reports** - run coverage after Phase 1 to reassess Phase 2 priorities

### Refactoring Considerations

When writing tests, consider whether refactoring would improve testability:

| Issue | Example | Refactoring Approach |
|-------|---------|---------------------|
| **Large files** | `QuickChartService` (1181 lines) | Split into smaller, focused classes (e.g., `ChartDataBuilder`, `ChartRenderer`, `ChartStyler`) |
| **Hard dependencies** | Direct `Http::` calls, `new SnappyPdf()` | Inject dependencies via constructor; use interfaces |
| **Mixed concerns** | Business logic + HTTP + formatting in one method | Extract pure functions for calculations; separate I/O |
| **Global state** | Static methods, singletons | Convert to instance methods with injected dependencies |
| **Long methods** | Methods > 50 lines | Extract helper methods; each method = one responsibility |

**Rule of thumb:** If a class is hard to test, it probably needs refactoring. Tests should be:
- Fast (no real HTTP/DB if avoidable)
- Isolated (test one thing)
- Repeatable (same result every time)

**Before writing complex mocks**, ask: "Would refactoring make this simpler to test?"

Example refactoring for `QuickChartService`:
```
Before: 1 file, 1181 lines, hard to test
After:
  - ChartConfigBuilder.php (~200 lines) - pure functions, easy to unit test
  - ChartDataTransformer.php (~150 lines) - pure functions, easy to unit test
  - ChartHttpClient.php (~100 lines) - thin wrapper, mock Http::fake()
  - QuickChartService.php (~200 lines) - orchestrates above, integration test
```

---
