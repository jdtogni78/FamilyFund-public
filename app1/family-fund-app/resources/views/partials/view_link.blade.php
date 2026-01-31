{{-- View link with eye icon
     Usage: @include('partials.view_link', ['route' => route('entity.show', $id), 'text' => $name])
     Optional: 'class' => 'fw-bold text-success', 'title' => 'View details'
--}}
<a href="{{ $route }}" class="text-nowrap {{ $class ?? '' }}" @isset($title) title="{{ $title }}" @endisset>
    <i class="fa fa-eye fa-fw me-1"></i>{{ $text }}
</a>
