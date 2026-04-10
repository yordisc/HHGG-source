<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @if (trim($cssTemplate) !== '')
        <style>
            {!! $cssTemplate !!}
        </style>
    @endif
</head>
<body>
    {!! $renderedHtml !!}
</body>
</html>
