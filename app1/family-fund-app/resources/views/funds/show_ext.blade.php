<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('funds.index') }}">Funds</a>
        </li>
        <li class="breadcrumb-item active">{{ $api['name'] }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')

            {{-- Fund Details --}}
            <div class="row mb-4" id="section-details">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Fund Details</strong>
                                <a href="{{ route('funds.index') }}" class="btn btn-light btn-sm ms-2">Back</a>
                            </div>
                            <div>
                                <a href="/funds/{{ $api['id'] }}/trade_bands"
                                   class="btn btn-outline-primary btn-sm me-2" title="View Trading Bands">
                                    <i class="fa fa-chart-bar me-1"></i> Trade Bands
                                </a>
                                <a href="/funds/{{ $api['id'] }}/pdf_as_of/{{ $asOf }}"
                                   class="btn btn-outline-danger btn-sm" target="_blank" title="Download PDF Report">
                                    <i class="fa fa-file-pdf me-1"></i> PDF Report
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('funds.show_fields_ext')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Jump Bar Anchor --}}
            <div id="jumpBarAnchor"></div>

            {{-- Sticky Jump Bar --}}
            <div id="jumpBar" class="card shadow-sm mb-3" style="position: sticky; top: 56px; z-index: 1020; border-radius: 4px; display: none;">
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="mr-3 text-muted small"><i class="fa fa-compass me-1"></i>Jump to:</span>
                        <a href="#section-charts" class="btn btn-sm btn-outline-primary mr-2 mb-1 jump-nav-btn">
                            <i class="fa fa-chart-line me-1"></i>Charts
                        </a>
                        <a href="#section-regression" class="btn btn-sm btn-outline-primary mr-2 mb-1 jump-nav-btn">
                            <i class="fa fa-chart-area me-1"></i>Regression
                        </a>
                        <a href="#section-assets" class="btn btn-sm btn-outline-primary mr-2 mb-1 jump-nav-btn">
                            <i class="fa fa-briefcase me-1"></i>Assets
                        </a>
                        @isset($api['admin'])
                        <a href="#section-allocation" class="btn btn-sm btn-outline-warning mr-2 mb-1 jump-nav-btn">
                            <i class="fa fa-chart-pie me-1"></i>Allocation
                        </a>
                        @endisset
                        <a href="#section-performance" class="btn btn-sm btn-outline-primary mr-2 mb-1 jump-nav-btn">
                            <i class="fa fa-table me-1"></i>Performance
                        </a>
                        <a href="#section-assets-table" class="btn btn-sm btn-outline-secondary mr-2 mb-1 jump-nav-btn">
                            <i class="fa fa-coins me-1"></i>Assets Table
                        </a>
                        <a href="#section-transactions" class="btn btn-sm btn-outline-secondary mr-2 mb-1 jump-nav-btn">
                            <i class="fa fa-exchange-alt me-1"></i>Transactions
                        </a>
                        @isset($api['admin'])
                        <a href="#section-accounts" class="btn btn-sm btn-outline-warning mr-2 mb-1 jump-nav-btn">
                            <i class="fa fa-user-friends me-1"></i>Accounts
                        </a>
                        @endisset
                        <a href="#section-details" class="btn btn-sm btn-outline-dark ml-auto mb-1" title="Back to top">
                            <i class="fa fa-arrow-up"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Main Charts Row --}}
            <div class="row mb-4" id="section-charts">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-chart-line mr-2"></i>Monthly Value</strong>
                        </div>
                        <div class="card-body">
                            @php($addSP500 = true)
                            @include('funds.performance_line_graph')
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
                            @include('funds.performance_graph')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Linear Regression --}}
            <div class="row mb-4" id="section-regression">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-chart-area me-2"></i>Linear Regression</strong>
                        </div>
                        <div class="card-body">
                            @include('funds.performance_line_graph_linreg')
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-table me-2"></i>Projection Table</strong>
                        </div>
                        <div class="card-body">
                            @include('funds.linreg_table')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Current Assets --}}
            <div class="row mb-4" id="section-assets">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-briefcase me-2"></i>Current Assets</strong>
                        </div>
                        <div class="card-body">
                            @include('funds.assets_graph')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trade Portfolios Graphs --}}
            @foreach($api['tradePortfolios'] as $tradePortfolio)
                @php($extraTitle = '' . $tradePortfolio->id . ' [' .
                        $tradePortfolio->start_dt->format('Y-m-d') . ' to ' .
                        $tradePortfolio->end_dt->format('Y-m-d') . ']')
                <div class="row mb-4">
                    @include('trade_portfolios.inner_show_graphs')
                </div>
            @endforeach

            {{-- Admin Allocation Charts --}}
            @isset($api['balances'])@isset($api['admin'])
            <div class="row mb-4" id="section-allocation">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header" style="background-color: #fff3cd;">
                            <strong><i class="fa fa-chart-pie me-2"></i>Fund Allocation (ADMIN)</strong>
                        </div>
                        <div class="card-body">
                            @include('funds.allocation_graph')
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header" style="background-color: #fff3cd;">
                            <strong><i class="fa fa-users me-2"></i>Accounts Allocation (ADMIN)</strong>
                        </div>
                        <div class="card-body">
                            @include('funds.accounts_graph')
                        </div>
                    </div>
                </div>
            </div>
            @endisset @endisset

            {{-- Performance Tables --}}
            <div class="row mb-4" id="section-performance">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-table me-2"></i>Yearly Performance</strong>
                        </div>
                        <div class="card-body">
                            @php ($performance_key = 'yearly_performance')
                            @include('funds.performance_table')
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
                            @include('funds.performance_table')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trade Portfolios Details --}}
            @foreach($api['tradePortfolios'] as $tradePortfolio)
                @php($extraTitle = '' . $tradePortfolio->id)
                @php($tradePortfolioItems = $tradePortfolio->items)
                <div class="row mb-4">
                    @include('trade_portfolios.inner_show')
                </div>
            @endforeach

            {{-- Assets Table - Collapsible --}}
            <div class="row mb-4" id="section-assets-table">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong><i class="fa fa-coins me-2"></i>Assets</strong>
                            <a class="btn btn-outline-secondary btn-sm" data-toggle="collapse" href="#collapseAT"
                               role="button" aria-expanded="false" aria-controls="collapseAT">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse" id="collapseAT">
                            <div class="card-body">
                                @include('funds.assets_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transactions Table - Collapsible --}}
            <div class="row mb-4" id="section-transactions">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong><i class="fa fa-exchange-alt me-2"></i>Transactions</strong>
                            <a class="btn btn-outline-secondary btn-sm" data-toggle="collapse" href="#collapseATrans"
                               role="button" aria-expanded="false" aria-controls="collapseATrans">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse" id="collapseATrans">
                            <div class="card-body">
                                @include('accounts.transactions_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Asset Performance by Group - Collapsible --}}
            @foreach($api['asset_monthly_performance'] as $group => $perf)
                <div class="row mb-4" id="section-group-{{ Str::slug($group) }}">
                    <div class="col">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <strong><i class="fa fa-layer-group me-2"></i>Group {{ $group }} Performance</strong>
                                <a class="btn btn-outline-secondary btn-sm" data-toggle="collapse" href="#collapse{{$group}}"
                                   role="button" aria-expanded="false" aria-controls="collapse{{$group}}">
                                    <i class="fa fa-chevron-down"></i>
                                </a>
                            </div>
                            <div class="collapse" id="collapse{{$group}}">
                                <div class="card-body">
                                    @include('funds.performance_line_graph_assets')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Accounts Table - Admin Only, Collapsible --}}
            @isset($api['balances']) @isset($api['admin'])
                <div class="row mb-4" id="section-accounts">
                    <div class="col">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #fff3cd;">
                                <strong><i class="fa fa-user-friends me-2"></i>Accounts (ADMIN)</strong>
                                <a class="btn btn-outline-secondary btn-sm" data-toggle="collapse" href="#collapseAccounts"
                                   role="button" aria-expanded="false" aria-controls="collapseAccounts">
                                    <i class="fa fa-chevron-down"></i>
                                </a>
                            </div>
                            <div class="collapse" id="collapseAccounts">
                                <div class="card-body">
                                    @include('funds.accounts_table')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endisset @endisset
        </div>
    </div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    const $jumpBar = $('#jumpBar');
    const $jumpBarAnchor = $('#jumpBarAnchor');

    // Show/hide nav based on scroll position
    function updateNavVisibility() {
        if ($jumpBarAnchor.length === 0) return;

        const anchorTop = $jumpBarAnchor.offset().top;
        const scrollTop = $(window).scrollTop();

        if (scrollTop > anchorTop - 100) {
            $jumpBar.slideDown(200);
        } else {
            $jumpBar.slideUp(200);
        }
    }

    // Initial check
    updateNavVisibility();

    // Smooth scroll for navigation
    $('.jump-nav-btn').click(function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        const $target = $(target);

        if ($target.length === 0) return;

        // Expand if target has a collapse
        const $collapse = $target.find('.collapse');
        if ($collapse.length > 0) {
            $collapse.collapse('show');
        }

        // Scroll with offset for sticky nav and main navbar
        const offset = 120;
        $('html, body').animate({
            scrollTop: $target.offset().top - offset
        }, 300);

        // Highlight active button
        $('.jump-nav-btn').removeClass('btn-primary btn-warning').addClass(function() {
            return $(this).hasClass('btn-outline-warning') ? 'btn-outline-warning' : 'btn-outline-primary';
        });
        $(this).removeClass('btn-outline-primary btn-outline-warning btn-outline-secondary').addClass('btn-primary');
    });

    // Update nav visibility and active button on scroll
    $(window).scroll(function() {
        updateNavVisibility();

        // Update active nav button
        const offset = 150;
        let currentSection = null;

        $('[id^="section-"]').each(function() {
            const sectionTop = $(this).offset().top - offset;
            if ($(window).scrollTop() >= sectionTop) {
                currentSection = $(this).attr('id');
            }
        });

        if (currentSection) {
            $('.jump-nav-btn').removeClass('btn-primary').addClass(function() {
                if ($(this).hasClass('btn-outline-warning') || $(this).data('admin')) {
                    return 'btn-outline-warning';
                }
                if ($(this).hasClass('btn-outline-secondary') || $(this).data('secondary')) {
                    return 'btn-outline-secondary';
                }
                return 'btn-outline-primary';
            });
            $('.jump-nav-btn[href="#' + currentSection + '"]')
                .removeClass('btn-outline-primary btn-outline-warning btn-outline-secondary')
                .addClass('btn-primary');
        }
    });
});
</script>
@endpush
</x-app-layout>
