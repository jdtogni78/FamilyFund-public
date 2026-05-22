@include('errors.layout', [
    'code'    => 403,
    'title'   => __('Access denied'),
    'message' => $exception?->getMessage() ?: __('You don\'t have permission to view this page. Ask an administrator if you think this is a mistake.'),
])
