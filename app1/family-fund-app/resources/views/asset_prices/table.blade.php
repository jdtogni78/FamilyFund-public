<div class="table-responsive-sm">
    <table class="table table-striped" id="assetPrices-table">
        <thead>
            <tr>
                <th>Asset Id</th>
        <th>Price</th>
        <th>Start Dt</th>
        <th>End Dt</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($assetPrices as $assetPrice)
            <tr>
                <td>{{ $assetPrice->asset_id }}</td>
            <td>{{ $assetPrice->price }}</td>
            <td>{{ $assetPrice->start_dt }}</td>
            <td>{{ $assetPrice->end_dt }}</td>
                <td>
                    {!! Form::open(['route' => ['assetPrices.destroy', $assetPrice->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('assetPrices.show', [$assetPrice->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('assetPrices.edit', [$assetPrice->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>