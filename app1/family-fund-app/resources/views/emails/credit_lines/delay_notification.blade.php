@extends('layouts.email')

@section('content')
<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    @php $account = $line->account; @endphp

    <!-- Header -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #d97706; border-radius: 8px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 24px;">
                <h2 style="margin: 0; font-size: 22px; font-weight: bold; color: white;">
                    &#9888; Payment Overdue
                </h2>
                <p style="margin: 6px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                    A loan share payment on your account is past its due date.
                </p>
            </td>
        </tr>
    </table>

    <!-- Greeting -->
    <p style="color: #333; font-size: 15px; margin: 0 0 20px 0;">
        Dear <strong>{{ $account->nickname ?? 'Account Holder' }}</strong>,
    </p>

    <!-- Overdue notice -->
    <div style="background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px;">
        <strong style="color: #92400e;">Notice {{ $notificationCount }}</strong> — this is a courtesy notification.
        There are no penalties for late payments on this loan share, but please make your payment when able.
    </div>

    <!-- Payment Details -->
    <div style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
        <div style="background-color: #f8f9fa; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
            <strong style="color: #333;">Overdue Payment</strong>
        </div>
        <div style="padding: 16px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #666;">Loan Share</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #333;">#{{ $line->id }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Was Due</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #d97706;">
                        {{ $payment->due_date->format('F j, Y') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Days Overdue</td>
                    <td style="padding: 8px 0; text-align: right; color: #d97706; font-weight: bold;">
                        {{ $payment->due_date->diffInDays(\Carbon\Carbon::today()) }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Shares Due</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #333;">
                        {{ number_format($payment->shares_due, 4) }} shares
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Outstanding (line total)</td>
                    <td style="padding: 8px 0; text-align: right; color: #333;">
                        {{ number_format($line->outstanding_shares, 4) }} shares
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <p style="color: #666; font-size: 13px;">
        To clear this overdue payment, log in to your account and submit a REP (Repayment) transaction.
    </p>

    <!-- Footer -->
    <div style="text-align: center; padding: 20px 0; border-top: 1px solid #e5e7eb;">
        <p style="color: #999; font-size: 12px; margin: 0;">
            This is an automated notification from Family Fund.<br>
            Questions? Contact your fund administrator.
        </p>
    </div>

</div>
@endsection
