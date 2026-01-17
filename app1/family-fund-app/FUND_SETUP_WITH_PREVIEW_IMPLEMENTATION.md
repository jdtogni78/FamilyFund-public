# Fund Setup with Preview - Implementation Summary

## Overview

Implemented a new fund creation workflow that creates a complete fund setup (fund + account + portfolios + initial transaction) with a preview step, similar to how transaction creation works.

## Key User Requirement

> "Lets ask for initial balance in shares and value." - User wants to specify BOTH shares and value separately, not calculate one from the other.

## What Was Implemented

### 1. New Controller Methods

**File:** `app/Http/Controllers/FundController.php`

Added two new methods:
- `createWithSetup()` - Shows the fund setup form
- `storeWithSetup(CreateFundWithSetupRequest $request)` - Handles form submission with preview/confirm flow

**Changes:**
- Added `FundSetupTrait` usage
- Injected `TransactionRepository` dependency
- Added `CreateFundWithSetupRequest` import

### 2. FundSetupTrait

**File:** `app/Http/Controllers/Traits/FundSetupTrait.php` (NEW)

Core business logic for fund setup:

```php
protected function setupFund(array $input, bool $dry_run = false): array
```

**What it does:**
1. Creates fund using `fundRepository->create()`
2. Creates fund account with `user_id=null`
3. Creates portfolio(s) from source identifiers
4. Creates initial transaction using `TransactionTrait::createTransaction()`
   - Calls `processPending()` which:
     - Calculates account balances
     - Allocates shares
     - Creates account_balances record
     - Sends confirmation email
5. Returns all created entities for preview/confirmation

**Dry Run Mode:**
- When `$dry_run = true`, executes all logic within a database transaction
- After gathering all data, calls `DB::rollBack()` to undo changes
- Returns preview data showing what WOULD be created
- Used for preview step before actual creation

### 3. Validation Request

**File:** `app/Http/Requests/CreateFundWithSetupRequest.php` (NEW)

Validates all input fields:

**Fund fields:**
- `name` - required, max 30 chars
- `goal` - optional, max 1024 chars

**Account fields:**
- `account_nickname` - optional, max 100 chars

**Portfolio fields:**
- `portfolio_source` - required, can be string or array (supports multiple portfolios)
- Each source max 30 chars

**Transaction fields:**
- `create_initial_transaction` - boolean flag
- `initial_shares` - numeric, 0.00000001 to 9999999999999.9991
- `initial_value` - numeric, 0.01 to 99999999999.99
- `transaction_description` - optional, max 255 chars

**Preview field:**
- `preview` - boolean, controls preview vs actual creation

### 4. Form View

**File:** `resources/views/funds/create_with_setup.blade.php` (NEW)

Main form with four sections:

1. **Fund Information**
   - Name (required)
   - Goal (optional)

2. **Fund Account**
   - Account Nickname (optional, auto-generated if blank)

3. **Portfolio(s)**
   - Portfolio Source Identifier (required)
   - Supports single portfolio (future: could support multiple)

4. **Initial Transaction**
   - Checkbox to enable/disable transaction creation
   - **Initial Shares** - allows specifying exact share count
   - **Initial Value** - dollar amount for the transaction
   - Transaction Description

**Submit Buttons:**
- "Preview Setup" - submits with `preview=1` (dry run)
- "Create Fund" - submits with `preview=0` (actual creation)

**JavaScript:**
- Toggles transaction fields visibility based on checkbox state

### 5. Form Fields Include

**File:** `resources/views/funds/fields_with_setup.blade.php` (NEW)

Detailed form fields with:
- Bootstrap styling matching existing FamilyFund UI
- Font Awesome icons
- Help text for each field
- Informational alert explaining shares vs value behavior

