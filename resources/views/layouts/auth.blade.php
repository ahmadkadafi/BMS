<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>@yield('title', 'BMS')</title>
    <link rel="icon" href="{{ asset('templating/assets/img/kaiadmin/icon_kai.png') }}" type="image/x-icon" />
    <link rel="stylesheet" href="{{ asset('templating/assets/css/bootstrap.min.css') }}" />
    @yield('page_css')

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Public Sans", sans-serif;
        }

        @yield('styles')
    </style>
</head>
<body>
    @yield('content')
    <script src="{{ asset('templating/assets/js/core/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('templating/assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('templating/assets/js/core/bootstrap.min.js') }}"></script>
    @yield('scripts')
</body>
</html>
