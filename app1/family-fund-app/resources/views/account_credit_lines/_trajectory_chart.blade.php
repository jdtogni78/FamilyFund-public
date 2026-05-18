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
    $trajectory = $trajectory ?? [];
    $original   = $trajectory['original_plan']      ?? [];
    $historical = $trajectory['historical_plans']   ?? [];
    $current    = $trajectory['current_plan']       ?? [];
    $actual     = $trajectory['actual_repayments']  ?? [];
    $expected   = $trajectory['expected_to_date']   ?? [];
    $overdueShares       = $trajectory['overdue_shares']       ?? 0;
    $overdueInstallments = $trajectory['overdue_installments'] ?? 0;
    $asOf       = $trajectory['as_of'] ?? null;
    $projected  = $trajectory['projected_payoff_date'] ?? null;
    $planned    = $trajectory['planned_payoff_date']   ?? null;
    $variance   = $trajectory['variance_days']         ?? null;

    // Optional truncation: when set, only include series data points whose
    // date is on or before $truncateAt (a 'Y-m-d' string). Used by the
    // "View trajectory through this point" inline action on the adjustment
    // timeline.
    $truncateAt = $truncateAt ?? null;
    if ($truncateAt) {
        $filterFn = fn ($series) => array_values(array_filter(
            $series, fn ($p) => isset($p['date']) && $p['date'] <= $truncateAt
        ));
        $original   = $filterFn($original);
        $current    = $filterFn($current);
        $actual     = $filterFn($actual);
        // The delinquency overlay is "as of today" — meaningless in a
        // through-this-adjustment historical view, so drop it there.
        $expected   = [];
        $overdueShares = 0;
        $overdueInstallments = 0;
        $historical = array_values(array_filter(array_map(function ($h) use ($filterFn, $truncateAt) {
            if (($h['adjusted_at'] ?? null) && $h['adjusted_at'] > $truncateAt) {
                return null;
            }
            $h['series'] = $filterFn($h['series'] ?? []);
            return $h;
        }, $historical)));
        $projected = null;
        $variance  = null;
    }

    // Build a SINGLE effective plan line: the schedule that was actually in
    // force over time — the original plan until the first adjustment, then
    // each successive generation's plan until the next adjustment, ending
    // with the latest. Replaces the old original + per-generation + current
    // overlay, which drew several parallel full-length plan lines for what
    // is conceptually one evolving plan.
    $plan = (function () use ($original, $historical, $current) {
        if (empty($historical)) {
            return !empty($current) ? $current : $original;
        }
        $eff = [];
        $firstBoundary = $historical[0]['adjusted_at'] ?? null;
        foreach ($original as $p) {
            // Original schedule is expected only until the change takes place.
            if ($firstBoundary !== null && ($p['date'] ?? '') >= $firstBoundary) {
                break;
            }
            $eff[] = $p;
        }
        foreach ($historical as $i => $h) {
            $start = $h['adjusted_at'] ?? null;
            $end   = $historical[$i + 1]['adjusted_at'] ?? null;
            foreach (($h['series'] ?? []) as $p) {
                $d = $p['date'] ?? '';
                if ($start !== null && $d < $start) {
                    continue;
                }
                if ($end !== null && $d >= $end) {
                    continue;
                }
                $eff[] = $p;
            }
        }
        return $eff;
    })();
    if ($truncateAt) {
        $plan = array_values(array_filter(
            $plan, fn ($p) => isset($p['date']) && $p['date'] <= $truncateAt
        ));
    }

    // Collect all unique dates for x-axis labels.
    $allDates = collect()
        ->merge(array_column($plan,   'date'))
        ->merge(array_column($actual, 'date'))
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
    // Per-generation palette — cycled by generation index so each historical
    // plan renders with a distinct color while remaining visually subordinate
    // to the bold "current plan" line.
    $generationPalette = ['#9999ff', '#9999cc', '#99cccc', '#99cc99', '#cccc99'];
    foreach ($historical as $i => $h) {
        $color = $generationPalette[$i % count($generationPalette)];
        $datasets[] = [
            'label'       => 'Plan after ' . $h['adjusted_at'],
            'data'        => $alignSeries($h['series']),
            'borderColor' => $color,
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
    if (!empty($expected)) {
        $datasets[] = [
            'label'       => 'Actual',
            'data'        => $alignSeries($expected),
            'borderColor' => '#dc2626',
            'fill'        => false,
            'pointRadius' => 0,
            'borderWidth' => 2,
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
        // Browser-side embed — use the public URL so the user's browser can
        // resolve it. SSR / wkhtmltopdf paths use `base_url` and render via
        // QuickchartUtil to a temp PNG instead, so they're unaffected.
        $base = config('quickchart.public_url', config('quickchart.base_url', 'http://quickchart:3400'));
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
            @if($overdueInstallments > 0)
                <div class="alert alert-danger py-2 mb-2 small" role="alert">
                    <strong>⚠️ Behind schedule:</strong>
                    {{ $overdueInstallments }} installment{{ $overdueInstallments === 1 ? '' : 's' }} overdue
                    ({{ number_format($overdueShares, 4) }} shares) as of {{ $asOf ?? 'today' }}.
                    The red <em>“Actual”</em> line is what the schedule says should
                    have been repaid by now; the gap to <em>“Actual repayments”</em> is the backlog.
                </div>
            @endif
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
