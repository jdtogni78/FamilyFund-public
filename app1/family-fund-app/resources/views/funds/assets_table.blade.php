<div class="table-responsive-sm">
    <table class="table table-striped" id="assets-table">
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
                <td>{{ number_format($asset['position'], 6) }}</td>
                <td>@isset($asset['price'])
                        ${{ number_format($asset['price'], 2) }}
                    @else
                        <span class="text-danger">N/A</span>
                    @endisset</td>
                <td>@isset($asset['value'])
                        ${{ number_format($asset['value'], 2) }}
                    @else
                        <span class="text-danger">N/A</span>
                    @endisset</td>
                <td>@isset($asset['value'])
                        {{ number_format(($asset['value'] / $api['summary']['value']) * 100.0, 2) }}%
                    @else
                        <span class="text-danger">N/A</span>
                    @endisset</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
