<div class="table-responsive-sm">
    <table class="table table-striped table-sm" id="accounts-linreg-table">
        <thead>
            <tr>
                <th>Year</th>
                <th class="text-end">Conservative</th>
                <th class="text-end">Predicted</th>
                <th class="text-end">Aggressive</th>
            </tr>
        </thead>
        <tbody>
        @foreach($api['linear_regression']['predictions'] as $year => $value)
            <tr>
                <td>{{ substr($year, 0, 4) }}</td>
                <td class="text-end">${{ number_format($value * 0.8, 0) }}</td>
                <td class="text-end"><strong>${{ number_format($value, 0) }}</strong></td>
                <td class="text-end">${{ number_format($value * 1.2, 0) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
