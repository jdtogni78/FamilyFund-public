<div class="table-responsive-sm">
    <table class="table table-striped table-sm" id="accounts-linreg-table">
        <thead>
            <tr>
                <th>Year</th>
                <th class="text-right">Conservative</th>
                <th class="text-right">Predicted</th>
                <th class="text-right">Aggressive</th>
            </tr>
        </thead>
        <tbody>
        @foreach($api['linear_regression']['predictions'] as $year => $value)
            <tr>
                <td>{{ substr($year, 0, 4) }}</td>
                <td class="text-right">${{ number_format($value * 0.8, 0) }}</td>
                <td class="text-right"><strong>${{ number_format($value, 0) }}</strong></td>
                <td class="text-right">${{ number_format($value * 1.2, 0) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
