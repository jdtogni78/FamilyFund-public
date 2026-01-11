<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Portfolio Assets</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')

             <!-- Filter Bar -->
             <div class="card mb-3">
                 <div class="card-body py-2">
                     <form method="GET" action="{{ route('portfolioAssets.index') }}" id="filterForm" class="row g-2 align-items-end">
                         <div class="col-md-2">
                             <label for="fund_id" class="form-label small mb-1">
                                 <i class="fa fa-landmark me-1"></i> Fund
                             </label>
                             <select name="fund_id" id="fund_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                 @foreach($api['fundMap'] ?? [] as $id => $name)
                                     <option value="{{ $id }}" {{ ($api['filters']['fund_id'] ?? '') == $id ? 'selected' : '' }}>
                                         {{ $name }}
                                     </option>
                                 @endforeach
                             </select>
                         </div>
                         <div class="col-md-3">
                             <label for="asset_id" class="form-label small mb-1">
                                 <i class="fa fa-coins me-1"></i> Assets <small class="text-muted">(Ctrl+click for multi)</small>
                             </label>
                             @php
                                 $selectedAssets = $api['filters']['asset_id'] ?? [];
                                 if (!is_array($selectedAssets)) {
                                     $selectedAssets = $selectedAssets ? [$selectedAssets] : [];
                                 }
                                 $selectedAssets = array_filter($selectedAssets, fn($id) => $id !== 'none');
                             @endphp
                             <select name="asset_id[]" id="asset_id" class="form-select form-select-sm" multiple size="3">
                                 @foreach($api['assetMap'] ?? [] as $id => $name)
                                     @if($id !== 'none')
                                     <option value="{{ $id }}" {{ in_array($id, $selectedAssets) ? 'selected' : '' }}>
                                         {{ $name }}
                                     </option>
                                     @endif
                                 @endforeach
                             </select>
                         </div>
                         <div class="col-md-2">
                             <label for="start_dt" class="form-label small mb-1">
                                 <i class="fa fa-calendar me-1"></i> From
                             </label>
                             <input type="date" name="start_dt" id="start_dt" class="form-control form-control-sm"
                                    value="{{ $api['filters']['start_dt'] ?? '' }}">
                         </div>
                         <div class="col-md-2">
                             <label for="end_dt" class="form-label small mb-1">
                                 <i class="fa fa-calendar me-1"></i> To
                             </label>
                             <input type="date" name="end_dt" id="end_dt" class="form-control form-control-sm"
                                    value="{{ $api['filters']['end_dt'] ?? '' }}">
                         </div>
                         <div class="col-md-3">
                             <button type="submit" class="btn btn-sm btn-primary">
                                 <i class="fa fa-filter me-1"></i> Filter
                             </button>
                             <a href="{{ route('portfolioAssets.index') }}" class="btn btn-sm btn-outline-secondary">
                                 <i class="fa fa-times me-1"></i> Clear
                             </a>
                         </div>
                     </form>
                 </div>
             </div>

             <!-- Chart (shown when asset or fund with <=8 assets selected) -->
             @if(isset($chartData) && $chartData)
             <div class="card mb-3">
                 <div class="card-header">
                     <i class="fa fa-chart-area me-2"></i>
                     @if(isset($chartData['multiAsset']) && $chartData['multiAsset'])
                         <strong>Fund Position History</strong>
                         <span class="badge bg-info ms-2">{{ count($chartData['datasets']) }} assets</span>
                     @else
                         <strong>{{ $chartData['assetName'] }}</strong> Position History
                     @endif
                 </div>
                 <div class="card-body">
                     <div style="height: 300px;">
                         <canvas id="positionChart"></canvas>
                     </div>
                 </div>
             </div>
             @endif

             <!-- Data Table -->
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                             <div>
                                 <i class="fa fa-coins me-2"></i>
                                 <strong>Portfolio Assets</strong>
                                 @if($portfolioAssets instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                     <span class="badge bg-primary ms-2">{{ $portfolioAssets->total() }}</span>
                                 @else
                                     <span class="badge bg-primary ms-2">{{ $portfolioAssets->count() }}</span>
                                 @endif
                             </div>
                             <a class="btn btn-sm btn-primary" href="{{ route('portfolioAssets.create') }}">
                                 <i class="fa fa-plus me-1"></i> New Asset
                             </a>
                         </div>
                         <div class="card-body">
                             @include('portfolio_assets.table')
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>

@if(isset($chartData) && $chartData)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('positionChart').getContext('2d');
        const isMultiAsset = @json($chartData['multiAsset'] ?? false);

        let chartConfig;
        if (isMultiAsset) {
            // Multi-asset chart with multiple lines (following funds page pattern)
            const rawDatasets = @json($chartData['datasets'] ?? []);
            const labels = @json($chartData['labels'] ?? []);

            // Transform datasets to use simple data arrays aligned to labels
            const datasets = rawDatasets.map(function(ds, index) {
                // Convert {x, y} format to simple array aligned to labels
                const dataMap = {};
                ds.data.forEach(function(pt) {
                    dataMap[pt.x] = pt.y;
                });
                const alignedData = labels.map(function(label) {
                    return dataMap[label] !== undefined ? dataMap[label] : null;
                });

                return {
                    label: ds.label,
                    data: alignedData,
                    backgroundColor: graphColors[index % graphColors.length],
                    borderColor: graphColors[index % graphColors.length],
                    borderWidth: 2,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    stepped: 'after',
                    fill: false,
                    spanGaps: true
                };
            });

            // Create sparse labels for cleaner x-axis
            const sparseLabels = typeof createSparseLabels === 'function'
                ? createSparseLabels(labels)
                : labels;

            chartConfig = {
                type: 'line',
                data: {
                    labels: sparseLabels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: chartTheme.fontColor,
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    return labels[context[0].dataIndex];
                                },
                                label: function(context) {
                                    if (context.raw === null) return null;
                                    return context.dataset.label + ': ' + context.raw.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4});
                                }
                            }
                        },
                        datalabels: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: chartTheme.fontColor,
                                maxRotation: 45,
                                minRotation: 0
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                },
                                color: chartTheme.fontColor
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        }
                    }
                }
            };
        } else {
            // Single asset chart - step graph for position data
            chartConfig = {
                type: 'line',
                data: {
                    labels: @json($chartData['labels'] ?? []),
                    datasets: [{
                        label: '{{ $chartData['assetName'] ?? 'Position' }}',
                        data: @json($chartData['data'] ?? []),
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.1)',
                        fill: true,
                        stepped: 'after',
                        pointRadius: 3,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y.toFixed(4) + ' units';
                                }
                            }
                        },
                        datalabels: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                color: chartTheme.fontColor
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(2);
                                },
                                color: chartTheme.fontColor
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        }
                    }
                }
            };
        }

        new Chart(ctx, chartConfig);
    });
</script>
@endpush
@endif

</x-app-layout>
