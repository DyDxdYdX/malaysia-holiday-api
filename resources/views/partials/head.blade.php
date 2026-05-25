<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $appName = config('app.name', 'Malaysia Holiday API');
    $pageTitle = filled($title ?? null) ? $title.' - '.$appName : $appName;
    $pageDescription = $description
        ?? 'A free, reliable, and verified public holiday API for Malaysia with state-level coverage and integrations for Python, JavaScript, and cURL.';
    $pageCanonical = $canonical ?? url()->current();
    $pageRobots = $robots
        ?? (request()->routeIs(
            'dashboard',
            'admin.*',
            'login',
            'register',
            'password.*',
            'verification.*',
            'two-factor.*',
            'profile.*',
            'appearance.*',
            'security.*'
        ) ? 'noindex, nofollow' : 'index, follow');
    $openGraphType = $ogType ?? 'website';
    $socialImage = $ogImage ?? asset('logo.png');
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">
<meta name="robots" content="{{ $pageRobots }}">

<link rel="canonical" href="{{ $pageCanonical }}">

<meta property="og:site_name" content="{{ $appName }}">
<meta property="og:type" content="{{ $openGraphType }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ $pageCanonical }}">
<meta property="og:image" content="{{ $socialImage }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $socialImage }}">

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
