<div class="table-responsive-sm">
    <table class="table table-striped" id="funds-table">
        <thead>
            <tr>
                <th>Year</th>
                <th>Conservative Prediction</th>
                <th>Predicted Value</th>
                <th>Aggressive Prediction</th>
            </tr>
        </thead>
        <tbody>
        @foreach($api['linear_regression']['predictions'] as $year => $value)
            <tr>
                <td>{{ $year }}</td>
                <td>{{ $value * 0.8 }}</td>
                <td>{{ $value }}</td>
                <td>{{ $value * 1.2 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>