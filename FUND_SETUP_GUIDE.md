# FamilyFund - Fund Setup Guide

Generic instructions for creating a new fund in FamilyFund.

Based on README.md lines 255-267.

---

## Adding an Account in FamilyFund

An account represents an investor's holdings within a fund.

**Steps:**
1. Create a user via the web interface
2. Create an account for that user
3. Add a transaction - this will create a balance for the account

---

## Adding a Fund in FamilyFund

A fund is an investment vehicle that holds a portfolio of assets.

**Steps:**

### 1. Create an Account for the Fund
- Create an account with **no user id** (this is the fund's account, not a user's account)
- This account represents the fund itself

### 2. Create the Fund
- Navigate to Funds → Create New Fund
- Fill in:
  - **Name:** Fund name (max 30 characters)
  - **Goal:** Description of the fund's investment goal (max 1024 characters)

### 3. Create Portfolio(s)
- Navigate to Portfolios → Create New Portfolio
- Fill in:
  - **Fund:** Select the fund created in step 2
  - **Source:** Unique identifier for this portfolio (max 30 characters)
    - Examples: "DWGIB", "FFIB", "MONARCH_IBKR_XXXX"
    - This is used by external sync systems to identify the portfolio
- **Note:** You can create multiple portfolios for one fund
  - Each portfolio can represent a different account or source
  - This allows consolidating multiple accounts into one fund

### 4. Create an Initial Transaction
- Navigate to Transactions → Create New Transaction
- Fill in:
  - **Fund:** Select the fund
  - **Type:** Initial deposit or appropriate transaction type
  - **Amount:** Initial funding amount (can be minimal, e.g., $0.01)
  - **Date:** Transaction date
- **Important:** This initializes the fund's accounting system

### 5. Verify Initial Balance
- Navigate to Funds → View your fund
- Check that the initial balance matches your transaction
- Verify portfolios are listed under the fund

---

## Fund Architecture

### Single Fund, Multiple Portfolios (Recommended)

Use this when you want to consolidate multiple external accounts into one fund for reporting.

**Example:**
```
Fund: "Family Investment Portfolio"
├── Portfolio: FIDELITY_401K (source)
├── Portfolio: IB_TAXABLE (source)
└── Portfolio: COINBASE_CRYPTO (source)
```

**Benefits:**
- Consolidated view of total portfolio value
- Track overall asset allocation
- Individual account history maintained separately
- Easy performance comparison across accounts

### Multiple Funds (One per Account)

Use this when accounts have completely different strategies or owners.

**Example:**
```
Fund: "John's Retirement"
└── Portfolio: JOHN_IRA (source)

Fund: "Jane's College Savings"
└── Portfolio: JANE_529 (source)
```

**When to use:**
- Completely different investment strategies
- Different owners/beneficiaries
- Separate performance tracking needed

---

## Portfolio Source Identifiers

The `source` field uniquely identifies each portfolio and is used by:
- External sync systems (e.g., dstrader, Monarch sync)
- Bulk update APIs (`/api/portfolio_assets_bulk_update`)

**Naming conventions:**
- Use descriptive, consistent names
- Examples:
  - Institution-based: `FIDELITY_401K`, `SCHWAB_ROTH`
  - System-based: `DWGIB` (DW's IB account), `MONARCH_IBKR_XXXX`
  - Purpose-based: `RETIREMENT_PRIMARY`, `TAXABLE_TRADING`

**Important:**
- Must be unique across all portfolios
- Max 30 characters
- Cannot be changed after creation (referenced by external systems)

---

## Making an Investment into a Fund

**Process:**
1. Create a transaction for the fund
2. Specify when the cash should be recognized (transaction date)
3. **Important:** Wait for the transaction to be recognized before making portfolio updates
   - Making portfolio updates before cash is recognized can cause miscalculation and validation errors

---

## Common Workflows

### Scenario 1: Track Multiple Brokerage Accounts as One Fund

**Goal:** Consolidate Fidelity 401k, IB taxable, and IB Roth into one view

**Steps:**
1. Create account for fund (no user)
2. Create fund "My Investments"
3. Create portfolio with source "FIDELITY_401K"
4. Create portfolio with source "IB_TAXABLE"
5. Create portfolio with source "IB_ROTH"
6. Create initial transaction
7. Set up external sync to push positions to each portfolio

**Result:** One fund showing total value across all three accounts

### Scenario 2: Separate Retirement and Taxable Funds

**Goal:** Track retirement separately from taxable investments

**Steps:**
1. Create account for retirement fund
2. Create fund "Retirement Portfolio"
3. Create portfolios for retirement accounts
4. Create initial transaction

5. Create account for taxable fund
6. Create fund "Taxable Investments"
7. Create portfolios for taxable accounts
8. Create initial transaction

**Result:** Two separate funds with independent tracking

---

## Integration with External Systems

### Bulk Update APIs

Once portfolios are created, external systems can sync data via:

**Price Updates:**
```
POST /api/asset_prices_bulk_update
{
  "source": "PORTFOLIO_SOURCE_ID",
  "timestamp": "2024-01-26T12:52:06",
  "symbols": [
    {"name": "SPXL", "type": "STK", "price": 123.45}
  ]
}
```

**Position Updates:**
```
POST /api/portfolio_assets_bulk_update
{
  "source": "PORTFOLIO_SOURCE_ID",
  "timestamp": "2024-01-26T12:52:06",
  "symbols": [
    {"name": "SPXL", "type": "STK", "position": 14.0}
  ]
}
```

The `source` field matches the portfolio source identifier.

---

## Troubleshooting

### "Making transaction before cash was recognized caused miscalculation and validation error"

**Problem:** Tried to update portfolio positions before the fund's cash transaction cleared

**Solution:**
- Ensure initial transaction date is before portfolio updates
- Wait for transaction to be recognized in the system
- Check fund balance before making portfolio updates

### "Portfolio source not found"

**Problem:** External system using incorrect source identifier

**Solution:**
- Verify portfolio source field in FamilyFund UI
- Ensure external system uses exact same source string (case-sensitive)
- Check for typos in source identifier

### "Validation error on transaction"

**Problem:** Transaction amount or date conflicts with existing data

**Solution:**
- Check fund's existing transactions
- Verify transaction date is logical
- Ensure sufficient balance for the transaction type

---

## Database Schema Reference

**Funds:**
- `id` - Auto-increment primary key
- `name` - Fund name (max 30 chars)
- `goal` - Investment goal description (max 1024 chars)

**Portfolios:**
- `id` - Auto-increment primary key
- `fund_id` - Foreign key to funds table
- `source` - Unique identifier (max 30 chars)

**Portfolio Assets:**
- Uses temporal versioning with `start_dt` and `end_dt`
- Maintains complete history of all position changes

**Asset Prices:**
- Uses temporal versioning with `start_dt` and `end_dt`
- Maintains complete price history

---

## See Also

- `README.md` - Full system documentation
- `CLAUDE.md` (Testing section) - Testing procedures
- `/api/` endpoints - API documentation for programmatic access
