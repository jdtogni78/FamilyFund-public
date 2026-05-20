<x-app-layout>
@section('content')
{{-- Note: matches show.blade.php's pattern; the @section marker is informational,
     content below is rendered into the layout's default slot. --}}
<ol class="breadcrumb">
    @if($account)
    <li class="breadcrumb-item">
        <a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('credit_lines.index', ['account' => $account->id]) }}">Loan Shares</a>
    </li>
    @endif
    <li class="breadcrumb-item">
        <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}">Loan Share #{{ $line->id }}</a>
    </li>
    <li class="breadcrumb-item active">Edit</li>
</ol>
<div class="container-fluid">
    @include('flash::message')

    <div class="card mb-3">
        <div class="card-header">
            <strong>Edit loan share #{{ $line->id }}</strong>
            @if($account)
                <span class="text-body-secondary ms-2">
                    &mdash; <a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a>
                    @if($account->fund)
                        (<a href="{{ route('funds.show', $account->fund_id) }}">{{ $account->fund->name }}</a>)
                    @endif
                </span>
            @endif
        </div>
        <div class="card-body">
            <p class="text-muted mb-0">
                Editing principal / origination is not supported after creation. Use
                <strong>Readjust</strong> from the show page to change term or payment frequency,
                or <strong>Cancel</strong> to close the line.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('credit_lines.update', ['line' => $line->id]) }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header"><strong>Notification settings</strong> (UC-20)</div>
            <div class="card-body">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="reminder_lead_days" class="form-label">
                            Reminder lead days
                            <small class="text-muted d-block">How many days before a due date to send a reminder.</small>
                        </label>
                        <input type="number" min="0" max="365"
                               class="form-control @error('reminder_lead_days') is-invalid @enderror"
                               id="reminder_lead_days" name="reminder_lead_days"
                               value="{{ old('reminder_lead_days', $line->reminder_lead_days) }}">
                        @error('reminder_lead_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block">Reminder enabled</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="reminder_enabled" value="0">
                            <input type="checkbox" class="form-check-input"
                                   id="reminder_enabled" name="reminder_enabled" value="1"
                                   {{ old('reminder_enabled', $line->reminder_enabled) ? 'checked' : '' }}>
                            <label for="reminder_enabled" class="form-check-label">
                                Send reminder emails before each due date
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label d-block">Delay notifications</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="delay_notification_enabled" value="0">
                            <input type="checkbox" class="form-check-input"
                                   id="delay_notification_enabled" name="delay_notification_enabled" value="1"
                                   {{ old('delay_notification_enabled', $line->delay_notification_enabled ?? true) ? 'checked' : '' }}>
                            <label for="delay_notification_enabled" class="form-check-label">
                                Send late-payment notifications for this line (master toggle for the three fields below)
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="delay_notification_grace_days" class="form-label">
                            Delay grace days
                            <small class="text-muted d-block">Days to wait after a missed due date before the first late notification.</small>
                        </label>
                        <input type="number" min="0" max="365"
                               class="form-control @error('delay_notification_grace_days') is-invalid @enderror"
                               id="delay_notification_grace_days" name="delay_notification_grace_days"
                               value="{{ old('delay_notification_grace_days', $line->delay_notification_grace_days) }}">
                        @error('delay_notification_grace_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="delay_notification_repeat_days" class="form-label">
                            Delay repeat days
                            <small class="text-muted d-block">Cadence (days) between repeated late notifications. Blank = no repeats.</small>
                        </label>
                        <input type="number" min="0" max="365"
                               class="form-control @error('delay_notification_repeat_days') is-invalid @enderror"
                               id="delay_notification_repeat_days" name="delay_notification_repeat_days"
                               value="{{ old('delay_notification_repeat_days', $line->delay_notification_repeat_days) }}">
                        @error('delay_notification_repeat_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="delay_notification_max_repeats" class="form-label">
                            Delay max repeats
                            <small class="text-muted d-block">Cap on the number of late-notification repeats per missed payment.</small>
                        </label>
                        <input type="number" min="0" max="100"
                               class="form-control @error('delay_notification_max_repeats') is-invalid @enderror"
                               id="delay_notification_max_repeats" name="delay_notification_max_repeats"
                               value="{{ old('delay_notification_max_repeats', $line->delay_notification_max_repeats) }}">
                        @error('delay_notification_max_repeats')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block">Transaction emails</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="transaction_email_enabled" value="0">
                            <input type="checkbox" class="form-check-input"
                                   id="transaction_email_enabled" name="transaction_email_enabled" value="1"
                                   {{ old('transaction_email_enabled', $line->transaction_email_enabled) ? 'checked' : '' }}>
                            <label for="transaction_email_enabled" class="form-check-label">
                                Email on every BOR / REP transaction tied to this line
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block">Mismatch alerts</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="mismatch_alert_enabled" value="0">
                            <input type="checkbox" class="form-check-input"
                                   id="mismatch_alert_enabled" name="mismatch_alert_enabled" value="1"
                                   {{ old('mismatch_alert_enabled', $line->mismatch_alert_enabled) ? 'checked' : '' }}>
                            <label for="mismatch_alert_enabled" class="form-check-label">
                                Email when a transaction is flagged ambiguous or unmatched
                            </label>
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save settings</button>
                <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
</x-app-layout>
