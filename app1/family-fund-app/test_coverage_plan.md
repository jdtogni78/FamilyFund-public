# Test Coverage Improvement Plan

## Phase 1 status (2026-05-23, issue #22) — DONE

The Phase 1 targets were already largely covered by suites added in prior
sessions; the "Current State" numbers below are stale. Measured line coverage of
the Phase 1 classes (pcov, full suite) before/after this pass:

| Class | Before | After |
|-------|-------:|------:|
| `Traits\ScheduledJobTrait` | 79.5% | **100.0%** |
| `WebV1\OperationsController` | 68.6% | **79.3%** |
| `WebV1\MatchingRuleControllerExt` | 92.5% | **98.5%** |
| `WebV1\TransactionControllerExt` | 91.6% | **97.0%** |
| `WebV1\ScheduledJobControllerExt` | 88.0% | **92.3%** |
| `Traits\TransactionTrait` | 94.3% | 94.3% |
| `Models\TransactionExt` | 87.6% | 88.4% |

Added: `OperationsControllerAdditionalTest` (validatePortfolioBalances + queue
worker PID/start/stop + skip-future processPending), `ScheduledJobTraitTest`
(soft-failure alert + forceRun "no data"), plus gap tests for transaction
index-filters/update/destroy/bulk-sale, matching-rule destroy, and scheduled-job
trade-band preview + update-not-found. Also hardened two pre-existing
order/faker-fragile tests (`TransactionRepositoryTest::read_transaction`,
`TransactionExtApiTest::validation_errors`).

## Phase 3 status (2026-05-24, issue #24) — DONE

As in Phase 1, the Phase 3 targets were already largely covered and the "Worst
Coverage Areas" table below is stale: the base `TradePortfolioController`,
`TradePortfolioItemController`, `PortfolioAssetController` and `AssetPriceController`
classes no longer exist — their logic was inlined into the `*Ext` variants. The four
real Phase 3 classes, measured by line/method coverage (pcov, full suite)
before/after this pass:

| Class | Lines before | Lines after | Methods before | Methods after |
|-------|-------------:|------------:|---------------:|--------------:|
| `WebV1\PortfolioAssetControllerExt` | 91.6% | **100.0%** | 78.6% | **100.0%** |
| `WebV1\AssetPriceControllerExt` | 92.5% | **100.0%** | 69.2% | **100.0%** |
| `WebV1\TradePortfolioControllerExt` | 82.1% | **98.1%** | 45.0% | **85.0%** |
| `Web\TradeBandReportController` | 100.0% | 100.0% | 100.0% | 100.0% |

Added gap tests:
- **TradePortfolio**: `store`/`update` happy paths, `show_diff` (happy + abort-404),
  `announce` (email), `doRebalance` not-found/catch path, `preview_deposits` 404,
  `do_deposits` (stubbed IB Flex via `Http::fake`).
- **AssetPrice**: `store`/`update` (+ update-not-found), `sort=type`, multi-asset
  chart end-date and unknown-asset branches.
- **PortfolioAsset**: chart-helper branches — closed positions (`end_dt != 9999`),
  fund-filtered single/multi charts, the >8-asset skip, and empty/unknown returns.

Also fixed a latent bug found while covering `show_diff`/`announce`:
`TradePortfolioExt::previous()` queried the base `TradePortfolio`, so
`createDiffAPIResponse()` fataled when it called the Ext-only
`annotateTotalShares()` on the result — making both routes unreachable (hence
uncovered). It now returns `TradePortfolioExt`. Hardened `AssetApiTest`
create/update against an order-dependent flake (`faker->word` can yield a 1-letter
`type` that fails the Asset `type` validation regex).

Remaining uncovered lines are dead / unreachable-via-HTTP: `TradePortfolioControllerExt::create()`
(shadowed by the `tradePortfolios.create` → `createWithParams` route) and the
`showRebalance`/`showAsOf` default-date branches (no route supplies null dates).

## Phase 2 status (2026-05-24, issue #23) — DONE

As in Phases 1 and 3, the Phase 2 "before" percentages in the plan/ticket were
badly stale — prior sessions had already covered most targets (the report
controllers via `AccountReportControllerTest` / `FundReportControllerExtTest`,
the API controller via `AccountReportApiTest`, the data-aggregation tree via
`PDFTest`, both traits via `FundTraitTest` / `AccountTraitTest`, and the mail
classes via `FundReportEmailTest` / `TransactionEmailTest`). Measured line/method
coverage (pcov, full suite) before/after this pass:

| Class | Lines before | Lines after | Methods before | Methods after |
|-------|-------------:|------------:|---------------:|--------------:|
| `WebV1\FundReportControllerExt` | 64.7% | **98.0%** | 66.7% | **88.9%** |
| `Traits\AccountPDF` | 41.2% | **100.0%** | 50.0% | **100.0%** |
| `Traits\AccountTrait` | 96.4% | **99.4%** | 90.9% | 90.9% |

