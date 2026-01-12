<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('operations.index') }}">Operations</a></li>
        <li class="breadcrumb-item"><a href="{{ route('emails.index') }}">Email</a></li>
        <li class="breadcrumb-item active">View</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('layouts.flash-messages')

            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-envelope me-2"></i>
                                <strong>Email Details</strong>
                            </div>
                            <a href="{{ route('emails.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fa fa-arrow-left me-1"></i> Back to List
                            </a>
                        </div>
                        <div class="card-body">
                            {{-- Email Headers --}}
                            <table class="table table-sm table-borderless mb-4">
                                <tr>
                                    <td class="text-muted" style="width: 100px;"><strong>Date:</strong></td>
                                    <td>{{ isset($email['timestamp']) ? \Carbon\Carbon::parse($email['timestamp'])->format('Y-m-d H:i:s') : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><strong>Subject:</strong></td>
                                    <td>{{ $email['subject'] ?? '(no subject)' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><strong>From:</strong></td>
                                    <td>
                                        @foreach($email['from'] ?? [] as $addr)
                                            {{ $addr['name'] ? $addr['name'] . ' <' . $addr['email'] . '>' : $addr['email'] }}@if(!$loop->last), @endif
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><strong>To:</strong></td>
                                    <td>
                                        @foreach($email['to'] ?? [] as $addr)
                                            {{ $addr['name'] ? $addr['name'] . ' <' . $addr['email'] . '>' : $addr['email'] }}@if(!$loop->last), @endif
                                        @endforeach
                                    </td>
                                </tr>
                                @if(!empty($email['cc']))
                                <tr>
                                    <td class="text-muted"><strong>CC:</strong></td>
                                    <td>
                                        @foreach($email['cc'] as $addr)
                                            {{ $addr['name'] ? $addr['name'] . ' <' . $addr['email'] . '>' : $addr['email'] }}@if(!$loop->last), @endif
                                        @endforeach
                                    </td>
                                </tr>
                                @endif
                                @if(!empty($email['attachments']))
                                <tr>
                                    <td class="text-muted"><strong>Attachments:</strong></td>
                                    <td>
                                        @foreach($email['attachments'] as $attachment)
                                            <span class="badge bg-secondary me-1">
                                                <i class="fa fa-paperclip me-1"></i>
                                                {{ $attachment['filename'] }}
                                                <small>({{ number_format($attachment['size'] / 1024, 1) }} KB)</small>
                                            </span>
                                        @endforeach
                                    </td>
                                </tr>
                                @endif
                            </table>

                            {{-- Email Body --}}
                            <hr>
                            <div class="mt-3">
                                @if(!empty($email['html_body']))
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <iframe srcdoc="{{ htmlspecialchars($email['html_body']) }}"
                                                    style="width: 100%; min-height: 400px; border: none;"
                                                    sandbox="allow-same-origin"></iframe>
                                        </div>
                                    </div>
                                @elseif(!empty($email['text_body']))
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <pre class="mb-0" style="white-space: pre-wrap;">{{ $email['text_body'] }}</pre>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted">No email body content available.</p>
                                @endif
                            </div>

                            {{-- File Info --}}
                            <hr>
                            <small class="text-muted">
                                <i class="fa fa-file me-1"></i> Log file: {{ $filename }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
