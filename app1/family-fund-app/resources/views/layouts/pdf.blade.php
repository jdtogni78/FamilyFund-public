<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{config('app.name')}}</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 4.1.1 -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/coreui@2.1.16/dist/css/coreui.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@icon/coreui-icons-free@1.0.1-alpha.1/coreui-icons-free.css">

    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.3.0/css/flag-icon.min.css">

    <style type="text/css">
        .new-page {
            overflow: hidden;
            page-break-after: always;
            page-break-inside: avoid;
        }
        label {
            min-width: 400px;
        }
        input {
            min-width: 150px;
            max-width: 150px;
            float: left;
        }

        div.input-group-text {
            min-width: 40px;
            max-width: 40px;
            float: left;
        }
        img {
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .col-left {
            width: 50%;
            float: left
        }
        .col-right {
            width: 50%;
            float: right
        }
    </style>

</head>
<body class="app">

<div class="app-body" style="background-color: white; margin: 0px">
    <main class="main" style="background-color: white;">
        @yield('content')
    </main>
</div>
</body>
{{--<!-- jQuery 3.1.1 -->--}}
{{--<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>--}}
{{--<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>--}}
{{--<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>--}}
{{--<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.20.1/moment.min.js"></script>--}}
{{--<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/js/bootstrap-datetimepicker.min.js"></script>--}}
{{--<script src="https://cdn.jsdelivr.net/npm/@coreui/coreui@2.1.16/dist/js/coreui.min.js"></script>--}}

{{--<script src="https://cdn.jsdelivr.net/npm/chart.js@^3"></script>--}}
{{--<script src="https://cdn.jsdelivr.net/npm/moment@^2"></script>--}}
{{--<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@^1"></script>--}}
</html>
