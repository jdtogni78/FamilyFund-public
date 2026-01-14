# Test Coverage Improvement Tracking

**Generated:** 2026-01-13 23:54:35
**Overall Coverage:** 63.48% lines (6088/9591)
**Target:** Bring all files to 50%+ coverage

## Summary

- **Total files <50%:** 33
- **Total uncovered lines:** 1507
- **Priority files (>100 uncovered):** 3

## Files Under 50% Coverage (Sorted by Coverage %)

| # | File | Lines | Methods | Uncovered | Priority |
|---|------|-------|---------|-----------|----------|
| 1 | `TradeBandReportTrait` | 9.1% (5/55) | 25.0% (1/4) | 50 | 🟢 LOW |
| 2 | `ScheduledJobController` | 11.8% (6/51) | 11.1% (1/9) | 45 | 🟢 LOW |
| 3 | `IdDocumentController` | 12.1% (4/33) | 25.0% (2/8) | 29 | 🟢 LOW |
| 4 | `AddressController` | 15.2% (5/33) | 37.5% (3/8) | 28 | 🟢 LOW |
| 5 | `AssetChangeLogController` | 15.2% (5/33) | 37.5% (3/8) | 28 | 🟢 LOW |
| 6 | `ChangeLogController` | 15.2% (5/33) | 37.5% (3/8) | 28 | 🟢 LOW |
| 7 | `PhoneController` | 15.2% (5/33) | 37.5% (3/8) | 28 | 🟢 LOW |
| 8 | `FundReportController` | 17.6% (6/34) | 12.5% (1/8) | 28 | 🟢 LOW |
| 9 | `AssetPriceController` | 20.9% (9/43) | 12.5% (1/8) | 34 | 🟢 LOW |
| 10 | `PersonController` | 23.6% (17/72) | 33.3% (3/9) | 55 | 🟡 MED |
| 11 | `ScheduledJobControllerExt` | 24.6% (27/110) | 33.3% (3/9) | 83 | 🟡 MED |
| 12 | `OperationsController` | 27.1% (87/321) | 13.3% (2/15) | 234 | 🔴 HIGH |
| 13 | `CashDepositControllerExt` | 27.3% (15/55) | 55.6% (5/9) | 40 | 🟢 LOW |
| 14 | `TransactionControllerExt` | 27.4% (71/259) | 28.6% (4/14) | 188 | 🔴 HIGH |
| 15 | `FundPDF` | 28.1% (68/242) | 23.1% (3/13) | 174 | 🔴 HIGH |
| 16 | `AccountReportController` | 30.3% (10/33) | 25.0% (2/8) | 23 | 🟢 LOW |
| 17 | `UserController` | 32.4% (11/34) | 37.5% (3/8) | 23 | 🟢 LOW |
| 18 | `TransactionController` | 34.3% (12/35) | 12.5% (1/8) | 23 | 🟢 LOW |
| 19 | `AccountAPIControllerExt` | 35.2% (19/54) | 25.0% (2/8) | 35 | 🟢 LOW |
| 20 | `AccountPDF` | 41.2% (14/34) | 50.0% (1/2) | 20 | 🟢 LOW |
| 21 | `AssetPriceControllerExt` | 42.5% (65/153) | 33.3% (3/9) | 88 | 🟡 MED |
| 22 | `FundReportControllerExt` | 42.9% (24/56) | 37.5% (3/8) | 32 | 🟢 LOW |
| 23 | `TradePortfolioController` | 44.1% (15/34) | 50.0% (4/8) | 19 | 🟢 LOW |
| 24 | `DepositRequestController` | 45.5% (15/33) | 25.0% (2/8) | 18 | 🟢 LOW |
| 25 | `GoalControllerExt` | 46.9% (15/32) | 71.4% (5/7) | 17 | 🟢 LOW |
| 26 | `TradePortfolioItemController` | 47.1% (16/34) | 25.0% (2/8) | 18 | 🟢 LOW |
| 27 | `AccountBalanceController` | 48.5% (16/33) | 37.5% (3/8) | 17 | 🟢 LOW |
| 28 | `AccountGoalController` | 48.5% (16/33) | 37.5% (3/8) | 17 | 🟢 LOW |
| 29 | `AssetController` | 48.5% (16/33) | 37.5% (3/8) | 17 | 🟢 LOW |
| 30 | `CashDepositController` | 48.5% (16/33) | 37.5% (3/8) | 17 | 🟢 LOW |
| 31 | `GoalController` | 48.5% (16/33) | 37.5% (3/8) | 17 | 🟢 LOW |
| 32 | `ScheduleController` | 48.5% (16/33) | 37.5% (3/8) | 17 | 🟢 LOW |
| 33 | `TransactionMatchingController` | 48.5% (16/33) | 37.5% (3/8) | 17 | 🟢 LOW |

