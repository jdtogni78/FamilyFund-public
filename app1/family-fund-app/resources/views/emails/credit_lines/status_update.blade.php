@extends('layouts.email')

@section('content')
<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Header -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #2563eb; border-radius: 8px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 24px;">
                <h2 style="margin: 0; font-size: 22px; font-weight: bold; color: white;">
                    &#128202; Credit Line Status Update
                </h2>
                <p style="margin: 6px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                    Quarterly summary &amp; forecast — as of {{ \Carbon\Carbon::parse($asOf)->format('F j, Y') }}.
                </p>
            </td>
        </tr>
    </table>

    <!-- Greeting -->
    <p style="color: #333; font-size: 15px; margin: 0 0 20px 0;">
        Dear <strong>{{ $account->nickname ?? 'Account Holder' }}</strong>,
    </p>

    <p style="color: #666; font-size: 14px; margin: 0 0 20px 0;">
        Here is the current status of your credit line{{ $summary['active_line_count'] === 1 ? '' : 's' }},
        followed by a forecast of when each is on track to be paid off.
    </p>

    <!-- Account summary -->
    <div style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px; overflow: hidden;">
        <div style="background-color: #f8f9fa; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
            <strong style="color: #333;">Account Summary</strong>
        </div>
        <div style="padding: 16px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #666;">Active credit lines</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #333;">
                        {{ $summary['active_line_count'] }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Net outstanding</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #333;">
                        {{ number_format($summary['net_outstanding_shares'], 4) }} shares
                        @if($summary['outstanding_value'] > 0)
                            <span style="color: #666; font-weight: normal;">(${{ number_format($summary['outstanding_value'], 2) }})</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Lifetime disbursed</td>
                    <td style="padding: 8px 0; text-align: right; color: #333;">
                        {{ number_format($summary['total_disbursed_shares'], 4) }} shares
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Lifetime repaid</td>
                    <td style="padding: 8px 0; text-align: right; color: #333;">
                        {{ number_format($summary['total_repaid_shares'], 4) }} shares
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Next payment due</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #2563eb;">
                        @if($summary['next_due_date'])
                            {{ \Carbon\Carbon::parse($summary['next_due_date'])->format('F j, Y') }}
                            ({{ number_format($summary['next_due_shares'], 4) }} shares)
                        @else
                            &mdash;
                        @endif
                    </td>
                </tr>
                @if($summary['behind_plan_count'] > 0)
                <tr>
                    <td style="padding: 8px 0; color: #666;">Lines behind plan</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #dc2626;">
                        {{ $summary['behind_plan_count'] }}
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Per-line forecast -->
    <h3 style="color: #333; font-size: 16px; margin: 0 0 12px 0;">Forecast by credit line</h3>

    @foreach($lines as $entry)
        @php
            $line = $entry['line'];
            $t    = $entry['trajectory'];
            $variance = $t['variance_days'] ?? null;
            $expectedTo = !empty($t['expected_to_date']) ? end($t['expected_to_date'])['cumulative_shares'] : 0.0;
            $actualTo   = !empty($t['actual_repayments']) ? end($t['actual_repayments'])['cumulative_shares'] : 0.0;
        @endphp

        <div style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 16px; overflow: hidden;">
            <div style="background-color: #f8f9fa; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                <strong style="color: #333;">{{ $line->nickname ? $line->nickname : 'Credit line' }} (#{{ $line->id }})</strong>
            </div>
            <div style="padding: 16px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 6px 0; color: #666;">Outstanding</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: bold; color: #333;">
                            {{ number_format($line->outstanding_shares, 4) }} shares
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #666;">Repaid to date</td>
                        <td style="padding: 6px 0; text-align: right; color: #333;">
                            {{ number_format($actualTo, 4) }} of {{ number_format($expectedTo, 4) }} expected
                        </td>
                    </tr>
                    @if(($t['overdue_shares'] ?? 0) > 0)
                    <tr>
                        <td style="padding: 6px 0; color: #666;">Overdue</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: bold; color: #dc2626;">
                            {{ number_format($t['overdue_shares'], 4) }} shares
                            ({{ $t['overdue_installments'] }} installment{{ $t['overdue_installments'] === 1 ? '' : 's' }})
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 6px 0; color: #666;">Planned payoff</td>
                        <td style="padding: 6px 0; text-align: right; color: #333;">
                            {{ $t['planned_payoff_date'] ? \Carbon\Carbon::parse($t['planned_payoff_date'])->format('M j, Y') : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #666;">Projected payoff</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: bold; color: #333;">
                            {{ $t['projected_payoff_date'] ? \Carbon\Carbon::parse($t['projected_payoff_date'])->format('M j, Y') : 'Not enough payment history yet' }}
                        </td>
                    </tr>
                    @if($variance !== null)
                    <tr>
                        <td style="padding: 6px 0; color: #666;">Pace vs plan</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: bold; color: {{ $variance > 0 ? '#dc2626' : '#16a34a' }};">
                            @if($variance > 0)
                                {{ $variance }} day{{ abs($variance) === 1 ? '' : 's' }} behind
                            @elseif($variance < 0)
                                {{ abs($variance) }} day{{ abs($variance) === 1 ? '' : 's' }} ahead
                            @else
                                On schedule
                            @endif
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    @endforeach

    <p style="color: #666; font-size: 13px;">
        The projected payoff date is an estimate based on your recent repayment pace and is not a
        contractual figure. To make a payment, log in and submit a REP (Repayment) transaction.
    </p>

    <!-- Footer -->
    <div style="text-align: center; padding: 20px 0; border-top: 1px solid #e5e7eb;">
        <p style="color: #999; font-size: 12px; margin: 0;">
            This is an automated quarterly status update from Family Fund.<br>
            Questions? Contact your fund administrator.
        </p>
    </div>

</div>
@endsection
