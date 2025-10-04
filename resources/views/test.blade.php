<!DOCTYPE html>
<html>
<head>
    <title>Test Blade</title>
</head>
<body>
    <h1>Test Page</h1>
    <p>Current time: {{ now() }}</p>
    <p>App name: {{ config('app.name') }}</p>
    @if(true)
        <p>Blade is working!</p>
    @endif
</body>
</html>
