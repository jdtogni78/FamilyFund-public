<div class="table-responsive-sm">
    <table class="table table-striped" id="portfolios-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Fund Id</th>
                <th>Fund Name</th>
                <th>Source</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($portfolios as $portfolio)
            <tr>
                <td>{{ $portfolio->id }}</td>
                <td>{{ $portfolio->fund_id }}</td>
                <td>{{ $portfolio->fund()->first()->name }}</td>
                <td>{{ $portfolio->source }}</td>
                <td>@include("portfolios.actions", ["portfolio" => $portfolio])</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#portfolios-table').DataTable();
    });
</script>
