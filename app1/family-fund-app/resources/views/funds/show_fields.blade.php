<div class="row">
    <div class="col-md-6">
        <!-- Name Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-landmark me-1"></i> Name:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $fund->name }}</p>
        </div>

        <!-- Value Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-dollar-sign me-1"></i> Total Value:</label>
            <p class="mb-0">
                <span class="fs-4 fw-bold text-success">${{ number_format($calculated['value'], 2) }}</span>
            </p>
        </div>

        <!-- AsOf Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar me-1"></i> As Of:</label>
            <p class="mb-0 fw-bold">{{ $calculated['as_of'] }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Shares Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-chart-pie me-1"></i> Total Shares:</label>
            <p class="mb-0">
                <span class="fs-4 fw-bold">{{ number_format($calculated['shares'], 4) }}</span>
            </p>
        </div>

        <!-- Unallocated Shares Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-inbox me-1"></i> Unallocated Shares:</label>
            <p class="mb-0">
                <span class="fs-5 {{ $calculated['unallocated_shares'] > 0 ? 'text-warning' : 'text-success' }}">
                    {{ number_format($calculated['unallocated_shares'], 4) }}
                </span>
                @if($calculated['unallocated_shares'] == 0)
                    <span class="badge bg-success ms-2">Fully Allocated</span>
                @endif
            </p>
        </div>

        <!-- Fund ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Fund ID:</label>
            <p class="mb-0">#{{ $fund->id }}</p>
        </div>
    </div>
</div>
