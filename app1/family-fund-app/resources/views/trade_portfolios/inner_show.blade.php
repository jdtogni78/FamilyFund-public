@php
    $editable = \Carbon\Carbon::parse($tradePortfolio->end_dt)->isBefore($asOf);
    $tradePortfolioItems = $tradePortfolio['items'];

    // Group items by their group field
    $groupedItems = collect($tradePortfolioItems)->groupBy('group');

    // Group colors
    $groupColors = [
        'Growth' => ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#15803d'],
        'Stability' => ['bg' => '#dbeafe', 'border' => '#2563eb', 'text' => '#1d4ed8'],
        'Crypto' => ['bg' => '#fef3c7', 'border' => '#d97706', 'text' => '#b45309'],
        'default' => ['bg' => '#f1f5f9', 'border' => '#64748b', 'text' => '#475569'],
    ];
@endphp

{{-- Compact Header Card --}}
<div class="row mb-3">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e40af; color: white;">
                <div>
                    <strong style="font-size: 1.1rem;">
                        <i class="fa fa-briefcase mr-2"></i>Trade Portfolio {{ $tradePortfolio->id }}
                    </strong>
                    <span class="badge ml-2" style="background: rgba(255,255,255,0.2);">{{ $api['portfolio']['source'] ?? 'N/A' }}</span>
                </div>
                <div>
                    <a href="{{ route('tradePortfolios.index') }}" class="btn btn-sm btn-light mr-1">Back</a>
                    @if(!$editable)
                        <a href="{{ route('tradePortfolios.edit', [$tradePortfolio->id]) }}" class="btn btn-sm btn-outline-light" title="Edit"><i class="fa fa-edit"></i></a>
                        <a href="{{ route('tradePortfolios.show_diff', [$tradePortfolio->id]) }}" class="btn btn-sm btn-outline-light" title="Compare"><i class="fa fa-random"></i></a>
                        <a href="{{ route('funds.show_trade_bands', [$tradePortfolio->portfolio->fund()->first()->id, $tradePortfolio->id, $asOf]) }}" class="btn btn-sm btn-outline-light" title="Trade Bands"><i class="fa fa-wave-square"></i></a>
                    @endif
                </div>
            </div>
            <div class="card-body py-2">
                <div class="row text-center">
                    <div class="col-md-2 col-4 border-right">
                        <div class="text-muted small">Period</div>
                        <div class="font-weight-bold">{{ \Carbon\Carbon::parse($tradePortfolio->start_dt)->format('M j, Y') }}</div>
                        <div class="text-muted small">to {{ \Carbon\Carbon::parse($tradePortfolio->end_dt)->format('M j, Y') }}</div>
                    </div>
                    <div class="col-md-2 col-4 border-right">
                        <div class="text-muted small">Cash Target</div>
                        <div class="font-weight-bold" style="color: #2563eb;">{{ $tradePortfolio->cash_target * 100 }}%</div>
                        <div class="text-muted small">Reserve: {{ $tradePortfolio->cash_reserve_target * 100 }}%</div>
                    </div>
                    <div class="col-md-2 col-4 border-right">
                        <div class="text-muted small">Order Limits</div>
                        <div class="font-weight-bold">${{ number_format($tradePortfolio->minimum_order, 0) }} min</div>
                        <div class="text-muted small">{{ $tradePortfolio->max_single_order * 100 }}% max</div>
                    </div>
                    <div class="col-md-2 col-4 border-right">
                        <div class="text-muted small">Rebalance</div>
                        <div class="font-weight-bold">{{ $tradePortfolio->rebalance_period }} days</div>
                    </div>
                    <div class="col-md-2 col-4 border-right">
                        <div class="text-muted small">Account</div>
                        <div class="font-weight-bold">{{ $tradePortfolio->account_name }}</div>
                    </div>
                    <div class="col-md-2 col-4">
                        <div class="text-muted small">Total Allocation</div>
                        @if($tradePortfolio->total_shares == 100)
                            <div class="font-weight-bold" style="color: #16a34a;"><i class="fa fa-check-circle mr-1"></i>{{ $tradePortfolio->total_shares }}%</div>
                        @else
                            <div class="font-weight-bold" style="color: #dc2626;"><i class="fa fa-exclamation-circle mr-1"></i>{{ $tradePortfolio->total_shares }}%</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Grouped Items --}}
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="fa fa-layer-group mr-2"></i>Portfolio Allocation</strong>
                @if(!$editable)
                    <a href="{{ route('tradePortfoliosItems.createWithParams', ['tradePortfolioId' => $tradePortfolio->id]) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-plus mr-1"></i>Add Item</a>
                @endif
            </div>
            <div class="card-body">
                @foreach($tradePortfolio->groups as $group => $targetPct)
                    @php
                        $colors = $groupColors[$group] ?? $groupColors['default'];
                        $items = $groupedItems->get($group, collect());
                        $actualPct = $items->sum('target_share') * 100;
                        $isMatch = abs($actualPct - $targetPct) < 0.01;
                    @endphp

                    <div class="mb-4">
                        {{-- Group Header --}}
                        <div class="d-flex justify-content-between align-items-center p-2 rounded-top" style="background: {{ $colors['bg'] }}; border-left: 4px solid {{ $colors['border'] }};">
                            <div>
                                <strong style="color: {{ $colors['text'] }}; font-size: 1rem;">{{ $group }}</strong>
                                <span class="badge ml-2" style="background: {{ $colors['border'] }}; color: white;">Target: {{ $targetPct }}%</span>
                            </div>
                            <div>
                                @if($isMatch)
                                    <span class="badge" style="background: #16a34a; color: white;"><i class="fa fa-check mr-1"></i>{{ number_format($actualPct, 1) }}%</span>
                                @else
                                    <span class="badge" style="background: #dc2626; color: white;"><i class="fa fa-exclamation mr-1"></i>{{ number_format($actualPct, 1) }}%</span>
                                @endif
                            </div>
                        </div>

                        {{-- Group Items Table --}}
                        <table class="table table-sm table-hover mb-0" style="border: 1px solid #e2e8f0; border-top: none;">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th style="width: 20%;">Symbol</th>
                                    <th style="width: 15%;">Type</th>
                                    <th style="width: 20%;" class="text-right">Target</th>
                                    <th style="width: 20%;" class="text-right">Deviation</th>
                                    <th style="width: 25%;" class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td><strong>{{ $item->symbol }}</strong></td>
                                        <td><span class="badge badge-secondary">{{ $item->type }}</span></td>
                                        <td class="text-right">{{ $item->target_share * 100 }}%</td>
                                        <td class="text-right">{{ $item->deviation_trigger * 100 }}%</td>
                                        <td class="text-right">
                                            <a href="{{ route('tradePortfolioItems.show', [$item->id]) }}" class="btn btn-sm btn-outline-success" title="View"><i class="fa fa-eye"></i></a>
                                            @if(!$editable)
                                                <a href="{{ route('tradePortfolioItems.edit', [$item->id]) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa fa-edit"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No items in this group</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach

                {{-- Items without a group (if any) --}}
                @php
                    $ungroupedItems = $groupedItems->filter(fn($items, $key) => !isset($tradePortfolio->groups[$key]));
                @endphp
                @if($ungroupedItems->isNotEmpty())
                    @foreach($ungroupedItems as $group => $items)
                        @php $colors = $groupColors['default']; @endphp
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center p-2 rounded-top" style="background: {{ $colors['bg'] }}; border-left: 4px solid {{ $colors['border'] }};">
                                <strong style="color: {{ $colors['text'] }};">{{ $group ?: 'Ungrouped' }}</strong>
                                <span class="badge" style="background: {{ $colors['border'] }}; color: white;">{{ number_format($items->sum('target_share') * 100, 1) }}%</span>
                            </div>
                            <table class="table table-sm table-hover mb-0" style="border: 1px solid #e2e8f0; border-top: none;">
                                <thead style="background: #f8fafc;">
                                    <tr>
                                        <th>Symbol</th>
                                        <th>Type</th>
                                        <th class="text-right">Target</th>
                                        <th class="text-right">Deviation</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $item)
                                        <tr>
                                            <td><strong>{{ $item->symbol }}</strong></td>
                                            <td><span class="badge badge-secondary">{{ $item->type }}</span></td>
                                            <td class="text-right">{{ $item->target_share * 100 }}%</td>
                                            <td class="text-right">{{ $item->deviation_trigger * 100 }}%</td>
                                            <td class="text-right">
                                                <a href="{{ route('tradePortfolioItems.show', [$item->id]) }}" class="btn btn-sm btn-outline-success"><i class="fa fa-eye"></i></a>
                                                @if(!$editable)
                                                    <a href="{{ route('tradePortfolioItems.edit', [$item->id]) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
