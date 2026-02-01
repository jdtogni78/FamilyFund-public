@extends('layouts.email')

@section('content')
<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Header Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 8px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 24px;">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align: middle; padding-right: 12px;">
                            <span style="font-size: 32px; color: white;">&#9200;</span>
                        </td>
                        <td style="vertical-align: middle;">
                            <h2 style="margin: 0; font-size: 24px; font-weight: bold; color: white;">Matching Expiring Soon</h2>
                            <p style="margin: 4px 0 0 0; color: rgba(255,255,255,0.9);">Don't miss out on free money!</p>
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
            You have matching opportunities expiring soon! Make a deposit before they expire to maximize your matching benefits.
        </p>
    </div>

    <!-- Account Card -->
    <div style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
        <div style="background-color: #f8f9fa; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
            <strong style="color: #333;">&#128100; Account: {{ $api['account']->nickname }}</strong>
        </div>
    </div>

    <!-- Expiring Soon Section -->
    @if(count($api['expiringRules']) > 0)
    <div style="border: 2px solid #f59e0b; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
        <div style="background-color: #f59e0b; padding: 12px 16px;">
            <strong style="color: white;">&#128680; Expiring Soon</strong>
        </div>
        <div style="padding: 16px; background-color: #fffbeb;">
            @foreach($api['expiringRules'] as $ruleData)
            <div style="{{ !$loop->last ? 'border-bottom: 1px solid #fde68a; padding-bottom: 16px; margin-bottom: 16px;' : '' }}">
                <div style="font-weight: bold; color: #92400e; font-size: 16px;">{{ $ruleData['rule']->name }}</div>
                <div style="margin-top: 8px;">
                    <span style="background-color: #f59e0b; color: white; padding: 6px 14px; border-radius: 16px; font-size: 14px; font-weight: bold;">
                        ${{ number_format($ruleData['remaining'], 0) }} of ${{ number_format($ruleData['total'], 0) }} at {{ number_format($ruleData['rule']->match_percent, 0) }}%
                    </span>
                </div>
                <div style="margin-top: 8px; color: #92400e; font-size: 13px;">
                    <strong>Expires {{ $ruleData['rule']->date_end->format('M j, Y') }}</strong>
                    ({{ $ruleData['days_left'] }} day{{ $ruleData['days_left'] != 1 ? 's' : '' }} left)
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- All Active Matching Section -->
    @if(count($api['allRules']) > 0)
    <div style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
        <div style="background-color: #9333ea; padding: 12px 16px;">
            <strong style="color: white;">&#127919; All Available Matching</strong>
        </div>
        <div style="padding: 16px;">
            @foreach($api['allRules'] as $ruleData)
            <div style="{{ !$loop->last ? 'border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 12px;' : '' }}">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: bold; color: #333;">{{ $ruleData['rule']->name }}</div>
                        <div style="color: #666; font-size: 13px;">
                            Expires {{ $ruleData['rule']->date_end->format('M j, Y') }}
                            @if($ruleData['is_expiring'])
                            <span style="background-color: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 4px;">EXPIRING</span>
                            @endif
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="background-color: #9333ea; color: white; padding: 6px 14px; border-radius: 16px; font-size: 14px;">
                            ${{ number_format($ruleData['remaining'], 0) }} of ${{ number_format($ruleData['total'], 0) }} at {{ number_format($ruleData['rule']->match_percent, 0) }}%
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Call to Action -->
    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin-bottom: 20px; text-align: center;">
        <div style="font-weight: bold; color: #166534; margin-bottom: 8px; font-size: 16px;">&#128161; Take Action</div>
        <p style="color: #166534; margin: 0; font-size: 14px;">
            Make a deposit before your matching expires to get the full benefit.
            Every dollar you deposit gets matched at the rates shown above!
        </p>
    </div>

    <!-- Footer -->
    <div style="text-align: center; padding: 20px 0; border-top: 1px solid #e5e7eb;">
        <p style="color: #999; font-size: 12px; margin: 0;">
            This is an automated reminder from Family Fund.<br>
            Questions? Contact your fund administrator.
        </p>
    </div>

</div>
@endsection
