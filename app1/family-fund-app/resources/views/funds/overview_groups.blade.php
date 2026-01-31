{{-- Grouped Portfolio Breakdown - Using Reusable Component --}}
@php
    // Transform overview groups data to match the partial's expected format
    $transformedGroups = [];
    foreach ($api['groups'] as $group) {
        $items = [];
        foreach ($group['portfolios'] as $portfolio) {
            $items[] = [
                'id' => $portfolio['id'],
                'name' => $portfolio['name'],
                'value' => $portfolio['currentValue'],
                'dollarChange' => $portfolio['dollarChange'],
                'percentChange' => $portfolio['percentChange'],
            ];
        }
        $transformedGroups[] = [
            'key' => $group['key'],
            'label' => $group['label'],
            'color' => $group['color'],
            'value' => $group['currentValue'],
            'count' => count($group['portfolios']),
            'dollarChange' => $group['dollarChange'],
            'percentChange' => $group['percentChange'],
            'items' => $items,
        ];
    }
@endphp

@include('partials.group_summary_cards', [
    'groups' => $transformedGroups,
    'sectionId' => 'overview',
    'itemRoute' => 'portfolios.show',
    'showChanges' => true,
    'showNetWorth' => true,
    'liabilityKeys' => ['liability', 'mortgage', 'loan', 'credit_card'],
])
