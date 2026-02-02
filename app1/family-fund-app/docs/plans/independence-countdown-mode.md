# Financial Independence Goal - Countdown Mode

**Date:** 2026-02-02
**Status:** Planned

## Overview

Add a "Countdown to Zero" mode to the Financial Independence Goal feature. This allows users to set a target date when the fund can reach zero (e.g., when pension kicks in), rather than assuming perpetual withdrawals.

## Current vs New Model

| Aspect | Perpetual (4% Rule) | Countdown to Zero |
|--------|---------------------|-------------------|
| Target | expenses / withdrawal_rate | PV of annuity |
| Duration | Forever | Until target date |
| Example ($120k/yr, 7% growth) | $3,000,000 | $842,880 (10 years) |
| Use case | Traditional retirement | Bridge to pension/SS |

## Math: Present Value of Annuity

```
Required = yearly_expenses × [(1 - (1 + growth_rate)^(-years)) / growth_rate]
```

Example: $120k/yr for 10 years at 7% growth:
```
= $120,000 × [(1 - 1.07^(-10)) / 0.07]
= $120,000 × 7.024
= $842,880 needed
```

---

## Implementation Tasks

### Task 1: Database Migration

**Add columns to `funds` table:**

```php
Schema::table('funds', function (Blueprint $table) {
    $table->enum('independence_mode', ['perpetual', 'countdown'])->default('perpetual');
    $table->date('independence_target_date')->nullable();
});
```

**Files:**
- `database/migrations/xxxx_add_independence_mode_to_funds.php`

**Acceptance:**
- Migration runs successfully
- Rollback works

---

### Task 2: Model Methods

**Add to FundExt.php:**

```php
/**
 * Get independence mode ('perpetual' or 'countdown').
 */
public function getIndependenceMode(): string
{
    return $this->independence_mode ?? 'perpetual';
}

/**
 * Get target date for countdown mode.
 */
public function getIndependenceTargetDate(): ?Carbon
{
    return $this->independence_target_date
        ? Carbon::parse($this->independence_target_date)
        : null;
}

/**
 * Get years remaining until target date.
 */
public function getYearsRemaining(string $asOf): ?float
{
    if ($this->getIndependenceMode() !== 'countdown') {
        return null;
    }
    $target = $this->getIndependenceTargetDate();
    if (!$target) return null;

    $now = Carbon::parse($asOf);
    return max(0, $now->floatDiffInYears($target, false));
}

/**
 * Calculate target value for countdown mode (PV of annuity).
 * Formula: PMT × [(1 - (1 + r)^(-n)) / r]
 */
public function calculateCountdownTargetValue(string $asOf): float
{
    $yearlyExpenses = (float) $this->withdrawal_yearly_expenses;
    $growthRate = $this->getExpectedGrowthRate() / 100;
    $years = $this->getYearsRemaining($asOf);

    if ($years <= 0 || $growthRate <= 0) {
        return $yearlyExpenses * $years; // No growth, simple multiplication
    }

    // PV of annuity formula
    return $yearlyExpenses * ((1 - pow(1 + $growthRate, -$years)) / $growthRate);
}

/**
 * Get funding percentage for countdown mode.
 */
public function getCountdownFundingPct(string $asOf): float
{
    $required = $this->calculateCountdownTargetValue($asOf);
    if ($required <= 0) return 100;

    $current = $this->withdrawalAdjustedValue($asOf);
    return min(100, ($current / $required) * 100);
}
```

**Update `withdrawalTargetValue()`:**
```php
public function withdrawalTargetValue(): float
{
    if ($this->getIndependenceMode() === 'countdown') {
        return $this->calculateCountdownTargetValue(now()->toDateString());
    }
    // Existing perpetual calculation
    return (float) $this->withdrawal_yearly_expenses * (100 / $this->getWithdrawalRate());
}
```

**Files:**
- `app/Models/FundExt.php`
- `app/Models/Fund.php` (add to $fillable, $casts)

