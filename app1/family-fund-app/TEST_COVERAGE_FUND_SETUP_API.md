# Test Coverage: Fund Setup API

Complete test coverage for the `POST /api/funds/setup` endpoint.

---

## Overview

**Endpoint:** `POST /api/funds/setup`

**Purpose:** Create fund with complete setup (account, portfolio, initial transaction) via REST API with dry run/preview support.

**Test Files:**
1. `tests/Feature/FundSetupAPITest.php` - 32 tests
2. `tests/Feature/FundSetupAPISequentialTest.php` - 10 tests

**Total Coverage:** 42 API tests

---

## Test File 1: FundSetupAPITest.php (32 tests)

Basic API functionality tests covering all scenarios.

### Basic Creation Tests (2 tests)

#### test_creates_fund_via_api_with_minimal_data
**What:** Creates fund with only required fields
**Input:**
- name: "API Test Fund"
- portfolio_source: "API_TEST"

**Verifies:**
- HTTP 200 response
- Response structure (fund, account, portfolios, transaction, account_balance)
- Fund created in database

#### test_creates_fund_via_api_with_complete_data
**What:** Creates fund with all optional fields
**Input:**
- name: "Complete API Fund"
- goal: "Testing complete fund creation via API"
- portfolio_source: "COMPLETE_API"
- account_nickname: "Custom API Account"
- create_initial_transaction: true
- initial_shares: 1000
- initial_value: 5000.00
- transaction_description: "API initial setup"

**Verifies:**
- All fields in response match input
- Account balance: $5000.00
- Shares: 1000
- Share value: $5.00

### Dry Run (Preview) Tests (2 tests)

#### test_dry_run_mode_does_not_create_entities
**What:** Verifies preview mode doesn't persist to database
**Input:**
- name: "Dry Run Fund"
- portfolio_source: "DRY_RUN"
- dry_run: true

**Verifies:**
- Response has dry_run: true
- Message: "Fund setup preview generated successfully"
- No funds, accounts, or portfolios created in database

#### test_dry_run_returns_all_entity_data
**What:** Verifies preview returns complete data structure
**Input:**
- Full fund setup with dry_run: true

**Verifies:**
- Response includes fund, account, portfolios, transaction, account_balance
- Calculations correct (share price = value ÷ shares)
- dry_run flag present in response

### Validation Tests (4 tests)

#### test_validates_required_fields
**What:** Empty request should fail validation
**Verifies:**
- HTTP 422
- Errors for 'name' and 'portfolio_source'

#### test_validates_name_max_length
**What:** Name exceeding 30 characters should fail
**Input:** name with 31 characters
**Verifies:** Validation error for 'name'

#### test_validates_initial_shares_minimum
**What:** Shares must be > 0
**Input:** initial_shares: 0
**Verifies:** Validation error

#### test_validates_initial_value_minimum
**What:** Value must be >= 0.01
**Input:** initial_value: 0
**Verifies:** Validation error

### Multiple Portfolio Tests (1 test)

#### test_creates_fund_with_multiple_portfolios
**What:** Array of portfolio sources
**Input:**
- portfolio_source: ["PORTFOLIO_A", "PORTFOLIO_B", "PORTFOLIO_C"]

**Verifies:**
- Response has 3 portfolios
- Each source present in response
- Database has 3 portfolio records

### Transaction Tests (3 tests)

#### test_creates_fund_without_initial_transaction
**What:** Skip transaction creation
**Input:**
- create_initial_transaction: false

**Verifies:**
- Response has NO 'transaction' key
- Response has NO 'account_balance' key

#### test_creates_transaction_with_only_value
**What:** Provide value, let system calculate shares
**Input:**
- initial_value: 100.00
- (no initial_shares)

**Verifies:**
- Transaction amount: 100.00
- Shares calculated by system

#### test_creates_transaction_with_shares_and_value
**What:** Provide both shares and value
**Input:**
- initial_shares: 250
- initial_value: 1000.00

**Verifies:**
- Transaction amount: 1000.00
- Transaction shares: 250
- Share value: 4.00

### Account Tests (4 tests)

#### test_creates_account_with_auto_generated_nickname
**What:** No account_nickname provided
**Verifies:** nickname = "{fund_name} Fund Account"

#### test_creates_account_with_custom_nickname
**What:** Custom account_nickname provided
**Verifies:** nickname matches input

#### test_account_has_null_user_id
**What:** Fund account always has user_id = null
**Verifies:** user_id is null in response and database

#### test_account_code_matches_fund_id
**What:** Account code is "F{fund_id}"
**Verifies:** code = "F1", "F2", etc.

### Precision Tests (1 test)

