<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('funds.index') }}">Funds</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('funds.show', $fund->id) }}">{{ $fund->name }}</a>
        </li>
        <li class="breadcrumb-item active">4% Rule Goal</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card" style="border: 2px solid #0d9488;">
                        <div class="card-header" style="background: #0d9488; color: white;">
                            <i class="fa fa-bullseye fa-lg"></i>
                            <strong>4% Rule Retirement Goal</strong>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('funds.four_pct_goal.update', $fund->id) }}">
                                @csrf
                                @method('PUT')

                                {{-- Explanation --}}
                                <div class="alert alert-info mb-4">
                                    <i class="fa fa-info-circle me-2"></i>
                                    The <strong>withdrawal rule</strong> suggests you need (100 / rate)x your annual expenses saved for retirement.
                                    Enter your yearly expenses and desired withdrawal rate below to calculate your target.
                                </div>

                                {{-- Yearly Expenses --}}
                                <div class="mb-4">
                                    <label for="four_pct_yearly_expenses" class="form-label">
                                        <strong>Yearly Expenses</strong>
                                        <small class="text-muted">(How much do you need per year?)</small>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control form-control-lg" id="four_pct_yearly_expenses"
                                               name="four_pct_yearly_expenses"
                                               value="{{ old('four_pct_yearly_expenses', $fund->four_pct_yearly_expenses) }}"
                                               placeholder="80000" min="0" step="1000">
                                        <span class="input-group-text">/year</span>
                                    </div>
                                    <div class="form-text">Leave empty to remove the 4% goal.</div>
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

                                {{-- Live Preview --}}
                                <div class="mb-4 p-3 rounded" style="background: #f0fdfa;" id="preview-section">
                                    <div class="row text-center">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Target Value (Expenses / Rate)</small>
                                            <span id="preview-target" style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">$0</span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Monthly Budget</small>
                                            <span id="preview-monthly" style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">$0</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Net Worth Percentage --}}
                                <div class="mb-4">
                                    <label for="four_pct_net_worth_pct" class="form-label">
                                        <strong>Net Worth Percentage</strong>
                                        <small class="text-muted">(What portion of fund value to use?)</small>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="four_pct_net_worth_pct"
                                               name="four_pct_net_worth_pct"
                                               value="{{ old('four_pct_net_worth_pct', $fund->four_pct_net_worth_pct ?? 100) }}"
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
                            <i class="fa fa-question-circle"></i> About the Withdrawal Rule
                        </div>
                        <div class="card-body">
                            <p class="small">
                                The <strong>Withdrawal Rule</strong> is a retirement guideline suggesting you can withdraw a percentage of your portfolio annually without running out of money.
                            </p>
                            <p class="small">
                                <strong>Common rates:</strong><br>
                                <span class="text-muted">4% - Traditional (25x expenses)</span><br>
                                <span class="text-muted">3.5% - Conservative (28.6x expenses)</span><br>
                                <span class="text-muted">3% - Very conservative (33.3x expenses)</span>
                            </p>
                            <p class="small mb-0">
                                <strong>Formula:</strong><br>
                                Target = Yearly Expenses / (Rate / 100)<br>
                                <span class="text-muted">(e.g., $80k/year at 4% = $2M target)</span>
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
    function updatePreview() {
        var expenses = parseFloat($('#four_pct_yearly_expenses').val()) || 0;
        var rate = parseFloat($('#withdrawal_rate').val()) || 4;
        var target = rate > 0 ? expenses / (rate / 100) : 0;
        var monthly = expenses / 12;

        $('#preview-target').text('$' + target.toLocaleString('en-US', {maximumFractionDigits: 0}));
        $('#preview-monthly').text('$' + monthly.toLocaleString('en-US', {maximumFractionDigits: 0}));

        if (expenses > 0) {
            $('#preview-section').show();
        } else {
            $('#preview-section').hide();
        }
    }

    $('#four_pct_yearly_expenses, #withdrawal_rate').on('input', updatePreview);
    updatePreview(); // Initial call
});
</script>
@endpush
</x-app-layout>
