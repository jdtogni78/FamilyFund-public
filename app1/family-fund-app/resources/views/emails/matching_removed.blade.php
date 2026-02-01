@extends('layouts.email')

@section('content')
<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Header Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #6b7280; border-radius: 8px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 24px;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align: middle; padding-right: 12px;">
                            <span style="font-size: 32px; color: white;">&#128465;</span>
                        </td>
                        <td style="vertical-align: middle;">
                            <h2 style="margin: 0; font-size: 24px; font-weight: bold; color: white;">Matching Rule Removed</h2>
                            <p style="margin: 4px 0 0 0; color: rgba(255,255,255,0.9);">Update on your account</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Greeting -->
    <div style="padding: 0 4px; margin-bottom: 20px;">
        <p style="color: #333; font-size: 16px; margin: 0;">
            Dear <strong>{{ $api['to'] }}</strong>,
        </p>
        <p style="color: #666; font-size: 15px; margin: 12px 0 0 0;">
            This is to inform you that a contribution matching rule has been removed from your account.
            Future deposits will no longer receive matching contributions under this rule.
        </p>
    </div>

    <!-- Account Card -->
    <div style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
        <div style="background-color: #f8f9fa; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
            <strong style="color: #333;">&#128100; Your Account</strong>
        </div>
        <div style="padding: 16px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #666;">Account</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #333;">
                        {{ $api['account']->nickname }} ({{ $api['account']->code }})
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Removed Matching Rule Card -->
    <div style="border: 2px solid #6b7280; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
        <div style="background-color: #6b7280; padding: 12px 16px;">
            <strong style="color: white;">&#10060; Removed Rule: {{ $api['mr']->name }}</strong>
        </div>
        <div style="padding: 20px;">
            <!-- Match Rate Info -->
            <div style="text-align: center; padding: 20px 0; border-bottom: 1px solid #e5e7eb; margin-bottom: 16px; opacity: 0.7;">
                <div style="color: #666; font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">Previous Match Rate</div>
                <div style="font-size: 36px; font-weight: bold; color: #6b7280; text-decoration: line-through;">{{ $api['mr']->match_percent }}%</div>
            </div>

            <!-- Range Info -->
            <table style="width: 100%; border-collapse: collapse; opacity: 0.7;">
                <tr>
                    <td style="padding: 10px 0; color: #666; width: 50%;">
                        <div style="font-size: 12px; text-transform: uppercase; color: #999;">Minimum Deposit</div>
                        <div style="font-size: 18px; color: #666;">${{ number_format($api['mr']->dollar_range_start, 0) }}</div>
                    </td>
                    <td style="padding: 10px 0; color: #666; text-align: right;">
                        <div style="font-size: 12px; text-transform: uppercase; color: #999;">Maximum Match</div>
                        <div style="font-size: 18px; color: #666;">${{ number_format($api['mr']->dollar_range_end, 0) }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Info Card -->
    <div style="background-color: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
        <div style="font-weight: bold; color: #92400e; margin-bottom: 8px;">&#9888;&#65039; Important</div>
        <p style="color: #92400e; margin: 0; font-size: 14px;">
            Any matching contributions already made to your account remain in place.
            This change only affects future deposits.
            If you have questions about this change, please contact your fund administrator.
        </p>
    </div>

    <!-- Footer -->
    <div style="text-align: center; padding: 20px 0; border-top: 1px solid #e5e7eb;">
        <p style="color: #999; font-size: 12px; margin: 0;">
            This is an automated notification from Family Fund.<br>
            Questions? Contact your fund administrator.
        </p>
    </div>

</div>
@endsection
