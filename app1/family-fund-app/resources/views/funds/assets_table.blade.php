<div class="table-responsive-sm">
    <table class="table table-striped" id="fund-assets-table">
        <thead>
            <tr>
                <th scope="col">Asset</th>
                <th scope="col">Type</th>
                <th scope="col">Position</th>
                <th scope="col">Price</th>
                <th scope="col">Market Value</th>
                <th scope="col">%</th>
            </tr>
        </thead>
        <tbody>
        @foreach($api['portfolio']['assets'] as $asset)
            <tr>
                <th scope="row">
                    {{ $asset['name'] }}
                </th>
                <td>{{ $asset['type'] }}</td>
                <td data-order="{{ $asset['position'] }}">{{ number_format($asset['position'], 6) }}</td>
                <td data-order="{{ $asset['price'] ?? 0 }}">@isset($asset['price'])
                        ${{ number_format($asset['price'], 2) }}
                    @else
                        <span class="text-danger">N/A</span>
                    @endisset</td>
                <td data-order="{{ $asset['value'] ?? 0 }}">@isset($asset['value'])
                        ${{ number_format($asset['value'], 2) }}
                    @else
                        <span class="text-danger">N/A</span>
                    @endisset</td>
                <td data-order="{{ isset($asset['value']) ? ($asset['value'] / $api['summary']['value']) * 100.0 : 0 }}">@isset($asset['value'])
                        {{ number_format(($asset['value'] / $api['summary']['value']) * 100.0, 2) }}%
                    @else
                        <span class="text-danger">N/A</span>
                    @endisset</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#fund-assets-table').DataTable({
        order: [[4, 'desc']], // Sort by Market Value descending
        pageLength: 25,
        paging: false,
        searching: false,
        info: false
    });
});
</script>
@endpush
