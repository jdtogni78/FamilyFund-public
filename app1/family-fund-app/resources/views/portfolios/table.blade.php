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
                $typeColors = \App\Models\PortfolioExt::TYPE_COLORS;
                $typeLabels = \App\Models\PortfolioExt::TYPE_LABELS;
                $categoryColors = \App\Models\PortfolioExt::CATEGORY_COLORS;
                $categoryLabels = \App\Models\PortfolioExt::CATEGORY_LABELS;
            @endphp
            <tr>
                <td>{{ $portfolio->id }}</td>
                <td>
                    @forelse($portfolio->funds as $fund)
                        <a href="{{ route('funds.show', $fund->id) }}" class="badge bg-primary me-1">
                            {{ $fund->name }}
                        </a>
                    @empty
                        <span class="text-muted">-</span>
                    @endforelse
                </td>
                <td>
                    @include('partials.view_link', ['route' => route('portfolios.show', $portfolio->id), 'text' => $portfolio->display_name ?? $portfolio->source, 'class' => 'fw-bold'])
                    @if($portfolio->display_name)
                        <br><code class="small">{{ $portfolio->source }}</code>
                    @endif
                </td>
                <td>
                    @if($portfolio->type)
                        <span class="badge" style="background: {{ $typeColors[$portfolio->type] ?? '#6b7280' }}; color: white;">
                            {{ $typeLabels[$portfolio->type] ?? ucfirst($portfolio->type) }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if($portfolio->category)
                        <span class="badge" style="background: {{ $categoryColors[$portfolio->category] ?? '#6b7280' }}; color: white;">
                            {{ $categoryLabels[$portfolio->category] ?? ucfirst($portfolio->category) }}
                        </span>
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
