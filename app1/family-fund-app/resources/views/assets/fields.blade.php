<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Name Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="name" class="form-label">
            <i class="fa fa-coins me-1"></i> Name <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" id="name" class="form-control" maxlength="128"
               value="{{ $asset->name ?? old('name') }}" required>
        <small class="text-body-secondary">Full name of the asset (max 128 characters)</small>
    </div>

    <!-- Source Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="source" class="form-label">
            <i class="fa fa-database me-1"></i> Source
        </label>
        <input type="text" name="source" id="source" class="form-control" maxlength="30"
               value="{{ $asset->source ?? old('source') }}">
        <small class="text-body-secondary">Portfolio source (legacy field)</small>
    </div>
</div>

<div class="row">
    <!-- Data Source Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="data_source" class="form-label">
            <i class="fa fa-server me-1"></i> Data Source <span class="text-danger">*</span>
        </label>
        <select name="data_source" id="data_source" class="form-select" required>
            <option value="">Select data source...</option>
            <option value="IB" {{ ($asset->data_source ?? old('data_source')) == 'IB' ? 'selected' : '' }}>IB (Interactive Brokers)</option>
            <option value="MONARCH" {{ ($asset->data_source ?? old('data_source')) == 'MONARCH' ? 'selected' : '' }}>Monarch</option>
            <option value="MANUAL" {{ ($asset->data_source ?? old('data_source')) == 'MANUAL' ? 'selected' : '' }}>Manual</option>
        </select>
        <small class="text-body-secondary">Where price data comes from</small>
    </div>

    <!-- Type Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="type" class="form-label">
            <i class="fa fa-tag me-1"></i> Type <span class="text-danger">*</span>
        </label>
        <select name="type" id="type" class="form-select" required>
            <option value="">Select type...</option>
            <option value="STK" {{ ($asset->type ?? old('type')) == 'STK' ? 'selected' : '' }}>Stock (STK)</option>
            <option value="CRYPTO" {{ ($asset->type ?? old('type')) == 'CRYPTO' ? 'selected' : '' }}>Crypto (CRYPTO)</option>
            <option value="CSH" {{ ($asset->type ?? old('type')) == 'CSH' ? 'selected' : '' }}>Cash (CSH)</option>
            <option value="FUND" {{ ($asset->type ?? old('type')) == 'FUND' ? 'selected' : '' }}>Fund (FUND)</option>
            <option value="BOND" {{ ($asset->type ?? old('type')) == 'BOND' ? 'selected' : '' }}>Bond (BOND)</option>
            <option value="RE" {{ in_array($asset->type ?? old('type'), ['RE', 'real_estate']) ? 'selected' : '' }}>Real Estate (RE)</option>
            <option value="VEHICLE" {{ in_array($asset->type ?? old('type'), ['VEHICLE', 'vehicle']) ? 'selected' : '' }}>Vehicle (VEHICLE)</option>
            <option value="MORTGAGE" {{ in_array($asset->type ?? old('type'), ['MORTGAGE', 'mortgage']) ? 'selected' : '' }}>Mortgage (MORTGAGE)</option>
        </select>
        <small class="text-body-secondary">Asset classification type</small>
    </div>

    <!-- Display Group Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="display_group" class="form-label">
            <i class="fa fa-layer-group me-1"></i> Display Group
        </label>
        @php
            $existingGroups = \App\Models\Asset::select('display_group')
                ->whereNotNull('display_group')
                ->distinct()
                ->orderBy('display_group')
                ->pluck('display_group')
                ->toArray();
        @endphp
        <input type="text" name="display_group" id="display_group" class="form-control" maxlength="50"
               value="{{ $asset->display_group ?? old('display_group') }}" list="display_group_options">
        <datalist id="display_group_options">
            @foreach($existingGroups as $group)
                <option value="{{ $group }}">
            @endforeach
        </datalist>
        <small class="text-body-secondary">
            Grouping for display purposes. Existing groups:
            @foreach($existingGroups as $group)
                <span class="badge badge-secondary me-1" style="background: {{ \App\Support\UIColors::byIndex(crc32($group)) }}; color: white; cursor: pointer;"
                      onclick="document.getElementById('display_group').value = '{{ $group }}'">{{ $group }}</span>
            @endforeach
        </small>
    </div>

    <!-- Linked Asset Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="linked_asset_id" class="form-label">
            <i class="fa fa-link me-1"></i> Linked Asset
        </label>
        @php
            // Get linkable assets (properties for mortgages, etc.)
            $linkableAssets = \App\Models\Asset::whereIn('type', ['RE', 'VEHICLE'])
                ->where('id', '!=', $asset->id ?? 0)
                ->orderBy('name')
                ->get();
        @endphp
        <select name="linked_asset_id" id="linked_asset_id" class="form-select">
            <option value="">No linked asset</option>
            @foreach($linkableAssets as $linkable)
                <option value="{{ $linkable->id }}" {{ ($asset->linked_asset_id ?? old('linked_asset_id')) == $linkable->id ? 'selected' : '' }}>
                    {{ $linkable->name }} ({{ $linkable->type }})
                </option>
            @endforeach
        </select>
        <small class="text-body-secondary">Link this asset to a property or vehicle (for mortgages/loans)</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('assets.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
