A scheduled job has failed to execute.

=== Job Details ===
Job ID: {{ $job->id }}
Type: {{ $entityType }}
Entity: {{ $entityName }}
Schedule: {{ $job->schedule_id }}

=== Timing ===
Due Date: {{ $shouldRunByDate->format('Y-m-d (l)') }}
Today: {{ $asOf->format('Y-m-d (l)') }}
Days Overdue: {{ $daysOverdue }}

=== Failure Reason ===
{{ $reason }}

@if($exception)
=== Exception Details ===
Message: {{ $exception->getMessage() }}
File: {{ $exception->getFile() }}:{{ $exception->getLine() }}

Stack Trace:
{{ $exception->getTraceAsString() }}
@endif

@if(!empty($context))
=== Additional Context ===
@foreach($context as $key => $value)
{{ $key }}: {{ is_array($value) || is_object($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}
@endforeach
@endif

=== Recommended Actions ===
{{ $recommendedActions }}

=== Links ===
View Job: {{ config('app.url') }}/scheduled_jobs/{{ $job->id }}
Force Run: POST {{ config('app.url') }}/api/scheduled_jobs/{{ $job->id }}/force_run
Force Run (Skip Data Check): POST {{ config('app.url') }}/api/scheduled_jobs/{{ $job->id }}/force_run?skip_data_check=true