**Acceptance:**
- All new methods have tests
- `withdrawalTargetValue()` returns correct value for both modes

---

### Task 3: Controller & Validation

**Update FundControllerExt.php:**

```php
// Validation rules
'independence_mode' => 'nullable|in:perpetual,countdown',
'independence_target_date' => 'nullable|date|after:today',

// Update logic
'independence_mode' => $request->independence_mode ?? 'perpetual',
'independence_target_date' => $request->independence_mode === 'countdown'
    ? $request->independence_target_date
    : null,
```

**Files:**
- `app/Http/Controllers/WebV1/FundControllerExt.php`

**Acceptance:**
- Validation works for both modes
- Target date cleared when switching to perpetual

---

### Task 4: Edit Form UI

**Update withdrawal_goal_edit.blade.php:**

Add mode toggle and date picker:

```blade
{{-- Independence Mode --}}
<div class="mb-4">
    <label class="form-label"><strong>Goal Type</strong></label>
    <div class="btn-group w-100" role="group">
        <input type="radio" class="btn-check" name="independence_mode"
               id="mode_perpetual" value="perpetual"
               {{ ($fund->independence_mode ?? 'perpetual') === 'perpetual' ? 'checked' : '' }}>
        <label class="btn btn-outline-primary" for="mode_perpetual">
            Perpetual ({{ $fund->withdrawal_rate ?? 4 }}% Rule)
        </label>

        <input type="radio" class="btn-check" name="independence_mode"
               id="mode_countdown" value="countdown"
               {{ ($fund->independence_mode ?? 'perpetual') === 'countdown' ? 'checked' : '' }}>
        <label class="btn btn-outline-primary" for="mode_countdown">
            Independence until date
        </label>
    </div>
</div>

{{-- Target Date (shown only in countdown mode) --}}
<div class="mb-4" id="target-date-section" style="display: none;">
    <label for="independence_target_date" class="form-label">
        <strong>Independence Until</strong>
        <small class="text-muted">(When fund can reach zero)</small>
    </label>
    <input type="date" class="form-control" id="independence_target_date"
           name="independence_target_date"
           value="{{ old('independence_target_date', $fund->independence_target_date) }}"
           min="{{ date('Y-m-d', strtotime('+1 month')) }}">
    <div class="form-text">
        Example: When pension, Social Security, or other income starts.
    </div>
</div>

<script>
// Toggle target date visibility based on mode
document.querySelectorAll('input[name="independence_mode"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('target-date-section').style.display =
            this.value === 'countdown' ? 'block' : 'none';
    });
});
// Initial state
document.querySelector('input[name="independence_mode"]:checked')?.dispatchEvent(new Event('change'));
</script>
```

**Files:**
- `resources/views/funds/withdrawal_goal_edit.blade.php`

**Acceptance:**
- Mode toggle works
- Date picker shows/hides based on mode
- Form submits correctly for both modes

---

### Task 5: Card Display UI

**Update withdrawal_goal_card.blade.php:**

Add mode-specific display:

```blade
@php
    $mode = $withdrawalGoal['independence_mode'] ?? 'perpetual';
    $targetDate = $withdrawalGoal['independence_target_date'] ?? null;
    $yearsRemaining = $withdrawalGoal['years_remaining'] ?? null;
    $fundingPct = $withdrawalGoal['funding_pct'] ?? $progressPct;
@endphp

{{-- Header --}}
<strong>
    <i class="fa fa-bullseye me-2"></i>
    @if($mode === 'countdown' && $targetDate)
        Independence until {{ \Carbon\Carbon::parse($targetDate)->format('M Y') }}
    @else
        Financial Independence Goal
    @endif
</strong>

{{-- For countdown mode, show different metrics --}}
@if($mode === 'countdown')
<div class="mb-3 p-3 rounded" style="background: #fef3c7;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="text-muted">Required Today:</span>
            <strong style="color: #d97706; font-size: 1.25rem;">${{ number_format($targetValue, 0) }}</strong>
        </div>
        <div class="text-end">
            <span class="badge bg-warning text-dark">{{ number_format($yearsRemaining, 1) }} years remaining</span>
        </div>
    </div>
</div>

{{-- Funding status --}}
<div class="alert {{ $fundingPct >= 100 ? 'alert-success' : 'alert-info' }} mb-3">
    <strong>{{ number_format($fundingPct, 1) }}% Funded</strong>
    @if($fundingPct >= 100)
        - On track for independence!
    @else
        - Need ${{ number_format($targetValue - $adjustedValue, 0) }} more
    @endif
</div>
@endif
```

