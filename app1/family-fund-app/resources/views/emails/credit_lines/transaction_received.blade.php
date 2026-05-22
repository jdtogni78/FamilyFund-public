@extends('layouts.email')

@section('content')
<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    @php
        $isBorrow = $tran->type === \App\Models\TransactionExt::TYPE_BORROW;
        $typeLabel = $isBorrow ? 'Borrow' : 'Repayment';
        $headerColor = $isBorrow ? '#dc3545' : '#28a745';
        $account = $tran->account;
    @endphp

    <!-- Header -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background: {{ $headerColor }}; border-radius: 8px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 24px;">
                <h2 style="margin: 0; font-size: 22px; font-weight: bold; color: white;">
                    {{ $typeLabel }} Recorded
                </h2>
                <p style="margin: 6px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                    A transaction has been recorded on your account.
                </p>
            </td>
        </tr>
    </table>

    <!-- Greeting -->
    <p style="color: #333; font-size: 15px; margin: 0 0 20px 0;">
        Dear <strong>{{ $account->nickname ?? 'Account Holder' }}</strong>,
    </p>

    <!-- Transaction Details -->
    <div style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
        <div style="background-color: #f8f9fa; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
            <strong style="color: #333;">Transaction Details</strong>
        </div>
        <div style="padding: 16px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #666;">Type</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #333;">{{ $typeLabel }}</td>
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
                @if($tran->descr)
                <tr>
                    <td style="padding: 8px 0; color: #666;">Memo</td>
                    <td style="padding: 8px 0; text-align: right; color: #333;">{{ $tran->descr }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Loan Share -->
    @if($result->targetCreditLineId)
    <div style="background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px;">
        <strong style="color: #0369a1;">Applied to loan share #{{ $result->targetCreditLineId }}</strong>
    </div>
    @elseif($result->needsReview())
    <div style="background-color: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px;">
        <strong style="color: #92400e;">Needs review</strong> — this transaction could not be automatically matched
        to a loan share. An administrator will contact you or you may resolve it at your account page.
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
