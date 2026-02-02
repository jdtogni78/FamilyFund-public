<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('funds.index') }}">Funds</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('funds.show', $fund->id) }}">{{ $fund->name }}</a>
        </li>
        <li class="breadcrumb-item active">Financial Independence Goal</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card" style="border: 2px solid #0d9488;">
                        <div class="card-header" style="background: #0d9488; color: white;">
                            <i class="fa fa-bullseye fa-lg"></i>
                            <strong>Financial Independence Goal</strong>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('funds.withdrawal_goal.update', $fund->id) }}">
                                @csrf
                                @method('PUT')

                                {{-- Explanation --}}
                                <div class="alert alert-info mb-4" id="mode-explanation">
                                    <i class="fa fa-info-circle me-2"></i>
                                    <span id="explanation-text">
                                        The <strong>withdrawal rule</strong> suggests you need (100 / rate)x your annual expenses saved for retirement.
                                        Enter your yearly expenses and desired withdrawal rate below to calculate your target.
                                    </span>
                                </div>

                                {{-- Goal Type Toggle --}}
                                <div class="mb-4">
                                    <label class="form-label">
                                        <strong>Goal Type</strong>
                                    </label>
                                    <div class="btn-group w-100" role="group" aria-label="Independence mode toggle">
                                        <input type="radio" class="btn-check" name="independence_mode" id="mode_perpetual"
                                               value="perpetual" {{ old('independence_mode', $fund->independence_mode ?? 'perpetual') === 'perpetual' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="mode_perpetual">
                                            <i class="fa fa-infinity me-1"></i> Perpetual (X% Rule)
                                        </label>

                                        <input type="radio" class="btn-check" name="independence_mode" id="mode_countdown"
                                               value="countdown" {{ old('independence_mode', $fund->independence_mode ?? 'perpetual') === 'countdown' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="mode_countdown">
                                            <i class="fa fa-calendar-check me-1"></i> Independence until date
                                        </label>
                                    </div>
                                </div>

                                {{-- Target Date (shown only in countdown mode) --}}
                                <div class="mb-4" id="target-date-section" style="{{ old('independence_mode', $fund->independence_mode ?? 'perpetual') === 'countdown' ? '' : 'display: none;' }}">
                                    <label for="independence_target_date" class="form-label">
                                        <strong>Independence Until</strong>
                                        <small class="text-muted">(When fund can reach zero)</small>
                                    </label>
                                    <input type="date" class="form-control" id="independence_target_date"
                                           name="independence_target_date"
                                           value="{{ old('independence_target_date', $fund->independence_target_date ? $fund->independence_target_date->format('Y-m-d') : '') }}"
                                           min="{{ now()->addMonth()->format('Y-m-d') }}">
                                    <div class="form-text">
                                        When pension, Social Security, or other income starts.
                                    </div>
                                </div>

                                {{-- Yearly Expenses --}}
                                <div class="mb-4">
                                    <label for="withdrawal_yearly_expenses" class="form-label">
                                        <strong>Yearly Expenses</strong>
                                        <small class="text-muted">(How much do you need per year?)</small>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control form-control-lg" id="withdrawal_yearly_expenses"
                                               name="withdrawal_yearly_expenses"
                                               value="{{ old('withdrawal_yearly_expenses', $fund->withdrawal_yearly_expenses) }}"
                                               placeholder="80000" min="0" step="1000">
                                        <span class="input-group-text">/year</span>
                                    </div>
                                    <div class="form-text">Leave empty to remove the withdrawal goal.</div>
                                </div>

                                {{-- Withdrawal Rate --}}
                                <div class="mb-4">
                                    <label for="withdrawal_rate" class="form-label">
                                        <strong>Withdrawal Rate</strong>
                                        <small class="text-muted">(What percentage can you safely withdraw per year?)</small>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="withdrawal_rate"
                                               name="withdrawal_rate"
                                               value="{{ old('withdrawal_rate', $fund->withdrawal_rate ?? 4) }}"
                                               min="0.5" max="10" step="0.25">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">
                                        Common rates: 4% (traditional), 3.5% (conservative), 3% (very conservative).
                                    </div>
                                </div>

                                {{-- Expected Growth Rate --}}
                                <div class="mb-4">
                                    <label for="expected_growth_rate" class="form-label">
                                        <strong>Expected Growth Rate</strong>
                                        <small class="text-muted">(Expected annual return on investments)</small>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="expected_growth_rate"
                                               name="expected_growth_rate"
                                               value="{{ old('expected_growth_rate', $fund->expected_growth_rate ?? 7) }}"
                                               min="0.5" max="20" step="0.5">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">
                                        Common rates: 7% (stocks), 5% (balanced), 3% (conservative). Used for target reach projection.
                                    </div>
                                </div>

                                {{-- Live Preview --}}
                                <div class="mb-4 p-3 rounded" style="background: #f0fdfa;" id="preview-section">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <small class="text-muted d-block" id="preview-target-label">Target Value (Expenses / Rate)</small>
                                            <span id="preview-target" style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">$0</span>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Monthly Budget</small>
                                            <span id="preview-monthly" style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">$0</span>
                                        </div>
                                        <div class="col-md-4" id="preview-years-row" style="display: none;">
                                            <small class="text-muted d-block">Time Remaining</small>
                                            <span id="preview-years" style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">0 years</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Net Worth Percentage --}}
                                <div class="mb-4">
                                    <label for="withdrawal_net_worth_pct" class="form-label">
                                        <strong>Net Worth Percentage</strong>
                                        <small class="text-muted">(What portion of fund value to use?)</small>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="withdrawal_net_worth_pct"
                                               name="withdrawal_net_worth_pct"
                                               value="{{ old('withdrawal_net_worth_pct', $fund->withdrawal_net_worth_pct ?? 100) }}"
                                               min="1" max="100" step="1">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">
                                        Use 100% for full fund value, or lower to reflect your allocation (e.g., 50% if sharing the fund).
                                    </div>
                                </div>

                                {{-- Buttons --}}
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('funds.show', $fund->id) }}" class="btn btn-secondary">
                                        <i class="fa fa-times me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary" style="background: #0d9488; border-color: #0d9488;">
                                        <i class="fa fa-save me-1"></i> Save Goal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Help Card --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-question-circle"></i> About Goal Types
                        </div>
                        <div class="card-body">
                            <p class="small">
                                <strong><i class="fa fa-infinity"></i> Perpetual Mode</strong><br>
                                The traditional retirement rule suggesting you need (100/rate)x your annual expenses. Fund never depletes.
                            </p>
                            <p class="small">
                                <strong>Common rates:</strong><br>
                                <span class="text-muted">4% - Traditional (25x expenses)</span><br>
                                <span class="text-muted">3.5% - Conservative (28.6x expenses)</span><br>
                                <span class="text-muted">3% - Very conservative (33.3x expenses)</span>
                            </p>
                            <hr>
                            <p class="small">
                                <strong><i class="fa fa-calendar-check"></i> Countdown Mode</strong><br>
                                For bridging to another income source (pension, Social Security). Fund can safely reach zero at target date.
                            </p>
                            <p class="small mb-0">
                                <strong>Formula:</strong><br>
                                Present Value of Annuity<br>
                                <span class="text-muted">PV = PMT × [(1-(1+r)^(-n))/r]</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
$(document).ready(function() {
    function getMode() {
        return $('input[name="independence_mode"]:checked').val() || 'perpetual';
    }

    function updateModeUI() {
        var mode = getMode();
        var isCountdown = mode === 'countdown';

        // Show/hide target date section
        if (isCountdown) {
            $('#target-date-section').slideDown(200);
        } else {
            $('#target-date-section').slideUp(200);
        }

        // Update explanation text
        if (isCountdown) {
            $('#explanation-text').html(
                'In <strong>countdown mode</strong>, calculate how much you need to fund withdrawals until a target date. ' +
                'The fund can safely reach zero when your other income (pension, Social Security) starts.'
            );
        } else {
            $('#explanation-text').html(
                'The <strong>withdrawal rule</strong> suggests you need (100 / rate)x your annual expenses saved for retirement. ' +
                'Enter your yearly expenses and desired withdrawal rate below to calculate your target.'
            );
        }

        // Update preview labels based on mode
        if (isCountdown) {
            $('#preview-target-label').text('Target Value (Present Value)');
        } else {
            $('#preview-target-label').text('Target Value (Expenses / Rate)');
        }

        updatePreview();
    }

    function calculatePresentValue(annualPayment, rate, years) {
        // PV = PMT × [(1 - (1 + r)^(-n)) / r]
        if (rate <= 0 || years <= 0) return 0;
        var r = rate / 100;
        return annualPayment * ((1 - Math.pow(1 + r, -years)) / r);
    }

    function getYearsRemaining() {
        var targetDate = $('#independence_target_date').val();
        if (!targetDate) return 0;

        var target = new Date(targetDate);
        var now = new Date();
        var diffMs = target - now;
        var diffDays = diffMs / (1000 * 60 * 60 * 24);
        return diffDays / 365.25;
    }

    function updatePreview() {
        var expenses = parseFloat($('#withdrawal_yearly_expenses').val()) || 0;
        var rate = parseFloat($('#withdrawal_rate').val()) || 4;
        var growthRate = parseFloat($('#expected_growth_rate').val()) || 7;
        var monthly = expenses / 12;
        var mode = getMode();
        var target = 0;

        if (mode === 'countdown') {
            var years = getYearsRemaining();
            if (years > 0) {
                target = calculatePresentValue(expenses, growthRate, years);
            }
            // Show years remaining
            if (years > 0) {
                $('#preview-years').text(years.toFixed(1) + ' years');
                $('#preview-years-row').show();
            } else {
                $('#preview-years-row').hide();
            }
        } else {
            target = rate > 0 ? expenses / (rate / 100) : 0;
            $('#preview-years-row').hide();
        }

        $('#preview-target').text('$' + target.toLocaleString('en-US', {maximumFractionDigits: 0}));
        $('#preview-monthly').text('$' + monthly.toLocaleString('en-US', {maximumFractionDigits: 0}));

        if (expenses > 0) {
            $('#preview-section').show();
        } else {
            $('#preview-section').hide();
        }
    }

    // Mode toggle handlers
    $('input[name="independence_mode"]').on('change', updateModeUI);

    // Input handlers for preview update
    $('#withdrawal_yearly_expenses, #withdrawal_rate, #expected_growth_rate, #independence_target_date').on('input change', updatePreview);

    // Initial calls
    updateModeUI();
});
</script>
@endpush
</x-app-layout>