Already ≥50% before this pass (no work needed; the ticket's low numbers were
stale): `FundTrait` 95.8%, `WebV1\FundControllerExt` 95.8%, `Traits\FundPDF`
98.8%, `WebV1\AccountReportControllerExt` 100%, `APIv1\AccountReportAPIControllerExt`
100%, `Models\TradePortfolioItem(Ext)` 100%, `Mail\TransactionEmail` 100%,
`Mail\FundReportEmail` 100%.

Added gap tests:
- **FundReportControllerExt** (`FundReportControllerExtAdditionalTest`): the
  write/dispatch paths the base suite didn't reach — `store` (happy + template +
  the no-email catch branch), `update` (happy + template + not-found), `resend`
  (happy + template-guard + not-found), and the `destroy` happy path.
- **Account report email-send** (`AccountReportEmailSendTest`): drives
  `sendAccountReport → createAccountViewData → new AccountPDF → accountEmailReport`
  under `Mail::fake()` — the happy path (with a trade portfolio so
  `AccountPDF::createPortfolioComparisonGraph` renders instead of early-returning)
  and the no-email branch.

Also fixed a latent bug found while covering `store`: `FundReportControllerExt`
caught `Exception` without importing it, so the catch resolved to a nonexistent
`App\Http\Controllers\WebV1\Exception` and a thrown `\Exception` (e.g. the
no-email validation in `createFundReport`) would fatal with "class not found"
instead of flashing the error. Added `use Exception;`.

The fund-report email path itself (`FundTrait::sendFundReport` → `fundEmailReport`)
was deliberately left to the existing `FundReportTest::testEmail`, which already
asserts it with `Mail::fake()`; a duplicate suite was written and then removed.

