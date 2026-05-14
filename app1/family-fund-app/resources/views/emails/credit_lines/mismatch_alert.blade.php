@extends('layouts.email')

@section('content')
<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    @php
        $account = $tran->account;
        $statusLabel = $result->status === \App\Services\Detection\DetectionResult::STATUS_AMBIGUOUS
            ? 'Ambiguous'
            : 'Unmatched';
    @endphp

    <!-- Header -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #dc2626; border-radius: 8px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 24px;">
                <h2 style="margin: 0; font-size: 22px; font-weight: bold; color: white;">
                    &#9888; Action Required: Repayment Needs Review
                </h2>
                <p style="margin: 6px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                    A repayment transaction could not be automatically assigned to a credit line.
                </p>
            </td>
        </tr>
    </table>

    <!-- Greeting -->
    <p style="color: #333; font-size: 15px; margin: 0 0 20px 0;">
        Dear <strong>{{ $account->nickname ?? 'Account Holder' }}</strong>,
    </p>

    <!-- Status explanation -->
    <div style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px;">
        <strong style="color: #991b1b;">Status: {{ $statusLabel }}</strong>
        @if($result->status === \App\Services\Detection\DetectionResult::STATUS_AMBIGUOUS)
        <p style="color: #7f1d1d; font-size: 13px; margin: 8px 0 0 0;">
            This repayment matches more than one active credit line. Please review and assign it to the correct line.
        </p>
        @else
        <p style="color: #7f1d1d; font-size: 13px; margin: 8px 0 0 0;">
            This repayment could not be matched to any active credit line. Please review and assign it manually.
        </p>
        @endif
    </div>

    <!-- Transaction Details -->
    <div style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
        <div style="background-color: #f8f9fa; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
            <strong style="color: #333;">Transaction Details</strong>
        </div>
        <div style="padding: 16px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #666;">Transaction ID</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #333;">#{{ $tran->id }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Date</td>
                    <td style="padding: 8px 0; text-align: right; color: #333;">
                        {{ $tran->timestamp ? \Carbon\Carbon::parse($tran->timestamp)->format('F j, Y') : 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Value</td>
                    <td style="padding: 8px 0; text-align: right; color: #333;">${{ number_format(abs($tran->value ?? 0), 2) }}</td>
                </tr>
                @if($tran->shares)
                <tr>
                    <td style="padding: 8px 0; color: #666;">Shares</td>
                    <td style="padding: 8px 0; text-align: right; color: #333;">{{ number_format(abs($tran->shares), 4) }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Resolve CTA -->
    <div style="text-align: center; margin-bottom: 24px;">
        <a href="{{ $resolveUrl }}"
           style="display: inline-block; background-color: #dc2626; color: white; padding: 12px 28px;
                  border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 15px;">
            Resolve Transaction
        </a>
        <p style="color: #999; font-size: 12px; margin: 8px 0 0 0;">
            Or copy this URL: {{ $resolveUrl }}
        </p>
    </div>

    @if($result->notes)
    <div style="background-color: #f8f9fa; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px;">
        <strong style="color: #666; font-size: 12px;">System notes:</strong>
        <p style="color: #666; font-size: 12px; margin: 4px 0 0 0;">{{ $result->notes }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div style="text-align: center; padding: 20px 0; border-top: 1px solid #e5e7eb;">
        <p style="color: #999; font-size: 12px; margin: 0;">
            This is an automated notification from Family Fund.<br>
            Questions? Contact your fund administrator.
        </p>
    </div>

</div>
@endsection
