# FamilyFund Transaction System

This document explains how transactions work in FamilyFund, including transaction types, flags, cash handling, and balance calculations.

## Overview

FamilyFund uses a **share-based accounting system** similar to mutual funds. Each transaction creates or destroys shares in a beneficiary's account relative to the fund's total shares. The share price (NAV per share) is calculated from the fund's total portfolio value divided by total shares outstanding.

### Key Concepts

- **Fund**: An investment fund with one or more portfolios
- **Fund Account**: A special account (`user_id = null`) representing the fund's total shares
- **Beneficiary Account**: An account owned by a user, holding a portion of fund shares
- **Share Value**: `Fund Value / Fund Shares` (NAV per share)
- **AccountBalance**: A time-ranged record of an account's share holdings

## Transaction Types

| Code | Name | Description |
|------|------|-------------|
| `PUR` | Purchase | Beneficiary deposits money, receives fund shares |
| `SAL` | Sale | Beneficiary withdraws money, returns fund shares |
| `INI` | Initial | Initial fund setup transaction (requires shares specified) |
| `MAT` | Matching | Auto-created when matching rules apply to a deposit |
| `BOR` | Borrow | Loan against account (creates negative balance type) |
| `REP` | Repay | Repayment of borrowed amount |

### Transaction Type Details

#### PUR (Purchase) - Deposits
- **Use case**: Beneficiary deposits cash into their account
- **Effect**: Creates new shares based on `value / share_value`
- **For Fund Accounts**: Requires flag `A` (add cash) or `C` (cash already added)
- **For Beneficiary Accounts**: No flag or `U` (no matching) flag allowed
- **Validation**: Fund must have enough unallocated shares

#### SAL (Sale) - Withdrawals
- **Use case**: Beneficiary withdraws cash from their account
- **Effect**: Removes shares from account (value/shares become negative)
- **Required flag**: `U` (no matching) - sales don't trigger matching
- **Validation**: Account must have enough shares to sell
- **Note**: Fund accounts cannot have SAL transactions

#### INI (Initial)
- **Use case**: Initialize a new fund with starting capital
- **Requires**: Both `value` and `shares` must be provided
- **Flag**: Usually `C` (cash already in portfolio)

#### MAT (Matching)
- **Created automatically** when a PUR transaction triggers matching rules
- **Links to**: The original PUR transaction via `TransactionMatching`
- **Not created via API** - system generates these

## Transaction Statuses

| Code | Name | Description |
|------|------|-------------|
| `P` | Pending | Not yet processed (will be processed on save) |
| `C` | Cleared | Successfully processed, balance updated |
| `S` | Scheduled | Template for recurring transactions |

### Processing Flow

1. Transaction created with status `P` (Pending)
2. `processPending()` called automatically
3. Share value calculated: `fund.shareValueAsOf(timestamp)`
4. Shares calculated: `value / shareValue`
5. AccountBalance record created/updated
6. Matching rules evaluated (if beneficiary PUR)
7. Status changed to `C` (Cleared)

## Transaction Flags

| Code | Name | Use Case |
|------|------|----------|
| `A` | Add Cash | **Fund only**: Deposit adds cash to portfolio, then allocates shares |
| `C` | Cash Added | **Fund only**: Cash was already deposited, recalculate share value |
| `U` | No Match | **Beneficiary only**: Skip matching rule evaluation (required for SAL) |
| `null` | No Flag | **Beneficiary PUR**: Normal purchase with matching evaluation |

### Flag Details

#### `A` - Add Cash (Fund Accounts Only)
When depositing to a fund account with flag `A`:
1. Cash is added to the fund's CASH portfolio asset
2. New share value calculated with increased fund value
3. Shares issued based on new share value

#### `C` - Cash Already Added (Fund Accounts Only)
When the cash deposit already happened (e.g., wire transfer received):
1. Recalculate share value: `(fundValue - depositValue) / fundShares`
2. This gives the share value BEFORE the cash was added
3. Issue shares based on the pre-deposit share value

#### `U` - No Matching (Beneficiary Accounts)
- Required for all SAL (sale/withdrawal) transactions
- Optional for PUR if you want to skip matching rule evaluation
- Matching rules are never evaluated when this flag is set

## Cash Handling

### Portfolio Cash Position
Cash in the fund is tracked as a portfolio asset:
- **Asset**: `CASH` with type `CSH`
- **Location**: `portfolio_assets` table with `position` = dollar amount
- **Price**: CASH asset always has price = 1.00

### How Cash Flows Work

1. **External deposit arrives** (e.g., wire transfer to brokerage)
2. **Create PUR transaction** for fund account with flag `A` or `C`
3. **If flag=A**: System adds cash to CASH portfolio asset
4. **If flag=C**: System assumes cash already in portfolio, adjusts share calc
5. **Beneficiary buys in**: PUR transaction transfers shares from fund to beneficiary

### Cash Position Updates

The `addCashToFund()` method in TransactionExt:
1. Gets the CASH asset record
2. Gets current position from `portfolio_assets`
3. Creates historical record (ends previous, starts new)
4. New position = old position + deposit value

## Balance Tracking

### AccountBalance Model

