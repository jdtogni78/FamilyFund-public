<div class="table-responsive-sm">
    <table class="table table-striped" id="matchingRules-table">
        <thead>
            <tr>
                <th>Name</th>
        <th>Dollar Range Start</th>
        <th>Dollar Range End</th>
        <th>Date Start</th>
        <th>Date End</th>
        <th>Match Percent</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($matchingRules as $matchingRule)
            <tr>
                <td>{{ $matchingRule->name }}</td>
            <td>{{ $matchingRule->dollar_range_start }}</td>
            <td>{{ $matchingRule->dollar_range_end }}</td>
            <td>{{ $matchingRule->date_start }}</td>
            <td>{{ $matchingRule->date_end }}</td>
            <td>{{ $matchingRule->match_percent }}</td>
                <td>
                    {!! Form::open(['route' => ['matchingRules.destroy', $matchingRule->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('matchingRules.show', [$matchingRule->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('matchingRules.edit', [$matchingRule->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>