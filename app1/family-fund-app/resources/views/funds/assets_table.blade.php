<div class="table-responsive-sm">
    <table class="table table-striped" id="assets-table">
        <thead>
            <tr>
                <th scope="col">Asset</th>
                <th scope="col">Type</th>
                <th scope="col">Position</th>
                <th scope="col">Price</th>
                <th scope="col">Market Value</th>
            </tr>
        </thead>
        <tbody>
        @foreach($api['portfolio']['assets'] as $asset)
            <tr>
                <th scope="row">
                    {{ $asset['name'] }}
                </th>
                <td>{{ $asset['type'] }}</td>
                <td>{{ $asset['position'] }}</td>
                <td>@isset($asset['price'])
                        $ {{ $asset['price'] }}
                    @else
                        <div class="alert alert-danger" role="alert">
                            N/A
                        </div>
                    @endisset</td>
                <td>@isset($asset['value'])
                        $ {{ $asset['value'] }}
                    @else
                        <div class="alert alert-danger" role="alert">
                        N/A
                        </div>
                    @endisset</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
