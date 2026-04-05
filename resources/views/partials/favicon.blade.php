@php
    $faviconV = file_exists(public_path('icon/investment_logo.svg')) ? filemtime(public_path('icon/investment_logo.svg')) : 0;
    $faviconHref = route('site.logo') . '?v=' . $faviconV;
@endphp
<link rel="icon" href="{{ $faviconHref }}" type="image/svg+xml" sizes="any">
<link rel="apple-touch-icon" href="{{ $faviconHref }}">
