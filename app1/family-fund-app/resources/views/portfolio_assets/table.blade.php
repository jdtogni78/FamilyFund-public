<div class="table-responsive-sm">
    <table class="table table-striped" id="portfolioAssets-table">
        <thead>
            <tr>
                <th>Portfolio Id</th>
        <th>Asset Id</th>
        <th>Position</th>
        <th>Start Dt</th>
        <th>End Dt</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($portfolioAssets as $portfolioAsset)
            <tr>
                <td>{{ $portfolioAsset->portfolio_id }}</td>
            <td>{{ $portfolioAsset->asset_id }}</td>
            <td>{{ $portfolioAsset->position }}</td>
            <td>{{ $portfolioAsset->start_dt }}</td>
            <td>{{ $portfolioAsset->end_dt }}</td>
                <td>
                    {!! Form::open(['route' => ['portfolioAssets.destroy', $portfolioAsset->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('portfolioAssets.show', [$portfolioAsset->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('portfolioAssets.edit', [$portfolioAsset->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>