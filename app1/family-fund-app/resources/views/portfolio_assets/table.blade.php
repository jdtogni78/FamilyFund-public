<div class="table-responsive">
    <table class="table table-striped" id="portfolioAssets-table">
        <thead>
            <tr>
                <th>Fund</th>
                <th>Asset</th>
                <th>Position</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($portfolioAssets as $portfolioAsset)
            <tr>
                <td>
                    @if($portfolioAsset->portfolio && $portfolioAsset->portfolio->fund)
                        <a href="{{ route('portfolioAssets.index', ['portfolio_id' => $portfolioAsset->portfolio_id]) }}">
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