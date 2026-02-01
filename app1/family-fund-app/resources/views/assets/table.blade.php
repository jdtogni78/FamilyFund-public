<div class="table-responsive-sm">
    <table class="table table-striped" id="assets-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Name</th>
                <th>Type</th>
                <th>Data Source</th>
                <th>Display Group</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($assets as $asset)
            <tr>
                <td>{{ $asset->id }}</td>
                <td>@include('partials.view_link', ['route' => route('assets.show', $asset->id), 'text' => $asset->name])</td>
                <td>
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
                </td>
                <td><span class="badge bg-secondary">{{ $asset->data_source }}</span></td>
                <td>
                    @if($asset->display_group)
                        @php
                            $groupColor = \App\Support\UIColors::byIndex(crc32($asset->display_group));
                        @endphp
                        <span class="badge" style="background: {{ $groupColor }}; color: white;">{{ $asset->display_group }}</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    <form action="{{ route('assets.destroy', $asset->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class='btn-group'>
                            <a href="{{ route('assets.show', [$asset->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                            <a href="{{ route('assets.edit', [$asset->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this asset?')"><i class="fa fa-trash"></i></button>
                        </div>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#assets-table').DataTable();
    });
</script>
@endpush