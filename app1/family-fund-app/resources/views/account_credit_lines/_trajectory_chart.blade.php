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
    $origination = $trajectory['origination_date'] ?? null;
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

    // A SINGLE effective plan line: the schedule that was actually in force
    // over time. TrajectoryBuilder already splices this across generations
    // (cumulative over the non-cancelled rows, keyed on due_date, switching
    // at each adjustment's effective_date) and hands it over as
    // `effective_plan` — one continuous monotonic series. The blade just
    // draws it. We must NOT re-splice original_plan + historical_plans on
    // adjusted_at here: that mixed two time axes and two cumulative
    // baselines, producing the dip-then-climb / sawtooth this fix removes.
    // Legacy / synthetic callers without an effective_plan fall back to the
    // current plan (then the original) as-is — never a re-splice.
    $effective = $trajectory['effective_plan'] ?? null;
    if (is_array($effective) && !empty($effective)) {
        $plan = $effective;
    } else {
        $plan = !empty($current) ? $current : $original;
    }
    if ($truncateAt) {
        $plan = array_values(array_filter(
            $plan, fn ($p) => isset($p['date']) && $p['date'] <= $truncateAt
        ));
    }

    // Anchor both lines at the loan's origination: at that instant nothing
    // has been repaid, so cumulative shares = 0. Without this the chart
    // starts at the first installment / first repayment instead of at the
    // origin, hiding the (origination, 0) baseline. Only prepend when the
    // existing series actually starts after origination.
    if ($origination) {
        $zeroPoint = ['date' => $origination, 'cumulative_shares' => 0];
        if (!empty($plan) && ($plan[0]['date'] ?? '') > $origination) {
            array_unshift($plan, $zeroPoint);
        }
        if (!empty($actual) && ($actual[0]['date'] ?? '') > $origination) {
            array_unshift($actual, $zeroPoint);
        }
    }

    // Collect all unique dates for x-axis labels.
    $allDates = collect()
        ->merge(array_column($plan,   'date'))
        ->merge(array_column($actual, 'date'))
        ->unique()
        ->sort()
        ->values()
        ->all();

    // $stopAtLast: when true, emit null for every x-axis date after the
    // series' last real data point instead of carrying the last value
    // forward. The actual-repayments line uses this so it ends at the last
    // actual payment rather than running flat to plan maturity — there have
    // been no further repayments, so there is nothing to draw there.
    //
    // $skipGaps: when true, emit null at every x-axis date that the series
    // itself does not have a point for (rather than carrying the last value
    // forward). The plan line uses this: between two scheduled installments
    // the x-axis may include actual-only dates, and carrying-forward would
    // draw the plan as a flat plateau that then jumps at the next scheduled
    // date. Emitting nulls + spanGaps on the dataset makes Chart.js draw a
    // direct line from one scheduled point to the next — a continuous
    // incline, which is how cumulative shares scheduled actually accrue.
    $alignSeries = function (array $series, bool $stopAtLast = false, bool $skipGaps = false) use ($allDates) {
        $byDate = [];
        foreach ($series as $p) {
            $byDate[$p['date']] = $p['cumulative_shares'];
        }
        $lastDate = $series ? $series[count($series) - 1]['date'] : null;
        $out = [];
        $last = null;
        foreach ($allDates as $d) {
            if (isset($byDate[$d])) {
                $last = $byDate[$d];
                $out[] = $byDate[$d];
            } elseif ($skipGaps) {
                $out[] = null;
            } elseif ($stopAtLast && $lastDate !== null && $d > $lastDate) {
                $out[] = null;
            } else {
                $out[] = $last;
            }
        }
        return $out;
    };

    // Exactly two series: the single effective plan (spliced across
    // generations above) and the actual repayments. Earlier revisions drew
    // one line per plan generation ("Original plan", "Plan after …",
    // "Current plan") plus a red expected-to-date "Actual" overlay — 4+
    // parallel lines for what is conceptually one plan vs. one actual.
    $datasets = [];
    if (!empty($plan)) {
        $datasets[] = [
            'label'                => 'Scheduled plan',
            'data'                 => $alignSeries($plan, false, true),
            'borderColor'          => '#2563eb',
            'borderDash'           => [6, 4],
            'fill'                 => false,
            // Hollow blue DIAMOND on each scheduled installment. A
            // diamond instead of a circle so where a scheduled date and
            // an actual repayment fall on the same day (the actual line
            // uses solid green circles) you can still see both markers
            // distinctly instead of one swallowing the other. Fill is
            // transparent (not white) so the green dot underneath stays
            // visible at overlap points. Null values (actual-only
            // x-axis dates) render no point — only real scheduled
            // dates get a marker.
            'pointStyle'           => 'rectRot',
            'pointRadius'          => 4,
            'pointBackgroundColor' => 'rgba(0,0,0,0)',
            'pointBorderColor'     => '#2563eb',
            'pointBorderWidth'     => 1.5,
            'borderWidth'          => 2,
            // Draw straight from one scheduled point to the next over
            // actual-only x-axis dates instead of plateauing through them.
            'spanGaps'             => true,
        ];
    }
    if (!empty($actual)) {
        $datasets[] = [
            'label'       => 'Actual repayments',
            'data'        => $alignSeries($actual, true),
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
