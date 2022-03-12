<div class="table-responsive-sm">
    <table class="table table-striped" id="balances-table">
        <thead>
            <tr>
                <th scope="col">Account</th>
                <th scope="col">User</th>
                <th scope="col">Shares</th>
                <th scope="col">Value</th>
                <th scope="col">Balance Type</th>
            </tr>
        </thead>
        <tbody>
        @foreach($api['balances'] as $bals)
            <tr>
                <th scope="row">
                    <a href="{{ route('accounts.show', [$bals['account_id']]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i>
                    {{ $bals['nickname'] }}</a>
                </td>
                <td>{{ $bals['user']['name'] }}</td>
                <td>{{ $bals['shares'] }}</td>
                <td>{{ $bals['value'] }}</td>
                <td>{{ $bals['type'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>