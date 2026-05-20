@include('errors.layout', [
    'code'    => 404,
    'title'   => __('Page not found'),
    'message' => __('The page you requested doesn\'t exist or has moved.'),
])
