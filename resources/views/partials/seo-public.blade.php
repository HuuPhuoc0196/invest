@php
    $logoV = file_exists(public_path('icon/investment_logo.svg')) ? filemtime(public_path('icon/investment_logo.svg')) : 0;
    $ogImageFinal = $ogImage ?? route('site.logo');
    $ogImageFinal .= (str_contains($ogImageFinal, '?') ? '&' : '?') . 'v=' . $logoV;
    $canonicalUrl = $canonical ?? request()->url();
    $siteName = config('app.name', 'Invest');
@endphp
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta name="description" content="{{ $description }}">
<meta property="og:type" content="website">
<meta property="og:locale" content="vi_VN">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $ogImageFinal }}">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $ogImageFinal }}">
