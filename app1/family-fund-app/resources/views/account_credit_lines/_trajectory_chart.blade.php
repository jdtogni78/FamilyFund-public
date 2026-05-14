{{--
    Multi-generation trajectory chart partial.
    Inputs:
      $trajectory  – output of TrajectoryBuilder::build()
      $chartUrl    – optional pre-rendered QuickChart image URL (string|null).
                     When null, we POST a GET-style config to QuickChart inline.
                     This keeps the partial usable from any context without
                     requiring the controller to pre-render.
--}}
@php
    use App\Services\CreditLine\Reporting\TrajectoryBuilder;

    $trajectory = $trajectory ?? [];
    $original   = $trajectory['original_plan']      ?? [];
    $historical = $trajectory['historical_plans']   ?? [];
    $current    = $trajectory['current_plan']       ?? [];
    $actual     = $trajectory['actual_repayments']  ?? [];
    $projected  = $trajectory['projected_payoff_date'] ?? null;
    $planned    = $trajectory['planned_payoff_date']   ?? null;
    $variance   = $trajectory['variance_days']         ?? null;

    // Collect all unique dates for x-axis labels.
    $allDates = collect()
        ->merge(array_column($original, 'date'))
        ->merge(array_column($current,  'date'))
        ->merge(array_column($actual,   'date'))
        ->unique()
        ->sort()
        ->values()
        ->all();

    $alignSeries = function (array $series) use ($allDates) {
        $byDate = [];
        $last = null;
        foreach ($series as $p) {
            $byDate[$p['date']] = $p['cumulative_shares'];
        }
        $out = [];
        foreach ($allDates as $d) {
            if (isset($byDate[$d])) {
                $last = $byDate[$d];
            }
            $out[] = $last;
        }
        return $out;
    };

    $datasets = [];
    if (!empty($original)) {
        $datasets[] = [
            'label'        => 'Original plan',
            'data'         => $alignSeries($original),
            'borderColor'  => 'rgba(120,120,120,0.7)',
            'borderDash'   => [6, 4],
            'fill'         => false,
            'pointRadius'  => 0,
            'borderWidth'  => 2,
        ];
    }
    foreach ($historical as $i => $h) {
        $datasets[] = [
            'label'       => 'Plan after ' . $h['adjusted_at'],
            'data'        => $alignSeries($h['series']),
            'borderColor' => 'rgba(160,160,200,0.55)',
            'borderDash'  => [3, 3],
            'fill'        => false,
            'pointRadius' => 0,
            'borderWidth' => 2,
        ];
    }
    if (!empty($current) && (empty($historical) || $current !== ($historical[count($historical) - 1]['series'] ?? null))) {
        $datasets[] = [
            'label'       => 'Current plan',
            'data'        => $alignSeries($current),
            'borderColor' => '#2563eb',
            'fill'        => false,
            'pointRadius' => 0,
            'borderWidth' => 3,
        ];
    }
    if (!empty($actual)) {
        $datasets[] = [
            'label'       => 'Actual repayments',
            'data'        => $alignSeries($actual),
            'borderColor' => '#16a34a',
            'fill'        => false,
            'pointRadius' => 3,
            'borderWidth' => 3,
        ];
    }

    if (!isset($chartUrl) || $chartUrl === null) {
        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels'   => $allDates,
                'datasets' => $datasets,
            ],
            'options' => [
                'responsive' => false,
                'plugins'    => ['title' => ['display' => true, 'text' => 'Payoff trajectory']],
                'scales'     => [
                    'yAxes' => [['ticks' => ['beginAtZero' => true]]],
                ],
            ],
        ];
        $base = config('quickchart.base_url', 'http://quickchart:3400');
        $chartUrl = $base . '/chart?c=' . urlencode(json_encode($chartConfig));
    }
@endphp

<div class="card mb-3">
    <div class="card-header"><strong>Payoff trajectory</strong></div>
    <div class="card-body">
        @if(empty($datasets))
            <p class="text-muted mb-0">Not enough data to render trajectory yet.</p>
        @else
            <div class="text-center mb-2">
                <img src="{{ $chartUrl }}" alt="Payoff trajectory chart" style="max-width:100%; height:auto;">
            </div>
            <div class="row small">
                <div class="col-md-4">
                    <strong>Planned payoff:</strong> {{ $planned ?? '—' }}
                </div>
                <div class="col-md-4">
                    <strong>Projected payoff:</strong> {{ $projected ?? '— (need ≥ 2 payments)' }}
                </div>
                <div class="col-md-4">
                    <strong>Variance:</strong>
                    @if($variance === null)
                        —
                    @elseif($variance < 0)
                        <span class="text-success">{{ abs($variance) }} days ahead</span>
                    @elseif($variance > 0)
                        <span class="text-danger">{{ $variance }} days behind</span>
                    @else
                        on plan
                    @endif
                </div>
            </div>
            <p class="text-muted mt-2 mb-0" style="font-size:0.85em;">
                You owe shares. The chart axis is cumulative shares repaid. The share count is what you owe back &mdash;
                it doesn't change with the market. The dollar value will move up or down with the fund's share price.
            </p>
        @endif
    </div>
</div>
