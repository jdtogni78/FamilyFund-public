<x-app-layout>
@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}">Credit Line #{{ $line->id }}</a>
    </li>
    <li class="breadcrumb-item active">Edit</li>
</ol>
<div class="container-fluid">
    <div class="card">
        <div class="card-header"><strong>Edit credit line #{{ $line->id }}</strong></div>
        <div class="card-body">
            <p class="text-muted">
                Editing principal / origination is not supported after creation. Use
                <strong>Readjust</strong> from the show page to change term or payment frequency,
                or <strong>Cancel</strong> to close the line.
            </p>
            <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}"
               class="btn btn-secondary">Back</a>
        </div>
    </div>
</div>
</x-app-layout>
