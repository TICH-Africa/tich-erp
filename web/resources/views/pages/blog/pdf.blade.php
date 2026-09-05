<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $post->title }}</title>
    <style>
        body { font-family: Georgia, "Times New Roman", serif; color: #333; font-size: 12pt; line-height: 1.55; }
        h1 { font-size: 22pt; color: #6cab33; margin: 0 0 8pt; }
        h2 { font-size: 16pt; color: #1669a6; margin: 16pt 0 6pt; }
        h3 { font-size: 14pt; color: #1669a6; margin: 14pt 0 6pt; }
        h4, h5 { font-size: 12pt; margin: 12pt 0 4pt; }
        .meta { color: #666; font-size: 10pt; margin-bottom: 12pt; }
        .subtitle { font-size: 12pt; color: #494c50; margin: 0 0 14pt; }
        img { max-width: 100%; height: auto; margin: 10pt 0; }
        blockquote { border-left: 3pt solid #1669a6; padding: 6pt 10pt; margin: 10pt 0; background: #eef6fb; font-style: italic; }
        .tich-prose-quote--intense { border-left-color: #6cab33; background: #eef6e4; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin: 10pt 0; }
        th, td { border: 1pt solid #ccc; padding: 6pt; text-align: left; }
        th { background: #f3f4f6; }
        hr { border: 0; border-top: 1pt solid #ccc; margin: 14pt 0; }
        .cover { width: 100%; max-height: 220pt; object-fit: cover; margin-bottom: 14pt; }
        .footer { margin-top: 24pt; font-size: 9pt; color: #777; border-top: 1pt solid #ddd; padding-top: 8pt; }
    </style>
</head>
<body>
    <h1>{{ $post->title }}</h1>
    <p class="meta">
        {{ $post->formatted_date ?? '' }}
        @if (!empty($post->reading_time_minutes))
            · {{ $post->reading_time_minutes }} min read
        @endif
    </p>
    @if (!empty($post->subtitle))
        <p class="subtitle">{{ $post->subtitle }}</p>
    @endif
    @if (!empty($post->featured_image_path))
        <img class="cover" src="{{ $post->featured_image_path }}" alt="">
    @endif
    <div class="body">
        {!! $post->body !!}
    </div>
    <p class="footer">
        {{ $institution['institution_name'] ?? 'TICH in Africa' }}
        · Generated {{ optional($generatedAt ?? now())->format('d M Y H:i') }}
    </p>
</body>
</html>