**Key features:**
- Both `initial_shares` and `initial_value` fields (user's requirement)
- Explanation of how shares and value interact:
  - If both provided → share price calculated automatically
  - If only value → shares calculated from fund's share price
  - For new funds → minimal value + shares sets initial share price

### 6. Preview View

**File:** `resources/views/funds/preview_setup.blade.php` (NEW)

Shows preview of all entities that will be created:

**Preview Cards:**
1. **Fund** - name, goal
2. **Account** - nickname, code, user_id (null)
3. **Portfolio(s)** - table showing all portfolios with sources
4. **Transaction** - type, amount, shares, share price, description, timestamp
5. **Account Balance** - resulting balance, shares, share value

**Confirmation Form:**
- Hidden fields preserve all original input
- "Confirm & Create Fund" button - actual creation
- "Back to Edit" - returns to form with data
- "Cancel" - returns to funds index

### 7. Routes

**File:** `routes/web.php`

Added before the funds resource route:

```php
Route::get('funds/create-with-setup', 'App\Http\Controllers\WebV1\FundControllerExt@createWithSetup')
    ->name('funds.createWithSetup');
Route::post('funds/store-with-setup', 'App\Http\Controllers\WebV1\FundControllerExt@storeWithSetup')
    ->name('funds.storeWithSetup');
```

### 8. Updated FundControllerExt

**File:** `app/Http/Controllers/WebV1/FundControllerExt.php`

Updated constructor to accept and pass `TransactionRepository`:

```php
public function __construct(FundRepository $fundRepo, TransactionRepository $transactionRepo)
{
    parent::__construct($fundRepo, $transactionRepo);
}
```

## Transaction Processing

### How processPending() Works

When the initial transaction is created, it goes through the full transaction processing:

1. **Transaction created** with:
   - `type = 'INI'` (TYPE_INITIAL)
   - `amount = initial_value`
   - `shares = initial_shares` (if provided)
   - `account_id = fund account ID`

2. **TransactionTrait::createTransaction()** called:
   - Wraps in `DB::beginTransaction()`
   - Creates transaction record via repository
   - Calls `processTransaction()` which calls `transaction->processPending()`
   - Commits transaction (or rollback if dry_run)

3. **processPending()** does:
   - Validates transaction against current state
   - Calculates new account balance
   - Creates `account_balances` record with:
     - balance = amount
     - shares = shares (or calculated)
     - share_value = balance / shares
   - Allocates shares to account
   - Updates fund's unallocated shares
   - May apply matching rules (not applicable for initial transactions)
   - Sends confirmation email (if not dry_run)

## User Flow

### Step 1: Create Form
1. Navigate to Funds → Create with Setup
2. Fill in fund name, goal
3. Optionally customize account nickname
4. Enter portfolio source identifier
5. Optionally:
   - Enter initial shares
   - Enter initial value
   - Customize transaction description

### Step 2: Preview
1. Click "Preview Setup"
2. System calls `setupFund($input, dry_run=true)`
3. All entities created in database
4. Preview data collected
5. Database transaction rolled back
6. Preview page shown with:
   - Fund details
   - Account details
   - Portfolio(s) list
   - Transaction details
   - Resulting account balance

### Step 3: Confirm
1. Click "Confirm & Create Fund"
2. System calls `setupFund($input, dry_run=false)`
3. All entities created again
4. Transaction processed via `processPending()`
5. Database transaction committed
6. Success message shown
7. Redirect to fund show page

## Shares and Value Handling

### Scenario 1: Both Shares and Value Provided

**Input:**
- initial_shares: 1000
- initial_value: 1000.00

**Result:**
- Transaction amount: $1000.00
- Transaction shares: 1000
- Share price: $1000.00 / 1000 = $1.00
- Account balance: $1000.00
- Account shares: 1000
- Account share value: $1.00

### Scenario 2: Only Value Provided

**Input:**
- initial_shares: (empty)
- initial_value: 0.01

**Result:**
- Transaction amount: $0.01
- Transaction shares: calculated by `processPending()` based on fund's share price
- For new fund: may default to 1 share or calculate based on previous fund state

### Scenario 3: Minimal Setup (New Fund)

**Input:**
- initial_shares: 1
- initial_value: 0.01

**Result:**
- Transaction amount: $0.01
- Transaction shares: 1
- Share price: $0.01 / 1 = $0.01
- Sets initial share price for the fund

## Files Created

1. `app/Http/Controllers/Traits/FundSetupTrait.php`
2. `app/Http/Requests/CreateFundWithSetupRequest.php`
3. `resources/views/funds/create_with_setup.blade.php`
4. `resources/views/funds/fields_with_setup.blade.php`
5. `resources/views/funds/preview_setup.blade.php`

## Files Modified

1. `app/Http/Controllers/FundController.php`
   - Added FundSetupTrait usage
   - Added TransactionRepository injection
   - Added createWithSetup() method
   - Added storeWithSetup() method

2. `app/Http/Controllers/WebV1/FundControllerExt.php`
   - Updated constructor to inject and pass TransactionRepository

3. `routes/web.php`
   - Added funds/create-with-setup route
   - Added funds/store-with-setup route

## Testing the Implementation

### Manual Testing Steps

1. **Navigate to the form:**
   ```
   http://localhost:3001/dev-login/funds/create-with-setup
   ```

2. **Fill in the form:**
   - Name: "Monarch Consolidated"
   - Goal: "Consolidated view of all Monarch accounts"
   - Portfolio Source: "MONARCH_IBKR_XXXX"
   - Initial Shares: 1
   - Initial Value: 0.01

3. **Click "Preview Setup"**
   - Verify preview shows all entities
   - Verify no records created in database yet

4. **Click "Confirm & Create Fund"**
   - Verify success message
   - Verify fund created
   - Verify account created (user_id=null)
   - Verify portfolio created
   - Verify transaction created and processed
   - Verify account balance created

### Database Verification

```sql
-- Check fund
SELECT * FROM funds WHERE name = 'Monarch Consolidated';

-- Check account (should have user_id=null)
SELECT * FROM accounts WHERE fund_id = [fund_id] AND user_id IS NULL;

-- Check portfolio
SELECT * FROM portfolios WHERE fund_id = [fund_id] AND source = 'MONARCH_IBKR_XXXX';

-- Check transaction
SELECT * FROM transactions WHERE account_id = [account_id] AND type = 'INI';

-- Check account balance
SELECT * FROM account_balances WHERE account_id = [account_id];
```

## Next Steps

### For Monarch Consolidated Setup

Once this implementation is tested and working:

1. Navigate to Funds → Create with Setup
2. Fill in:
   - Name: "Monarch Consolidated"
   - Goal: "Consolidated view of all investment accounts from Monarch Money"
   - Portfolio Source: "MONARCH_FIDE_XXXX" (first of 16)
   - Initial Shares: 1
   - Initial Value: 0.01
3. Preview and confirm
4. Manually create remaining 15 portfolios via Portfolios → Create
   - Or modify form to support array of portfolio sources

### Future Enhancements

1. **Multiple portfolios in one form:**
   - Update form to support array of portfolio source inputs
   - Add JavaScript to dynamically add/remove portfolio fields
   - Update validation to handle portfolio_source as array

2. **Skip initial transaction option:**
   - Make transaction creation truly optional
   - Handle case where fund has no initial balance

3. **Custom share price calculation:**
   - Allow specifying share price directly
   - Calculate shares or value from the other two fields

## Architecture Notes

### Why This Approach?

1. **Reuses existing logic:**
   - Uses `TransactionTrait::createTransaction()`
   - Uses `processPending()` for full transaction processing
   - Ensures consistency with manual transaction creation

2. **Preview pattern:**
   - Follows established pattern from transaction creation
   - Uses `dry_run` flag with DB rollback
   - Provides user confidence before committing

3. **Extensible:**
   - FundSetupTrait can be used elsewhere
   - Validation request is reusable
   - Form can be extended for more complex scenarios

### Differences from Manual Setup

**Manual UI flow (README):**
1. Create account (separate page)
2. Create fund (separate page)
3. Create portfolio (separate page)
4. Create transaction (separate page)

**New setup flow:**
1. One form with all fields
2. Preview step shows everything
3. Confirm creates all entities atomically
4. Transaction is fully processed automatically

**Benefits:**
- Faster setup (4 pages → 1 page)
- Less error-prone (all required fields in one place)
- Atomic operation (all or nothing)
- Preview reduces risk of mistakes

## Related Documentation

- `/Users/dtogni/dev/FamilyFund/FUND_SETUP_GUIDE.md` - Generic fund setup guide
- `/Users/dtogni/dev/finex/FAMILYFUND_UI_SETUP_GUIDE.md` - Monarch-specific UI setup guide
- `/Users/dtogni/dev/FamilyFund/API_VS_WEB_LOGIC_ANALYSIS.md` - Analysis of API vs Web logic
- `/Users/dtogni/dev/finex/setup_familyfund_via_api.py` - API-based setup script

## Summary

Implemented a complete fund setup workflow with:
- ✅ Single form for fund, account, portfolio, and transaction
- ✅ Preview step before creation (dry run pattern)
- ✅ Support for both shares AND value input (user requirement)
- ✅ Full transaction processing via `processPending()`
- ✅ Atomic creation (all entities created together)
- ✅ Bootstrap-styled UI matching existing FamilyFund design
- ✅ Comprehensive validation
- ✅ Informative preview showing all entities

This addresses the user's goal: **"create fund, account, initial trans, account balance, initial trans all in one to reduce the burden"** with the added requirement to **"ask for initial balance in shares and value"**.
