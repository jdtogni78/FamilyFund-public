<x-app-layout>
@section('content')
@php
    $isAdmin = (bool) (auth()->user()?->is_admin());
    $sv = $currentShareValue ?? 0;
@endphp
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('credit_lines.index', ['account' => $account->id]) }}">Credit Lines</a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}">#{{ $line->id }}</a>
    </li>
    <li class="breadcrumb-item active">Simulator</li>
</ol>

<div class="container-fluid">
    @include('coreui-templates.common.errors')

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Payment simulator &mdash; Credit Line #{{ $line->id }}</strong>
            <span class="badge bg-info">{{ $line->status }}</span>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Account</dt>
                <dd class="col-sm-3">{{ $account->nickname ?? ('#' . $account->id) }}</dd>
                <dt class="col-sm-3">Principal (shares)</dt>
                <dd class="col-sm-3">{{ number_format($line->principal_shares, 4) }}</dd>

                <dt class="col-sm-3">Outstanding (shares)</dt>
                <dd class="col-sm-3">{{ number_format($line->outstanding_shares, 4) }}</dd>
                <dt class="col-sm-3">Current share value</dt>
                <dd class="col-sm-3">${{ number_format($sv, 4) }}</dd>
            </dl>
            <p class="text-muted small mt-3 mb-0">
                You owe {{ number_format($line->outstanding_shares, 4) }} shares
                @if($sv > 0)
                    (currently valued at ${{ number_format($line->outstanding_shares * $sv, 2) }})
                @endif. The share count is what you owe back &mdash; it doesn't change with the market.
                The dollar value will move up or down with the fund's share price.
            </p>
        </div>
    </div>

    @php $mode = $mode ?? 'payment'; @endphp
    <div class="card mb-3">
        <div class="card-header"><strong>Hypothetical scenario</strong></div>
        <div class="card-body">
            <form method="GET" action="{{ route('credit_lines.simulator', ['line' => $line->id]) }}" class="row g-2 align-items-end">
                <div class="col-12 mb-2">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="mode" id="mode_payment" value="payment"
                               {{ $mode === 'payment' ? 'checked' : '' }}
                               onclick="document.getElementById('payment_input').style.display=''; document.getElementById('time_input').style.display='none';">
                        <label class="form-check-label" for="mode_payment">Compute payoff from monthly payment</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="mode" id="mode_time" value="time"
                               {{ $mode === 'time' ? 'checked' : '' }}
                               onclick="document.getElementById('payment_input').style.display='none'; document.getElementById('time_input').style.display='';">
                        <label class="form-check-label" for="mode_time">Compute monthly payment from target time</label>
                    </div>
                </div>
                <div class="col-md-4" id="payment_input" style="{{ $mode === 'payment' ? '' : 'display:none;' }}">
                    <label class="form-label" for="monthly_payment_usd">Monthly payment (USD)</label>
                    <input type="number"
                           name="monthly_payment_usd"
                           id="monthly_payment_usd"
                           step="0.01"
                           min="0.01"
                           class="form-control"
                           value="{{ $monthlyPaymentUsd !== null ? number_format($monthlyPaymentUsd, 2, '.', '') : '' }}">
                </div>
                <div class="col-md-4" id="time_input" style="{{ $mode === 'time' ? '' : 'display:none;' }}">
                    <label class="form-label" for="target_months">Target payoff time (months)</label>
                    <input type="number"
                           name="target_months"
                           id="target_months"
                           step="1"
                           min="1"
                           max="600"
                           class="form-control"
                           value="{{ $targetMonths !== null ? (int) $targetMonths : '' }}">
                    <small class="text-muted">Integer between 1 and 600.</small>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Simulate</button>
                    <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}" class="btn btn-link">Back</a>
                </div>
            </form>
            @if(!empty($simError))
                <div class="alert alert-danger mt-3 mb-0">{{ $simError }}</div>
            @endif
            <p class="text-muted small mt-3 mb-0">
                The simulator is read-only &mdash; nothing is recorded against the line.
                Each scenario assumes the same monthly USD payment and varies only the
                fund's annual growth-rate assumption.
            </p>
        </div>
    </div>

    @if(!empty($results))
        @php
            $labelMap = [
                'conservative' => 'Conservative (expected &times; 0.8)',
                'expected'     => 'Expected',
                'aggressive'   => 'Aggressive (expected &times; 1.2)',
            ];
            $rowOrder = ['conservative', 'expected', 'aggressive'];
        @endphp

        <div class="card mb-3">
            <div class="card-header"><strong>Scenario summary</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Scenario</th>
                            <th>Growth rate</th>
                            @if($mode === 'time')
                                <th class="text-end">Required monthly payment (USD)</th>
                            @endif
                            <th>Payoff month</th>
                            <th>Payoff date</th>
                            <th class="text-end">Total paid (USD)</th>
                            <th class="text-end">Total paid (shares)</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($rowOrder as $key)
                        @php $r = $results[$key]; @endphp
                        <tr>
                            <td>{!! $labelMap[$key] !!}</td>
                            <td>{{ number_format($r->annual_growth_rate_pct, 2) }}%</td>
                            @if($mode === 'time')
                                <td class="text-end"><strong>${{ number_format($solvedPayments[$key] ?? 0, 2) }}</strong></td>
                            @endif
                            <td>
                                @if($r->payoff_month === null)
                                    <span class="text-danger">&gt; {{ \App\Services\CreditLine\Simulation\PaymentSimulator::MONTH_CAP }} (capped)</span>
                                @else
                                    {{ $r->payoff_month }}
                                @endif
                            </td>
                            <td>{{ $r->payoff_date ?? '—' }}</td>
                            <td class="text-end">${{ number_format($r->total_paid_usd, 2) }}</td>
                            <td class="text-end">{{ number_format($r->total_paid_shares, 4) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <p class="text-muted small mt-3 mb-0">
                    Higher growth means the share price rises faster, so each fixed-USD payment buys fewer shares
                    &mdash; aggressive scenarios typically pay off slower than conservative ones.
                    The share count is what you owe back; the dollar value moves with the fund's share price.
                </p>
            </div>
        </div>

        @php
            // Build the three-line cumulative-shares-paid chart inline using
            // QuickChart, matching the pattern in _trajectory_chart.blade.php.
            $allMonths = [];
            foreach ($rowOrder as $k) {
                foreach ($results[$k]->monthly_series as $row) {
                    $allMonths[$row['month']] = true;
                }
            }
            $months = array_keys($allMonths);
            sort($months);
            $labels = array_map(fn ($m) => 'M' . $m, $months);

            $alignSeries = function (array $series) use ($months) {
                $byMonth = [];
                $last = 0.0;
                foreach ($series as $p) {
                    $byMonth[$p['month']] = $p['cumulative_shares_paid'];
                }
                $out = [];
                foreach ($months as $m) {
                    if (isset($byMonth[$m])) {
                        $last = $byMonth[$m];
                    }
                    $out[] = $last;
                }
                return $out;
            };

            $datasets = [
                [
                    'label'       => 'Conservative',
                    'data'        => $alignSeries($results['conservative']->monthly_series),
                    'borderColor' => 'rgba(120,120,120,0.9)',
                    'borderDash'  => [6, 4],
                    'fill'        => false,
                    'pointRadius' => 0,
                    'borderWidth' => 2,
                ],
                [
                    'label'       => 'Expected',
                    'data'        => $alignSeries($results['expected']->monthly_series),
                    'borderColor' => '#2563eb',
                    'fill'        => false,
                    'pointRadius' => 0,
                    'borderWidth' => 3,
                ],
                [
                    'label'       => 'Aggressive',
                    'data'        => $alignSeries($results['aggressive']->monthly_series),
                    'borderColor' => '#16a34a',
                    'borderDash'  => [4, 4],
                    'fill'        => false,
                    'pointRadius' => 0,
                    'borderWidth' => 2,
                ],
            ];

            $chartConfig = [
                'type' => 'line',
                'data' => [
                    'labels'   => $labels,
                    'datasets' => $datasets,
                ],
                'options' => [
                    'responsive' => false,
                    'plugins'    => ['title' => ['display' => true, 'text' => 'Cumulative shares repaid (simulated)']],
                    'scales'     => [
                        'yAxes' => [['ticks' => ['beginAtZero' => true]]],
                    ],
                ],
            ];
            $base = config('quickchart.public_url', config('quickchart.base_url', 'http://quickchart:3400'));
            $chartUrl = $base . '/chart?c=' . urlencode(json_encode($chartConfig));
        @endphp

        <div class="card mb-3">
            <div class="card-header"><strong>Cumulative-shares-paid projection</strong></div>
            <div class="card-body">
                <div class="text-center mb-2">
                    <img src="{{ $chartUrl }}" alt="Three-scenario payment simulation chart" style="max-width:100%; height:auto;">
                </div>
                <p class="text-muted small mb-0">
                    @if($mode === 'time')
                        Three scenarios — each line uses the per-scenario required monthly payment to hit
                        the {{ $targetMonths }}-month target.
                    @else
                        Three scenarios assuming the same ${{ number_format($monthlyPaymentUsd, 2) }}/month payment.
                    @endif
                    The line shows shares repaid over time &mdash; not dollars.
                </p>
            </div>
        </div>
    @endif
</div>
</x-app-layout>
