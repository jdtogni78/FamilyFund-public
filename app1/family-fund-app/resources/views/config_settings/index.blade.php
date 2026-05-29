<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('operations.index') }}">Operations</a></li>
        <li class="breadcrumb-item active">Config Settings</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('layouts.flash-messages')

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa fa-cog me-2"></i>
                        <strong>Configuration Settings</strong>
                        <span class="badge bg-secondary ms-2">{{ $settings->count() }} settings</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSettingModal">
                        <i class="fa fa-plus me-1"></i> Add Setting
                    </button>
                </div>
                <div class="card-body">
                    {{-- Filters --}}
                    <form method="GET" action="{{ route('configSettings.index') }}" class="row g-3 mb-3">
                        <div class="col-auto">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Search key, description..." value="{{ request('search') }}" style="width: 250px;">
                        </div>
                        <div class="col-auto">
                            <select name="category" class="form-control form-control-sm">
                                <option value="">All Categories</option>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa fa-search me-1"></i> Filter
                            </button>
                        </div>
                        @if(request('search') || request('category'))
                        <div class="col-auto">
                            <a href="{{ route('configSettings.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fa fa-times me-1"></i> Clear
                            </a>
                        </div>
                        @endif
                    </form>

                    {{-- Settings Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="configSettings-table">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Category</th>
                                    <th style="width: 200px;">Key</th>
                                    <th>Value</th>
                                    <th style="width: 80px;">Type</th>
                                    <th>Description</th>
                                    <th style="width: 150px;">Updated</th>
                                    <th style="width: 80px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($settings as $setting)
                                <tr data-id="{{ $setting->id }}">
                                    <td>
                                        <span class="badge bg-info">{{ $categories[$setting->category] ?? $setting->category }}</span>
                                    </td>
                                    <td>
                                        <code>{{ $setting->key }}</code>
                                        @if($setting->is_sensitive)
                                            <i class="fa fa-lock text-warning ms-1" title="Sensitive"></i>
                                        @endif
                                    </td>
                                    <td class="value-cell">
                                        <div class="value-display" style="cursor: pointer;" title="Click to edit">
                                            @if($setting->type === 'boolean')
                                                <span class="badge {{ filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No' }}
                                                </span>
                                            @else
                                                <span class="value-text">{{ $setting->display_value ?: '(empty)' }}</span>
                                            @endif
                                            <i class="fa fa-pencil text-muted ms-2 edit-icon" style="opacity: 0.5;"></i>
                                        </div>
                                        <div class="value-edit" style="display: none;">
                                            @if($setting->type === 'boolean')
                                                <select class="form-control form-control-sm edit-input" data-type="boolean">
                                                    <option value="true" {{ filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'selected' : '' }}>Yes</option>
                                                    <option value="false" {{ !filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) ? 'selected' : '' }}>No</option>
                                                </select>
                                            @elseif($setting->type === 'json')
                                                <textarea class="form-control form-control-sm edit-input" rows="3" data-type="json">{{ $setting->is_sensitive ? '' : $setting->value }}</textarea>
                                            @else
                                                <input type="{{ $setting->type === 'integer' ? 'number' : 'text' }}"
                                                       class="form-control form-control-sm edit-input"
                                                       value="{{ $setting->is_sensitive ? '' : $setting->value }}"
                                                       data-type="{{ $setting->type }}"
                                                       placeholder="{{ $setting->is_sensitive ? 'Enter new value...' : '' }}">
                                            @endif
                                            <div class="mt-1">
                                                <button type="button" class="btn btn-sm btn-success save-btn"><i class="fa fa-check"></i></button>
                                                <button type="button" class="btn btn-sm btn-secondary cancel-btn"><i class="fa fa-times"></i></button>
                                            </div>
                                        </div>
                                    </td>
                                    <td><small class="text-muted">{{ $types[$setting->type] ?? $setting->type }}</small></td>
                                    <td><small>{{ $setting->description }}</small></td>
                                    <td>
                                        <small class="text-muted">
                                            @if($setting->updated_at)
                                                {{ $setting->updated_at->format('M j, Y H:i') }}<br>
                                                {{ $setting->updated_by }}
                                            @else
                                                -
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-ghost-danger btn-sm delete-btn" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Setting Modal --}}
    <div class="modal fade" id="addSettingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addSettingForm">
                        <div class="mb-3">
                            <label class="form-label">Key <span class="text-danger">*</span></label>
                            <input type="text" name="key" class="form-control" required placeholder="category.setting_name">
                            <small class="text-muted">Use dot notation, e.g., mail.admin_email</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-control">
                                    @foreach($types as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control">
                                    @foreach($categories as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Value</label>
                            <textarea name="value" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="What this setting controls...">
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_sensitive" class="form-check-input" id="isSensitive">
                            <label class="form-check-label" for="isSensitive">Sensitive (mask value in display)</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveNewSetting">Save Setting</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Initialize DataTable (sorting only, no pagination for config)
        $('#configSettings-table').DataTable({
            paging: false,
            searching: false, // We have custom search
            info: false,
            order: [[0, 'asc'], [1, 'asc']]
        });

        // Click to edit
        $('.value-display').on('click', function() {
            var cell = $(this).closest('.value-cell');
            cell.find('.value-display').hide();
            cell.find('.value-edit').show();
            cell.find('.edit-input').focus();
        });

        // Cancel edit
        $('.cancel-btn').on('click', function() {
            var cell = $(this).closest('.value-cell');
            cell.find('.value-edit').hide();
            cell.find('.value-display').show();
        });

        // Save edit
        $('.save-btn').on('click', function() {
            var btn = $(this);
            var row = btn.closest('tr');
            var cell = btn.closest('.value-cell');
            var id = row.data('id');
            var input = cell.find('.edit-input');
            var value = input.val();

            btn.prop('disabled', true);

            $.ajax({
                url: '/configSettings/' + id,
                method: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}',
                    value: value
                },
                success: function(response) {
                    // Update display
                    if (input.data('type') === 'boolean') {
                        var isTrue = value === 'true';
                        cell.find('.value-display .badge')
                            .removeClass('bg-success bg-secondary')
                            .addClass(isTrue ? 'bg-success' : 'bg-secondary')
                            .text(isTrue ? 'Yes' : 'No');
                    } else {
                        cell.find('.value-text').text(response.display_value || '(empty)');
                    }
                    cell.find('.value-edit').hide();
                    cell.find('.value-display').show();

                    // Flash success
                    row.addClass('table-success');
                    setTimeout(function() { row.removeClass('table-success'); }, 1000);
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.error || 'Failed to save');
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });

        // Delete setting
        $('.delete-btn').on('click', function() {
            if (!confirm('Delete this setting?')) return;

            var row = $(this).closest('tr');
            var id = row.data('id');

            $.ajax({
                url: '/configSettings/' + id,
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function() {
                    row.fadeOut(function() { $(this).remove(); });
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.error || 'Failed to delete');
                }
            });
        });

        // Add new setting
        $('#saveNewSetting').on('click', function() {
            var btn = $(this);
            var form = $('#addSettingForm');

            btn.prop('disabled', true);

            $.ajax({
                url: '{{ route("configSettings.store") }}',
                method: 'POST',
                data: form.serialize() + '&_token={{ csrf_token() }}&is_sensitive=' + ($('#isSensitive').is(':checked') ? '1' : '0'),
                success: function() {
                    location.reload();
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || xhr.responseJSON?.error || 'Failed to create');
                    btn.prop('disabled', false);
                }
            });
        });

        // Keyboard shortcuts
        $(document).on('keydown', '.edit-input', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                $(this).closest('.value-edit').find('.save-btn').click();
            }
            if (e.key === 'Escape') {
                $(this).closest('.value-edit').find('.cancel-btn').click();
            }
        });
    });
    </script>
</x-app-layout>
