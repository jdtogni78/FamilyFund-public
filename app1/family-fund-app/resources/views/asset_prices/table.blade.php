<div class="table-responsive-sm">
    <table class="table table-striped" id="assetPrices-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Asset Id</th>
                <th>Asset Name</th>
                <th>Asset Type</th>
                <th>Price</th>
                <th>Start Dt</th>
                <th>End Dt</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($assetPrices as $assetPrice)
            <tr>
                <td>{{ $assetPrice->id }}</td>
                <td>{{ $assetPrice->asset_id }}</td>
                <td>{{ $assetPrice->asset()->first()?->name }}</td>
                <td>{{ $assetPrice->asset()->first()?->type }}</td>
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

@push('scripts')
<script>
    $(document).ready(function() {
        $('#assetPrices-table').DataTable();
    });
</script>
@endpush