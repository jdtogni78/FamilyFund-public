<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('operations.index') }}">Operations</a></li>
        <li class="breadcrumb-item active">Email</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('layouts.flash-messages')

            {{-- Email Configuration --}}
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-cog me-2"></i>
                            <strong>Email Configuration</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 140px;">Mailer:</td>
                                            <td><code>{{ $emailConfig['mailer'] }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">SMTP Host:</td>
                                            <td><code>{{ $emailConfig['host'] }}:{{ $emailConfig['port'] }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Encryption:</td>
                                            <td><code>{{ $emailConfig['encryption'] }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Username:</td>
                                            <td><code>{{ $emailConfig['username'] }}</code></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 140px;">From Address:</td>
                                            <td><code>{{ $emailConfig['from_address'] }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">From Name:</td>
                                            <td><code>{{ $emailConfig['from_name'] }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Admin Email:</td>
                                            <td><code>{{ $emailConfig['admin_address'] }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Send Test:</td>
                                            <td>
                                                <form action="{{ route('emails.send_test') }}" method="POST" class="d-flex gap-2">
                                                    @csrf
                                                    <input type="email" name="email" class="form-control form-control-sm" style="width: 200px;"
                                                           placeholder="recipient@example.com" value="{{ auth()->user()->email }}" required>
                                                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Send test email?')">
                                                        <i class="fa fa-paper-plane me-1"></i> Send
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Email Logs --}}
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-envelope-open me-2"></i>
                                <strong>Email Logs</strong>
                                <span class="badge bg-secondary ms-2">{{ $emailLogsTotal }} total</span>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Filters --}}
                            <form method="GET" action="{{ route('emails.index') }}" class="row g-3 mb-3">
                                <div class="col-auto">
                                    <label class="visually-hidden" for="search">Search</label>
                                    <input type="text" name="search" id="search" class="form-control form-control-sm"
                                           placeholder="Search subject, to, from..." value="{{ $emailSearch }}" style="width: 200px;">
                                </div>
                                <div class="col-auto">
                                    <label class="visually-hidden" for="date_from">From Date</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control form-control-sm"
                                           value="{{ $emailDateFrom }}" title="From date">
                                </div>
                                <div class="col-auto">
                                    <label class="visually-hidden" for="date_to">To Date</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control form-control-sm"
                                           value="{{ $emailDateTo }}" title="To date">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fa fa-search me-1"></i> Search
                                    </button>
                                </div>
                                @if($emailSearch || $emailDateFrom || $emailDateTo)
                                <div class="col-auto">
                                    <a href="{{ route('emails.index') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa fa-times me-1"></i> Clear
                                    </a>
                                </div>
                                @endif
                            </form>

                            @if(empty($emailLogs))
                                <p class="text-muted text-center mb-0">No email logs found</p>
                            @else
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Subject</th>
                                            <th>To</th>
                                            <th>From</th>
                                            <th>Attach</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($emailLogs as $log)
                                        <tr>
                                            <td><small>{{ $log['timestamp'] ? \Carbon\Carbon::parse($log['timestamp'])->format('Y-m-d H:i:s') : '-' }}</small></td>
                                            <td>{{ \Str::limit($log['subject'], 50) }}</td>
                                            <td><small>{{ \Str::limit($log['to'], 30) }}</small></td>
                                            <td><small>{{ \Str::limit($log['from'], 25) }}</small></td>
                                            <td>
                                                @if($log['attachments'] > 0)
                                                    <span class="badge bg-info">{{ $log['attachments'] }}</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('emails.show', $log['file']) }}" class="btn btn-ghost-success btn-sm" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination --}}
                            <div class="d-flex justify-content-center mt-3">
                                {{ $emailLogsPaginator->links() }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
