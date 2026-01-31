{{-- Period and Group Controls --}}
<div class="card-body py-3" style="background: #f0fdfa; border-bottom: 1px solid #99f6e4;">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="btn-group" role="group" aria-label="Period selector">
                @foreach($api['availablePeriods'] as $p)
                    <button type="button"
                            class="btn btn-sm period-btn {{ $period === $p ? 'btn-primary active' : 'btn-outline-primary' }}"
                            data-period="{{ $p }}">
                        {{ $p }}
                    </button>
                @endforeach
            </div>
            <span class="text-muted small ml-3">
                {{ $api['startDate'] }} to {{ $api['asOf'] }}
            </span>
        </div>
        <div class="col-md-4 text-md-right mt-2 mt-md-0">
            <label class="small text-muted mr-2">Group by:</label>
            <select id="group-by-select" class="form-control form-control-sm d-inline-block" style="width: auto;">
                <option value="category" {{ $groupBy === 'category' ? 'selected' : '' }}>Category</option>
                <option value="type" {{ $groupBy === 'type' ? 'selected' : '' }}>Type</option>
            </select>
        </div>
    </div>
</div>
