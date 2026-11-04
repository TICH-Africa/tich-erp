<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle ?? 'Timetable' }}</title>
    <style>
        html, body { margin: 0; height: 100%; }
        iframe { border: 0; width: 100%; height: 100%; }
    </style>
</head>
<body>
    <iframe src="{{ $pdfUrl }}" title="{{ $documentTitle ?? 'Timetable' }}"></iframe>
</body>
</html>
