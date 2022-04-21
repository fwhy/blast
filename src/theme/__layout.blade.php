<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', $config['site']['name'])</title>
    <link rel="stylesheet" href="https://cdn.metroui.org.ua/v4/css/metro-all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=BIZ+UDPGothic:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'BIZ UDPGothic', -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, Ubuntu, "Helvetica Neue", sans-serif
        }
    </style>
</head>
<body style="padding-top: 64px;">
<header class="fixed-top" data-role="appbar" data-expand-point="md">
    <a href="/" class="brand no-hover">{{ $config['site']['name'] }}</a>
</header>
<main class="container">
    @yield('content', '')
</main>
<script src="https://cdn.metroui.org.ua/v4/js/metro.min.js"></script>
</body>
</html>
