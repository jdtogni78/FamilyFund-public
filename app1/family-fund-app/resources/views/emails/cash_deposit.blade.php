@extends('layouts.email')

@section('content')
<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    @php($cashDeposit = $data['cash_deposit'])

    <!-- Header Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="border-left: 4px solid #2563eb; margin-bottom: 16px;">
        <tr>
            <td style="padding: 16px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="color: #333; font-size: 24px; font-weight: bold;">Cash Deposit Detected</td>
                        <td style="text-align: right;">
                            <span style="background-color: #2563eb; color: white; padding: 8px 16px; font-size: 14px; border-radius: 4px; display: inline-block;">
                                {{ $cashDeposit->status_string() }}
                            </span>
                        </td>
                    </tr>
                </table>
                <p style="color: #666; margin: 12px 0 0 0;">Hello Admin,</p>
            </td>
        </tr>
    </table>

    <!-- Amount Highlight -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 16px;">
        <tr>
            <td style="text-align: center; padding: 24px; background-color: #f0fdf4; border-radius: 8px;">
                <div style="color: #666; font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">Amount Received</div>
                <div style="font-size: 36px; font-weight: bold; color: #16a34a;">${{ number_format($cashDeposit->amount, 2) }}</div>
            </td>
        </tr>
    </table>

    <!-- Deposit Details Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 16px;">
        <tr>
            <td style="background-color: #f8f9fa; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                <strong>Deposit Details</strong>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Date</td>
                        <td style="padding: 8px 0; text-align: right; font-weight: bold;">{{ \Carbon\Carbon::parse($cashDeposit->date)->format('F j, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Description</td>
                        <td style="padding: 8px 0; text-align: right;">{{ $cashDeposit->description ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Account</td>
                        <td style="padding: 8px 0; text-align: right;">{{ $cashDeposit->account->nickname }}</td>
                    </tr>
                    @if($cashDeposit->transaction_id)
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Transaction ID</td>
                        <td style="padding: 8px 0; text-align: right;">#{{ $cashDeposit->transaction_id }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Assignment Status -->
    @php($unassigned = $cashDeposit->amount - $cashDeposit->depositRequests->sum('amount'))
    @if($unassigned > 0)
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; margin-bottom: 16px;">
        <tr>
            <td style="padding: 16px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="color: #92400e; font-weight: bold;">
                            Action Required
                        </td>
                        <td style="text-align: right; color: #92400e; font-weight: bold;">
                            ${{ number_format($unassigned, 2) }} unassigned
                        </td>
                    </tr>
                </table>
                <p style="color: #92400e; margin: 8px 0 0 0; font-size: 14px;">
                    Please review and assign this deposit to the appropriate accounts.
                </p>
            </td>
        </tr>
    </table>
    @else
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0fdf4; border: 1px solid #22c55e; border-radius: 8px; margin-bottom: 16px;">
        <tr>
            <td style="padding: 16px; text-align: center; color: #16a34a;">
                <strong>Fully Assigned</strong>
            </td>
        </tr>
    </table>
    @endif

    <!-- Footer -->
    <div style="text-align: center; color: #999; font-size: 12px; padding: 24px 0;">
        This is an automated notification from Family Fund.
    </div>

</div>
@endsection
