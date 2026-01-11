<div class="row">
    <div class="col-md-6">
        <!-- Name Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-link me-1"></i> Name:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $matchingRule->name }}</p>
        </div>

        <!-- Dollar Range Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-dollar-sign me-1"></i> Dollar Range:</label>
            <p class="mb-0">
                ${{ number_format($matchingRule->dollar_range_start, 2) }}
                <i class="fa fa-arrow-right mx-2 text-body-secondary"></i>
                ${{ number_format($matchingRule->dollar_range_end, 2) }}
            </p>
        </div>

        <!-- Date Range Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar me-1"></i> Active Period:</label>
            <p class="mb-0">
                {{ $matchingRule->date_start }}
                <i class="fa fa-arrow-right mx-2 text-body-secondary"></i>
                {{ $matchingRule->date_end }}
                @php
                    $now = now()->format('Y-m-d');
                    $isActive = $now >= $matchingRule->date_start && $now <= $matchingRule->date_end;
                @endphp
                @if($isActive)
                    <span class="badge bg-success ms-2">Active</span>
                @else
                    <span class="badge bg-secondary ms-2">Inactive</span>
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Match Percent Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-percent me-1"></i> Match Percent:</label>
            <p class="mb-0">
                <span class="fs-4 fw-bold text-primary">{{ number_format($matchingRule->match_percent * 100, 0) }}%</span>
            </p>
        </div>

        <!-- Account Matching Rules Count -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-users me-1"></i> Linked Accounts:</label>
            <p class="mb-0">
                <span class="badge bg-primary">{{ $matchingRule->accountMatchingRules()->count() }}</span>
            </p>
        </div>

        <!-- Matching Rule ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Matching Rule ID:</label>
            <p class="mb-0">#{{ $matchingRule->id }}</p>
        </div>
    </div>
</div>
