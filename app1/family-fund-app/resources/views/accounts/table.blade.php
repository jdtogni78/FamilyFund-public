<div class="table-responsive-sm">
    <table class="table table-striped" id="accounts-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Code</th>
                <th>Nickname</th>
                <th>Email Cc</th>
                <th>User Id</th>
                <th>User Name</th>
                <th>Fund Id</th>
                <th>Fund Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($accounts as $account)
            <tr>
                <td><a href="{{ route('accounts.show', $account->id) }}" class="text-nowrap"><i class="fa fa-eye fa-fw me-1"></i>{{ $account->id }}</a></td>
                <td>{{ $account->code }}</td>
                <td>{{ $account->nickname }}</td>
                <td>{{ $account->email_cc }}</td>
                <td>{{ $account->user->id ?? '-' }}</td>
                <td>{{ $account->user->name ?? '-' }}</td>
                <td>@if($account->fund)<a href="{{ route('funds.show', $account->fund->id) }}" class="text-nowrap"><i class="fa fa-eye fa-fw me-1"></i>{{ $account->fund->id }}</a>@else - @endif</td>
                <td>@if($account->fund)@include('partials.view_link', ['route' => route('funds.show', $account->fund->id), 'text' => $account->fund->name])@else - @endif</td>
                <td>
                    <form action="{{ route('accounts.destroy', $account->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class='btn-group'>
                            <a href="{{ route('accounts.show', [$account->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                            <a href="{{ route('accounts.edit', [$account->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this account?')"><i class="fa fa-trash"></i></button>
                        </div>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#accounts-table').DataTable();
    });
</script>
@endpush
