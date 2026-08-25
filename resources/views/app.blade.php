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
        <meta name="description" content="A curated open learning platform for HSC & SSC students in Bangladesh with video lectures, notes, books, and question banks.">
        <meta property="og:title" content="HSCStack - Open source repository">
        <meta property="og:description" content="A curated resource platform for HSC & SSC students of Bangladesh — built by members, for everyone.">
        <meta property="og:image" content="{{ url('/feature.png') }}">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="HSCStack">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="HSCStack - Open source repository">
        <meta name="twitter:description" content="A curated resource platform for HSC & SSC students of Bangladesh — built by members, for everyone.">
        <meta name="twitter:image" content="{{ url('/feature.png') }}">
        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'HSCStack') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
