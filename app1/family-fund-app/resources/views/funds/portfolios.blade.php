<x-app-layout>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('funds.index') }}">Funds</a></li>
        <li class="breadcrumb-item"><a href="{{ route('funds.show', $fund->id) }}">{{ $fund->name }}</a></li>
        <li class="breadcrumb-item active">Portfolios</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('layouts.flash-messages')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-folder-open me-2"></i>
                                <strong>{{ $fund->name }} - Portfolios</strong>
                                <span class="badge bg-primary ms-2">{{ $portfolios->count() }}</span>
                            </div>
                            <a class="btn btn-sm btn-primary" href="{{ route('portfolios.create') }}">
                                <i class="fa fa-plus me-1"></i> Add Portfolio
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive-sm">
                                <table class="table table-striped" id="portfolios-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Source</th>
                                            <th>Assets</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($portfolios as $portfolio)
                                        @php
                                            $assetCount = $portfolio->portfolioAssets()
                                                ->where('end_dt', '>=', now()->format('Y-m-d'))
                                                ->count();
                                        @endphp
                                        <tr>
                                            <td>{{ $portfolio->id }}</td>
                                            <td>
                                                @if($portfolio->display_name)
                                                    <strong>{{ $portfolio->display_name }}</strong>
                                                    <br><code class="small">{{ $portfolio->source }}</code>
                                                @else
                                                    <code>{{ $portfolio->source }}</code>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $assetCount }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('portfolios.show', $portfolio->id) }}"
                                                       class="btn btn-sm btn-ghost-success" title="View Portfolio">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('portfolios.showRebalance', [$portfolio->id, now()->subMonths(3)->format('Y-m-d'), now()->format('Y-m-d')]) }}"
                                                       class="btn btn-sm btn-ghost-primary" title="Rebalance Analysis">
                                                        <i class="fa fa-chart-line"></i>
                                                    </a>
                                                    <a href="{{ route('portfolios.edit', $portfolio->id) }}"
                                                       class="btn btn-sm btn-ghost-info" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#portfolios-table').DataTable({
                paging: false,
                searching: false,
                info: false
            });
        });
    </script>
</x-app-layout>
