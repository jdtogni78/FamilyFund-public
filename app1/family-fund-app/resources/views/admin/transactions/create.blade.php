<x-app-layout>
@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item">Admin</li>
    <li class="breadcrumb-item active">Create transaction (backdated)</li>
</ol>
<div class="container-fluid">
    @include('flash::message')

    <div class="card">
        <div class="card-header">
            <strong>Admin: create transaction with arbitrary timestamp</strong>
            <small class="text-muted d-block">UC-46 — useful for backfilling historical records. Bypasses the normal pending-processing path; the detection pipeline still runs via observer.</small>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.transactions.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="account_id" class="form-label">Account</label>
                        <select id="account_id" name="account_id" class="form-select @error('account_id') is-invalid @enderror" required>
                            <option value="">— select account —</option>
                            @foreach ($accounts as $acct)
                                <option value="{{ $acct->id }}" {{ old('account_id') == $acct->id ? 'selected' : '' }}>
                                    #{{ $acct->id }} — {{ $acct->nickname ?: ('Account ' . $acct->id) }}
                                </option>
                            @endforeach
                        </select>
                        @error('account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="account_credit_line_id" class="form-label">Credit line (optional)</label>
                        <select id="account_credit_line_id" name="account_credit_line_id" class="form-select @error('account_credit_line_id') is-invalid @enderror">
                            <option value="">— none —</option>
                            @foreach ($creditLines as $line)
                                <option value="{{ $line->id }}" {{ old('account_credit_line_id') == $line->id ? 'selected' : '' }}>
                                    #{{ $line->id }} (acct {{ $line->account_id }}, {{ $line->status }}){{ $line->descr ? ' — ' . $line->descr : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('account_credit_line_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="type" class="form-label">Type</label>
                        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                            @foreach ($typeMap as $code => $label)
                                <option value="{{ $code }}" {{ old('type') === $code ? 'selected' : '' }}>{{ $code }} — {{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach ($statusMap as $code => $label)
                                <option value="{{ $code }}" {{ old('status') === $code ? 'selected' : '' }}>{{ $code }} — {{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="timestamp" class="form-label">
                            Timestamp
                            <small class="text-muted d-block">Any date/time, including past or future.</small>
                        </label>
                        <input type="datetime-local" id="timestamp" name="timestamp"
                               class="form-control @error('timestamp') is-invalid @enderror"
                               value="{{ old('timestamp') }}" required>
                        @error('timestamp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="value" class="form-label">Value</label>
                        <input type="number" step="any" id="value" name="value"
                               class="form-control @error('value') is-invalid @enderror"
                               value="{{ old('value') }}" required>
                        @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="shares" class="form-label">Shares (optional)</label>
                        <input type="number" step="any" id="shares" name="shares"
                               class="form-control @error('shares') is-invalid @enderror"
                               value="{{ old('shares') }}">
                        @error('shares')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label for="descr" class="form-label">Description (optional)</label>
                        <input type="text" id="descr" name="descr" maxlength="255"
                               class="form-control @error('descr') is-invalid @enderror"
                               value="{{ old('descr') }}">
                        @error('descr')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Create transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
