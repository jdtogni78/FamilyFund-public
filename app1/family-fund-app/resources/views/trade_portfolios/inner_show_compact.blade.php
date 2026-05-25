@php
    $editable = \Carbon\Carbon::parse($tradePortfolio->end_dt)->isBefore($asOf);
    $tradePortfolioItems = $tradePortfolio['items'];

    // Group items by their group field
    $groupedItems = collect($tradePortfolioItems)->groupBy('group');

    // Get cash asset's group for proper display
    $cashAsset = \App\Models\AssetExt::getCashAsset();
    $cashGroup = $cashAsset->display_group ?? 'Stability';
    $cashPct = $tradePortfolio->cash_target * 100;

    // Group color palette (indexed by hash for consistent colors)
    $groupPalette = [
        ['bg' => '#2563eb', 'text' => '#ffffff'], // blue
        ['bg' => '#0f766e', 'text' => '#ffffff'], // teal
        ['bg' => '#b45309', 'text' => '#ffffff'], // amber
        ['bg' => '#7c3aed', 'text' => '#ffffff'], // violet
        ['bg' => '#be123c', 'text' => '#ffffff'], // rose
        ['bg' => '#4338ca', 'text' => '#ffffff'], // indigo
    ];
    $getGroupColors = fn($name) => $groupPalette[crc32($name) % 6];

    $groupCount = count($tradePortfolio->groups);
    $colClass = $groupCount <= 2 ? 'col-md-6' : ($groupCount == 3 ? 'col-lg-4 col-md-6' : 'col-lg-3 col-md-6');
@endphp

{{-- Portfolio Sub-header --}}
<div class="d-flex justify-content-between align-items-center py-2 px-3 mb-2 rounded bg-slate-100 dark:bg-slate-700" style="border-left: 4px solid #134e4a;">
    <div>
        <strong class="text-teal-800 dark:text-teal-200">
            <i class="fa fa-briefcase me-1"></i>Portfolio {{ $tradePortfolio->id }}
        </strong>
        <span class="badge ms-2" style="background: #134e4a; color: white;">{{ $api['portfolio']['source'] ?? 'N/A' }}</span>
    </div>
    <div>
        @if(!$editable)
            <a href="{{ route('tradePortfolios.rebalance', [$tradePortfolio->id]) }}" class="btn btn-sm btn-outline-warning me-1" title="Rebalance"><i class="fa fa-balance-scale"></i></a>
            <a href="{{ route('tradePortfoliosItems.createWithParams', ['tradePortfolioId' => $tradePortfolio->id]) }}" class="btn btn-sm btn-outline-secondary me-1" title="Add Item"><i class="fa fa-plus"></i></a>
            <a href="{{ route('tradePortfolios.edit', [$tradePortfolio->id]) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fa fa-edit"></i></a>
            <a href="{{ route('tradePortfolios.show_diff', [$tradePortfolio->id]) }}" class="btn btn-sm btn-outline-secondary" title="Compare"><i class="fa fa-random"></i></a>
            <a href="{{ route('funds.show_trade_bands', [$tradePortfolio->portfolio->fund()->first()->id, $tradePortfolio->id, $asOf]) }}" class="btn btn-sm btn-outline-secondary" title="Trade Bands"><i class="fa fa-wave-square"></i></a>
        @endif
    </div>
</div>

{{-- Summary Stats --}}
<div class="d-flex flex-wrap justify-content-between text-center mb-3 pb-2 border-b border-slate-200 dark:border-slate-600" style="gap: 0.5rem;">
    <div class="px-3 border-end">
        <div class="text-muted small">Period</div>
        <div class="fw-bold small">{{ \Carbon\Carbon::parse($tradePortfolio->start_dt)->format('M j, Y') }} - {{ \Carbon\Carbon::parse($tradePortfolio->end_dt)->format('M j, Y') }}</div>
    </div>
    <div class="px-3 border-end">
        <div class="text-muted small">Cash</div>
        <div class="fw-bold" style="color: #0d9488;">{{ $tradePortfolio->cash_target * 100 }}%</div>
    </div>
    <div class="px-3 border-end">
        <div class="text-muted small">Reserve</div>
        <div class="fw-bold">{{ $tradePortfolio->cash_reserve_target * 100 }}%</div>
    </div>
    <div class="px-3 border-end">
        <div class="text-muted small">Min Order</div>
        <div class="fw-bold">${{ number_format($tradePortfolio->minimum_order, 0) }}</div>
    </div>
    <div class="px-3 border-end">
        <div class="text-muted small">Max Order</div>
        <div class="fw-bold">{{ $tradePortfolio->max_single_order * 100 }}%</div>
    </div>
    <div class="px-3 border-end">
        <div class="text-muted small">Rebalance</div>
        <div class="fw-bold">{{ $tradePortfolio->rebalance_period }}d</div>
    </div>
    <div class="px-3 border-end">
        <div class="text-muted small">Account</div>
        <div class="fw-bold">{{ $tradePortfolio->account_name }}</div>
    </div>
    <div class="px-3">
        <div class="text-muted small">Total</div>
        @if($tradePortfolio->total_shares == 100)
            <div class="fw-bold" style="color: #16a34a;"><i class="fa fa-check-circle"></i> {{ $tradePortfolio->total_shares }}%</div>
        @else
            <div class="fw-bold" style="color: #dc2626;"><i class="fa fa-exclamation-circle"></i> {{ $tradePortfolio->total_shares }}%</div>
        @endif
    </div>
