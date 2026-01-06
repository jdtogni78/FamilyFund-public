<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('accounts.index') }}">Accounts</a>
        </li>
        <li class="breadcrumb-item active">{{ $account->nickname }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')

            {{-- Account Details --}}
            <div class="row mb-4" id="section-details">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Account Details</strong>
                                <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm ms-2">Back</a>
                            </div>
                            <div>
                                <a href="/accounts/{{ $account->id }}/pdf_as_of/{{ $api['asOf'] ?? now()->format('Y-m-d') }}"
                                   class="btn btn-outline-danger btn-sm" target="_blank" title="Download PDF Report">
                                    <i class="fa fa-file-pdf me-1"></i> PDF Report
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('accounts.show_fields_ext')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Jump Bar Anchor --}}
            <div id="jumpNavAnchor"></div>

            {{-- Sticky Jump Bar --}}
            <div id="jumpNav" class="card shadow-sm mb-4" style="position: sticky; top: 56px; z-index: 1020; display: none;">
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="me-3 text-muted small">Jump to:</span>
                        <a href="#section-details" class="btn btn-sm btn-outline-primary me-2 mb-1 section-nav-btn" data-section="details">
                            <i class="fa fa-user-circle me-1"></i>Details
                        </a>
                        <a href="#section-disbursement" class="btn btn-sm btn-outline-primary me-2 mb-1 section-nav-btn" data-section="disbursement">
                            <i class="fa fa-money-bill-wave me-1"></i>Disbursement
                        </a>
                        @if($account->goals->count() > 0)
                        <a href="#section-goals" class="btn btn-sm btn-outline-primary me-2 mb-1 section-nav-btn" data-section="goals">
                            <i class="fa fa-bullseye me-1"></i>Goals
                        </a>
                        @endif
                        <a href="#section-charts" class="btn btn-sm btn-outline-primary me-2 mb-1 section-nav-btn" data-section="charts">
                            <i class="fa fa-chart-line me-1"></i>Charts
                        </a>
                        <a href="#section-shares" class="btn btn-sm btn-outline-primary me-2 mb-1 section-nav-btn" data-section="shares">
                            <i class="fa fa-chart-area me-1"></i>Shares
                        </a>
                        <a href="#section-performance" class="btn btn-sm btn-outline-primary me-2 mb-1 section-nav-btn" data-section="performance">
                            <i class="fa fa-table me-1"></i>Performance
                        </a>
                        <a href="#section-transactions" class="btn btn-sm btn-outline-primary me-2 mb-1 section-nav-btn" data-section="transactions">
                            <i class="fa fa-exchange-alt me-1"></i>Transactions
                        </a>
                        @if(!empty($api['matching_rules']))
                        <a href="#section-matching" class="btn btn-sm btn-outline-primary me-2 mb-1 section-nav-btn" data-section="matching">
                            <i class="fa fa-hand-holding-usd me-1"></i>Matching
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Disbursement Eligibility --}}
            <div class="row mb-4" id="section-disbursement">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong><i class="fa fa-money-bill-wave me-2"></i>Disbursement Eligibility</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.disbursement')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Goals Section --}}
            @if($account->goals->count() > 0)
            <div class="row mb-4" id="section-goals">
                <div class="col">
                    <h5 class="mb-3"><i class="fa fa-bullseye me-2"></i>Goals</h5>
                    @foreach($account->goals as $goal)
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8f9fa;">
                                <strong>{{ $goal->name }}</strong>
                                <span class="badge bg-secondary">ID: {{ $goal->id }}</span>
                            </div>
                            <div class="card-body">
                                @include('goals.progress_bar')
                                @include('goals.progress_details')
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Charts Row --}}
            <div class="row mb-4" id="section-charts">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-chart-line me-2"></i>Monthly Value</strong>
                        </div>
                        <div class="card-body">
                            @php($addSP500 = true)
                            @include('accounts.performance_line_graph')
                            @php($addSP500 = false)
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-chart-bar me-2"></i>Yearly Value</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.performance_graph')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Shares Chart --}}
            <div class="row mb-4" id="section-shares">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong><i class="fa fa-chart-area me-2"></i>Shares History</strong>
                        </div>
                        <div class="card-body">
                            <div>
                                <canvas id="balancesGraph"></canvas>
                            </div>
                            @include('accounts.balances_graph')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Tables Row --}}
            <div class="row mb-4" id="section-performance">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-table me-2"></i>Yearly Performance</strong>
                        </div>
                        <div class="card-body">
                            @php ($performance_key = 'yearly_performance')
                            @include('accounts.performance_table')
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-table me-2"></i>Monthly Performance</strong>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            @php ($performance_key = 'monthly_performance')
                            @include('accounts.performance_table')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transactions --}}
            <div class="row mb-4" id="section-transactions">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong><i class="fa fa-exchange-alt me-2"></i>Transactions</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.transactions_table')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Matching Rules --}}
            @if(!empty($api['matching_rules']))
                <div class="row mb-4" id="section-matching">
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong><i class="fa fa-hand-holding-usd me-2"></i>Matching Rules</strong>
                            </div>
                            <div class="card-body">
                                @include('accounts.matching_rules_table')
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    const $jumpNav = $('#jumpNav');
    const $jumpNavAnchor = $('#jumpNavAnchor');

    // Show/hide nav based on scroll position
    function updateNavVisibility() {
        if ($jumpNavAnchor.length === 0) return;

        const anchorTop = $jumpNavAnchor.offset().top;
        const scrollTop = $(window).scrollTop();

        if (scrollTop > anchorTop - 100) {
            $jumpNav.slideDown(200);
        } else {
            $jumpNav.slideUp(200);
        }
    }

    // Initial check
    updateNavVisibility();

    // Smooth scroll for navigation
    $('.section-nav-btn').click(function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        const $target = $(target);

        if ($target.length === 0) return;

        // Scroll to section with offset for sticky nav
        const offset = 120;
        $('html, body').animate({
            scrollTop: $target.offset().top - offset
        }, 300);

        // Highlight active button
        $('.section-nav-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
    });

    // Update nav visibility and active button on scroll
    $(window).scroll(function() {
        updateNavVisibility();

        // Update active nav button based on scroll position
        const offset = 150;
        let currentSection = null;

        $('[id^="section-"]').each(function() {
            const sectionTop = $(this).offset().top - offset;
            if ($(window).scrollTop() >= sectionTop) {
                currentSection = $(this).attr('id');
            }
        });

        if (currentSection) {
            $('.section-nav-btn').removeClass('btn-primary').addClass('btn-outline-primary');
            $('.section-nav-btn[href="#' + currentSection + '"]')
                .removeClass('btn-outline-primary').addClass('btn-primary');
        }
    });
});
</script>
@endpush
</x-app-layout>
