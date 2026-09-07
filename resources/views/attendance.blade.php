<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#10b981">
    <meta name="description" content="Check in, take breaks, and check out with your employee code.">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Attendance">
    <link rel="manifest" href="{{ asset('pwa/attendance/manifest.webmanifest') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('pwa/attendance/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('pwa/attendance/icon-192.png') }}">
    <title>Attendance</title>
</head>
<body>
    <div id="app"></div>
    @vite(['resources/css/app.css', 'resources/js/attendance.js'])
</body>
</html>
