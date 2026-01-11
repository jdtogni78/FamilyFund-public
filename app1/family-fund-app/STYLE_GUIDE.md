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

**Pending:**
- [ ] Review trade_portfolios detail views (27 files)
- [ ] Review accounts detail views (23 files)
- [ ] Review other detail/show pages

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
