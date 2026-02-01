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

        <!-- Data Source Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-server me-1"></i> Data Source:</label>
            <p class="mb-0">
                <span class="badge bg-secondary">{{ $asset->data_source }}</span>
            </p>
        </div>

        <!-- Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-tag me-1"></i> Type:</label>
            <p class="mb-0">
                @php
                    $typeColors = [
                        'CSH' => ['bg' => '#dbeafe', 'border' => '#2563eb', 'text' => '#1d4ed8', 'label' => 'Cash'],
                        'STK' => ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#15803d', 'label' => 'Stock'],
                        'CRYPTO' => ['bg' => '#fef3c7', 'border' => '#d97706', 'text' => '#b45309', 'label' => 'Crypto'],
                        'FUND' => ['bg' => '#e0e7ff', 'border' => '#4f46e5', 'text' => '#4338ca', 'label' => 'Fund'],
                        'RE' => ['bg' => '#ccfbf1', 'border' => '#0d9488', 'text' => '#0f766e', 'label' => 'Real Estate'],
                        'VEHICLE' => ['bg' => '#e0f2fe', 'border' => '#0284c7', 'text' => '#0369a1', 'label' => 'Vehicle'],
                        'MORTGAGE' => ['bg' => '#fee2e2', 'border' => '#dc2626', 'text' => '#b91c1c', 'label' => 'Mortgage'],
                        'BOND' => ['bg' => '#fae8ff', 'border' => '#c026d3', 'text' => '#a21caf', 'label' => 'Bond'],
                    ];
                    $colors = $typeColors[$asset->type] ?? ['bg' => '#f3e8ff', 'border' => '#9333ea', 'text' => '#7e22ce', 'label' => $asset->type];
                @endphp
                <span class="badge" style="background: {{ $colors['bg'] }}; color: {{ $colors['text'] }}; border: 1px solid {{ $colors['border'] }};">
                    {{ $colors['label'] }}
                </span>
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Display Group Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-layer-group me-1"></i> Display Group:</label>
            <p class="mb-0">
                @if($asset->display_group)
                    @php
                        $groupColor = \App\Support\UIColors::byIndex(crc32($asset->display_group));
                    @endphp
                    <span class="badge" style="background: {{ $groupColor }}; color: white;">{{ $asset->display_group }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </p>
        </div>

        <!-- Linked Asset Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-link me-1"></i> Linked To:</label>
            <p class="mb-0">
                @if($asset->linkedAsset)
                    <a href="{{ route('assets.show', $asset->linkedAsset->id) }}" class="badge bg-success text-decoration-none">
                        <i class="fa fa-home me-1"></i>{{ $asset->linkedAsset->name }}
                    </a>
                @else
                    <span class="text-muted">-</span>
                @endif
            </p>
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
