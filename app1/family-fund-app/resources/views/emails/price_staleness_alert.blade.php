@extends('layouts.email')

@section('content')
@php
    $count = count($stale ?? []);
    $worst = $stale[0] ?? null;
    $isCritical = $worst && ($worst['days'] ?? 0) > ($threshold * 2);
    $severityColor = $isCritical ? '#dc3545' : '#f59e0b';
    $severityGradient = $isCritical
        ? 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)'
        : 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
    $severityLabel = $isCritical ? 'CRITICAL' : 'WARNING';
    $severityBadgeBg = $isCritical ? '#dc3545' : '#f59e0b';
@endphp

<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <div style="background: {{ $severityGradient }}; color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <div style="font-size: 48px; margin-bottom: 10px;">⚠️</div>
        <h1 style="margin: 0; font-size: 24px; font-weight: 600;">Asset Price Feed Appears Stalled</h1>
        <div style="margin-top: 12px;">
            <span style="background-color: rgba(255, 255, 255, 0.2); padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; letter-spacing: 0.5px;">
                {{ $severityLabel }}
            </span>
        </div>
    </div>

    <div style="background: white; border: 1px solid #e5e7eb; border-top: none; padding: 20px;">

        <div style="background: #f9fafb; border-left: 4px solid {{ $severityColor }}; padding: 16px; margin-bottom: 20px; border-radius: 4px;">
            <h2 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #374151;">📋 Summary</h2>
            <table style="width: 100%; font-size: 14px; color: #6b7280;">
                <tr>
                    <td style="padding: 4px 0; font-weight: 500;">Report date:</td>
                    <td style="padding: 4px 0; color: #111827;">{{ $asOf->format('Y-m-d (l)') }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; font-weight: 500;">Threshold:</td>
                    <td style="padding: 4px 0; color: #111827;">{{ $threshold }} trading days</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; font-weight: 500;">Affected assets:</td>
                    <td style="padding: 4px 0;">
                        <span style="background-color: {{ $severityBadgeBg }}; color: white; padding: 4px 10px; border-radius: 12px; font-weight: 600;">
                            {{ $count }}
                        </span>
                    </td>
                </tr>
                @if($worst)
                <tr>
                    <td style="padding: 4px 0; font-weight: 500;">Worst lag:</td>
                    <td style="padding: 4px 0; color: #111827; font-weight: 600;">
                        {{ $worst['name'] }} — {{ $worst['days'] }} trading days (since {{ $worst['from'] }})
                    </td>
                </tr>
                @endif
            </table>
        </div>

        <div style="background: #fee2e2; border-left: 4px solid #dc3545; padding: 16px; margin-bottom: 20px; border-radius: 4px;">
            <h2 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #991b1b;">❌ Stale assets</h2>
            <table style="width: 100%; font-size: 13px; color: #7f1d1d; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #fca5a5;">
                        <th style="padding: 6px 4px;">Asset</th>
                        <th style="padding: 6px 4px;">Last price date</th>
                        <th style="padding: 6px 4px; text-align: right;">Trading days behind</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($stale as $row)
                    <tr style="border-bottom: 1px solid #fecaca;">
                        <td style="padding: 6px 4px; color: #451a03; font-weight: 600;">{{ $row['name'] }}</td>
                        <td style="padding: 6px 4px;">{{ $row['from'] }}</td>
                        <td style="padding: 6px 4px; text-align: right; font-weight: 600;">{{ $row['days'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div style="background: #f0fdf4; border: 2px solid #22c55e; padding: 20px; margin-bottom: 20px; border-radius: 6px;">
            <h2 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #15803d;">💡 What to check</h2>
            <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #166534; line-height: 1.7;">
                <li>Is the FamilyFund container up and reachable from dstrader?</li>
                <li>Is dstrader running, and is its TWS/IB Gateway session live?</li>
                <li>Did the daily price-post job fire? Check dstrader-aws cron + logs.</li>
                <li>Are the POSTed prices being rejected (auth, schema, dedupe)?</li>
                <li>Once the feed is back, run dstrader gaps-mode to backfill the missed window.</li>
            </ul>
        </div>

        <div style="background: #f9fafb; padding: 20px; border-radius: 6px; text-align: center;">
            <div style="font-size: 12px; color: #6b7280;">
                <p style="margin: 8px 0;">
                    <strong>Manual recheck:</strong><br>
                    <code style="background: #e5e7eb; padding: 6px 10px; border-radius: 4px; display: inline-block; margin-top: 4px;">php artisan prices:check-staleness</code>
                </p>
            </div>
        </div>
    </div>

    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-top: none; padding: 16px 20px; text-align: center; border-radius: 0 0 8px 8px;">
        <p style="margin: 0; font-size: 12px; color: #9ca3af;">
            Independent safety-net alert from FamilyFund (prices:check-staleness)
        </p>
    </div>

</div>
@endsection
