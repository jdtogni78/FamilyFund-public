<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('tradePortfolios.index') }}">Trade Portfolio</a>
        </li>
        <li class="breadcrumb-item active">Show Diff</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Trade Portfolio Diff</strong>
                            @if(!isset($is_announce))
                                <form action="{{ route('tradePortfolios.announce', [$api['new']->id]) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost-primary" title="Announce" onclick="return confirm('Send the announcement email for this trade portfolio?')"><i class="fa fa-envelope"></i></button>
                                </form>
                            @endif
                        </div>
                        <div class="card-body">
                            @include('trade_portfolios.show_diff_fields')
                        </div>
                    </div>
                </div>
            </div>
            @include("trade_portfolios.show_diff_inner")
        </div>
    </div>
</x-app-layout>
