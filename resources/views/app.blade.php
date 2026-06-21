<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta property="og:title" content="HSC Stack - Open source repository">
<meta property="og:description" content="A curated resource platform for HSC students of Bangladesh — built by members, for everyone.">
<meta property="og:image" content="https://hscstack.tajimz.xyz/feature.png">
<meta property="og:url" content="https://hscstack.tajimz.xyz">
<meta property="og:type" content="website">
<meta property="og:site_name" content="HSC Stack">
        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'HSC Stack') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
