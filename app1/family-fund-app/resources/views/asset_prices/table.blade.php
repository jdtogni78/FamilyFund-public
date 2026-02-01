<div class="table-responsive-sm">
    @php
        $currentSort = request('sort', 'start_dt');
        $currentDir = request('dir', 'desc');
        $nextDir = $currentDir === 'asc' ? 'desc' : 'asc';

        if (!function_exists('sortLink')) {
            function sortLink($column, $label, $currentSort, $currentDir, $nextDir) {
                $isActive = $currentSort === $column;
                $dir = $isActive ? $nextDir : 'desc';
                $icon = '';
                if ($isActive) {
                    $icon = $currentDir === 'asc' ? ' <i class="fa fa-sort-up"></i>' : ' <i class="fa fa-sort-down"></i>';
                }
                $url = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $dir]);
                return '<a href="' . $url . '" class="text-decoration-none' . ($isActive ? ' fw-bold' : '') . '">' . $label . $icon . '</a>';
            }
        }
    @endphp
    <table class="table table-striped" id="assetPrices-table">
        <thead>
            <tr>
                <th>{!! sortLink('asset', 'Asset', $currentSort, $currentDir, $nextDir) !!}</th>
                <th>{!! sortLink('type', 'Type', $currentSort, $currentDir, $nextDir) !!}</th>
                <th>{!! sortLink('price', 'Price', $currentSort, $currentDir, $nextDir) !!}</th>
                <th>{!! sortLink('start_dt', 'Start Date', $currentSort, $currentDir, $nextDir) !!}</th>
                <th>{!! sortLink('end_dt', 'End Date', $currentSort, $currentDir, $nextDir) !!}</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($assetPrices as $assetPrice)
            @php
                $isOverlapping = in_array($assetPrice->id, $dataWarnings['overlappingIds'] ?? []);
                $hasGap = in_array($assetPrice->id, $dataWarnings['gapIds'] ?? []);
                $hasLongSpan = in_array($assetPrice->id, $dataWarnings['longSpanIds'] ?? []);
            @endphp
            <tr class="{{ ($isOverlapping || $hasGap || $hasLongSpan) ? 'table-warning' : '' }}">
                <td>
                    @if($isOverlapping)
                        <i class="fa fa-clone text-warning me-1" title="Overlapping date range"></i>
                    @endif
                    @if($hasLongSpan)
                        <i class="fa fa-calendar-plus text-warning me-1" title="Days without new data"></i>
                    @endif
                    @if($hasGap)
                        <i class="fa fa-calendar-times text-warning me-1" title="Adjacent to data gap"></i>
                    @endif
                    <a href="{{ route('assetPrices.index', ['asset_id' => $assetPrice->asset_id]) }}">
                        {{ $assetPrice->asset->name ?? 'Unknown' }}
                    </a>
                </td>
                <td><span class="badge bg-info text-white">{{ $assetPrice->asset->type ?? '-' }}</span></td>
                <td class="text-end">${{ number_format($assetPrice->price, 4) }}</td>
                <td>{{ $assetPrice->start_dt?->format('Y-m-d') }}</td>
                <td>
                    @if($assetPrice->end_dt && $assetPrice->end_dt->format('Y') === '9999')
                        <span class="badge bg-success">Current</span>
                    @else
                        {{ $assetPrice->end_dt?->format('Y-m-d') }}
                    @endif
                </td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('assetPrices.show', [$assetPrice->id]) }}" class='btn btn-ghost-success' title="View"><i class="fa fa-eye"></i></a>
                        <a href="{{ route('assetPrices.edit', [$assetPrice->id]) }}" class='btn btn-ghost-info' title="Edit"><i class="fa fa-edit"></i></a>
                        <form action="{{ route('assetPrices.destroy', $assetPrice->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this asset price?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if($assetPrices instanceof \Illuminate\Pagination\LengthAwarePaginator)
<div class="d-flex justify-content-center mt-3">
    {{ $assetPrices->appends(request()->query())->links() }}
</div>
@endif
