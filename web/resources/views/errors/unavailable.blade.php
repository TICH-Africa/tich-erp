<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Service unavailable' }}</title>
    <style>
        :root { color-scheme: light; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Georgia, "Times New Roman", serif;
            background: #f5f6f6;
            color: #494c50;
            padding: 24px;
        }
        .card {
            max-width: 28rem;
            width: 100%;
            background: #fff;
            border-top: 4px solid #6cab33;
            border-bottom: 3px solid #1669a6;
            padding: 2rem 1.75rem;
            box-shadow: 0 8px 24px rgba(0,0,0,.06);
        }
        .code {
            margin: 0 0 .5rem;
            font-family: Arial, sans-serif;
            font-size: .75rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #1669a6;
            font-weight: 700;
        }
        h1 {
            margin: 0 0 .75rem;
            font-size: 1.6rem;
            line-height: 1.25;
            color: #6cab33;
        }
        p {
            margin: 0 0 .75rem;
            font-size: .95rem;
            line-height: 1.55;
        }
        .hint { color: #6b6e72; font-size: .85rem; }
        a {
            display: inline-block;
            margin-top: 1rem;
            padding: .7rem 1rem;
            background: #1669a6;
            color: #fff;
            text-decoration: none;
            font-family: Arial, sans-serif;
            font-size: .85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <p class="code">{{ $code ?? '503' }}</p>
        <h1>{{ $title ?? 'Service unavailable' }}</h1>
        <p>{{ $message ?? 'The platform is temporarily unavailable. Please try again shortly.' }}</p>
        <p class="hint">{{ $hint ?? 'If this continues, contact ICT. No sensitive details are shown here.' }}</p>
        <a href="/">Back to homepage</a>
    </div>
</body>
</html>
