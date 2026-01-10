{{--
    Reusable sticky jump bar navigation with collapse toggle

    Usage:
    @include('partials.jump_bar', ['sections' => [
        ['id' => 'section-details', 'icon' => 'fa-user', 'label' => 'Details'],
        ['id' => 'section-charts', 'icon' => 'fa-chart-line', 'label' => 'Charts', 'condition' => true],
    ]])
--}}

{{-- Sticky Jump Bar - Always Visible --}}
<div id="jumpNav" class="card shadow-sm mb-4" style="position: sticky; top: 56px; z-index: 1020;">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center">
            <span class="me-3 text-muted small" title="Click to scroll, double-click to collapse"><i class="fa fa-compass me-1"></i>Jump to: <span style="font-size: 0.7rem; opacity: 0.7;">(2× click to collapse)</span></span>
            @foreach($sections as $section)
                @if(!isset($section['condition']) || $section['condition'])
                    <a href="#{{ $section['id'] }}"
                       class="btn btn-sm btn-outline-primary me-2 mb-1 section-nav-btn"
                       data-section="{{ $section['id'] }}"
                       title="Click to scroll, double-click to toggle">
                        @if(isset($section['icon']))
                            <i class="fa {{ $section['icon'] }} me-1"></i>
                        @endif
                        {{ $section['label'] }}
                    </a>
                @endif
            @endforeach
            <button class="btn btn-sm btn-outline-secondary ms-auto mb-1" id="toggleAllSections" title="Expand/Collapse All">
                <i class="fa fa-compress-arrows-alt"></i>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    // Track click timing for single vs double click differentiation
    let clickTimer = null;
    let lastClickedTarget = null;

    $('.section-nav-btn').click(function(e) {
        e.preventDefault();
        const $btn = $(this);
        const target = $btn.attr('href');
        const $target = $(target);

        if ($target.length === 0) return;

        // If same button clicked twice quickly, treat as toggle
        if (lastClickedTarget === target && clickTimer) {
            clearTimeout(clickTimer);
            clickTimer = null;
            lastClickedTarget = null;

            // Toggle collapse
            const $collapse = $target.find('.collapse');
            if ($collapse.length > 0) {
                $collapse.collapse('toggle');
            }
            return;
        }

        lastClickedTarget = target;

        // Wait briefly to see if it's a double-click
        clickTimer = setTimeout(function() {
            clickTimer = null;

            // Single click: scroll to section and expand if collapsed
            const $collapse = $target.find('.collapse');
            if ($collapse.length > 0 && !$collapse.hasClass('show')) {
                $collapse.collapse('show');
            }

            // Scroll to section with offset for sticky nav
            const offset = 120;
            $('html, body').animate({
                scrollTop: $target.offset().top - offset
            }, 300);

            // Highlight active button
            $('.section-nav-btn').removeClass('btn-primary').addClass('btn-outline-primary');
            $btn.removeClass('btn-outline-primary').addClass('btn-primary');
        }, 200);
    });

    // Toggle all sections
    let allExpanded = true;
    $('#toggleAllSections').click(function() {
        if (allExpanded) {
            $('.collapse.show').collapse('hide');
            $(this).find('i').removeClass('fa-compress-arrows-alt').addClass('fa-expand-arrows-alt');
        } else {
            $('.collapse:not(.show)').collapse('show');
            $(this).find('i').removeClass('fa-expand-arrows-alt').addClass('fa-compress-arrows-alt');
        }
        allExpanded = !allExpanded;
    });

    // Update active nav button based on scroll position
    $(window).scroll(function() {
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
