<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Single Benefit</title>
</head>
<body>
    <div
        id="app"
    ></div>

    @vite(['resources/css/app.css', 'resources/js/single-benefit.js'])
</body>
</html>