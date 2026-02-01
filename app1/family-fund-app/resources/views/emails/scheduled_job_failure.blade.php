@extends('layouts.email')

@section('content')
@php
    $isCritical = $daysOverdue > 4;
    $severityColor = $isCritical ? '#dc3545' : '#f59e0b';
    $severityGradient = $isCritical
        ? 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)'
        : 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
    $severityLabel = $isCritical ? 'CRITICAL' : 'WARNING';
    $severityBadgeBg = $isCritical ? '#dc3545' : '#f59e0b';
@endphp

<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    {{-- Header with gradient --}}
    <div style="background: {{ $severityGradient }}; color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <div style="font-size: 48px; margin-bottom: 10px;">⚠️</div>
        <h1 style="margin: 0; font-size: 24px; font-weight: 600;">Scheduled Job Failed</h1>
        <div style="margin-top: 12px;">
            <span style="background-color: rgba(255, 255, 255, 0.2); padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; letter-spacing: 0.5px;">
                {{ $severityLabel }}
            </span>
        </div>
    </div>

    {{-- Job Details Card --}}
    <div style="background: white; border: 1px solid #e5e7eb; border-top: none; padding: 20px;">
        <div style="background: #f9fafb; border-left: 4px solid {{ $severityColor }}; padding: 16px; margin-bottom: 20px; border-radius: 4px;">
            <h2 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #374151;">📋 Job Details</h2>
            <table style="width: 100%; font-size: 14px; color: #6b7280;">
                <tr>
                    <td style="padding: 4px 0; font-weight: 500;">Job ID:</td>
                    <td style="padding: 4px 0; color: #111827;">#{{ $job->id }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; font-weight: 500;">Type:</td>
                    <td style="padding: 4px 0;">
                        <span style="background-color: #3b82f6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
                            {{ $entityType }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; font-weight: 500;">Entity:</td>
                    <td style="padding: 4px 0; color: #111827; font-weight: 600;">{{ $entityName }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; font-weight: 500;">Schedule:</td>
                    <td style="padding: 4px 0; color: #111827;">{{ $job->schedule_id }}</td>
                </tr>
            </table>
        </div>

        {{-- Timing Card --}}
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin-bottom: 20px; border-radius: 4px;">
            <h2 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #92400e;">⏰ Timing</h2>
            <table style="width: 100%; font-size: 14px; color: #78350f;">
                <tr>
                    <td style="padding: 4px 0; font-weight: 500;">Due Date:</td>
                    <td style="padding: 4px 0; color: #451a03; font-weight: 600;">{{ $shouldRunByDate->format('Y-m-d (l)') }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; font-weight: 500;">Today:</td>
                    <td style="padding: 4px 0; color: #451a03;">{{ $asOf->format('Y-m-d (l)') }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; font-weight: 500;">Days Overdue:</td>
                    <td style="padding: 4px 0;">
                        <span style="background-color: {{ $severityBadgeBg }}; color: white; padding: 4px 10px; border-radius: 12px; font-weight: 600;">
                            {{ $daysOverdue }} {{ $daysOverdue == 1 ? 'day' : 'days' }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Failure Reason Card --}}
        <div style="background: #fee2e2; border-left: 4px solid #dc3545; padding: 16px; margin-bottom: 20px; border-radius: 4px;">
            <h2 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #991b1b;">❌ Failure Reason</h2>
            <p style="margin: 0; font-size: 14px; color: #7f1d1d; line-height: 1.6; white-space: pre-wrap;">{{ $reason }}</p>
        </div>

        @if($exception)
        {{-- Exception Details Card --}}
        <div style="background: #f3f4f6; border: 1px solid #d1d5db; padding: 16px; margin-bottom: 20px; border-radius: 4px;">
            <h2 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #374151;">🐛 Exception Details</h2>
            <div style="font-size: 13px; color: #4b5563;">
                <p style="margin: 0 0 8px 0;">
                    <strong>Message:</strong><br>
                    <code style="background: #fff; padding: 8px; display: block; margin-top: 4px; border-radius: 4px; color: #dc3545;">{{ $exception->getMessage() }}</code>
                </p>
                <p style="margin: 8px 0;">
                    <strong>File:</strong><br>
                    <code style="background: #fff; padding: 8px; display: block; margin-top: 4px; border-radius: 4px;">{{ $exception->getFile() }}:{{ $exception->getLine() }}</code>
                </p>
                <details style="margin-top: 12px;">
                    <summary style="cursor: pointer; font-weight: 600; color: #6b7280;">Stack Trace</summary>
                    <pre style="background: #fff; padding: 12px; margin-top: 8px; border-radius: 4px; overflow-x: auto; font-size: 11px; line-height: 1.4;">{{ $exception->getTraceAsString() }}</pre>
                </details>
            </div>
        </div>
        @endif

        @if(!empty($context))
        {{-- Additional Context Card --}}
        <div style="background: #ede9fe; border-left: 4px solid #9333ea; padding: 16px; margin-bottom: 20px; border-radius: 4px;">
            <h2 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #581c87;">ℹ️ Additional Context</h2>
            <div style="font-size: 13px; color: #4c1d95;">
                @foreach($context as $key => $value)
                <p style="margin: 6px 0;">
                    <strong>{{ $key }}:</strong>
                    @if(is_array($value) || is_object($value))
                        <pre style="background: #fff; padding: 8px; margin-top: 4px; border-radius: 4px; overflow-x: auto; font-size: 12px;">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                    @else
                        {{ $value }}
                    @endif
                </p>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Recommended Actions Card --}}
        <div style="background: #f0fdf4; border: 2px solid #22c55e; padding: 20px; margin-bottom: 20px; border-radius: 6px;">
            <h2 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #15803d;">💡 Recommended Actions</h2>
            <div style="font-size: 14px; color: #166534; line-height: 1.8; white-space: pre-wrap;">{{ $recommendedActions }}</div>
        </div>

        {{-- Action Links --}}
        <div style="background: #f9fafb; padding: 20px; border-radius: 6px; text-align: center;">
            <h2 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #374151;">🔗 Quick Actions</h2>

            <div style="margin-bottom: 12px;">
                <a href="{{ config('app.url') }}/scheduled_jobs/{{ $job->id }}"
                   style="display: inline-block; background-color: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
                    View Job Details
                </a>
            </div>

            <div style="font-size: 12px; color: #6b7280; margin-top: 16px;">
                <p style="margin: 8px 0;">
                    <strong>Force Run:</strong><br>
                    <code style="background: #e5e7eb; padding: 6px 10px; border-radius: 4px; display: inline-block; margin-top: 4px;">POST {{ config('app.url') }}/api/scheduled_jobs/{{ $job->id }}/force_run</code>
                </p>
                <p style="margin: 8px 0;">
                    <strong>Force Run (Skip Data Check):</strong><br>
                    <code style="background: #e5e7eb; padding: 6px 10px; border-radius: 4px; display: inline-block; margin-top: 4px;">POST {{ config('app.url') }}/api/scheduled_jobs/{{ $job->id }}/force_run?skip_data_check=true</code>
                </p>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-top: none; padding: 16px 20px; text-align: center; border-radius: 0 0 8px 8px;">
        <p style="margin: 0; font-size: 12px; color: #9ca3af;">
            This is an automated alert from FamilyFund Scheduled Jobs Monitor
        </p>
    </div>

</div>
@endsection
