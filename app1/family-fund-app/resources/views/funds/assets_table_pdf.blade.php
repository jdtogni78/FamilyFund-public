@if(isset($api['portfolio']['assets']) && count($api['portfolio']['assets']) > 0)
<table style="width: 100%;">
    <thead>
        <tr>
            <th>Asset</th>
            <th>Type</th>
            <th class="col-number">Position</th>
            <th class="col-number">Price</th>
            <th class="col-number">Market Value</th>
            <th class="col-number">%</th>
        </tr>
    </thead>
    <tbody>
    @foreach($api['portfolio']['assets'] as $asset)
        <tr>
            <td><strong>{{ $asset['name'] }}</strong></td>
            <td>{{ $asset['type'] ?? '-' }}</td>
            <td class="col-number">{{ number_format($asset['position'] ?? 0, 6) }}</td>
            <td class="col-number">
                @isset($asset['price'])
                    ${{ number_format($asset['price'], 2) }}
                @else
                    <span class="text-muted">N/A</span>
                @endisset
            </td>
            <td class="col-number">
                @isset($asset['value'])
                    ${{ number_format($asset['value'], 2) }}
                @else
                    <span class="text-muted">N/A</span>
                @endisset
            </td>
            <td class="col-number">
                @isset($asset['value'])
                    {{ number_format(($asset['value'] / $api['summary']['value']) * 100.0, 1) }}%
                @else
                    <span class="text-muted">-</span>
                @endisset
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr style="background: #f1f5f9; font-weight: 600;">
            <td colspan="4">Total</td>
            <td class="col-number">${{ number_format($api['summary']['value'], 2) }}</td>
            <td class="col-number">100%</td>
        </tr>
    </tfoot>
</table>
@else
<div class="text-muted" style="padding: 20px; text-align: center; background: #f8fafc; border-radius: 6px;">
    No assets data available.
</div>
@endif
