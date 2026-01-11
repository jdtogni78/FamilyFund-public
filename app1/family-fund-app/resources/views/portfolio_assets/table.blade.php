<div class="table-responsive">
    @php
        $currentSort = request('sort', 'start_dt');
        $currentDir = request('dir', 'desc');
        $nextDir = $currentDir === 'asc' ? 'desc' : 'asc';

        function sortLinkPA($column, $label, $currentSort, $currentDir, $nextDir) {
            $isActive = $currentSort === $column;
            $dir = $isActive ? $nextDir : 'desc';
            $icon = '';
            if ($isActive) {
                $icon = $currentDir === 'asc' ? ' <i class="fa fa-sort-up"></i>' : ' <i class="fa fa-sort-down"></i>';
            }
            $url = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $dir]);
            return '<a href="' . $url . '" class="text-decoration-none' . ($isActive ? ' fw-bold' : '') . '">' . $label . $icon . '</a>';
        }
    @endphp
    <table class="table table-striped" id="portfolioAssets-table">
        <thead>
            <tr>
                <th>{!! sortLinkPA('fund', 'Fund', $currentSort, $currentDir, $nextDir) !!}</th>
                <th>{!! sortLinkPA('asset', 'Asset', $currentSort, $currentDir, $nextDir) !!}</th>
                <th>{!! sortLinkPA('position', 'Position', $currentSort, $currentDir, $nextDir) !!}</th>
                <th>{!! sortLinkPA('start_dt', 'Start Date', $currentSort, $currentDir, $nextDir) !!}</th>
                <th>{!! sortLinkPA('end_dt', 'End Date', $currentSort, $currentDir, $nextDir) !!}</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($portfolioAssets as $portfolioAsset)
            @php
                $isOverlapping = in_array($portfolioAsset->id, $dataWarnings['overlappingIds'] ?? []);
                $hasGap = in_array($portfolioAsset->id, $dataWarnings['gapIds'] ?? []);
            @endphp
            <tr class="{{ ($isOverlapping || $hasGap) ? 'table-warning' : '' }}">
                <td>
                    @if($isOverlapping)
                        <i class="fa fa-clone text-warning me-1" title="Overlapping date range"></i>
                    @endif
                    @if($hasGap)
                        <i class="fa fa-calendar-times text-warning me-1" title="Adjacent to data gap"></i>
                    @endif
                    @if($portfolioAsset->portfolio && $portfolioAsset->portfolio->fund)
                        <a href="{{ route('portfolioAssets.index', ['fund_id' => $portfolioAsset->portfolio->fund_id]) }}">
                            {{ $portfolioAsset->portfolio->fund->name }}
                        </a>
                    @else
                        Portfolio #{{ $portfolioAsset->portfolio_id }}
                    @endif
                </td>
                <td>
                    <a href="{{ route('portfolioAssets.index', ['asset_id' => $portfolioAsset->asset_id]) }}">
                        {{ $portfolioAsset->asset->name ?? 'Unknown' }}
                    </a>
                    <span class="badge bg-info text-white ms-1">{{ $portfolioAsset->asset->type ?? '-' }}</span>
                </td>
                <td class="text-end">{{ number_format($portfolioAsset->position, 4) }}</td>
                <td>{{ $portfolioAsset->start_dt?->format('Y-m-d') }}</td>
                <td>
                    @if($portfolioAsset->end_dt && $portfolioAsset->end_dt->format('Y') === '9999')
                        <span class="badge bg-success">Current</span>
                    @else
                        {{ $portfolioAsset->end_dt?->format('Y-m-d') }}
                    @endif
                </td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('portfolioAssets.show', [$portfolioAsset->id]) }}" class='btn btn-ghost-success' title="View"><i class="fa fa-eye"></i></a>
                        <a href="{{ route('portfolioAssets.edit', [$portfolioAsset->id]) }}" class='btn btn-ghost-info' title="Edit"><i class="fa fa-edit"></i></a>
                        <form action="{{ route('portfolioAssets.destroy', $portfolioAsset->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this portfolio asset?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if($portfolioAssets instanceof \Illuminate\Pagination\LengthAwarePaginator)
<div class="d-flex justify-content-center mt-3">
    {{ $portfolioAssets->appends(request()->query())->links() }}
</div>
@endif
