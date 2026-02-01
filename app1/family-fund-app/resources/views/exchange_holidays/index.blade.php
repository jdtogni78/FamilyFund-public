<x-app-layout>
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{!! route('exchange-holidays.index') !!}">Exchange Holidays</a>
        </li>
        <li class="breadcrumb-item active">{{ $exchange }} {{ $year }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('layouts.flash-messages')

            {{-- Stats Cards --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="fa fa-calendar-alt me-2"></i>Total Holidays</h5>
                            <h2 class="mb-0">{{ $totalHolidays }}</h2>
                            <p class="text-muted small mb-0">All exchanges, all years</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="fa fa-calendar-check me-2"></i>Current Year</h5>
                            <h2 class="mb-0">{{ $currentYearCount }}</h2>
                            <p class="text-muted small mb-0">{{ now()->year }} holidays</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="fa fa-chart-line me-2"></i>Viewing</h5>
                            <h2 class="mb-0">{{ $holidays->count() }}</h2>
                            <p class="text-muted small mb-0">{{ $exchange }} {{ $year }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Form --}}
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('exchange-holidays.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="exchange" class="form-label">Exchange</label>
                            <select name="exchange" id="exchange" class="form-select">
                                @if($exchanges->isEmpty())
                                    <option value="NYSE" selected>NYSE (no data yet)</option>
                                @else
                                    @foreach($exchanges as $ex)
                                        <option value="{{ $ex }}" {{ $ex == $exchange ? 'selected' : '' }}>
                                            {{ $ex }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="year" class="form-label">Year</label>
                            <select name="year" id="year" class="form-select">
                                @if($years->isEmpty())
                                    @for($y = 2025; $y <= 2026; $y++)
                                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                @else
                                    @foreach($years as $y)
                                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Holidays Table --}}
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-calendar-alt me-2"></i>
                    <strong>{{ $exchange }} Holidays - {{ $year }}</strong>
                    <span class="badge bg-primary ms-2">{{ $holidays->count() }} holidays</span>
                </div>
                <div class="card-body">
                    @if($holidays->isEmpty())
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            <strong>No holidays found for {{ $exchange }} {{ $year }}</strong>
                            <p class="mb-0 mt-2">
                                Holiday data is automatically synced via scheduled jobs.
                            </p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Day of Week</th>
                                        <th>Holiday Name</th>
                                        <th>Early Close</th>
                                        <th>Source</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($holidays as $holiday)
                                        <tr class="{{ $holiday->holiday_date->isPast() ? 'text-muted' : '' }}">
                                            <td>
                                                <strong>{{ $holiday->holiday_date->format('Y-m-d') }}</strong>
                                            </td>
                                            <td>
                                                {{ $holiday->holiday_date->format('l') }}
                                            </td>
                                            <td>
                                                <i class="fa fa-calendar-times me-1"></i>
                                                {{ $holiday->holiday_name }}
                                            </td>
                                            <td>
                                                @if($holiday->early_close_time)
                                                    <span class="badge bg-warning">
                                                        <i class="fa fa-clock me-1"></i>
                                                        {{ $holiday->early_close_time->format('H:i') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $holiday->source }}</span>
                                            </td>
                                            <td>
                                                @if($holiday->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Help Text --}}
            <div class="card mt-3 border-info">
                <div class="card-body">
                    <h5 class="card-title"><i class="fa fa-info-circle me-2"></i>About Exchange Holidays</h5>
                    <p class="mb-2">
                        Exchange holidays are used by the gap detection system to identify non-trading days and exclude them from missing price data queries.
                    </p>
                    <ul class="mb-0">
                        <li><strong>Automatic Sync:</strong> Holidays are synchronized quarterly via scheduled jobs</li>
                        <li><strong>Gap Detection:</strong> Automatically uses these holidays to filter weekends and holidays from missing dates</li>
                        <li><strong>Data Sources:</strong> Synced from external sources (NYSE.com, TradingHours.com API)</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