Full suite in the testpool slot: 2280 tests, 79 failures (the known stale-baseline
set tracked by #55 — byte-identical before/after, i.e. zero regressions).

## Current State (original snapshot — stale, see Phase 1 status above)
- **Overall Coverage**: 55.67% lines (5338/9588)
- **Classes Covered**: 46.11% (172/373)
- **Methods Covered**: 55.83% (785/1406)

## Worst Coverage Areas (Priority Order)

### Critical - Below 15% Line Coverage

| Class | Methods | Lines | LOC |
|-------|---------|-------|-----|
| `WebV1\FundControllerExt` | 0% (0/7) | 3.12% | 160 |
| `WebV1\AccountMatchingRuleControllerExt` | 25% (1/4) | 3.03% | 33 |
| `WebV1\PortfolioAssetControllerExt` | 13% (2/15) | 7.48% | 321 |
| `Traits\TransactionTrait` | 25% (1/4) | 9.09% | 55 |
| `WebV1\OperationsController` | 20% (1/5) | 10.53% | 95 |
| `ScheduledJobController` | 11% (1/9) | 11.76% | 51 |
| `MatchingRuleController` | 25% (2/8) | 12.12% | 33 |
| `TradePortfolioController` | 30% (3/10) | 15.00% | 60 |

### High Priority - 15-30% Line Coverage

| Class | Methods | Lines | LOC |
|-------|---------|-------|-----|
| `FundReportController` | 12.5% (1/8) | 17.65% | 34 |
| `WebV1\FundReportControllerExt` | 29% (2/7) | 19.05% | 42 |
| `AssetPriceController` | 12.5% (1/8) | 20.93% | 43 |
| `PortfolioAssetController` | 12.5% (1/8) | 21.43% | 42 |
| `WebV1\AccountReportControllerExt` | 14% (1/7) | 21.21% | 66 |
| `PersonController` | 33% (3/9) | 23.61% | 72 |
| `WebV1\TradePortfolioControllerExt` | 33% (3/9) | 24.55% | 110 |
| `Traits\FundTrait` | 23% (3/13) | 28.10% | 242 |

## Recommended Integration Test Plan

### Phase 1: Core Business Logic (High Impact)

#### 1.1 Transaction System Integration Tests
**File**: `tests/Feature/TransactionIntegrationTest.php`

Coverage targets:
- `TransactionTrait` - transaction creation workflow
- `TransactionController` - CRUD operations
- `MatchingRuleController` - matching rule application
- `AccountMatchingRuleControllerExt` - rule assignments

Test scenarios:
- [ ] Create deposit transaction with matching
- [ ] Create withdrawal transaction
- [ ] Transaction preview with available matching calculation
- [ ] Transaction approval/rejection workflow
- [ ] Bulk transaction creation
- [ ] Transaction email notifications

#### 1.2 Scheduled Jobs Integration Tests
**File**: `tests/Feature/ScheduledJobIntegrationTest.php`

Coverage targets:
- `ScheduledJobController` - job management
- `OperationsController` - job execution
- `ScheduledJobTrait` - handler dispatch

Test scenarios:
- [ ] Create/edit/delete scheduled jobs
- [ ] Run fund report job
- [ ] Run matching reminder job
- [ ] Run trade band report job
- [ ] Job execution via Operations page
- [ ] Job scheduling logic (shouldRunBy)

### Phase 2: Reporting System

#### 2.1 Fund Reports Integration Tests
**File**: `tests/Feature/FundReportIntegrationTest.php`

Coverage targets:
- `FundControllerExt` - fund operations
- `FundReportController` - report generation
- `FundReportControllerExt` - extended reports
- `FundTrait` - fund calculations
- `FundPDF` - PDF generation

Test scenarios:
- [ ] Generate fund report (HTML)
- [ ] Generate fund report (PDF)
- [ ] Fund NAV calculation
- [ ] Fund share price history
- [ ] Fund performance metrics

#### 2.2 Account Reports Integration Tests
**File**: `tests/Feature/AccountReportIntegrationTest.php`

Coverage targets:
- `AccountReportController`
- `AccountReportControllerExt`
- `AccountPDF`
- `AccountTrait`

Test scenarios:
- [ ] Generate account statement
- [ ] Account balance history
- [ ] Account goal tracking
- [ ] Account matching summary

### Phase 3: Portfolio Management

#### 3.1 Trade Portfolio Integration Tests
**File**: `tests/Feature/TradePortfolioIntegrationTest.php`

Coverage targets:
- `TradePortfolioController`
- `TradePortfolioControllerExt`
- `TradePortfolioItemController`
- `TradeBandReportController`

Test scenarios:
- [ ] Create trade portfolio
- [ ] Add/remove portfolio items
- [ ] Portfolio rebalancing
- [ ] Trade band report generation

#### 3.2 Asset Management Integration Tests
**File**: `tests/Feature/AssetManagementIntegrationTest.php`

Coverage targets:
- `PortfolioAssetController`
- `PortfolioAssetControllerExt`
- `AssetPriceController`

Test scenarios:
- [ ] Asset price history
- [ ] Portfolio asset allocation
- [ ] Asset position calculations

### Phase 4: User & Access Control

#### 4.1 User Role Integration Tests
**File**: `tests/Feature/UserRoleIntegrationTest.php`

Coverage targets:
- `UserRoleController`
- `SetFundPermissions` middleware
- `AuthorizationService`

Test scenarios:
- [ ] Role-based access control
- [ ] Fund-specific permissions
- [ ] Admin vs user capabilities

## Test Infrastructure Recommendations

### 1. Improve DataFactory

Add methods for common test scenarios:
```php
// In tests/DataFactory.php
public function createFullTransactionScenario()  // Fund + Account + Transaction + Matching
public function createScheduledJobScenario()     // Schedule + Job + Handler requirements
public function createPortfolioScenario()        // Portfolio + Assets + Prices
```

### 2. Add Test Helpers

```php
// In tests/TestCase.php or traits
protected function actingAsAdmin()              // Login as admin user
protected function actingAsFundManager($fund)   // Login with fund permissions
protected function createAndApproveTransaction($data)
protected function runScheduledJob($job)
```

### 3. Mock External Services

```php
// For PDF generation
$this->mock(SnappyPdfWrapper::class)->shouldReceive('generate')->andReturn('pdf-content');

// For email
Mail::fake();

// For QuickChart
$this->mock(QuickChartService::class)->shouldReceive('generateChart')->andReturn('chart-url');
```

## Success Metrics

### Target Coverage by Phase

| Phase | Target Line Coverage |
|-------|---------------------|
| After Phase 1 | 65% |
| After Phase 2 | 72% |
| After Phase 3 | 78% |
| After Phase 4 | 82% |

### Files to Reach 80%+ Coverage

Priority files that would most impact overall coverage:
1. `FundControllerExt` (160 LOC) - currently 3%
2. `PortfolioAssetControllerExt` (321 LOC) - currently 7%
3. `FundTrait` (242 LOC) - currently 28%
4. `TradePortfolioControllerExt` (110 LOC) - currently 25%
5. `OperationsController` (95 LOC) - currently 11%

## Implementation Order

1. **Start with existing patterns**: Follow the style of existing tests like `TransactionPreviewCalculationTest.php` and `MatchingReminderTest.php`

2. **Use DatabaseTransactions**: All integration tests should use the `DatabaseTransactions` trait

3. **Leverage DataFactory**: Use and extend `DataFactory` for test data creation

4. **Test happy paths first**: Cover successful scenarios before edge cases

5. **Add assertions for both UI and API**: Test that views render correctly and API responses are correct
