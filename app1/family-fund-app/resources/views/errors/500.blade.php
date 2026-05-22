@include('errors.layout', [
    'code'    => 500,
    'title'   => __('Server error'),
    'message' => __('Something went wrong on our side. The team has been notified.'),
])
