@extends('layouts.email')

@section('content')
<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Header Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #9333ea; border-radius: 8px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 24px;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align: middle; padding-right: 12px;">
                            <span style="font-size: 32px; color: white;">&#127873;</span>
                        </td>
                        <td style="vertical-align: middle;">
                            <h2 style="margin: 0; font-size: 24px; font-weight: bold; color: white;">Matching Rule Added</h2>
                            <p style="margin: 4px 0 0 0; color: rgba(255,255,255,0.9);">Good news about your account!</p>
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
            Great news! A contribution matching rule has been added to your account.
            This means your deposits will now be matched by the fund.
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
                <tr>
                    <td style="padding: 8px 0; color: #666;">Effective From</td>
                    <td style="padding: 8px 0; text-align: right; color: #333;">
                        {{ \Carbon\Carbon::parse(max($api['mr']->date_start, $accountMatchingRule->created_at))->format('F j, Y') }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Matching Rule Card -->
    <div style="border: 2px solid #9333ea; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
        <div style="background-color: #9333ea; padding: 12px 16px;">
            <strong style="color: white;">&#127919; {{ $api['mr']->name }}</strong>
        </div>
        <div style="padding: 20px;">
            <!-- Match Rate Highlight -->
            <div style="text-align: center; padding: 20px 0; border-bottom: 1px solid #e5e7eb; margin-bottom: 16px;">
                <div style="color: #666; font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">Match Rate</div>
                <div style="font-size: 48px; font-weight: bold; color: #9333ea;">{{ $api['mr']->match_percent }}%</div>
                <div style="color: #666; font-size: 14px;">of your contributions</div>
            </div>

            <!-- Range Info -->
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px 0; color: #666; width: 50%;">
                        <div style="font-size: 12px; text-transform: uppercase; color: #999;">Minimum Deposit</div>
                        <div style="font-size: 20px; font-weight: bold; color: #333;">${{ number_format($api['mr']->dollar_range_start, 0) }}</div>
                    </td>
                    <td style="padding: 10px 0; color: #666; text-align: right;">
                        <div style="font-size: 12px; text-transform: uppercase; color: #999;">Maximum Match</div>
                        <div style="font-size: 20px; font-weight: bold; color: #333;">${{ number_format($api['mr']->dollar_range_end, 0) }}</div>
                    </td>
                </tr>
            </table>

            <!-- Valid Period -->
            <div style="background-color: #f8f9fa; border-radius: 6px; padding: 12px; margin-top: 16px;">
                <div style="font-size: 12px; color: #666;">
                    <strong>Valid Period:</strong>
                    {{ \Carbon\Carbon::parse($api['mr']->date_start)->format('M j, Y') }}
                    &mdash;
                    {{ $api['mr']->date_end ? \Carbon\Carbon::parse($api['mr']->date_end)->format('M j, Y') : 'Ongoing' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Example Card -->
    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
        <div style="font-weight: bold; color: #166534; margin-bottom: 8px;">&#128161; Example</div>
        <p style="color: #166534; margin: 0; font-size: 14px;">
            If you deposit <strong>${{ number_format(min(100, $api['mr']->dollar_range_end), 0) }}</strong>,
            the fund will contribute an additional
            <strong>${{ number_format(min(100, $api['mr']->dollar_range_end) * $api['mr']->match_percent / 100, 2) }}</strong>
            to your account!
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
