<div class="table-responsive-sm">
    <table class="table table-striped" id="tradePortfolios-table">
        <thead>
        <tr>
            <th class="no_mobile">Group</th>
            <th class="no_mobile">Target %</th>
        </tr>
        </thead>
        <tbody>
        @foreach($tradePortfolio->groups as $group => $target)
            <tr>
                <td class="no_mobile">{{ $group }}</td>
                <td class="no_mobile">{{ $target }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
