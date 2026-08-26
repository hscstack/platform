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
    $props = $page['props'] ?? [];

    $metaTitle = config('app.name', 'HSCStack');
    $ogTitle = 'HSCStack - Open source repository';
    $metaDescription = 'A curated open learning platform for HSC & SSC students in Bangladesh with video lectures, notes, books, and question banks.';
    $ogImage = url('/feature.png');
    $ogType = 'website';
    $ogUrl = url()->current();

    if ($pageComponent === 'Blog/Show' && !empty($props['blog'])) {
        $blog = $props['blog'];
        $rawTitle = data_get($blog, 'meta_title') ?: data_get($blog, 'title');
        $metaTitle = $rawTitle ? "{$rawTitle} - " . config('app.name', 'HSCStack') : config('app.name', 'HSCStack');
        $ogTitle = $rawTitle ?: config('app.name', 'HSCStack');
        $metaDescription = data_get($blog, 'meta_description') ?: data_get($blog, 'excerpt') ?: $rawTitle;
        $ogImage = data_get($blog, 'featured_image') ?: url('/feature.png');
        $ogType = 'article';
        $ogUrl = data_get($blog, 'slug') ? url('/blogs/' . data_get($blog, 'slug')) : url()->current();
    } elseif ($pageComponent === 'User/Show' && !empty($props['profileUser'])) {
        $profileUser = $props['profileUser'];
        $userName = data_get($profileUser, 'name');
        $userHandle = data_get($profileUser, 'username');
        $userTitle = data_get($profileUser, 'title');
        $userInstitution = data_get($profileUser, 'institution');
        $userAbout = data_get($profileUser, 'about');

        $metaTitle = "{$userName} (@{$userHandle}) - " . config('app.name', 'HSCStack');
        $ogTitle = "{$userName} (@{$userHandle})";
        
        $descParts = array_filter([$userTitle, $userInstitution, $userAbout]);
        $metaDescription = !empty($descParts) 
            ? implode(' · ', array_slice($descParts, 0, 2)) 
            : "View {$userName}'s profile, completed study topics, and contributions on HSCStack.";
        $ogImage = data_get($profileUser, 'image_url') ?: url('/feature.png');
        $ogType = 'profile';
        $ogUrl = $userHandle ? url('/u/' . $userHandle) : url()->current();
    } elseif ($pageComponent === 'Node' && (!empty($props['breadcrumb']) || !empty($props['subject']))) {
        $crumbs = $props['breadcrumb'] ?? [];
        $lastCrumb = is_array($crumbs) && count($crumbs) ? end($crumbs) : null;
        $nodeTitle = data_get($lastCrumb, 'name') ?: data_get($props['subject'], 'name', 'Curriculum');
        $metaTitle = "{$nodeTitle} - " . config('app.name', 'HSCStack');
        $ogTitle = "{$nodeTitle} - HSCStack";
        $metaDescription = "Study materials, chapter breakdown, and lecture notes for {$nodeTitle} - HSCStack.";
    } elseif ($pageComponent === 'Resource' && !empty($props['resource'])) {
        $res = $props['resource'];
        $resTitle = data_get($res, 'title', 'Resource');
        $resType = data_get($res, 'resource_type', 'material');
        $metaTitle = "{$resTitle} - " . config('app.name', 'HSCStack');
        $ogTitle = "{$resTitle} - HSCStack";
        $metaDescription = "Study material: {$resTitle} ({$resType}) on HSCStack.";
        if ($resType === 'image' && data_get($res, 'file_path')) {
            $ogImage = str_starts_with(data_get($res, 'file_path'), 'http') ? data_get($res, 'file_path') : \Illuminate\Support\Facades\Storage::url(data_get($res, 'file_path'));
        }
    } elseif ($pageComponent === 'Blog/Index') {
        $metaTitle = 'Educational Blogs & Study Guides - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Educational Blogs & Study Guides - HSCStack';
        $metaDescription = 'Read study tips, educational articles, subject advice, and preparation guides for HSC and SSC students on HSCStack.';
    } elseif ($pageComponent === 'Projects') {
        $metaTitle = 'Our Products & Open Source Projects - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Our Products & Open Source Projects - HSCStack';
        $metaDescription = 'Explore educational platforms, open-source web applications, and learning tools developed by the HSCStack team.';
    } elseif ($pageComponent === 'platform/AboutUs') {
        $metaTitle = 'About Us & Core Team - ' . config('app.name', 'HSCStack');
        $ogTitle = 'About Us & Core Team - HSCStack';
        $metaDescription = 'Meet the creators, developers, campus promoters, and resource curators behind HSCStack.';
    } elseif ($pageComponent === 'platform/JoinTeam') {
        $metaTitle = 'Join the Team - Become a Contributor - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Join the Team - Become a Contributor - HSCStack';
        $metaDescription = 'Join HSCStack as a Campus Promoter, Resource Curator, Social Media Moderator, Blog Writer, or Software Developer.';
    } elseif ($pageComponent === 'ContributorGuide') {
        $metaTitle = 'Contributor Handbook & Guidelines - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Contributor Handbook & Guidelines - HSCStack';
        $metaDescription = 'Official contributor documentation and step-by-step handbook for HSCStack maintainers and curators.';
    } elseif ($pageComponent === 'Support') {
        $metaTitle = 'Support & Donate - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Support & Donate - HSCStack';
        $metaDescription = 'Support HSCStack to keep the platform free, ad-free, and accessible to every student in Bangladesh.';
    } elseif ($pageComponent === 'ai/Index') {
        $metaTitle = 'HSCStack AI - Smart Learning Assistant - ' . config('app.name', 'HSCStack');
        $ogTitle = 'HSCStack AI - Smart Learning Assistant';
        $metaDescription = 'Interactive AI Learning Assistant for HSC & SSC curriculum in Bangladesh.';
    } elseif ($pageComponent === 'legal/PrivacyPolicy') {
        $metaTitle = 'Privacy Policy - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Privacy Policy - HSCStack';
        $metaDescription = 'Privacy Policy for HSCStack. Learn how we handle your data, protect user privacy, and ensure transparent practices.';
    } elseif ($pageComponent === 'legal/TermsConditions') {
        $metaTitle = 'Terms & Conditions - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Terms & Conditions - HSCStack';
        $metaDescription = 'Terms and Conditions of use for the HSCStack open educational platform.';
    } elseif ($pageComponent === 'legal/ContentPolicy') {
        $metaTitle = 'Content & Copyright Policy - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Content & Copyright Policy - HSCStack';
        $metaDescription = 'Content and Copyright Policy of HSCStack. Information on educational resource fair use and contributor content guidelines.';
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
