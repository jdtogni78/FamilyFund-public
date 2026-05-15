{{--
    Fund credit-line exposure section for the quarterly PDF report (UC-17).
    Inputs:
      $fund         – FundExt
      $quarterStart – Carbon (optional)
      $quarterEnd   – Carbon (optional)
--}}
@php
    $quarterStart = $quarterStart ?? \Carbon\Carbon::now()->startOfQuarter();
    $quarterEnd   = $quarterEnd   ?? \Carbon\Carbon::now()->endOfQuarter();

    try {
        $exposure = app(\App\Services\CreditLine\Reporting\FundExposureBuilder::class)->forFund($fund);
    } catch (\Throwable $e) {
        $exposure = [];
    }

    $accountIds = \App\Models\Account::where('fund_id', $fund->id)->pluck('id');
    $lineIds = \App\Models\AccountCreditLine::whereIn('account_id', $accountIds)->pluck('id');

    $qDisbursed = (float) \App\Models\TransactionExt::whereIn('account_id', $accountIds)
        ->where('type', \App\Models\TransactionExt::TYPE_BORROW)
        ->where('status', \App\Models\TransactionExt::STATUS_CLEARED)
        ->where('reversed', false)
        ->whereBetween('timestamp', [$quarterStart, $quarterEnd])
        ->sum('shares');

    $qRepaid = (float) \App\Models\TransactionExt::whereIn('account_id', $accountIds)
        ->where('type', \App\Models\TransactionExt::TYPE_REPAY)
        ->where('status', \App\Models\TransactionExt::STATUS_CLEARED)
        ->where('reversed', false)
        ->whereBetween('timestamp', [$quarterStart, $quarterEnd])
        ->sum('shares');

    $qAdjustmentCount = \App\Models\CreditLineAdjustment::whereIn('account_credit_line_id', $lineIds)
        ->whereBetween('adjusted_at', [$quarterStart, $quarterEnd])
        ->count();

    $behindLines = \App\Models\AccountCreditLine::whereIn('id', $lineIds)
        ->where('status', 'active')
        ->whereExists(function ($q) use ($quarterEnd) {
            $q->from('credit_line_payments')
              ->whereColumn('credit_line_payments.account_credit_line_id', 'account_credit_lines.id')
              ->whereIn('status', ['scheduled', 'late', 'partial'])
              ->where('due_date', '<', \Carbon\Carbon::today()->toDateString());
        })
        ->get();
@endphp

@if(!empty($exposure) && ($exposure['total_lines'] ?? 0) > 0)
<div style="page-break-inside: avoid; margin-top: 18px; border: 2px solid #0d9488; border-radius: 8px; overflow: hidden;">
    <div style="background:#f0fdf4; padding:12px 16px; color:#0f766e; font-weight:700; font-size:14px;">
        Credit-line Exposure &mdash; Quarter {{ $quarterStart->format('Y-m-d') }} to {{ $quarterEnd->format('Y-m-d') }}
    </div>
    <div style="padding:16px; background:#ffffff;">
        <table width="100%" cellspacing="0" cellpadding="6" style="font-size:12px; margin-bottom: 12px;">
            <tr style="background:#f8fafc;">
                <td><strong>Outstanding (shares)</strong><br>{{ number_format($exposure['outstanding_shares'] ?? 0, 4) }}</td>
                <td><strong>Outstanding value</strong><br>${{ number_format($exposure['outstanding_value'] ?? 0, 2) }}</td>
                <td><strong>Q disbursed</strong><br>{{ number_format($qDisbursed, 4) }} shares</td>
                <td><strong>Q repaid</strong><br>{{ number_format($qRepaid, 4) }} shares</td>
                <td><strong>Active / behind</strong><br>{{ $exposure['active_line_count'] ?? 0 }} / {{ $exposure['behind_plan_count'] ?? 0 }}</td>
                <td><strong>Q adjustments</strong><br>{{ $qAdjustmentCount }}</td>
            </tr>
        </table>

        @if($behindLines->isNotEmpty())
            <div style="font-weight:700; color:#b91c1c; margin-bottom: 6px;">Lines currently behind plan</div>
            <table width="100%" cellspacing="0" cellpadding="5" style="font-size:11px; border-collapse:collapse;">
                <thead>
                    <tr style="background:#fef2f2;">
                        <th align="left">Line</th>
                        <th align="left">Account</th>
                        <th align="right">Outstanding (shares)</th>
                        <th align="left">Maturity</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($behindLines as $line)
                    <tr style="border-bottom:1px solid #eee;">
                        <td>#{{ $line->id }}</td>
                        <td>{{ optional($line->account)->nickname ?? '#' . $line->account_id }}</td>
                        <td align="right">{{ number_format($line->outstanding_shares, 4) }}</td>
                        <td>{{ optional($line->maturity_date)->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        <p style="font-size:10px; color:#666; margin: 10px 0 0 0;">
            Receivable-as-asset model: the outstanding receivable is held at the fund's current share price (see
            docs/credit_lines/fund_cashflow.md). Share counts move only with draws and repayments; dollar value tracks
            the fund's share price.
        </p>
    </div>
</div>
@endif
