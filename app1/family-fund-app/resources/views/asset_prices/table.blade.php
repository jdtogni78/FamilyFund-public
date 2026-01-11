<div class="table-responsive-sm">
    <table class="table table-striped" id="assetPrices-table">
        <thead>
            <tr>
                <th>Asset</th>
                <th>Type</th>
                <th>Price</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($assetPrices as $assetPrice)
            <tr>
                <td>
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