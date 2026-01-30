<div class="table-responsive-sm">
    <table class="table table-striped" id="portfolios-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Fund</th>
                <th>Name</th>
                <th>Type</th>
                <th>Category</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($portfolios as $portfolio)
            @php
                $categoryColors = \App\Models\PortfolioExt::CATEGORY_COLORS;
                $typeLabels = \App\Models\PortfolioExt::TYPE_LABELS;
                $categoryLabels = \App\Models\PortfolioExt::CATEGORY_LABELS;
            @endphp
            <tr>
                <td>{{ $portfolio->id }}</td>
                <td>
                    @if($portfolio->fund)
                        <a href="{{ route('funds.show', $portfolio->fund_id) }}">{{ $portfolio->fund->name }}</a>
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('portfolios.show', $portfolio->id) }}">
                        <strong>{{ $portfolio->display_name ?? $portfolio->source }}</strong>
                    </a>
                    @if($portfolio->display_name)
                        <br><code class="small">{{ $portfolio->source }}</code>
                    @endif
                </td>
                <td>
                    @if($portfolio->type)
                        <span class="badge" style="background: {{ $categoryColors[$portfolio->category] ?? '#6b7280' }}; color: white;">
                            {{ $typeLabels[$portfolio->type] ?? ucfirst($portfolio->type) }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if($portfolio->category)
                        {{ $categoryLabels[$portfolio->category] ?? ucfirst($portfolio->category) }}
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
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
