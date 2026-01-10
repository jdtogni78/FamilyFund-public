@extends('layouts.email')

@section('content')
<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Header Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #2563eb; border-radius: 8px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 24px;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align: middle; padding-right: 12px;">
                            <span style="font-size: 32px; color: white;">&#128176;</span>
                        </td>
                        <td style="vertical-align: middle;">
                            <h2 style="margin: 0; font-size: 24px; font-weight: bold; color: white;">Deposit Allocation Confirmed</h2>
                            <p style="margin: 4px 0 0 0; color: rgba(255,255,255,0.9);">Your deposit has been processed!</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Greeting -->
    <div style="padding: 0 4px; margin-bottom: 20px;">
        <p style="color: #333; font-size: 16px; margin: 0;">
            Dear <strong>{{ $to }}</strong>,
        </p>
        <p style="color: #666; font-size: 15px; margin: 12px 0 0 0;">
            Great news! A deposit has been allocated to your account and is being processed.
        </p>
    </div>

    <!-- Amount Highlight -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
        <tr>
            <td style="text-align: center; padding: 24px; background-color: #f0fdf4; border-radius: 8px;">
                <div style="color: #666; font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">Allocated Amount</div>
                <div style="font-size: 36px; font-weight: bold; color: #16a34a;">${{ number_format($depositRequest->amount, 2) }}</div>
            </td>
        </tr>
    </table>

    <!-- Account Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px;">
        <tr>
            <td style="background-color: #f8f9fa; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                <strong>Account Details</strong>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Account</td>
                        <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #333;">
                            {{ $account->nickname }}
                            @if($account->code)
                                ({{ $account->code }})
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Status</td>
                        <td style="padding: 8px 0; text-align: right;">
                            <span style="background-color: #dbeafe; color: #1d4ed8; padding: 4px 12px; border-radius: 4px; font-size: 13px;">
                                {{ $depositRequest->status_string() }}
                            </span>
                        </td>
                    </tr>
                    @if($cashDeposit && $cashDeposit->date)
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Deposit Date</td>
                        <td style="padding: 8px 0; text-align: right; color: #333;">
                            {{ \Carbon\Carbon::parse($cashDeposit->date)->format('F j, Y') }}
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- What's Next Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 16px;">
                <div style="font-weight: bold; color: #0369a1; margin-bottom: 8px;">What happens next?</div>
                <p style="color: #0369a1; margin: 0; font-size: 14px;">
                    @if($depositRequest->status == 'APPROVED')
                        Your deposit is approved and will be converted to fund shares once the cash is received and processed.
                        You'll receive a transaction confirmation email when complete.
                    @elseif($depositRequest->status == 'COMPLETED')
                        Your deposit has been fully processed and converted to fund shares.
                        Check your account for the updated balance.
                    @else
                        Your deposit allocation is being processed. You'll receive updates as it progresses.
                    @endif
                </p>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div style="text-align: center; padding: 20px 0; border-top: 1px solid #e5e7eb;">
        <p style="color: #999; font-size: 12px; margin: 0;">
            This is an automated notification from Family Fund.<br>
            Questions? Contact your fund administrator.
        </p>
    </div>

</div>
@endsection
