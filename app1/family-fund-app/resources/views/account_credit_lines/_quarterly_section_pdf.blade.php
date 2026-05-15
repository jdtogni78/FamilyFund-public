{{--
    Account credit-lines section for the quarterly PDF report (UC-16, UC-44, UC-49).
    Inputs:
      $account     – AccountExt
      $quarterStart – Carbon (optional; defaults to start of current quarter)
      $quarterEnd   – Carbon (optional; defaults to end of current quarter)
--}}
@php
    $quarterStart = $quarterStart ?? \Carbon\Carbon::now()->startOfQuarter();
    $quarterEnd   = $quarterEnd   ?? \Carbon\Carbon::now()->endOfQuarter();

    $lines = \App\Models\AccountCreditLine::where('account_id', $account->id)->get();
    if ($lines->isNotEmpty()) {
        try {
            $loansSummary = app(\App\Services\CreditLine\Reporting\LoansSummaryBuilder::class)->forAccount($account);
        } catch (\Throwable $e) {
            $loansSummary = [];
        }

        // Per-line activity in the quarter.
        $lineIds = $lines->pluck('id');
        $qReps = \App\Models\TransactionExt::where('account_id', $account->id)
            ->where('type', \App\Models\TransactionExt::TYPE_REPAY)
            ->where('status', \App\Models\TransactionExt::STATUS_CLEARED)
            ->where('reversed', false)
            ->whereBetween('timestamp', [$quarterStart, $quarterEnd])
            ->get()
            ->groupBy('account_credit_line_id');

        $qAdjustments = \App\Models\CreditLineAdjustment::whereIn('account_credit_line_id', $lineIds)
            ->whereBetween('adjusted_at', [$quarterStart, $quarterEnd])
            ->orderBy('adjusted_at')
            ->get();
    } else {
        $loansSummary = [];
        $qReps = collect();
        $qAdjustments = collect();
    }
@endphp

<div style="page-break-inside: avoid; margin-top: 18px; border: 2px solid #0d9488; border-radius: 8px; overflow: hidden;">
    <div style="background:#f0fdf4; padding:12px 16px; color:#0f766e; font-weight:700; font-size:14px;">
        Credit Lines &mdash; Quarter {{ $quarterStart->format('Y-m-d') }} to {{ $quarterEnd->format('Y-m-d') }}
    </div>
@if($lines->isEmpty())
    <div style="padding:16px; background:#ffffff; color:#64748b; font-size:12px;">
        No credit lines on this account.
    </div>
</div>
@else
    <div style="padding:16px; background:#ffffff;">

        {{-- Loans summary card --}}
        @if(!empty($loansSummary))
            <table width="100%" cellspacing="0" cellpadding="6" style="font-size:12px; margin-bottom: 12px;">
                <tr style="background:#f8fafc;">
                    <td><strong>Disbursed (lifetime)</strong><br>{{ number_format($loansSummary['total_disbursed_shares'] ?? 0, 4) }} shares</td>
                    <td><strong>Repaid (lifetime)</strong><br>{{ number_format($loansSummary['total_repaid_shares'] ?? 0, 4) }} shares</td>
                    <td><strong>Net outstanding</strong><br>{{ number_format($loansSummary['net_outstanding_shares'] ?? 0, 4) }} shares<br>
                        <small>≈ ${{ number_format($loansSummary['outstanding_value'] ?? 0, 2) }} today</small>
                    </td>
                    <td><strong>Next due</strong><br>{{ $loansSummary['next_due_date'] ?? '&mdash;' }}</td>
                    <td><strong>Active / behind</strong><br>{{ $loansSummary['active_line_count'] ?? 0 }} / {{ $loansSummary['behind_plan_count'] ?? 0 }}</td>
                </tr>
            </table>
            <p style="font-size:10px; color:#666; margin: 0 0 12px 0;">
                You owe shares (not dollars). The share count doesn't change with the market; the dollar value moves
                with the fund's share price.
            </p>
        @endif

        {{-- Per-line activity in the quarter --}}
        <table width="100%" cellspacing="0" cellpadding="6" border="0" style="font-size:11px; border-collapse:collapse;">
            <thead>
                <tr style="background:#0d9488; color:#fff;">
                    <th align="left">Line</th>
                    <th align="right">Outstanding</th>
                    <th align="left">Status</th>
                    <th align="right">Q payments</th>
                    <th align="right">Q shares repaid</th>
                    <th align="left">Maturity</th>
                </tr>
            </thead>
            <tbody>
            @foreach($lines as $line)
                @php
                    $reps = $qReps->get($line->id, collect());
                    $repShares = (float) $reps->sum('shares');
                @endphp
                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td>#{{ $line->id }}</td>
                    <td align="right">{{ number_format($line->outstanding_shares, 4) }}</td>
                    <td>{{ $line->status }}</td>
                    <td align="right">{{ $reps->count() }}</td>
                    <td align="right">{{ number_format($repShares, 4) }}</td>
                    <td>{{ optional($line->maturity_date)->format('Y-m-d') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        {{-- Adjustments in the quarter --}}
        @if($qAdjustments->isNotEmpty())
        <div style="margin-top: 14px;">
            <div style="font-weight:700; color:#0f766e; margin-bottom: 6px;">Adjustments this quarter</div>
            <table width="100%" cellspacing="0" cellpadding="5" style="font-size:11px; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th align="left">When</th>
                        <th align="left">Line</th>
                        <th align="left">Term</th>
                        <th align="left">Frequency</th>
                        <th align="left">Maturity</th>
                        <th align="left">Reason</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($qAdjustments as $a)
                    <tr style="border-bottom:1px solid #eee;">
                        <td>{{ Carbon::parse($a->adjusted_at)->format('Y-m-d') }}</td>
                        <td>#{{ $a->account_credit_line_id }}</td>
                        <td>{{ $a->old_term_months }} &rarr; {{ $a->new_term_months }} mo</td>
                        <td>{{ $a->old_payment_frequency }} &rarr; {{ $a->new_payment_frequency }}</td>
                        <td>{{ Carbon::parse($a->old_maturity_date)->format('Y-m-d') }} &rarr; {{ Carbon::parse($a->new_maturity_date)->format('Y-m-d') }}</td>
                        <td>{{ $a->reason }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endif
