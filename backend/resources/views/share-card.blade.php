<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $card->title }} · linkcut</title>
    <meta name="description" content="{{ $card->description }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="linkcut">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:title" content="{{ $card->title }}">
    <meta property="og:description" content="{{ $card->description }}">
    <meta property="og:url" content="{{ $card->url }}">
@if ($card->image_url)
    <meta property="og:image" content="{{ $card->image_url }}">
    <meta name="twitter:card" content="summary_large_image">
@else
    <meta name="twitter:card" content="summary">
@endif
    <link rel="canonical" href="{{ $card->url }}">
    <meta http-equiv="refresh" content="0; url={{ $card->url }}">
</head>
<body>
    <p><a href="{{ $card->url }}">{{ $card->title }}</a></p>
</body>
</html>
