# PDF & Chart Improvements - Fund Reports

This document summarizes all improvements made to the Fund PDF reports. Apply these same patterns to:
- Web version charts
- Account PDF reports

---

## 1. Chart Service (QuickChartService.php)

### Line Charts
- **Max 24 date labels**: Sparse labels array - show label every Nth point, empty string for others
- **Y-axis formatting**: Thousand comma separators (80,000 not 80000)
- **Point radius**: Reduced to 2px for cleaner look
- **Colors**: Use `datasetColors` array (30 colors available)

### Bar Charts (Yearly Performance)
- **Data labels inside bars**: Centered, white text, bold
- **Format**: `$76,984` with thousand separators
- **Font**: Arial Black, weight 900, size 16px
- **Last bar highlighted**: Primary color (blue), others secondary (gray)

### Doughnut Charts
- **Legend**: Black (#000000), bold, 14px font
- **Data labels**: Black, bold, 14px, show percentage
- **Formatter**: Handles both decimal (0.256) and percentage (25.6) values
- **Labels only on slices >5%** to avoid clutter

### Stacked Bar Charts
- **Legend**: Bottom position, black bold 12px
- **Data labels**: Black bold 12px, show % for values >5%

---

## 2. Data Processing (ChartBaseTrait.php)

### Critical Fix: array_values()
All chart data must use `array_values()` to convert associative arrays to indexed arrays:
```php
$values = array_values($this->getGraphData($api['monthly_performance']));
```

### Group Performance Charts
- Add S&P500 from dedicated `sp500_monthly_performance` data
- Skip S&P500 variants in asset loop to avoid duplicates:
  ```php
  $sp500Variants = ['S&P500', 'SP500', 'SPY', '^GSPC'];
  ```

### Sparse Labels for Line Charts
```php
if ($totalLabels > $maxLabels) {
    $step = ceil($totalLabels / $maxLabels);
    foreach ($labels as $i => $label) {
        $sparseLabels[] = ($i % $step === 0) ? $label : '';
    }
}
```

---

## 3. Fund PDF Specifics (FundPDF.php)

### Accounts Allocation (Admin)
- **Chart type**: Doughnut
- **Group small accounts (<3%)** into "Others (N accounts, X%)"
- **Include unallocated** shares in chart
- **Values as percentages** for proper display

### Target Allocation
- **Convert decimals to percentages**: `$v['target_share'] * 100`

---

## 4. PDF Templates

### Accounts Table (accounts_table_pdf.blade.php)
- Show **user name**: `$balance['user']['name']`
- Check both **market_value** and **value** keys
- Add **Unallocated row** (yellow background #fef3c7)
- Add **Total row** showing 100% (blue background #1e40af, white text)

### Assets Table (assets_table_pdf.blade.php)
- **Position column**: 6 decimal places for full precision

### Trade Portfolio Section (show_pdf.blade.php)
- **Bordered container**: 2px solid #1e40af
- **Header**: Blue background with portfolio name/ID and date range
- **Combined**: Details + Holdings in single bordered section
- **Sort**: By start_dt descending (newest first)

### Performance Tables
- **Color coding**: Green for positive, red for negative performance
- **2 decimal places** for all percentages

---

## 5. Number Formatting Standards

| Type | Format | Example |
|------|--------|---------|
| Currency | $X,XXX.XX | $76,984.16 |
| Shares | X,XXX.XX | 40,428.91 |
| Position | X.XXXXXX | 0.123456 |
| Percentage | XX.XX% | 87.06% |
| Large Y-axis | X,XXX | 80,000 |

---

## 6. Color Palette (config/quickchart.php)

### Primary Colors
- Primary: #2563eb (Blue)
- Secondary: #64748b (Slate/Gray)
- Success: #16a34a (Green)
- Warning: #d97706 (Amber)
- Danger: #dc2626 (Red)

### 30 Dataset Colors
Extended palette for charts with many series - cycles through colors if more items than colors available.

---

## 7. Font Standards

| Element | Color | Size | Weight |
|---------|-------|------|--------|
| Chart title | #1e293b | 16px | bold |
| Axis labels | #1e293b | 14px | 500 |
| Legend | #1e293b | 13px | 600 |
| Doughnut legend | #000000 | 14px | bold |
| Bar data labels | #ffffff | 16px | 900 |

---

## 8. JavaScript Formatter for Thousand Separators

Used in QuickChart callbacks (handles nested braces):
```javascript
function(v) {
    var n = Math.round(v).toString();
    var r = '';
    for(var i=0; i<n.length; i++) {
        if(i>0 && (n.length-i)%3===0) r+=',';
        r+=n[i];
    }
    return '$'+r
}
```

---

## 9. configToJson Regex

Updated to handle nested braces in JS functions:
```php
'/"(function\s*\([^)]*\)\s*\{(?:[^{}]|\{(?:[^{}]|\{[^{}]*\})*\})*\})"/s'
```

---

## 10. Files Modified

### Services
- `app/Services/QuickChartService.php` - Chart generation

### Traits
- `app/Http/Controllers/Traits/ChartBaseTrait.php` - Chart data processing
- `app/Http/Controllers/Traits/FundPDF.php` - Fund-specific charts

### Config
- `config/quickchart.php` - Colors, dimensions, fonts

### Views
- `resources/views/funds/show_pdf.blade.php` - Main layout
- `resources/views/funds/accounts_table_pdf.blade.php` - Accounts table
- `resources/views/funds/assets_table_pdf.blade.php` - Assets table
- `resources/views/funds/performance_table_pdf.blade.php` - Performance tables
- `resources/views/layouts/pdf_modern.blade.php` - Base PDF layout

---

## 11. TODO: Apply to Account Reports

Apply same patterns to:
- [ ] `app/Http/Controllers/Traits/AccountPDF.php`
- [ ] `resources/views/accounts/show_pdf.blade.php`
- [ ] Account-specific chart methods

## 12. TODO: Apply to Web Version

Apply same chart configurations to web JavaScript charts for visual consistency.
