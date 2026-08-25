<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="manifest" href="/build/manifest.webmanifest">
        <meta name="theme-color" content="#000000">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/favicon.png">
@php
    $pageComponent = $page['component'] ?? '';
    $blog = $page['props']['blog'] ?? null;

    if ($pageComponent === 'Blog/Show' && $blog) {
        $rawTitle = data_get($blog, 'meta_title') ?: data_get($blog, 'title');
        $metaTitle = $rawTitle ? "{$rawTitle} - " . config('app.name', 'HSCStack') : config('app.name', 'HSCStack');
        $ogTitle = $rawTitle ?: config('app.name', 'HSCStack');
        $metaDescription = data_get($blog, 'meta_description') ?: data_get($blog, 'excerpt') ?: $rawTitle;
        $ogImage = data_get($blog, 'featured_image') ?: url('/feature.png');
        $ogType = 'article';
        $ogUrl = data_get($blog, 'slug') ? url('/blogs/' . data_get($blog, 'slug')) : url()->current();
    } else {
        $metaTitle = config('app.name', 'HSCStack');
        $ogTitle = 'HSCStack - Open source repository';
        $metaDescription = 'A curated open learning platform for HSC & SSC students in Bangladesh with video lectures, notes, books, and question banks.';
        $ogImage = url('/feature.png');
        $ogType = 'website';
        $ogUrl = url()->current();
    }
@endphp
        <meta name="description" content="{{ $metaDescription }}">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:url" content="{{ $ogUrl }}">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:site_name" content="HSCStack">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $ogTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $metaTitle ?? config('app.name', 'HSCStack') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
