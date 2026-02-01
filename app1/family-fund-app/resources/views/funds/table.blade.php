<div class="table-responsive-sm">
    <table class="table table-striped" id="funds-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Name</th>
                <th class="no_mobile">Goal</th>
                <th>Portfolios</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($funds as $fund)
            @php $portfolioCount = $fund->portfolios()->count(); @endphp
            <tr>
                <td>{{ $fund->id }}</td>
                <td>{{ $fund->name }}</td>
                <td class="no_mobile">{{ $fund->goal }}</td>
                <td>
                    @if($portfolioCount > 0)
                        <a href="{{ route('funds.portfolios', $fund->id) }}" class="badge bg-info text-decoration-none">
                            {{ $portfolioCount }} <i class="fa fa-folder-open ms-1"></i>
                        </a>
                    @else
                        <span class="badge bg-secondary">0</span>
                    @endif
                </td>
                <td>@include("funds.actions", ["fund" => $fund])</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#funds-table').DataTable();
    });
</script>