```
account_balances
├── account_id      # Which account
├── transaction_id  # Transaction that created this balance
├── type           # 'OWN' (ownership) or 'BOR' (borrowed)
├── shares         # Cumulative share count
├── start_dt       # When this balance became effective
├── end_dt         # When superseded (9999-12-31 if current)
└── previous_balance_id  # Link to prior balance
```

### Balance Creation Rules

1. Each transaction creates a new AccountBalance record
2. The previous balance's `end_dt` is set to the transaction timestamp
3. New balance's `shares` = old shares + transaction shares
4. Sales result in negative `shares` on the transaction, reducing total

### Querying Balances

```php
// Get shares as of a specific date
$shares = AccountBalance::where('account_id', $id)
    ->where('type', 'OWN')
    ->whereDate('start_dt', '<=', $date)
    ->whereDate('end_dt', '>', $date)  // end_dt is exclusive
    ->first()?->shares ?? 0;
```

## Performance Calculations

### Time-Weighted Return (TWR)

FamilyFund uses TWR to measure investment performance, which eliminates the impact of deposits/withdrawals.

**Implementation**: `AccountExt::calculateTWR()` and `periodPerformance()`

```php
// TWR = Product of (End Value - Cash Flow) / Start Value for each period
foreach ($periods as [$startValue, $endValue, $cashFlow]) {
    $periodReturn = ($endValue - $cashFlow) / $startValue;
    $cumulativeReturn *= $periodReturn;
}
$twr = $cumulativeReturn - 1;
```

### Share-Based Performance

Since share price = NAV / shares, share price growth naturally excludes deposits:
```php
$performance = $sharePriceTo / $sharePriceFrom - 1;
```

## Matching Rules

Matching rules allow automatic contribution matching (like employer 401k match).

### Models
- **MatchingRule**: Defines match parameters (%, dollar range, date range)
- **AccountMatchingRule**: Links an account to a matching rule
- **TransactionMatching**: Links a MAT transaction to its reference PUR and rule

### Matching Flow

1. Beneficiary creates PUR transaction (no flag or null flag)
2. `createMatching()` evaluates all account's matching rules
3. Rules sorted by expiration date (earlier-expiring first)
4. For each applicable rule:
   - Calculate how much of deposit falls in rule's dollar range
   - Apply match percentage
   - Create MAT transaction with same shares calculation
   - Create TransactionMatching record linking MAT to PUR and rule

## External Sync Considerations

When syncing transactions from Monarch/Pluggy:

### Deposit Mapping
| External | FamilyFund | Notes |
|----------|------------|-------|
| Cash deposit | PUR with flag=C | Cash already in brokerage |
| Transfer in | PUR with flag=A or C | Depends on cash timing |
| Interest/dividend | No transaction | Reflected in portfolio value |

### Withdrawal Mapping
| External | FamilyFund | Notes |
|----------|------------|-------|
| Cash withdrawal | SAL with flag=U | Always use U flag |
| Transfer out | SAL with flag=U | |

### Deduplication

FamilyFund doesn't have built-in deduplication. Strategies:
1. Store external transaction ID in `descr` field
2. Check for existing transactions with same account/date/value
3. Use `CashDeposit` or `DepositRequest` models to track workflow state

### Recommended Sync Flow

1. **Identify new external transactions** from Monarch/Pluggy
2. **Check if already synced** (search by external ID in descr or separate table)
3. **Determine transaction type**:
   - Deposit to fund → `PUR` with flag `C` (assume cash in account)
   - Withdrawal → `SAL` with flag `U`
4. **Create transaction** via API or TransactionTrait
5. **Mark as synced** in your tracking mechanism

### Gotchas

1. **Share value timing**: Ensure `timestamp` is when the deposit was valued, not when it cleared
2. **Fund account vs beneficiary**: Fund deposits increase total fund shares; beneficiary deposits reallocate existing shares
3. **Matching rules**: Only apply to beneficiary PUR without `U` flag
4. **Future dates**: Transactions with future timestamps stay in `P` status until that date
5. **Negative values**: SAL transactions should have positive input value; system negates during processing

## Related Models

| Model | Purpose |
|-------|---------|
| `CashDeposit` | Track cash deposit workflow (PENDING → DEPOSITED → ALLOCATED → COMPLETED) |
| `DepositRequest` | Beneficiary request for allocation from a CashDeposit |
| `ScheduledJob` | Recurring transaction templates |

## API Endpoints

- `POST /api/v1/transactions` - Create and process transaction
- `GET /api/transactions` - List all transactions
- `GET /api/transactions/{id}` - Get transaction details

### Create Transaction Request

```json
{
    "type": "PUR",
    "status": "P",
    "value": 1000.00,
    "shares": null,
    "timestamp": "2024-01-15",
    "account_id": 5,
    "flags": "C",
    "descr": "Monthly deposit"
}
```

### Validation Rules

```php
'type' => 'required|in:PUR,INI,SAL',  // Create only allows these
'status' => 'required|in:P,S',         // Can't create as Cleared
'value' => 'required|numeric',
'shares' => 'nullable|numeric',        // Required only for INI
'timestamp' => 'nullable|after:last year|before_or_equal:tomorrow',
'account_id' => 'required',
'flags' => 'nullable|string|in:A,C,U',
```
