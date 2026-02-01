# Test Coverage Improvement Plan

## Current State
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
