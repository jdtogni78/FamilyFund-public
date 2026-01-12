<div class="table-responsive-sm">
    <table class="table table-striped" id="funds-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Name</th>
                <th class="no_mobile">Goal</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($funds as $fund)
            <tr>
                <td>{{ $fund->id }}</td>
                <td>{{ $fund->name }}</td>
                <td class="no_mobile">{{ $fund->goal }}</td>
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