## Priority Order (Highest Impact First)

### 🔴 HIGH Priority (>100 uncovered lines)
1. OperationsController - 234 uncovered lines
2. TransactionControllerExt - 188 uncovered lines
3. FundPDF - 174 uncovered lines

### 🟡 MEDIUM Priority (50-100 uncovered lines)
4. AssetPriceControllerExt - 88 uncovered lines
5. ScheduledJobControllerExt - 83 uncovered lines
6. PersonController - 55 uncovered lines
7. TradeBandReportTrait - 50 uncovered lines

### 🟢 LOW Priority (<50 uncovered lines)
All remaining files - Total: 493 uncovered lines across 26 files

## Progress Tracking

### ✅ Completed
1. **OperationsController** - 27% → 55%+ (34 tests, all passing)
   - Added positive flow tests (26 tests)
   - Added negative/error tests (8 tests)
   - Covered: admin access, dashboard, job execution, queue management, email testing

2. **TransactionControllerExt** - 27% → 60%+ (34 tests, 26 passing + 7 risky + 1 skipped)
   - Added positive flow tests (24 tests)
   - Added comprehensive negative tests (10 tests)
   - Covered: CRUD operations, preview, bulk operations, validation, error handling

### 🔄 In Progress
- None

### 📋 Next Up
1. FundPDF (174 uncovered lines)
2. AssetPriceControllerExt (88 uncovered lines)
3. ScheduledJobControllerExt (83 uncovered lines)
4. PersonController (55 uncovered lines)

## Strategy

### Phase 1: HIGH Priority (Target: Add ~350 covered lines)
- Focus on the 3 files with >100 uncovered lines
- These represent 40% of all uncovered lines
- Bring each from ~27% to 50%+ coverage

### Phase 2: MEDIUM Priority (Target: Add ~150 covered lines)
- Cover 4 files with 50-100 uncovered lines
- Smaller effort per file but still meaningful impact

### Phase 3: LOW Priority (Bulk improvement)
- Many of these are simple CRUD controllers
- Can be improved with template/pattern-based tests
- Focus on the ones close to 50% (easiest wins)

## Test Coverage Details

### OperationsController - 34 tests
**Positive Tests (26):**
- Admin access control (3 tests)
- Dashboard display with filters (2 tests)
- Scheduled job execution (2 tests)
- Pending transaction processing (3 tests)
- Queue management (9 tests)
- Email testing (3 tests)
- Queue job display (2 tests)
- Operation logging (2 tests)

**Negative Tests (8):**
- Non-admin access denial (verified across all operations)
- Invalid email validation (5 edge cases)
- Error handling during job execution
- Invalid filter values handling
- Empty/invalid UUID handling
- Unauthenticated access protection

### TransactionControllerExt - 34 tests
**Positive Tests (24):**
- Create/Preview/Store (4 tests)
- Preview pending (2 tests)
- Process pending (3 tests)
- Edit transactions (2 tests)
- Clone transactions (2 tests)
- Resend email (3 tests)
- Bulk operations (6 tests)
- Validation (2 tests)

**Negative Tests (10):**
- Invalid transaction type
- Invalid status
- Missing required fields
- Invalid value (non-numeric)
- Exception from invalid account
- Store with bad data
- Future-dated transaction handling
- Empty account list validation
- Bulk validation failures (multiple scenarios)
- Partial failure handling

## Notes
- **Focus on meaningful tests**, not just line coverage
- Negative tests cover validation, error handling, and edge cases
- Skip purely generated code or trivial CRUD if already well-tested via integration tests
- Prioritize business logic and error handling paths
- Many files near 48-49% only need 1-2 more tests to hit 50%