**Update FundTrait.php to pass new data:**
```php
$arr['withdrawal_goal'] = array_merge($withdrawalProgress, [
    'independence_mode' => $fund->getIndependenceMode(),
    'independence_target_date' => $fund->independence_target_date,
    'years_remaining' => $fund->getYearsRemaining($asOf),
    'funding_pct' => $fund->getIndependenceMode() === 'countdown'
        ? $fund->getCountdownFundingPct($asOf)
        : $withdrawalProgress['progress_pct'],
]);
```

**Files:**
- `resources/views/funds/withdrawal_goal_card.blade.php`
- `app/Http/Controllers/Traits/FundTrait.php`

**Acceptance:**
- Perpetual mode displays as before
- Countdown mode shows: target date, years remaining, funding %, required amount
- Both projections still work for perpetual mode

---

### Task 6: Tests

**Add tests for:**

1. `getIndependenceMode()` returns correct default
2. `calculateCountdownTargetValue()` math is correct
3. `getYearsRemaining()` calculates correctly
4. `getCountdownFundingPct()` returns correct percentage
5. `withdrawalTargetValue()` respects mode
6. Edge cases: 0 years remaining, past target date, no growth rate

**Files:**
- `tests/Unit/FundExtTest.php`

**Acceptance:**
- All tests pass
- Coverage for both modes

---

## Task Dependencies

```
Task 1 (Migration)
    ↓
Task 2 (Model Methods)
    ↓
Task 3 (Controller)
    ↓
Task 4 (Edit Form) ←→ Task 5 (Card Display)
    ↓
Task 6 (Tests)
```

---

## UI Mockup

### Edit Form (Countdown Mode)
```
┌─────────────────────────────────────────────────────────────┐
│ Goal Type                                                    │
│ ┌──────────────────────┐ ┌──────────────────────────────┐   │
│ │ Perpetual (4% Rule)  │ │ ● Independence until date    │   │
│ └──────────────────────┘ └──────────────────────────────┘   │
│                                                              │
│ Independence Until                                           │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 2035-01-01                                        📅    │ │
│ └─────────────────────────────────────────────────────────┘ │
│ Example: When pension, Social Security, or other income     │
│ starts.                                                      │
└─────────────────────────────────────────────────────────────┘
```

### Card Display (Countdown Mode)
```
┌─────────────────────────────────────────────────────────────┐
│ 🎯 Independence until Jan 2035                         ✏️   │
├─────────────────────────────────────────────────────────────┤
│ Required Today: $842,880              8.9 years remaining   │
│                                                              │
│ ████████████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░  70.5%     │
│                                                              │
│ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐ │
│ │ Current Value   │ │ Yearly Need     │ │ Still Required  │ │
│ │ $594,331        │ │ $120,000/yr     │ │ $248,549        │ │
│ │ 70.5% funded    │ │ $10,000/mo      │ │ to be on track  │ │
│ └─────────────────┘ └─────────────────┘ └─────────────────┘ │
│                                                              │
│ ℹ️ 70.5% Funded - Need $248,549 more to be fully on track   │
└─────────────────────────────────────────────────────────────┘
```

---

## Notes

- Target value changes over time (decreases as target date approaches)
- "% Funded" is more meaningful than "years to reach" for countdown mode
- Keep perpetual projections ("with/without withdrawals") for perpetual mode only
