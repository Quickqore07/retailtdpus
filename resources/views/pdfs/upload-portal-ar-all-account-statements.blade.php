<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Account Statements</title>
    @include('pdfs.partials.upload-portal-ar-account-statement-styles')
</head>

<body>
    @foreach ($statements as $statement)
        <div class="statement-page">
            @include('pdfs.partials.upload-portal-ar-account-statement-content', $statement)
        </div>
    @endforeach
</body>

</html>
