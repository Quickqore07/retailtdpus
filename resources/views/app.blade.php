<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TDPUS</title>

    <link rel="icon" type="image/webp" href="{{ asset('logo.webp') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>

    <script type="application/json" id="flow-data">{!! json_encode([
        'user' => [
            'id' => auth()->id(),
            'name' => optional(auth()->user())->name,
            'admin' => optional(auth()->user()->role)->name === 'Admin',
        ]
    ]) !!}</script>
    <script>window.FLOW = JSON.parse(document.getElementById('flow-data').textContent);</script>
</body>
</html>
