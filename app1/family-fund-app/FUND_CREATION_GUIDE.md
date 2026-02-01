# FamilyFund - Fund Creation Guide

Complete guide for creating funds in FamilyFund using Web UI or APIs.

---

## Table of Contents
- [Overview](#overview)
- [Option 1: Web UI with Complete Setup (Recommended)](#option-1-web-ui-with-complete-setup-recommended)
- [Option 2: Web UI Manual Setup](#option-2-web-ui-manual-setup)
- [Option 3: REST APIs](#option-3-rest-apis)
- [Understanding Fund Structure](#understanding-fund-structure)
- [Common Scenarios](#common-scenarios)

---

## Overview

A **Fund** in FamilyFund represents an investment vehicle. Each fund requires:
1. **Fund** - The fund entity (name, goal)
2. **Fund Account** - An account with `user_id=null` representing the fund itself
3. **Portfolio(s)** - One or more portfolios to hold positions
4. **Initial Transaction** - Optional but recommended to initialize accounting

FamilyFund provides **three ways** to create funds:

| Method | Use Case | Complexity | Time |
|--------|----------|------------|------|
| **Web UI - Complete Setup** | First-time setup, single fund | Easy | 2 min |
| **Web UI - Manual** | Fine control over each entity | Medium | 5-10 min |
| **REST API** | Automation, bulk creation | Advanced | Varies |

---

## Option 1: Web UI with Complete Setup (Recommended)

**NEW in 2025**: One-page setup that creates fund + account + portfolio + transaction.

### When to Use
- ✅ Creating a single fund for the first time
- ✅ You want preview before creation
- ✅ You need both shares and value specified
- ✅ Quick setup (< 2 minutes)

### Steps

#### 1. Navigate to Create with Setup
```
http://localhost:3001/funds/create-with-setup
```

Or: **Funds** → **Create with Setup**

#### 2. Fill in the Form

**Fund Information:**
- **Name**: Fund name (required, max 30 chars)
  - Example: `Monarch Consolidated`
- **Goal**: Investment goal (optional, max 1024 chars)
  - Example: `Consolidated view of all Monarch accounts`

**Fund Account:**
- **Account Nickname**: Optional, auto-generated if blank
  - Example: `Monarch Consolidated Fund Account`

**Portfolio:**
- **Portfolio Source**: Unique identifier (required, max 30 chars)
  - Example: `MONARCH_IBKR_XXXX`
  - Used by sync scripts to identify the portfolio

**Initial Transaction** (optional but recommended):
- **☑ Create initial transaction** - Check to enable
- **Initial Shares**: Number of shares to allocate
  - Example: `1` (for new funds)
- **Initial Value**: Dollar amount
  - Example: `0.01` (minimal initialization)
- **Description**: Transaction description
  - Example: `Initial fund setup`

#### 3. Preview (Optional)

Click **"Preview Setup"** to see what will be created:
- Fund details
- Account structure
- Portfolio(s)
- Transaction details
- Resulting account balance

No database changes made during preview.

#### 4. Create

Click **"Create Fund"** (or **"Confirm & Create"** after preview).

System will:
1. Create fund record
2. Create fund account (user_id=null)
3. Create portfolio
4. Create and process initial transaction
   - Calculate account balance
   - Allocate shares
   - Create account_balances record
   - Send confirmation email

#### 5. Verify

You'll be redirected to the fund show page:
```
http://localhost:3001/funds/{id}
```

Verify:
- Fund name and goal
- Initial balance (if transaction created)
- Portfolio listed

### Example: Monarch Consolidated Fund

```
Name: Monarch Consolidated
Goal: Consolidated view of all investment accounts from Monarch Money
Portfolio Source: MONARCH_IBKR_XXXX
Initial Shares: 1
Initial Value: 0.01
Description: Initial fund setup
```

**Result:**
- Share price: $0.01 / 1 = $0.01 per share
- Account balance: $0.01
- Account shares: 1
- Ready for bulk position updates

---

## Option 2: Web UI Manual Setup

**Classic workflow**: Create each entity separately.

### When to Use
- ✅ You need fine control over each entity
- ✅ Creating complex fund structures
- ✅ You understand fund accounting

### Steps

#### Step 1: Create Account for the Fund

Navigate: **Accounts** → **Create New Account**

Fill in:
- **Fund**: (leave blank - fund doesn't exist yet)
- **User**: **Leave blank** ← Important! This is the fund account
- **Nickname**: `[Fund Name] Fund Account`
- **Code**: Will be auto-generated

Click **Save**.

⚠️ **Note**: You'll need to come back and set the fund_id after creating the fund.

#### Step 2: Create the Fund

Navigate: **Funds** → **Create New Fund**

Fill in:
- **Name**: Fund name (max 30 chars)
- **Goal**: Investment goal (max 1024 chars)

Click **Save**.

Note the **Fund ID** from the URL or page.

#### Step 3: Update Account

Navigate back to **Accounts** → find the account you created → **Edit**

Set:
- **Fund**: Select the fund you just created

Click **Save**.

#### Step 4: Create Portfolio

Navigate: **Portfolios** → **Create New Portfolio**

Fill in:
- **Fund**: Select your fund
- **Source**: Unique identifier (e.g., `DWGIB`, `MONARCH_IBKR_XXXX`)

Click **Save**.

Repeat for additional portfolios.

#### Step 5: Create Initial Transaction

Navigate: **Transactions** → **Create New Transaction**

Fill in:
- **Account**: Select the fund account (user_id=null)
- **Type**: Initial Value
- **Amount**: Initial funding (e.g., $0.01)
- **Shares**: Share count (e.g., 1)
- **Date**: Transaction date
- **Description**: `Initial fund setup`

Click **Preview** to see the impact, then **Confirm**.

#### Step 6: Verify

Navigate to **Funds** → find your fund → **View**

Check:
- Initial balance matches
- Portfolios listed
- Account balance created

### Example: Manual Setup

```
1. Create account: "My Fund Account" (no user, no fund yet)
2. Create fund: "My Investment Fund"
3. Edit account: Set fund_id to "My Investment Fund"
4. Create portfolio: source="PORTFOLIO_1"
5. Create transaction: amount=$0.01, shares=1
6. Verify: Fund shows $0.01 balance
```

---

## Option 3: REST APIs

### Available APIs

#### A. Basic Fund API (Fund Only)

Creates just the fund entity.

**Endpoint:** `POST /api/funds`

**Request:**
```json
{
  "name": "My Fund",
  "goal": "Investment goal description"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "My Fund",
    "goal": "Investment goal description",
    "created_at": "2025-01-17T10:00:00.000000Z",
    "updated_at": "2025-01-17T10:00:00.000000Z"
  },
  "message": "Fund saved successfully"
}
```

**Then manually create:**
- Account via `POST /api/accounts`
- Portfolio via `POST /api/portfolios`
- Transaction via `POST /api/transactions`

#### B. Complete Setup API (Recommended)

**NEW in 2025**: Complete fund setup in one API call.

**Endpoint:** `POST /api/funds/setup`

**Purpose:** Creates fund + account + portfolio(s) + initial transaction in one atomic operation.

**Request:**
```json
{
  "name": "Monarch Consolidated",
  "goal": "Consolidated view of all Monarch accounts",
  "portfolio_source": "MONARCH_IBKR_XXXX",
  "account_nickname": "Fund Account",
  "create_initial_transaction": true,
  "initial_shares": 1,
  "initial_value": 0.01,
  "transaction_description": "Initial setup"
}
```

**Request Fields:**
- `name` (required): Fund name (max 30 chars)
- `goal` (optional): Investment goal (max 1024 chars)
- `portfolio_source` (required): String or array of portfolio sources
- `account_nickname` (optional): Custom account name (auto-generated if blank)
- `create_initial_transaction` (optional): Default true
- `initial_shares` (optional): Number of shares to allocate
- `initial_value` (optional): Dollar amount (default 0.01)
- `transaction_description` (optional): Default "Initial fund setup"
- `dry_run` (optional): Set to true for preview mode (no database changes)

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "fund": {
      "id": 1,
      "name": "Monarch Consolidated",
      "goal": "Consolidated view of all Monarch accounts"
    },
    "account": {
      "id": 1,
      "fund_id": 1,
      "user_id": null,
      "nickname": "Fund Account",
      "code": "F1"
    },
    "portfolios": [
      {
        "id": 1,
        "fund_id": 1,
        "source": "MONARCH_IBKR_XXXX"
      }
    ],
    "transaction": {
      "id": 1,
      "account_id": 1,
      "fund_id": 1,
      "type": "INI",
      "amount": 0.01,
      "shares": 1,
      "description": "Initial setup",
      "timestamp": "2025-01-17T10:00:00.000000Z"
    },
    "account_balance": {
      "balance": 0.01,
      "shares": 1,
      "share_value": 0.01
    }
  },
  "message": "Fund created successfully with account, portfolio, and initial transaction"
}
```

**Response (Preview Mode with dry_run: true):**
```json
{
  "success": true,
  "data": {
    "fund": { ... },
    "account": { ... },
    "portfolios": [ ... ],
    "transaction": { ... },
    "account_balance": { ... },
    "dry_run": true,
    "note": "Preview mode - no changes were saved to database"
  },
  "message": "Fund setup preview generated successfully"
}
```

**Example: Create Fund with Preview Then Confirm**
```bash
# 1. Preview first (dry run)
curl -X POST http://localhost:3001/api/funds/setup \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Fund",
    "portfolio_source": "MY_PORTFOLIO",
    "initial_shares": 1000,
    "initial_value": 1000.00,
    "dry_run": true
  }'

# 2. Review output, then create for real
curl -X POST http://localhost:3001/api/funds/setup \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Fund",
    "portfolio_source": "MY_PORTFOLIO",
    "initial_shares": 1000,
    "initial_value": 1000.00,
    "dry_run": false
  }'
```

**Example: Create Fund with Multiple Portfolios**
```bash
curl -X POST http://localhost:3001/api/funds/setup \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Consolidated Fund",
    "portfolio_source": ["PORTFOLIO_1", "PORTFOLIO_2", "PORTFOLIO_3"],
    "initial_shares": 100,
    "initial_value": 500.00
  }'
```

#### C. Individual Entity APIs

**Account:**
```bash
POST /api/accounts
{
  "fund_id": 1,
  "user_id": null,
  "nickname": "Fund Account"
}
```

**Portfolio:**
```bash
POST /api/portfolios
{
  "fund_id": 1,
  "source": "PORTFOLIO_SOURCE"
}
```

**Transaction:**
```bash
POST /api/transactions
{
  "fund_id": 1,
  "account_id": 1,
  "type": "INI",
  "amount": 0.01,
  "shares": 1,
  "timestamp": "2025-01-17T10:00:00",
  "description": "Initial setup"
}
```

⚠️ **Important**: Transaction API calls `processPending()` automatically (unlike Web UI).

### Python Script

For automated setup via API, see:
```
/Users/dtogni/dev/finex/setup_familyfund_via_api.py
```

Creates fund + account + 16 portfolios + transaction in one script.

**Usage:**
```bash
cd /Users/dtogni/dev/finex
python setup_familyfund_via_api.py
```

**What it does:**
1. POST /api/funds - Create fund
2. POST /api/accounts - Create fund account
3. POST /api/portfolios (x16) - Create 16 portfolios
4. POST /api/transactions - Create initial transaction (calls processPending)
5. Verify setup

---

## Understanding Fund Structure

### Required Components

Every fund MUST have:

1. **Exactly 1 fund account** (user_id=null)
   - Represents the fund entity itself
   - Code: `F{fund_id}` (e.g., F1, F2)

2. **At least 1 portfolio**
   - Holds positions (stocks, ETFs, crypto)
   - Identified by unique `source` string

3. **Initial balance** (recommended)
   - Created via initial transaction
   - Establishes share price
   - Required before position updates

### Optional Components

- **User accounts** (user_id set)
  - Investors who buy into the fund
  - Can have multiple per fund

- **Multiple portfolios**
  - Each portfolio = separate account/source
  - All contribute to fund's total value

### Architecture Examples

#### Single Fund, Multiple Portfolios (Recommended)

```
Fund: "Family Investments" ($2.77M)
├── Fund Account (user_id=null)
│   └── Initial Transaction: $0.01 (1 share @ $0.01)
│
└── Portfolios:
    ├── FIDELITY_401K ($500K)
    ├── IB_TAXABLE ($1.2M)
    ├── IB_ROTH ($800K)
    └── COINBASE ($270K)
```

**Benefits:**
- Consolidated view of total value
- Track overall asset allocation
- Individual account history maintained
- Easy performance comparison

#### Multiple Funds

```
Fund: "Retirement Portfolio"
└── Portfolio: RETIREMENT_401K

Fund: "College Savings"
└── Portfolio: COLLEGE_529

Fund: "Trading Account"
└── Portfolio: TRADING_TAXABLE
```

**When to use:**
- Different investment strategies
- Different owners/beneficiaries
- Separate performance tracking

---

## Common Scenarios

### Scenario 1: Monarch Money Sync (16 Accounts)

**Goal:** Create fund with 16 portfolios for Monarch accounts.

**Recommended:** Web UI with Complete Setup

**Steps:**
1. Use `/funds/create-with-setup`
2. Create fund with first portfolio
3. Add 15 more portfolios manually via `/portfolios/create`
4. Run `sync_monarch_to_familyfund.py`

**Or use Python script:**
```bash
cd /Users/dtogni/dev/finex
python setup_familyfund_via_api.py
```

Creates everything at once.

### Scenario 2: Single Brokerage Account

**Goal:** Track one IB account.

**Recommended:** Web UI with Complete Setup

**Steps:**
1. Navigate to `/funds/create-with-setup`
2. Fill in:
   - Name: `My IB Account`
   - Portfolio Source: `IB_TAXABLE`
   - Initial Shares: 1
   - Initial Value: 0.01
3. Preview → Confirm
4. Use bulk APIs to sync positions

### Scenario 3: Fund with Investors

**Goal:** Investment fund with multiple investors.

**Recommended:** Manual setup

**Steps:**
1. Create fund via `/funds/create-with-setup`
2. For each investor:
   - Create user account (user_id set)
   - Create purchase transaction
3. Track each investor's shares

### Scenario 4: Bulk Fund Creation

**Goal:** Create 10+ funds programmatically.

**Recommended:** Python script + APIs

**Steps:**
1. Modify `setup_familyfund_via_api.py`
2. Loop through fund configs
3. POST to `/api/funds`, `/api/accounts`, `/api/portfolios`
4. Verify each fund

---

## Shares and Value

### How Share Price Works

**Formula:** `Share Price = Total Value ÷ Total Shares`

**Example:**
- Initial Value: $10,000
- Initial Shares: 1,000
- Share Price: $10,000 / 1,000 = **$10.00 per share**

### Scenarios

#### New Fund (Standard)
```
Initial Shares: 1
Initial Value: $0.01
Share Price: $0.01 / 1 = $0.01
```

Sets share price at $0.01. Fund value grows as positions added.

#### New Fund (Real Value)
```
Initial Shares: 1000
Initial Value: $10,000
Share Price: $10,000 / 1000 = $10.00
```

Realistic starting point if you're tracking existing portfolio.

#### Only Value Provided
```
Initial Value: $0.01
Initial Shares: (empty)
```

System calculates shares based on fund's current share price (or defaults).

---

## Troubleshooting

### "Every fund must have 1 account with no user id"

**Problem:** Fund doesn't have a fund account (user_id=null).

**Solution:**
- If using manual setup: Create account with user_id=null BEFORE or AFTER fund
- If using complete setup: This is created automatically

### "Portfolio source not found"

**Problem:** Bulk API can't find portfolio.

**Solution:**
- Verify portfolio source matches exactly (case-sensitive)
- Check: `SELECT * FROM portfolios WHERE source = 'YOUR_SOURCE';`

### "Transaction validation error"

**Problem:** Transaction data invalid.

**Solution:**
- Ensure account_id exists
- Check amount > 0
- Verify timestamp is valid date

### Preview Shows Wrong Data

**Problem:** Preview calculations seem incorrect.

**Solution:**
- Preview uses dry_run (rolls back)
- Share price = value ÷ shares
- Account balance = transaction amount
- Check your math

---

## API Reference Summary

### Fund APIs

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | /api/funds | List all funds |
| POST | /api/funds | Create fund (name, goal only) |
| GET | /api/funds/{id} | Get fund details |
| PUT | /api/funds/{id} | Update fund |
| DELETE | /api/funds/{id} | Delete fund |
| GET | /api/funds/{id}/as_of/{date} | Fund state as of date |

### Account APIs

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | /api/accounts | List all accounts |
| POST | /api/accounts | Create account |
| GET | /api/accounts/{id} | Get account details |
| PUT | /api/accounts/{id} | Update account |
| DELETE | /api/accounts/{id} | Delete account |

### Portfolio APIs

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | /api/portfolios | List all portfolios |
| POST | /api/portfolios | Create portfolio |
| GET | /api/portfolios/{id} | Get portfolio details |
| PUT | /api/portfolios/{id} | Update portfolio |
| DELETE | /api/portfolios/{id} | Delete portfolio |

### Transaction APIs

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | /api/transactions | Create transaction (calls processPending) |
| GET | /api/transactions/{id} | Get transaction details |

### Bulk Update APIs

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | /api/portfolio_assets_bulk_update | Update positions for portfolio |
| POST | /api/asset_prices_bulk_update | Update asset prices |

---

## Related Documentation

- **Implementation Details**: `FUND_SETUP_WITH_PREVIEW_IMPLEMENTATION.md`
- **Test Coverage**: `TEST_COVERAGE_FUND_SETUP.md`
- **Bulk APIs**: `BULK_UPDATE_APIS.md` (from plan mode)
- **Python Setup Script**: `/Users/dtogni/dev/finex/setup_familyfund_via_api.py`
- **Monarch Sync**: `/Users/dtogni/dev/finex/QUICK_START_MONARCH_SYNC.md`

---

## Quick Reference

### Create Fund - Web UI Complete Setup
```
1. Go to /funds/create-with-setup
2. Fill: Name, Source, Shares=1, Value=0.01
3. Preview → Confirm
4. Done in < 2 minutes
```

### Create Fund - API Complete Setup (Recommended)
```bash
# One API call - creates fund + account + portfolio + transaction
curl -X POST http://localhost:3001/api/funds/setup \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Fund",
    "portfolio_source": "MY_PORTFOLIO",
    "initial_shares": 1000,
    "initial_value": 1000.00
  }'

# With preview (dry run) first
curl -X POST http://localhost:3001/api/funds/setup \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Fund",
    "portfolio_source": "MY_PORTFOLIO",
    "initial_shares": 1000,
    "initial_value": 1000.00,
    "dry_run": true
  }'
```

### Create Fund - API (Basic)
```bash
# 1. Create fund
curl -X POST http://localhost:3001/api/funds \
  -H "Content-Type: application/json" \
  -d '{"name":"My Fund","goal":"Investment fund"}'

# 2. Create account (use fund ID from response)
curl -X POST http://localhost:3001/api/accounts \
  -H "Content-Type: application/json" \
  -d '{"fund_id":1,"user_id":null,"nickname":"Fund Account"}'

# 3. Create portfolio
curl -X POST http://localhost:3001/api/portfolios \
  -H "Content-Type: application/json" \
  -d '{"fund_id":1,"source":"PORTFOLIO_1"}'

# 4. Create transaction
curl -X POST http://localhost:3001/api/transactions \
  -H "Content-Type: application/json" \
  -d '{"fund_id":1,"account_id":1,"type":"INI","amount":0.01,"timestamp":"2025-01-17T10:00:00"}'
```

### Create Fund - Python Script
```bash
cd /Users/dtogni/dev/finex
python setup_familyfund_via_api.py
```

---

**Last Updated**: January 17, 2025
**Version**: 2.1 (includes complete setup workflow + API endpoint with dry run)