#### test_preserves_high_precision_shares
**What:** Shares with 8 decimal places preserved
**Input:** initial_shares: 123.45678901
**Verifies:** Response has exact value

### Edge Cases (2 tests)

#### test_creates_fund_with_minimal_initial_value
**What:** Minimum allowed value
**Input:**
- initial_shares: 1
- initial_value: 0.01

**Verifies:**
- Share value: 0.01
- All entities created correctly

#### test_creates_fund_with_fractional_shares
**What:** Fractional share count
**Input:**
- initial_shares: 0.5
- initial_value: 50.00

**Verifies:**
- Share value: 100.00

### Sequential API Calls (1 test)

#### test_creates_multiple_funds_sequentially_via_api
**What:** Create 3 funds in loop
**Verifies:** All 3 funds created in database

### Preview Then Create Pattern (1 test)

#### test_preview_then_create_workflow
**What:** Preview with dry_run: true, then create with dry_run: false
**Verifies:**
- Preview doesn't create entities
- Create does persist entities
- Same input produces expected output

### Error Handling (1 test)

#### test_returns_error_on_exception
**What:** Invalid input causes exception
**Verifies:**
- Response has success: false
- Error message included

---

## Test File 2: FundSetupAPISequentialTest.php (10 tests)

Realistic scenarios and complex workflows via API.

### test_creates_fund_with_16_portfolios_via_api
**What:** Monarch scenario - create fund with 16 portfolios in one call
**Input:**
- portfolio_source: [array of 16 Monarch portfolio sources]

**Verifies:**
- Response has 16 portfolios
- Each source present
- Database has 16 portfolio records

### test_creates_multiple_funds_sequentially_via_api
**What:** Create 5 funds with different configurations
**Input:**
- 5 funds with varying shares and values

**Verifies:**
- All 5 funds created
- Each has correct account, portfolio structure

### test_batch_preview_then_selective_create_via_api
**What:** Preview 5 funds, create only 3 (A, C, E)
**Steps:**
1. Preview all 5 with dry_run: true
2. Create only selected ones with dry_run: false

**Verifies:**
- Previews don't create entities
- Only selected funds created

### test_various_share_price_scenarios_via_api
**What:** Different share price calculations
**Scenarios:**
- Penny stock: 10000 shares @ $100 = $0.01/share
- Standard: 1000 shares @ $1000 = $1.00/share
- High value: 100 shares @ $10000 = $100.00/share
- Fractional: 0.5 shares @ $50 = $100.00/share
- Precision: 123.45678901 shares @ $1234.56 = $10.00/share

**Verifies:** Each scenario calculates correct share price

### test_creates_fund_then_adds_portfolios_via_api
**What:** Create fund with 1 portfolio, then add 10 more
**Steps:**
1. POST /api/funds/setup with 1 portfolio
2. Loop: Create 10 more portfolios directly

**Verifies:** Total 11 portfolios for fund

### test_creates_fund_alongside_existing_datafactory_fund_via_api
**What:** Integration with DataFactory
**Steps:**
1. Create fund via DataFactory
2. Create fund via API

**Verifies:**
- Both funds exist
- Funds are independent

### test_creates_fund_then_multiple_transactions_via_api
**What:** Create fund, then add transactions
**Steps:**
1. Create fund via API
2. Add 3 transactions via DataFactory

**Verifies:** Total 4 transactions (1 initial + 3 additional)

### test_simulates_automated_fund_creation_script
**What:** Simulate batch creation script
**Input:**
- 3 funds with different portfolio counts

**Verifies:**
- All funds created
- Correct number of portfolios per fund

### test_preview_multiple_create_best_option_via_api
**What:** Preview 3 options, create 1
**Steps:**
1. Preview Option A, B, C with dry_run: true
2. Create only Option B

**Verifies:**
- Previews complete without creating
- Only Option B created

---

## API Request/Response Examples

### Minimal Request
```json
POST /api/funds/setup
{
  "name": "My Fund",
  "portfolio_source": "MY_PORTFOLIO"
}
```

### Complete Request
```json
POST /api/funds/setup
{
  "name": "Complete Fund",
  "goal": "Investment tracking",
  "portfolio_source": "COMPLETE_PORTFOLIO",
  "account_nickname": "Custom Account",
  "create_initial_transaction": true,
  "initial_shares": 1000,
  "initial_value": 5000.00,
  "transaction_description": "Initial setup",
  "dry_run": false
}
```

### Multiple Portfolios Request
```json
POST /api/funds/setup
{
  "name": "Multi Portfolio Fund",
  "portfolio_source": ["PORT_1", "PORT_2", "PORT_3"]
}
```

