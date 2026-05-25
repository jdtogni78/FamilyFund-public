# Family Fund Style Guide

## Progress & Notes

### Standardization Progress (Jan 2026)

**Completed:**
- [x] Chart labels - dynamic dark mode support with registry pattern
- [x] Table headers - Tailwind dark mode classes
- [x] Goal progress boxes - dark mode CSS overrides
- [x] Table total/subtotal rows - dark mode support
- [x] Login screen title color
- [x] Badge contrast (bg-primary instead of bg-secondary)
- [x] Index page headers (26 pages) - standardized layout with icons, badges, buttons
- [x] Form fields (30 fields.blade.php files) - standardized two-column layout with icons, helper text
- [x] Trade Portfolios detail views (#26) - dark-mode + BS5 migration (see note below)
- [x] Accounts detail views (#27) - dark-mode + BS5 migration (see note below)
- [x] Funds & Portfolios detail views (#28) - dark-mode + BS5 migration (see note below)

**Form Fields Standardized (30 forms):**
All create/edit forms now follow consistent pattern:
- Font size 0.875rem for form controls
- Two-column responsive layout (col-md-6)
- Icons in labels with FontAwesome
- Helper text with text-body-secondary
- Section dividers (hr.my-3)
- Required field markers (*)
- Styled submit/cancel buttons with icons

**Index Pages Standardized:**
| Entity | Icon | Variable |
|--------|------|----------|
| Accounts | `fa-users` | `$accounts` |
| Account Balances | `fa-balance-scale` | `$accountBalances` |
| Account Goals | `fa-bullseye` | `$accountGoals` |
| Account Matching Rules | `fa-link` | `$accountMatchingRules` |
| Account Reports | `fa-file-alt` | `$accountReports` |
| Addresses | `fa-map-marker-alt` | `$addresses` |
| Assets | `fa-coins` | `$assets` |
| Asset Change Logs | `fa-history` | `$assetChangeLogs` |
| Asset Prices | `fa-chart-line` | `$assetPrices` |
| Cash Deposits | `fa-dollar-sign` | `$cashDeposits` |
| Change Logs | `fa-history` | `$changeLogs` |
| Deposit Requests | `fa-hand-holding-usd` | `$depositRequests` |
| Fund Reports | `fa-file-alt` | `$fundReports` |
| Funds | `fa-landmark` | `$funds` |
| Goals | `fa-bullseye` | `$goals` |
| ID Documents | `fa-id-card` | `$idDocuments` |
| Matching Rules | `fa-link` | `$matchingRules` |
| People | `fa-users` | `$people` |
| Phones | `fa-phone` | `$phones` |
| Portfolio Assets | `fa-coins` | `$portfolioAssets` |
| Portfolio Reports | `fa-file-alt` | `$portfolioReports` |
| Portfolios | `fa-briefcase` | `$portfolios` |
| Scheduled Jobs | `fa-clock` | `$scheduledJobs` |
| Schedules | `fa-calendar-alt` | `$schedules` |
| Trade Band Reports | `fa-file-alt` | `$tradeBandReports` |
| Trade Portfolio Items | `fa-list` | `$tradePortfolioItems` |
| Trade Portfolios | `fa-chart-pie` | `$tradePortfolios` |
| Transaction Matchings | `fa-link` | `$transactionMatchings` |
| Transactions | `fa-exchange-alt` | `$transactions` |
| Users | `fa-user-cog` | `$users` |

**Trade Portfolios detail views (#26, done):**
Standardized the live detail/show views to the dark-mode + Bootstrap 5 conventions:
- Detail headers use `card-header card-header-dark` (themed teal, adapts to dark) instead of inline `style="background:#0d9488"`.
- Cash highlight rows use the dark-adapting `.table-info` contextual class instead of a hard-coded light `#f0fdfa` (which vanished in dark mode).
- Light panels/footers converted to Tailwind `dark:` utilities (`bg-slate-100 dark:bg-slate-700`, `bg-emerald-100 dark:bg-emerald-900/40`, `border-x border-b border-slate-200 dark:border-slate-600`).
- Bootstrap-4 leftovers migrated to BS5: `mr-*/ml-*`→`me-*/ms-*`, `text-right`→`text-end`, `font-weight-bold`→`fw-bold`, `border-right`→`border-end`, `thead-light`→`table-light`, and the dead `data-toggle="collapse"`→`data-bs-toggle` (collapse toggles were broken under BS5).
- Deleted 6 unreferenced/dead views (`inner_show_tables`, `show_fields`, `show_fields_pdf`, `split_fields`, `group_table`, `show_dates`).
- PDF views (`*_pdf.blade.php`) intentionally left light (wkhtmltopdf has no dark mode).
- Covered by `tests/Browser/TradePortfolioDetailUITest.php` (light + dark, incl. the collapse-toggle fix).

> Reminder: BS dark mode here is driven by Tailwind's `.dark` class on `<html>` (not `data-bs-theme`). Prefer Tailwind `dark:` utilities or the contextual table classes (`.table-info` etc., which `public/css/navigation.css` dark-adapts) over inline hex backgrounds, which can't respond to dark mode.

**Accounts detail views (#27, done):**
Standardized the live account detail view (`accounts/show_ext.blade.php`) and its
partials to the dark-mode + Bootstrap 5 conventions:
- All section headers (Goals, Charts, Forecast, Shares, Performance, Transaction
  History, Scheduled, Matching, Disbursement) use `card-header card-header-dark`
  instead of inline `style="background:#134e4a"`. navigation.css's `.card-header`
  `!important` light-teal gradient was silently overriding that inline hex, which
  left the white `btn-outline-light` collapse buttons nearly invisible in light
  mode — `card-header-dark` restores the intended solid teal in both modes.
- Collapse toggles migrated from the dead BS4 `data-toggle` to BS5
  `data-bs-toggle` (the BS4 attribute did nothing under Bootstrap 5, so the
  section chevrons were inert).
- Inline light hex panels/dividers converted to exact Tailwind equivalents that
  dark-adapt: `#f0fdfa`→`bg-teal-50`, `#99f6e4`→`border-teal-200 dark:border-slate-600`,
  `#e2e8f0`→`border-slate-200 dark:border-slate-600` (with `border-e`/`border-t`).
- BS4 leftovers migrated to BS5: `text-right`→`text-end`, `mr-*`→`me-*`, and the
  dead BS3 `pull-right` removed. The Expired matching-rule badge got an explicit
  `text-white` (its siblings all set a text color; bare `bg-secondary` risked low
  contrast).
- Deleted the unreferenced `show_fields_ext.blade.php` (a superseded early
  iteration of the market-value card).
- `*_pdf.blade.php` views intentionally left light (wkhtmltopdf has no dark mode).
- Covered by `tests/Browser/AccountDetailUITest.php` (light + dark, incl. the
  collapse-toggle fix and the `card-header-dark` assertion).

**Funds & Portfolios detail views (#28, done):**
Standardized the remaining live detail/show views to the dark-mode + Bootstrap 5
conventions (the flagship `funds/show_ext.blade.php` plus `funds/show_trade_bands`,
`portfolios/show`, `portfolios/show_rebalance`, `emails/show`, and
`account_matching_rules/show`):
- All section headers migrated from inline `style="background: #134e4a; color: white;"`
  to `card-header card-header-dark` — navigation.css's `.card-header` `!important`
  light-teal gradient was silently overriding the inline dark hex (white text on a
  near-white header in light mode), the same bug fixed in #26/#27.
- **New `card-header-admin` class** (navigation.css) for admin-only section headers
  (amber gradient, white text, dark-adapting). The funds admin sections previously
  used inline amber gradients that were *also* overridden by the base `.card-header`
  rule, losing the admin colour cue. Placed after `html.dark .card-header` so it wins
  on source order in dark mode. See "Card Headers → Admin Section Headers" below.
- Dead BS4 `data-toggle="collapse"` / `data-target` collapse attributes migrated to
  BS5 `data-bs-toggle` / `data-bs-target` (the chevrons/section toggles were inert
  under Bootstrap 5). Removed an over-broad `.collapse.show { display: inline }`
  rule in `funds/show_ext` that would have broken the now-working section collapses
  (the `+N more` tbody expanders keep their own `tbody.collapse` rules).
- Inline light-hex panels/dividers converted to dark-adapting utilities:
  `#f0fdfa`→`bg-teal-50`, `#fffbeb` admin panels→`bg-amber-50 dark:bg-amber-900/40`,
  `border-right/top: 1px solid #99f6e4`→`border-e/-t border-teal-200 dark:border-slate-600`,
  `#e5e7eb`/`#e2e8f0` dividers→`border-slate-200 dark:border-slate-600`. Saturated
  semantic/data-viz colours (allocation bars, category/type/group tints, liability
  red) are intentional and left as-is; the email-preview iframe stays white (email
  HTML expects a light canvas) with only its border adapted.
- BS4 leftovers migrated to BS5: `mr-*/ml-*`→`me-*/ms-*`, `ml-auto`→`ms-auto`,
  `text-right`→`text-end`, `font-weight-bold`→`fw-bold`, `thead-light`→`table-light`.
- Count badges using `bg-secondary` switched to `bg-primary` (style guide); neutral
  *state* badges (Inactive/Expired/Disabled/N/A/type) kept grey.
- Deleted 3 dead views rendered by nothing (`funds/show.blade.php`,
  `funds/show_fields.blade.php`, `funds/show_fields_ext.blade.php`) — the
  `funds.show` route resolves to the controller which renders `show_ext`.
- `*_pdf.blade.php` views intentionally left light (wkhtmltopdf has no dark mode).
- The InfyOm scaffold show pages (assets, goals, transactions, …) already conform
  (BS5 + dark-adapting plain `card-header`); left unchanged.
- Covered by `tests/Browser/FundDetailUITest.php` (light + dark, incl. the
  collapse-toggle fix and the `card-header-dark`/`card-header-admin` assertions).

**Pending:**
- [x] Review other detail/show pages (#28) — done (see note above)

---

## Buttons

### Primary Actions
- **Class:** `btn btn-sm btn-primary`
- **Use for:** Create, Save, Submit, New X
- **Example:**
```html
<a class="btn btn-sm btn-primary" href="...">
    <i class="fa fa-plus me-1"></i> New Fund
</a>
```

### Secondary/Back Actions
- **Class:** `btn btn-sm btn-primary` (same as primary for header buttons)
- **Use for:** Back, navigation icons in headers

### Outline Actions (Secondary buttons in groups)
- **Class:** `btn btn-sm btn-outline-primary`
- **Use for:** Bulk operations, secondary actions alongside primary
- **Example:**
```html
<a class="btn btn-sm btn-outline-primary me-1" href="...">
    <i class="fa fa-users me-1"></i> Bulk
</a>
```

### Warning/Admin Actions
- **Class:** `btn btn-sm btn-warning`
- **Use for:** Admin-only actions, switch to admin view

### Table Row Actions (Icon Buttons)
- **View:** `btn btn-ghost-success` with `fa-eye`
- **Edit:** `btn btn-ghost-info` with `fa-edit`
- **Delete:** `btn btn-ghost-danger` with `fa-trash`
- **Other:** `btn btn-ghost-primary` for additional actions
- **Example:**
```html
<div class='btn-group'>
    <a href="..." class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
    <a href="..." class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
    <button type="submit" class="btn btn-ghost-danger"><i class="fa fa-trash"></i></button>
</div>
```

### Collapse Toggles
- **Class:** `btn btn-sm btn-outline-light`
- **Use for:** Expand/collapse sections on detail pages
- **Example:**
```html
<a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseSection">
    <i class="fa fa-chevron-down"></i>
</a>
```

---

## Card Headers

### Index/List Pages
```html
<div class="card-header d-flex justify-content-between align-items-center">
    <div>
        <i class="fa fa-landmark me-2"></i>
        <strong>Funds</strong>
        <span class="badge bg-primary ms-2">{{ $funds->count() }}</span>
    </div>
    <a class="btn btn-sm btn-primary" href="{{ route('funds.create') }}">
        <i class="fa fa-plus me-1"></i> New Fund
    </a>
</div>
```

### Index Page with Multiple Buttons
```html
<div class="card-header d-flex justify-content-between align-items-center">
    <div>
        <i class="fa fa-exchange-alt me-2"></i>
        <strong>Transactions</strong>
        <span class="badge bg-primary ms-2">{{ $transactions->count() }}</span>
    </div>
    <div>
        <a href="..." class="btn btn-sm btn-outline-primary me-1">
            <i class="fa fa-users me-1"></i> Bulk
        </a>
        <a class="btn btn-sm btn-primary" href="...">
            <i class="fa fa-plus me-1"></i> New Transaction
        </a>
    </div>
</div>
```

### Detail Page Headers (Dark Background)
```html
<div class="card-header card-header-dark d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <h4 class="mb-0" style="font-weight: 700;">{{ $title }}</h4>
        @if($isAdmin)
            <span class="badge ms-2" style="background: #d97706;">ADMIN</span>
        @endif
    </div>
    <div class="d-flex flex-wrap" style="gap: 4px;">
        <a href="..." class="btn btn-sm btn-primary">Back</a>
        <a href="..." class="btn btn-sm btn-primary"><i class="fa fa-file-pdf"></i></a>
    </div>
</div>
```

### Admin Section Headers (Amber Background)
Use `card-header-admin` for headers of admin-only sections so they read as distinct
from the standard teal headers. Like `card-header-dark`, it overrides the base
`.card-header` `!important` gradient and adapts to dark mode (defined in
`public/css/navigation.css`). Do **not** use an inline `style="background: ..."` —
the base `.card-header` rule will silently override it.
```html
<div class="card-header card-header-admin d-flex justify-content-between align-items-center">
    <strong><i class="fa fa-users me-2"></i>Accounts <span class="badge badge-warning">ADMIN</span></strong>
    <a class="btn btn-sm btn-outline-light" data-bs-toggle="collapse" href="#collapseAccounts">
        <i class="fa fa-chevron-down"></i>
    </a>
</div>
```

> Collapse toggles use BS5 `data-bs-toggle="collapse"` (+ `data-bs-target`/`href`).
> The BS4 `data-toggle`/`data-target` attributes are inert under Bootstrap 5.

---

## Badges

### Count Badges
- **Class:** `badge bg-primary`
- **Use for:** Item counts in headers
- **Note:** Avoid `bg-secondary` - poor contrast in dark mode

### Admin Badges
- **Style:** `background: #d97706; color: #fff;`
- **Example:**
```html
<span class="badge" style="background: #d97706;">ADMIN</span>
```

---

## Tables

### Total Rows
- **Class:** `table-total-row`
- **Style:** Dark teal background (#134e4a), white text

### Subtotal Rows
- **Class:** `table-subtotal-row`
- **Style:** Light teal in light mode, dark teal in dark mode

### Warning Rows
- **Class:** `table-warning-row`
- **Style:** Light amber in light mode, dark amber in dark mode

### Table Headers
- **Class:** `bg-slate-50 dark:bg-slate-700`
- **Note:** Avoid inline `style="background: #f8fafc"` - doesn't adapt to dark mode

---

## Dark Mode

### Colors That Adapt
- Use Bootstrap classes with dark mode support: `bg-primary`, `text-body-secondary`
- Use Tailwind dark: prefix: `bg-slate-50 dark:bg-slate-700`
- Use CSS variables: `var(--bs-tertiary-bg, #f8f9fa)`

### Colors That Don't Adapt (Avoid)
- Inline styles with hardcoded light colors: `style="background: #f8fafc"`
- `bg-secondary` badges (poor contrast in dark mode)

### Chart Labels
- Charts use `chartTheme.fontColor` which adapts via getter function
- Charts must be registered with `registerChart()` for dynamic dark mode updates
- Example:
```javascript
const chart = registerChart(new Chart(ctx, config));
```

### Dark Mode CSS Overrides
When light mode styles need preserving but dark mode needs different colors:
```css
/* In app.css or inline <style> */
.dark .my-element {
    background: #darker-color !important;
    color: #lighter-text !important;
}
```

---

## Form Inputs

### Standard Input Field
```html
<div class="form-group col-md-6 mb-3">
    <label for="field_name" class="form-label">
        <i class="fa fa-icon me-1"></i> Field Label
    </label>
    <input type="text" name="field_name" id="field_name" class="form-control"
           value="{{ $model->field_name ?? old('field_name') }}">
    <small class="text-body-secondary">Helper text description</small>
</div>
```

### Required Field
```html
<label for="field_name" class="form-label">
    <i class="fa fa-icon me-1"></i> Field Label <span class="text-danger">*</span>
</label>
```

### Input with Prefix/Suffix (Currency, Percentage)
```html
<div class="input-group">
    <span class="input-group-text">$</span>
    <input type="number" name="amount" class="form-control" step="0.01" min="0"
           value="{{ $model->amount ?? old('amount') }}">
</div>

<div class="input-group">
    <input type="number" name="percentage" class="form-control" step="0.01" min="0" max="1"
           value="{{ $model->percentage ?? old('percentage') }}">
    <span class="input-group-text">%</span>
</div>
```

### Select Field
```html
<select name="mode" id="mode" class="form-control form-select" required>
    <option value="option1" {{ ($model->mode ?? old('mode')) == 'option1' ? 'selected' : '' }}>
        Option 1
    </option>
</select>
```

### Form Layout (Two Columns)
```html
<div class="row">
    <div class="form-group col-md-6 mb-3">
        <!-- First field -->
    </div>
    <div class="form-group col-md-6 mb-3">
        <!-- Second field -->
    </div>
</div>
```

### Form Section Divider
```html
<hr class="my-3">
```

### Submit Buttons
```html
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('model.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
```

### Font Size Override (Optional)
```html
<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>
```

---

## Icons

### Entity Icons (for index pages)
| Category | Icon | Entities |
|----------|------|----------|
| Financial | `fa-landmark` | Funds |
| Financial | `fa-coins` | Assets, Portfolio Assets |
| Financial | `fa-dollar-sign` | Cash Deposits |
| Financial | `fa-hand-holding-usd` | Deposit Requests |
| Financial | `fa-balance-scale` | Account Balances |
| Financial | `fa-exchange-alt` | Transactions |
| Reports | `fa-file-alt` | All report types |
| Portfolios | `fa-briefcase` | Portfolios |
| Portfolios | `fa-chart-pie` | Trade Portfolios |
| Portfolios | `fa-list` | Trade Portfolio Items |
| Goals | `fa-bullseye` | Goals, Account Goals |
| People | `fa-users` | Accounts, People |
| People | `fa-user-cog` | Users |
| Contact | `fa-phone` | Phones |
| Contact | `fa-map-marker-alt` | Addresses |
| Contact | `fa-id-card` | ID Documents |
| Rules | `fa-link` | Matching Rules, Transaction Matchings |
| Time | `fa-clock` | Scheduled Jobs |
| Time | `fa-calendar-alt` | Schedules |
| History | `fa-history` | Change Logs, Asset Change Logs |
| Data | `fa-chart-line` | Asset Prices |

### Action Icons
- Create: `fa-plus`
- View: `fa-eye`
- Edit: `fa-edit`
- Delete: `fa-trash`
- PDF: `fa-file-pdf`
- Back: (text only, no icon)
- Bulk: `fa-users`
- Charts: `fa-chart-line`, `fa-chart-bar`
- Admin: `fa-user-shield`

### Icon Spacing
- Always include spacing: `me-1` or `me-2` after icon before text
- Use `me-2` for header icons (before title)
- Use `me-1` for button icons (before button text)

---

## Dropdown Fields Reference

Fields with predefined values should use `<select>` dropdowns instead of free text inputs. Use the `*Ext` model's static map methods to get labels.

### Transaction
| Field | Values | Label Source |
|-------|--------|--------------|
| `type` | PUR, INI, SAL, MAT, BOR, REP | `TransactionExt::$typeMap` |
| `status` | P, C, S | `TransactionExt::$statusMap` |
| `flags` | A, C, U, null | `TransactionExt::$flagsMap` |

### Cash Deposit
| Field | Values | Label Source |
|-------|--------|--------------|
| `status` | PEN, DEP, ALL, COM, CAN | `CashDepositExt::statusMap()` |

### Deposit Request
| Field | Values | Label Source |
|-------|--------|--------------|
| `status` | PEN, APP, REJ, COM | `DepositRequestExt::statusMap()` |

### Fund Report
| Field | Values | Label Source |
|-------|--------|--------------|
| `type` | ALL, ADM, TRADING_BANDS | `FundReportExt::$typeMap` |

### Account Report
| Field | Values | Label Source |
|-------|--------|--------------|
| `type` | ALL | `AccountReportExt::$typeMap` |

### Schedule
| Field | Values | Label Source |
|-------|--------|--------------|
| `type` | DOM, DOW, DOQ, DOY | `ScheduleExt::$typeMap` |

### Scheduled Job
| Field | Values | Label Source |
|-------|--------|--------------|
| `entity_descr` | fund_report, trade_band_report, transaction | `ScheduledJobExt::$entityMap` |

### Trade Portfolio
| Field | Values | Label Source |
|-------|--------|--------------|
| `mode` | STD, MAX | (inline: Standard, Maximum) |

### Trade Portfolio Item
| Field | Values | Label Source |
|-------|--------|--------------|
| `type` | STK, FUND, CRYPTO, OTHER | `TradePortfolioItemExt::typeMap()` |

### Goal
| Field | Values | Label Source |
|-------|--------|--------------|
| `target_type` | TOTAL, 4PCT | `GoalExt::targetTypeMap()` |

### ID Document
| Field | Values | Label Source |
|-------|--------|--------------|
| `type` | CPF, RG, CNH, Passport, SSN, other | (inline labels) |

### Address
| Field | Values | Label Source |
|-------|--------|--------------|
| `type` | home, work, other | (inline: Home, Work, Other) |

### Phone
| Field | Values | Label Source |
|-------|--------|--------------|
| `type` | mobile, home, work, other | (inline: Mobile, Home, Work, Other) |

### Example Implementation
```php
// In controller - pass map to view
public function create()
{
    return view('transactions.create', [
        'typeMap' => TransactionExt::$typeMap,
        'statusMap' => TransactionExt::$statusMap,
    ]);
}
```

```html
<!-- In blade template -->
<select name="type" class="form-control form-select" required>
    <option value="">-- Select Type --</option>
    @foreach($typeMap as $value => $label)
        <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>
            {{ $label }}
        </option>
    @endforeach
</select>
```
