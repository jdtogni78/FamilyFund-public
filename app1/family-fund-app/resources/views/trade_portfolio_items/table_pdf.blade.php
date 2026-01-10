@if(isset($tradePortfolioItems) && count($tradePortfolioItems) > 0)
<table style="width: 100%;">
    <thead>
        <tr>
            <th>Symbol</th>
            <th>Type</th>
            <th>Group</th>
            <th class="col-number">Target Share</th>
        </tr>
    </thead>
    <tbody>
    @foreach($tradePortfolioItems as $item)
        <tr>
            <td><strong>{{ $item->symbol }}</strong></td>
            <td>{{ $item->type }}</td>
            <td>{{ $item->group }}</td>
            <td class="col-number">{{ number_format($item->target_share * 100, 1) }}% <span style="color: #94a3b8; font-size: 9px;">±{{ number_format($item->deviation_trigger * 100, 1) }}%</span></td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="text-muted" style="padding: 20px; text-align: center; background: #f8fafc; border-radius: 6px;">
    No portfolio items available.
</div>
@endif