### Preview Request
```json
POST /api/funds/setup
{
  "name": "Preview Fund",
  "portfolio_source": "PREVIEW",
  "dry_run": true
}
```

### Success Response Structure
```json
{
  "success": true,
  "data": {
    "fund": {
      "id": 1,
      "name": "...",
      "goal": "..."
    },
    "account": {
      "id": 1,
      "fund_id": 1,
      "user_id": null,
      "nickname": "...",
      "code": "F1"
    },
    "portfolios": [
      {
        "id": 1,
        "fund_id": 1,
        "source": "..."
      }
    ],
    "transaction": {
      "id": 1,
      "account_id": 1,
      "fund_id": 1,
      "type": "INI",
      "amount": 5000.00,
      "shares": 1000,
      "description": "...",
      "timestamp": "..."
    },
    "account_balance": {
      "balance": 5000.00,
      "shares": 1000,
      "share_value": 5.00
    }
  },
  "message": "Fund created successfully with account, portfolio, and initial transaction"
}
```

### Preview Response (dry_run: true)
```json
{
  "success": true,
  "data": {
    ... (same structure as above) ...,
    "dry_run": true,
    "note": "Preview mode - no changes were saved to database"
  },
  "message": "Fund setup preview generated successfully"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Fund setup failed: [error details]"
}
```

### Validation Error Response
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["Fund name is required"],
    "portfolio_source": ["Portfolio source is required"]
  }
}
```

---

## Coverage Summary

### By Feature
- ✅ Basic fund creation
- ✅ Complete fund creation with all fields
- ✅ Dry run / preview mode
- ✅ Multiple portfolios (array input)
- ✅ Transaction creation (with/without)
- ✅ Shares and value specification
- ✅ Account nickname (auto/custom)
- ✅ High precision shares
- ✅ Edge cases (minimal value, fractional shares)
- ✅ Sequential creation
- ✅ Batch preview then selective create
- ✅ Integration with DataFactory
- ✅ Error handling
- ✅ Validation (required fields, min/max values)

### By Test Type
- **Unit-level:** 32 tests (basic functionality)
- **Integration:** 10 tests (realistic scenarios)
- **Total:** 42 tests

### By HTTP Method
- POST /api/funds/setup - All 42 tests

### Response Types Tested
- Success (200 + success: true)
- Validation error (422)
- Exception error (200 + success: false)

---

## Test Execution

### Run all API tests
```bash
cd app1/family-fund-app
php artisan test --filter=FundSetupAPI
```

### Run basic API tests only
```bash
php artisan test tests/Feature/FundSetupAPITest.php
```

### Run sequential API tests only
```bash
php artisan test tests/Feature/FundSetupAPISequentialTest.php
```

### Run specific test
```bash
php artisan test --filter=test_creates_fund_via_api_with_minimal_data
```

---

## Comparison: Web UI vs API

| Feature | Web UI Tests | API Tests |
|---------|-------------|-----------|
| Basic creation | ✅ 27 tests | ✅ 32 tests |
| Sequential scenarios | ✅ 10 tests | ✅ 10 tests |
| Workflow integration | ✅ 17 tests | N/A (API only) |
| Trait unit tests | ✅ 23 tests | Shared |
| Request validation | ✅ 41 tests | ✅ 4 tests (in feature) |
| **Total** | **118 tests** | **42 tests** |

**Shared Logic:**
- Both use `FundSetupTrait::setupFund()`
- Identical dry run behavior
- Same transaction processing (TransactionTrait)
- Same database structure

**Differences:**
- Web UI: Returns HTML views with preview page
- API: Returns JSON with dry_run flag
- Web UI: Session-based, CSRF protected
- API: Stateless, token-based (if auth enabled)

---

## Related Files

### Implementation
- `app/Http/Controllers/APIv1/FundAPIControllerExt.php` - API endpoint
- `app/Http/Controllers/Traits/FundSetupTrait.php` - Shared logic
- `app/Http/Requests/API/CreateFundWithSetupAPIRequest.php` - Validation

### Tests
- `tests/Feature/FundSetupAPITest.php` - Basic tests
- `tests/Feature/FundSetupAPISequentialTest.php` - Sequential tests

### Routes
- `routes/api.php` - POST /api/funds/setup

### Documentation
- `FUND_CREATION_GUIDE.md` - User-facing guide
- `TEST_COVERAGE_FUND_SETUP.md` - Web UI test coverage
- `TEST_COVERAGE_FUND_SETUP_API.md` - This file

---

**Last Updated:** January 17, 2025
**API Version:** 1.0
**Total Test Coverage:** 42 API tests + 118 Web UI tests = **160 total tests**
