<x-app-layout>

@section('content')
    @php
        // Build list of symbols that are in at least one trade portfolio (for jump bar)
        $portfolioSymbols = collect($api['tradePortfolios'] ?? [])
            ->flatMap(fn($tp) => collect($tp->items ?? $tp['items'] ?? [])->pluck('symbol'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    @endphp

    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('funds.index') }}">Fund</a>
        </li>
        <li class="breadcrumb-item active">Trading Bands</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')

            {{-- Simple Header --}}
            <div class="row mb-3" id="section-details">
                <div class="col">
                    <div class="card" style="border: 2px solid #1e40af;">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); gap: 8px;">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0" style="color: #ffffff; font-weight: 700;">
                                    <i class="fa fa-chart-bar mr-2"></i>{{ $api['name'] }} - Trading Bands
                                </h5>
                            </div>
                            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                <form method="GET" action="" class="d-flex align-items-center gap-2 mb-0">
                                    <label for="from" class="mb-0 small" style="color: rgba(255,255,255,0.8);">From:</label>
                                    <input type="date" name="from" id="from" class="form-control form-control-sm" style="width: auto;"
                                           value="{{ $fromDate ?? '' }}">
                                    <label for="to" class="mb-0 small" style="color: rgba(255,255,255,0.8);">To:</label>
                                    <input type="date" name="to" id="to" class="form-control form-control-sm" style="width: auto;"
                                           value="{{ $asOf ?? '' }}" disabled title="Use as_of route for end date">
                                    <button type="submit" class="btn btn-light btn-sm">Apply</button>
                                </form>
                                <button class="btn btn-outline-light btn-sm" id="toggleAllFromHeader" title="Collapse/Expand All">
                                    <i class="fa fa-compress-arrows-alt"></i> All
                                </button>
                                <button class="btn btn-outline-light btn-sm" id="toggleAssetsFromHeader" title="Collapse/Expand Assets Only">
                                    <i class="fa fa-chart-line"></i> Assets
                                </button>
                                <a href="/funds/{{ $api['id'] }}/trade_bands_pdf_as_of/{{ $asOf ?? now()->format('Y-m-d') }}{{ $fromDate ? '?from=' . $fromDate : '' }}" class="btn btn-outline-light btn-sm" title="Download PDF">
                                    <i class="fa fa-file-pdf"></i>
                                </a>
                                <a href="/funds/{{ $api['id'] }}" class="btn btn-outline-light btn-sm" title="Fund Details">
                                    <i class="fa fa-info-circle"></i>
                                </a>
                                <a href="{{ route('funds.index') }}" class="btn btn-outline-light btn-sm">Back</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Jump Bar with stock sections --}}
            @php
                $jumpSections = [
                    ['id' => 'section-details', 'icon' => 'fa-info-circle', 'label' => 'Details'],
                ];
                if (!empty($api['allocation_status']['symbols'])) {
                    $jumpSections[] = ['id' => 'section-status', 'icon' => 'fa-tasks', 'label' => 'Status'];
                }
                $jumpSections[] = ['id' => 'section-comparison', 'icon' => 'fa-columns', 'label' => 'Comparison'];
                foreach ($portfolioSymbols as $symbol) {
                    $jumpSections[] = ['id' => 'section-' . $symbol, 'icon' => 'fa-chart-line', 'label' => $symbol];
                }
            @endphp
            @include('partials.jump_bar', ['sections' => $jumpSections])

            {{-- Current Allocation Status --}}
            @if(!empty($api['allocation_status']['symbols']))
                <div class="row mb-4" id="section-status">
                    <div class="col">
                        @include('partials.allocation_status_table', [
                            'allocationStatus' => $api['allocation_status']
                        ])
                    </div>
                </div>
            @endif

            {{-- Trade Portfolios Comparison --}}
            <div class="row mb-4" id="section-comparison">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: white;">
                            <strong><i class="fa fa-columns mr-2"></i>Trade Portfolios Comparison</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseComparison"
                               role="button" aria-expanded="true" aria-controls="collapseComparison">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseComparison">
                            <div class="card-body">
                                @include('trade_portfolios.inner_show_alt')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Asset Bands Charts --}}
            @include('funds.performance_line_graph_assets_with_bands', ['portfolioSymbols' => $portfolioSymbols])
        </div>
    </div>

@push('scripts')
<script>
$(document).ready(function() {
    // Asset symbols for filtering
    const assetSymbols = @json($portfolioSymbols);
    const assetCollapseIds = assetSymbols.map(s => '#collapse' + s);

    // Add "Assets" button to jump bar (after the existing toggle all button)
    $('#toggleAllSections').after(
        '<button class="btn btn-sm btn-outline-secondary ms-1 mb-1" id="toggleAssetSections" title="Collapse/Expand Assets Only">' +
        '<i class="fa fa-chart-line"></i>' +
        '</button>'
    );

    // Toggle ALL sections (header button syncs with jump bar)
    let allExpanded = true;
    function toggleAll() {
        if (allExpanded) {
            $('.collapse.show').collapse('hide');
            $('#toggleAllFromHeader i, #toggleAllSections i').removeClass('fa-compress-arrows-alt').addClass('fa-expand-arrows-alt');
        } else {
            $('.collapse:not(.show)').collapse('show');
            $('#toggleAllFromHeader i, #toggleAllSections i').removeClass('fa-expand-arrows-alt').addClass('fa-compress-arrows-alt');
        }
        allExpanded = !allExpanded;
    }
    $('#toggleAllFromHeader').click(toggleAll);

    // Toggle ASSETS only (header and jump bar)
    let assetsExpanded = true;
    function toggleAssets() {
        assetCollapseIds.forEach(id => {
            const $el = $(id);
            if (assetsExpanded) {
                $el.collapse('hide');
            } else {
                $el.collapse('show');
            }
        });
        const icon = assetsExpanded ? 'fa-expand' : 'fa-chart-line';
        const oldIcon = assetsExpanded ? 'fa-chart-line' : 'fa-expand';
        $('#toggleAssetsFromHeader i, #toggleAssetSections i').removeClass(oldIcon).addClass(icon);
        assetsExpanded = !assetsExpanded;
    }
    $('#toggleAssetsFromHeader').click(toggleAssets);
    $('#toggleAssetSections').click(toggleAssets);
});
</script>
@endpush
</x-app-layout>
