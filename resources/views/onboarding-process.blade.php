<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Onboarding Process</title>
</head>
<body>
    @php
        $processId = custom_decrypt(@$_GET['processId'] ?? '') ?: 1;
    @endphp
    <div
        id="app"
        data-process-id="{{ is_numeric($processId) ? $processId : $processId }}"
    ></div>

    @vite(['resources/css/app.css', 'resources/js/onboarding-process-entry.js'])
</body>
</html>