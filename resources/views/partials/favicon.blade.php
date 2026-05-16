@php
    $faviconV = file_exists(public_path('icon/investment_logo.svg')) ? filemtime(public_path('icon/investment_logo.svg')) : 0;
    $faviconHref = route('site.logo') . '?v=' . $faviconV;
@endphp
<link rel="icon" href="{{ $faviconHref }}" type="image/svg+xml" sizes="any">
<link rel="shortcut icon" href="{{ $faviconHref }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ $faviconHref }}">
<meta name="theme-color" content="#0f172a">
<meta name="msapplication-TileColor" content="#0f172a">
