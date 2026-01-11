# Family Fund Style Guide

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

### Font Awesome Icons
- Always include spacing: `me-1` or `me-2` after icon before text
- Common icons:
  - Create: `fa-plus`
  - View: `fa-eye`
  - Edit: `fa-edit`
  - Delete: `fa-trash`
  - PDF: `fa-file-pdf`
  - Back: (text only, no icon)
  - Charts: `fa-chart-line`, `fa-chart-bar`
  - Users: `fa-user`, `fa-user-shield` (admin)
