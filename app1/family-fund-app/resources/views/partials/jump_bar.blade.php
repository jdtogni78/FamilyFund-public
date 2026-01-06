{{--
    Reusable sticky jump bar navigation

    Usage:
    @include('partials.jump_bar', ['sections' => [
        ['id' => 'section-details', 'icon' => 'fa-user', 'label' => 'Details'],
        ['id' => 'section-charts', 'icon' => 'fa-chart-line', 'label' => 'Charts', 'condition' => true],
    ]])
--}}

{{-- Jump Bar Anchor --}}
<div id="jumpNavAnchor"></div>

{{-- Sticky Jump Bar --}}
<div id="jumpNav" class="card shadow-sm mb-4" style="position: sticky; top: 56px; z-index: 1020; display: none;">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center">
            <span class="me-3 text-muted small">Jump to:</span>
            @foreach($sections as $section)
                @if(!isset($section['condition']) || $section['condition'])
                    <a href="#{{ $section['id'] }}"
                       class="btn btn-sm btn-outline-primary me-2 mb-1 section-nav-btn"
                       data-section="{{ $section['id'] }}">
                        @if(isset($section['icon']))
                            <i class="fa {{ $section['icon'] }} me-1"></i>
                        @endif
                        {{ $section['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    const $jumpNav = $('#jumpNav');
    const $jumpNavAnchor = $('#jumpNavAnchor');

    if ($jumpNavAnchor.length === 0) return;

    // Show/hide nav based on scroll position
    function updateNavVisibility() {
        const anchorTop = $jumpNavAnchor.offset().top;
        const scrollTop = $(window).scrollTop();

        if (scrollTop > anchorTop - 100) {
            $jumpNav.slideDown(200);
        } else {
            $jumpNav.slideUp(200);
        }
    }

    // Initial check
    updateNavVisibility();

    // Smooth scroll for navigation
    $('.section-nav-btn').click(function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        const $target = $(target);

        if ($target.length === 0) return;

        // Scroll to section with offset for sticky nav
        const offset = 120;
        $('html, body').animate({
            scrollTop: $target.offset().top - offset
        }, 300);

        // Highlight active button
        $('.section-nav-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
    });

    // Update nav visibility and active button on scroll
    $(window).scroll(function() {
        updateNavVisibility();

        // Update active nav button based on scroll position
        const offset = 150;
        let currentSection = null;

        $('.section-nav-btn').each(function() {
            const sectionId = $(this).attr('href');
            const $section = $(sectionId);
            if ($section.length && $(window).scrollTop() >= $section.offset().top - offset) {
                currentSection = sectionId;
            }
        });

        if (currentSection) {
            $('.section-nav-btn').removeClass('btn-primary').addClass('btn-outline-primary');
            $('.section-nav-btn[href="' + currentSection + '"]')
                .removeClass('btn-outline-primary').addClass('btn-primary');
        }
    });
});
</script>
@endpush
