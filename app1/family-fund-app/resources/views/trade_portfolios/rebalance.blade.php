<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('tradePortfolios.index') }}">Trade Portfolios</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('tradePortfolios.show', $tradePortfolio->id) }}">Portfolio #{{ $tradePortfolio->id }}</a>
        </li>
        <li class="breadcrumb-item active">Rebalance</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header" style="background: #0d9488; color: white;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="fa fa-balance-scale me-2"></i>
                                        Rebalance Portfolio: {{ $tradePortfolio->portfolio->fund->name ?? 'Portfolio #' . $tradePortfolio->id }}
                                    </h5>
                                    <small>Account: {{ $tradePortfolio->account_name }}</small>
                                </div>
                                <div>
                                    <a href="{{ route('tradePortfolios.showRebalance', [
                                        $tradePortfolio->id,
                                        $tradePortfolio->start_dt->toDateString(),
                                        \Carbon\Carbon::today()->toDateString()
                                    ]) }}" class="btn btn-sm btn-outline-light" title="View Analysis">
                                        <i class="fa fa-chart-line me-1"></i> View Analysis
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('tradePortfolios.doRebalance', $tradePortfolio->id) }}" id="rebalance-form">
                                @csrf

                                {{-- Dates Section --}}
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <i class="fa fa-calendar me-2"></i>
                                        <strong>Effective Dates</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-md-6 mb-3">
                                                <label for="start_dt" class="form-label">
                                                    <i class="fa fa-play me-1"></i> Start Date <span class="text-danger">*</span>
                                                </label>
                                                <input type="date" name="start_dt" id="start_dt" class="form-control"
                                                       value="{{ old('start_dt', $start_dt) }}" required>
                                                <small class="text-body-secondary">When this new configuration becomes active</small>
                                            </div>
                                            <div class="form-group col-md-6 mb-3">
                                                <label for="end_dt" class="form-label">
                                                    <i class="fa fa-stop me-1"></i> End Date <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="date" name="end_dt" id="end_dt" class="form-control"
                                                           value="{{ old('end_dt', $end_dt) }}" required>
                                                    <button type="button" class="btn btn-outline-secondary" id="set_never_end" title="Set to never expire">
                                                        <i class="fa fa-infinity"></i> Forever
                                                    </button>
                                                </div>
                                                <small class="text-body-secondary">When this configuration expires</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Portfolio Settings Section --}}
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <i class="fa fa-cog me-2"></i>
                                        <strong>Portfolio Settings</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-md-4 mb-3">
                                                <label for="cash_target" class="form-label">
                                                    <i class="fa fa-dollar-sign me-1"></i> Cash Target <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" name="cash_target" id="cash_target" class="form-control"
                                                           step="0.01" min="0" max="0.99"
                                                           value="{{ old('cash_target', $tradePortfolio->cash_target) }}" required>
                                                    <span class="input-group-text">(decimal)</span>
                                                </div>
                                                <small class="text-body-secondary">e.g., 0.10 = 10%</small>
                                            </div>
                                            <div class="form-group col-md-4 mb-3">
                                                <label for="cash_reserve_target" class="form-label">
                                                    <i class="fa fa-piggy-bank me-1"></i> Cash Reserve
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" name="cash_reserve_target" id="cash_reserve_target" class="form-control"
                                                           step="0.01" min="0" max="0.99"
                                                           value="{{ old('cash_reserve_target', $tradePortfolio->cash_reserve_target) }}">
                                                    <span class="input-group-text">(decimal)</span>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-4 mb-3">
                                                <label for="rebalance_period" class="form-label">
                                                    <i class="fa fa-sync me-1"></i> Rebalance Period <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" name="rebalance_period" id="rebalance_period" class="form-control"
                                                           step="1" min="1"
                                                           value="{{ old('rebalance_period', $tradePortfolio->rebalance_period) }}" required>
                                                    <span class="input-group-text">days</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col-md-4 mb-3">
                                                <label for="minimum_order" class="form-label">
                                                    <i class="fa fa-arrow-down me-1"></i> Minimum Order <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" name="minimum_order" id="minimum_order" class="form-control"
                                                           step="0.01" min="0"
                                                           value="{{ old('minimum_order', $tradePortfolio->minimum_order) }}" required>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-4 mb-3">
                                                <label for="max_single_order" class="form-label">
                                                    <i class="fa fa-arrow-up me-1"></i> Max Single Order <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" name="max_single_order" id="max_single_order" class="form-control"
                                                           step="0.01" min="0" max="1"
                                                           value="{{ old('max_single_order', $tradePortfolio->max_single_order) }}" required>
                                                    <span class="input-group-text">(decimal)</span>
                                                </div>
                                                <small class="text-body-secondary">e.g., 0.05 = 5% of portfolio</small>
                                            </div>
                                            <div class="form-group col-md-4 mb-3">
                                                <label for="mode" class="form-label">
                                                    <i class="fa fa-sliders-h me-1"></i> Mode <span class="text-danger">*</span>
                                                </label>
                                                <select name="mode" id="mode" class="form-control form-select" required>
                                                    <option value="STD" {{ old('mode', $tradePortfolio->mode) == 'STD' ? 'selected' : '' }}>
                                                        STD - Standard
                                                    </option>
                                                    <option value="MAX" {{ old('mode', $tradePortfolio->mode) == 'MAX' ? 'selected' : '' }}>
                                                        MAX - Maximum
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Holdings Section --}}
                                <div class="card mb-4">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fa fa-chart-pie me-2"></i>
                                            <strong>Holdings</strong>
                                        </div>
                                        <div>
                                            <span class="badge" id="total-badge" style="font-size: 1rem;"></span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="items-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 200px;">Symbol</th>
                                                        <th style="width: 120px;">Type</th>
                                                        <th style="width: 150px;">Target %</th>
                                                        <th style="width: 150px;">Deviation</th>
                                                        <th style="width: 80px;" class="text-center">Remove</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="items-tbody">
                                                    @foreach($items as $i => $item)
                                                    <tr data-index="{{ $i }}" class="item-row">
                                                        <td>
                                                            <input type="hidden" name="items[{{ $i }}][symbol]" value="{{ $item->symbol }}">
                                                            <strong>{{ $item->symbol }}</strong>
                                                        </td>
                                                        <td>
                                                            <input type="hidden" name="items[{{ $i }}][type]" value="{{ $item->type }}">
                                                            <span class="badge bg-secondary">{{ $item->type }}</span>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="items[{{ $i }}][target_share]"
                                                                   class="form-control form-control-sm target-share-input pct-input" step="0.1" min="0" max="100"
                                                                   value="{{ $item->target_share * 100 }}" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="items[{{ $i }}][deviation_trigger]"
                                                                   class="form-control form-control-sm pct-input" step="0.1" min="0" max="100"
                                                                   value="{{ $item->deviation_trigger * 100 }}" required>
                                                        </td>
                                                        <td class="text-center">
                                                            <input type="hidden" name="items[{{ $i }}][deleted]" value="0" class="deleted-flag">
                                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove" title="Remove item">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        {{-- Add New Item --}}
                                        <div class="card mt-3" style="background: #f8f9fa;">
                                            <div class="card-body">
                                                <h6 class="card-title"><i class="fa fa-plus me-2"></i>Add New Holding</h6>
                                                <div class="row align-items-end">
                                                    <div class="col-md-3 mb-2">
                                                        <label for="new-symbol" class="form-label small">Symbol</label>
                                                        <select id="new-symbol" class="form-select form-select-sm">
                                                            @foreach($assetMap as $symbol => $name)
                                                                <option value="{{ $symbol }}">{{ $name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 mb-2">
                                                        <label for="new-type" class="form-label small">Type</label>
                                                        <select id="new-type" class="form-select form-select-sm">
                                                            @foreach($typeMap as $type => $label)
                                                                <option value="{{ $type }}">{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 mb-2">
                                                        <label for="new-target" class="form-label small">Target %</label>
                                                        <input type="number" id="new-target" class="form-control form-control-sm"
                                                               step="0.1" min="0" max="100" placeholder="10">
                                                    </div>
                                                    <div class="col-md-2 mb-2">
                                                        <label for="new-deviation" class="form-label small">Deviation %</label>
                                                        <input type="number" id="new-deviation" class="form-control form-control-sm"
                                                               step="0.1" min="0" max="100" placeholder="5">
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <button type="button" id="btn-add" class="btn btn-sm btn-success w-100">
                                                            <i class="fa fa-plus me-1"></i> Add
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Submit Buttons --}}
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('tradePortfolios.show', $tradePortfolio->id) }}" class="btn btn-secondary">
                                        <i class="fa fa-times me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save me-1"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let itemIndex = {{ count($items) }};
            const tbody = document.getElementById('items-tbody');
            const totalBadge = document.getElementById('total-badge');
            const cashTarget = document.getElementById('cash_target');

            // Set end date to "never" (9999-12-31)
            document.getElementById('set_never_end').addEventListener('click', function() {
                document.getElementById('end_dt').value = '9999-12-31';
            });

            // Calculate and update total (values are now in percentage form)
            function updateTotal() {
                let total = 0;
                document.querySelectorAll('.item-row').forEach(row => {
                    const deletedFlag = row.querySelector('.deleted-flag');
                    if (deletedFlag && deletedFlag.value === '0') {
                        const targetInput = row.querySelector('.target-share-input');
                        if (targetInput) {
                            total += parseFloat(targetInput.value) || 0;
                        }
                    }
                });

                // Add cash target (still in decimal form, convert to %)
                const cashVal = (parseFloat(cashTarget.value) || 0) * 100;
                total += cashVal;

                const totalPct = total.toFixed(1);

                if (Math.abs(total - 100) < 0.1) {
                    totalBadge.className = 'badge bg-success';
                    totalBadge.innerHTML = '<i class="fa fa-check-circle me-1"></i> Total: ' + totalPct + '%';
                } else {
                    totalBadge.className = 'badge bg-danger';
                    totalBadge.innerHTML = '<i class="fa fa-exclamation-circle me-1"></i> Total: ' + totalPct + '%';
                }
            }

            // Listen for changes on target inputs and cash target
            document.querySelectorAll('.target-share-input').forEach(input => {
                input.addEventListener('input', updateTotal);
            });
            cashTarget.addEventListener('input', updateTotal);

            // Initial calculation
            updateTotal();

            // Remove button handler
            tbody.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-remove');
                if (btn) {
                    const row = btn.closest('tr');
                    const deletedFlag = row.querySelector('.deleted-flag');
                    if (deletedFlag.value === '0') {
                        deletedFlag.value = '1';
                        row.style.opacity = '0.4';
                        row.style.textDecoration = 'line-through';
                        btn.innerHTML = '<i class="fa fa-undo"></i>';
                        btn.classList.remove('btn-outline-danger');
                        btn.classList.add('btn-outline-secondary');
                        btn.title = 'Restore item';
                    } else {
                        deletedFlag.value = '0';
                        row.style.opacity = '1';
                        row.style.textDecoration = 'none';
                        btn.innerHTML = '<i class="fa fa-times"></i>';
                        btn.classList.remove('btn-outline-secondary');
                        btn.classList.add('btn-outline-danger');
                        btn.title = 'Remove item';
                    }
                    updateTotal();
                }
            });

            // Add button handler
            document.getElementById('btn-add').addEventListener('click', function() {
                const symbol = document.getElementById('new-symbol').value;
                const type = document.getElementById('new-type').value;
                const target = document.getElementById('new-target').value;
                const deviation = document.getElementById('new-deviation').value;

                if (!symbol || symbol === 'none' || !target || !deviation) {
                    alert('Please fill in all fields for the new holding.');
                    return;
                }

                // Check if symbol already exists
                const existingSymbols = Array.from(document.querySelectorAll('.item-row')).map(row => {
                    const input = row.querySelector('input[name$="[symbol]"]');
                    const deletedFlag = row.querySelector('.deleted-flag');
                    return (deletedFlag && deletedFlag.value === '0') ? input?.value : null;
                }).filter(s => s);

                if (existingSymbols.includes(symbol)) {
                    alert('This symbol already exists in the portfolio.');
                    return;
                }

                const typeLabel = document.getElementById('new-type').options[document.getElementById('new-type').selectedIndex].text;

                const row = document.createElement('tr');
                row.className = 'item-row';
                row.dataset.index = itemIndex;
                row.innerHTML = `
                    <td>
                        <input type="hidden" name="items[${itemIndex}][symbol]" value="${symbol}">
                        <strong>${symbol}</strong>
                        <span class="badge bg-info ms-1">NEW</span>
                    </td>
                    <td>
                        <input type="hidden" name="items[${itemIndex}][type]" value="${type}">
                        <span class="badge bg-secondary">${type}</span>
                    </td>
                    <td>
                        <input type="number" name="items[${itemIndex}][target_share]"
                               class="form-control form-control-sm target-share-input pct-input" step="0.1" min="0" max="100"
                               value="${target}" required>
                    </td>
                    <td>
                        <input type="number" name="items[${itemIndex}][deviation_trigger]"
                               class="form-control form-control-sm pct-input" step="0.1" min="0" max="100"
                               value="${deviation}" required>
                    </td>
                    <td class="text-center">
                        <input type="hidden" name="items[${itemIndex}][deleted]" value="0" class="deleted-flag">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove" title="Remove item">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                `;

                tbody.appendChild(row);

                // Add event listener to new target input
                row.querySelector('.target-share-input').addEventListener('input', updateTotal);

                itemIndex++;

                // Clear input fields
                document.getElementById('new-symbol').value = 'none';
                document.getElementById('new-target').value = '';
                document.getElementById('new-deviation').value = '';

                updateTotal();
            });

            // Convert percentage values to decimals before form submission
            document.getElementById('rebalance-form').addEventListener('submit', function(e) {
                document.querySelectorAll('.pct-input').forEach(input => {
                    const pctValue = parseFloat(input.value) || 0;
                    input.value = (pctValue / 100).toFixed(6);
                });
            });
        });
    </script>
</x-app-layout>
