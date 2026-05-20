{{--
    Shared chrome for 403 / 404 / 500 pages. Wrap the message in
    x-app-layout so the user keeps the dashboard nav + a Back to dashboard
    CTA, avoiding the bare-stub error pages that left users stranded
    (QA_BUGS_2026-05-19 #8).
--}}
<x-app-layout>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Error {{ $code ?? '' }}</li>
    </ol>
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5 text-center">
                        <div class="display-1 text-body-secondary mb-3">{{ $code ?? '?' }}</div>
                        <h2 class="mb-3">{{ $title ?? __('Something went wrong') }}</h2>
                        <p class="text-body-secondary mb-4">
                            {{ $message ?? __('We could not complete your request.') }}
                        </p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary">
                                <i class="fa fa-home me-1"></i> Back to dashboard
                            </a>
                            <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                                <i class="fa fa-arrow-left me-1"></i> Go back
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
