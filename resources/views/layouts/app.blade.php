<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ $csrfToken }}">
    <title>@yield('title', 'Approval Mapping')</title>
    <link rel="stylesheet" href="{{ url(trim(config('approval-mapping.route.web_prefix', 'approval-mapping'), '/').'/assets/approval-mapping.css') }}">
    @stack('styles')
</head>
<body class="am-body">
    @yield('content')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