</div>

{{-- Portfolio Allocation Groups --}}
<div class="row">
    @foreach($tradePortfolio->groups as $group => $targetPct)
        @php
            $colors = $getGroupColors($group);
            $items = $groupedItems->get($group, collect());
            $actualPct = $items->sum('target_share') * 100;
            // Add cash to the group it belongs to
            $groupHasCash = ($group === $cashGroup && $cashPct > 0);
            if ($groupHasCash) {
                $actualPct += $cashPct;
            }
            $isMatch = abs($actualPct - $targetPct) < 0.01;
        @endphp

        <div class="{{ $colClass }} mb-3">
            {{-- Group Header --}}
            <div class="d-flex justify-content-between align-items-center p-2 rounded-top" style="background: {{ $colors['bg'] }};">
                <strong style="color: {{ $colors['text'] }};">{{ $group }}</strong>
                <div>
                    <span class="badge" style="background: #111827; color: #ffffff;">{{ $targetPct }}%</span>
                    @if($isMatch)
                        <i class="fa fa-check-circle ms-1" style="color: #16a34a;"></i>
                    @else
                        <span class="badge badge-danger ms-1">{{ number_format($actualPct, 1) }}%</span>
                    @endif
                </div>
            </div>

            {{-- Group Items --}}
            <table class="table table-sm table-hover mb-0 border-x border-b border-slate-200 dark:border-slate-600" style="font-size: 0.85rem;">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th>Symbol</th>
                        <th class="text-end">Target</th>
                        <th class="text-end" style="width: 60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td><strong>{{ $item->symbol }}</strong></td>
                            <td class="text-end">{{ $item->target_share * 100 }}% <span class="text-muted" style="font-size: 0.75rem;">±{{ $item->deviation_trigger * 100 }}%</span></td>
                            <td class="text-end">
                                <a href="{{ route('tradePortfolioItems.show', [$item->id]) }}" class="btn btn-sm btn-link p-0" title="View"><i class="fa fa-eye text-success"></i></a>
                                @if(!$editable)
                                    <a href="{{ route('tradePortfolioItems.edit', [$item->id]) }}" class="btn btn-sm btn-link p-0 ms-1" title="Edit"><i class="fa fa-edit text-primary"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @if(!$groupHasCash)
                        <tr>
                            <td colspan="3" class="text-center text-muted py-2">No items</td>
                        </tr>
                        @endif
                    @endforelse
                    @if($groupHasCash)
                        <tr class="table-info">
                            <td><strong><i class="fa fa-coins me-1" style="color: #0d9488;"></i>CASH</strong></td>
                            <td class="text-end">{{ $cashPct }}%</td>
                            <td class="text-end"></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endforeach

    {{-- Items without a defined group target --}}
    @php
        $ungroupedItems = $groupedItems->filter(fn($items, $key) => !isset($tradePortfolio->groups[$key]));
    @endphp
    @foreach($ungroupedItems as $group => $items)
        @php $colors = $getGroupColors($group ?: 'Other'); @endphp
        <div class="{{ $colClass }} mb-3">
            <div class="d-flex justify-content-between align-items-center p-2 rounded-top" style="background: {{ $colors['bg'] }};">
                <strong style="color: {{ $colors['text'] }};">{{ $group ?: 'Other' }}</strong>
                <span class="badge" style="background: #111827; color: #ffffff;">{{ number_format($items->sum('target_share') * 100, 1) }}%</span>
            </div>
            <table class="table table-sm table-hover mb-0 border-x border-b border-slate-200 dark:border-slate-600" style="font-size: 0.85rem;">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th>Symbol</th>
                        <th class="text-end">Target</th>
                        <th class="text-end" style="width: 60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td><strong>{{ $item->symbol }}</strong></td>
                            <td class="text-end">{{ $item->target_share * 100 }}% <span class="text-muted" style="font-size: 0.75rem;">±{{ $item->deviation_trigger * 100 }}%</span></td>
                            <td class="text-end">
                                <a href="{{ route('tradePortfolioItems.show', [$item->id]) }}" class="btn btn-sm btn-link p-0"><i class="fa fa-eye text-success"></i></a>
                                @if(!$editable)
                                    <a href="{{ route('tradePortfolioItems.edit', [$item->id]) }}" class="btn btn-sm btn-link p-0 ms-1"><i class="fa fa-edit text-primary"></i></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>

@if(!$loop->last)
    <hr class="my-4">
@endif
