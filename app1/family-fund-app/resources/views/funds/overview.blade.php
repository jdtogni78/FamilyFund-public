<x-app-layout>

{{-- Define API data first so chart scripts can access it --}}
@push('scripts')
<script type="text/javascript">
var overviewApi = {!! json_encode($api) !!};
var currentPeriod = '{{ $period }}';
var currentGroupBy = '{{ $groupBy }}';
var fundId = {{ $api['id'] }};
</script>
@endpush

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('funds.index') }}">Funds</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('funds.show', $api['id']) }}">{{ $api['name'] }}</a>
        </li>
        <li class="breadcrumb-item active">Overview</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')

            {{-- Overview Header Card --}}
            <div class="row mb-4" id="section-header">
                <div class="col">
                    <div class="card" style="border: 2px solid #0d9488;">
                        {{-- Header --}}
                        <div class="card-header card-header-dark d-flex justify-content-between align-items-center flex-wrap py-3" style="gap: 8px;">
                            <div class="d-flex align-items-center">
                                <h4 class="mb-0" style="font-weight: 700;">{{ $api['name'] }} Overview</h4>
                            </div>
                            <div class="d-flex flex-wrap" style="gap: 4px;">
                                <a href="{{ route('funds.show', $api['id']) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-arrow-left"></i> Back to Fund
                                </a>
                            </div>
                        </div>

                        {{-- Period Controls --}}
                        @include('funds.overview_controls')

                        {{-- Summary Stats --}}
                        @include('funds.overview_summary')
                    </div>
                </div>
            </div>

            {{-- Net Worth Chart --}}
            <div class="row mb-4" id="section-chart">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white;">
                            <strong><i class="fa fa-chart-line mr-2"></i>Net Worth Over Time</strong>
                        </div>
                        <div class="card-body">
                            @include('funds.overview_chart')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Grouped Portfolios --}}
            <div class="row mb-4" id="section-groups">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white;">
                            <strong><i class="fa fa-layer-group mr-2"></i>Portfolio Breakdown</strong>
                        </div>
                        <div class="card-body" id="groups-container">
                            @include('funds.overview_groups')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script type="text/javascript">
// Update overview data via AJAX
function updateOverview(period, groupBy) {
    // Update button states immediately
    $('.period-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
    $('.period-btn[data-period="' + period + '"]').removeClass('btn-outline-primary').addClass('active btn-primary');

    // Update group select
    $('#group-by-select').val(groupBy);

    // Show loading state
    $('#summary-container').addClass('loading');
    $('#groups-container').addClass('loading');

    $.ajax({
        url: '{{ route("api.funds.overview_data", $api["id"]) }}',
        data: {
            period: period,
            group_by: groupBy
        },
        success: function(data) {
            overviewApi = data;
            currentPeriod = period;
            currentGroupBy = groupBy;

            // Update summary values
            updateSummary(data.summary);

            // Update chart
            updateChart(data.chartData);

            // Update groups - reload via partial
            $.ajax({
                url: '{{ route("funds.overview", $api["id"]) }}',
                data: {
                    period: period,
                    group_by: groupBy,
                    _partial: 'groups'
                },
                success: function(html) {
                    // Extract just the groups partial content
                    var $html = $(html);
                    var groupsContent = $html.find('#groups-container').html();
                    if (groupsContent) {
                        $('#groups-container').html(groupsContent);
                    }
                    $('#groups-container').removeClass('loading');
                }
            });

            // Update URL without reload
            var newUrl = window.location.pathname + '?period=' + period + '&group_by=' + groupBy;
            window.history.pushState({period: period, groupBy: groupBy}, '', newUrl);

            $('#summary-container').removeClass('loading');
        },
        error: function() {
            $('#summary-container').removeClass('loading');
            $('#groups-container').removeClass('loading');
            alert('Failed to load overview data');
        }
    });
}

// Update summary card values
function updateSummary(summary) {
    $('#current-value').text(formatCurrency(summary.currentValue));
    $('#dollar-change').text((summary.dollarChange >= 0 ? '+' : '') + formatCurrency(summary.dollarChange));
    $('#percent-change').text((summary.percentChange >= 0 ? '+' : '') + summary.percentChange.toFixed(1) + '%');

    // Update colors based on positive/negative
    var changeColor = summary.dollarChange >= 0 ? '#16a34a' : '#dc2626';
    $('#dollar-change').css('color', changeColor);
    $('#percent-change').css('color', changeColor);
}

// Update chart with new data
function updateChart(chartData) {
    if (typeof overviewChart !== 'undefined') {
        overviewChart.data.labels = createSparseLabels(chartData.labels);
        overviewChart.data.datasets[0].data = chartData.values;
        overviewChart.rawLabels = chartData.labels;
        overviewChart.update();
    }
}

// Period button click handlers
$(document).on('click', '.period-btn', function() {
    var period = $(this).data('period');
    updateOverview(period, currentGroupBy);
});

// Group by select change handler
$(document).on('change', '#group-by-select', function() {
    var groupBy = $(this).val();
    updateOverview(currentPeriod, groupBy);
});

// Handle browser back/forward
window.onpopstate = function(event) {
    if (event.state) {
        updateOverview(event.state.period, event.state.groupBy);
    }
};
</script>

<style>
.loading {
    opacity: 0.5;
    pointer-events: none;
}
.period-btn {
    min-width: 50px;
}
.period-btn.active {
    font-weight: 600;
}
.portfolio-row:hover {
    background-color: rgba(0, 0, 0, 0.02);
}
.group-card {
    transition: box-shadow 0.2s ease;
}
.group-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
@endpush
</x-app-layout>
