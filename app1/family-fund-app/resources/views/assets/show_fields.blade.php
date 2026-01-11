<div class="row">
    <div class="col-md-6">
        <!-- Name Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-coins me-1"></i> Name:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $asset->name }}</p>
        </div>

        <!-- Source Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-database me-1"></i> Source:</label>
            <p class="mb-0">{{ $asset->source ?: '-' }}</p>
        </div>

        <!-- Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-tag me-1"></i> Type:</label>
            <p class="mb-0">
                <span class="badge bg-info">{{ $asset->type }}</span>
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Display Group Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-layer-group me-1"></i> Display Group:</label>
            <p class="mb-0">{{ $asset->display_group ?: '-' }}</p>
        </div>

        <!-- Portfolio Assets Count -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-briefcase me-1"></i> In Portfolios:</label>
            <p class="mb-0">
                <span class="badge bg-primary">{{ $asset->portfolioAssets()->where('end_dt', '9999-12-31')->count() }} current</span>
            </p>
        </div>

        <!-- Asset ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Asset ID:</label>
            <p class="mb-0">#{{ $asset->id }}</p>
        </div>
    </div>
</div>
