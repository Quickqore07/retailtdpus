<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/webp" href="{{ asset('logo.webp') }}">

    <title>Upload Portal</title>
    @vite(['resources/js/upload-portal/main.js', 'resources/css/app.css'])
</head>
<body>
    <div id="upload-app"></div>
</body>
</html>
