<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="manifest" href="/build/manifest.webmanifest">
        <meta name="theme-color" content="#000000">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/favicon.png">
        @if ($posthogKey = config('services.posthog.api_key'))
            <script>
                !function(t,e){var o,n,p,r;e.__SV||(window.posthog && window.posthog.__loaded)||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}p||((p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",p.onerror=function(){p=null},(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r));var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="Oo Lo $o init rl nl tl el ll pa il hl Ko capture sl Ao gl calculateEventProperties pl register register_once register_for_session unregister unregister_for_session Xo ml getFeatureFlag getFeatureFlagPayload getFeatureFlagResult getAllFeatureFlags isFeatureEnabled reloadFeatureFlags updateFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSurveysLoaded onSessionId getSurveys getActiveMatchingSurveys renderSurvey displaySurvey cancelPendingSurvey canRenderSurvey canRenderSurveyAsync wl identify setPersonProperties unsetPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset kl shutdown setIdentity clearIdentity get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException addExceptionStep captureLog startExceptionAutocapture stopExceptionAutocapture loadToolbar get_property getSessionProperty yl cl createPersonProfile setInternalOrTestUser bl Do No opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing get_explicit_consent_status is_capturing clear_opt_in_out_capturing dl debug ma kn getPageViewId captureTraceFeedback captureTraceMetric Go".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
                posthog.init('{{ $posthogKey }}', {
                    api_host: '{{ config('services.posthog.host', 'https://us.i.posthog.com') }}',
                    person_profiles: 'identified_only',
                });
            </script>
        @endif
@php
    $pageComponent = $page['component'] ?? '';
    $props = $page['props'] ?? [];

    $s3Url = rtrim(config('filesystems.disks.s3.url') ?: env('AWS_URL', 'https://cdn.hscstack.site'), '/');
    $defaultOgImage = $s3Url ? "{$s3Url}/images/og.png" : url('/images/og.png');

    $metaTitle = config('app.name', 'HSCStack');
    $ogTitle = 'HSCStack - Open source repository';
    $metaDescription = 'A curated open learning platform for HSC & SSC students in Bangladesh with video lectures, notes, books, and question banks.';
    $ogImage = $defaultOgImage;
    $ogType = 'website';
    $ogUrl = url()->current();

    $isNoIndex = str_starts_with(strtolower($pageComponent), 'admin') || in_array($pageComponent, [
        'auth/Onboarding',
        'Onboarding',
        'Profile',
        'SupportMyTickets',
        'errors/404',
        'errors/503',
    ], true);

    $jsonLdSchemas = [];

    // Base Organization & WebSite Schemas for sitelinks
    $jsonLdSchemas[] = [
        '@type' => 'Organization',
        '@id' => url('/') . '/#organization',
        'name' => 'HSCStack',
        'url' => url('/'),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $defaultOgImage,
        ],
        'sameAs' => [
            'https://www.facebook.com/hscstack',
            'https://github.com/hscstack',
        ],
    ];

    $jsonLdSchemas[] = [
        '@type' => 'WebSite',
        '@id' => url('/') . '/#website',
        'url' => url('/'),
        'name' => 'HSCStack',
        'description' => 'Curated open learning platform for HSC & SSC students in Bangladesh',
        'publisher' => [
            '@id' => url('/') . '/#organization',
        ],
    ];

    if ($pageComponent === 'Home' && request()->is('ssc')) {
        $metaTitle = 'SSC Study Materials, Lecture Notes & Question Bank - ' . config('app.name', 'HSCStack');
        $ogTitle = 'SSC Study Materials & Question Bank - HSCStack';
        $metaDescription = 'Explore curated SSC video lectures, notes, books, and question banks for Science, Commerce, and Arts on HSCStack.';
        $ogUrl = url('/ssc');
    } elseif ($pageComponent === 'Blog/Show' && !empty($props['blog'])) {
        $blog = $props['blog'];
        $rawTitle = data_get($blog, 'meta_title') ?: data_get($blog, 'title');
        $metaTitle = $rawTitle ? "{$rawTitle} - " . config('app.name', 'HSCStack') : config('app.name', 'HSCStack');
        $ogTitle = $rawTitle ?: config('app.name', 'HSCStack');
        $metaDescription = data_get($blog, 'meta_description') ?: data_get($blog, 'excerpt') ?: $rawTitle;
        $ogImage = data_get($blog, 'featured_image') ?: $defaultOgImage;
        $ogType = 'article';
        $ogUrl = data_get($blog, 'slug') ? url('/blogs/' . data_get($blog, 'slug')) : url()->current();

        $jsonLdSchemas[] = [
            '@type' => 'BlogPosting',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $ogUrl,
            ],
            'headline' => $ogTitle,
            'description' => $metaDescription,
            'image' => $ogImage,
            'datePublished' => data_get($blog, 'created_at'),
            'dateModified' => data_get($blog, 'updated_at'),
            'author' => [
                '@type' => 'Person',
                'name' => data_get($blog, 'user.name', 'HSCStack Contributor'),
                'url' => data_get($blog, 'user.username') ? url('/u/' . data_get($blog, 'user.username')) : null,
            ],
            'publisher' => [
                '@id' => url('/') . '/#organization',
            ],
        ];
    } elseif ($pageComponent === 'User/Show' && !empty($props['profileUser'])) {
        $profileUser = $props['profileUser'];
        $userName = data_get($profileUser, 'name');
        $userHandle = data_get($profileUser, 'username');
        $userInstitution = data_get($profileUser, 'institution');
        $userAbout = data_get($profileUser, 'about');

        $metaTitle = "{$userName} (@{$userHandle}) - " . config('app.name', 'HSCStack');
        $ogTitle = "{$userName} (@{$userHandle})";
        
        $descParts = array_filter([$userInstitution, $userAbout]);
        $metaDescription = !empty($descParts) 
            ? implode(' · ', array_slice($descParts, 0, 2)) 
            : "View {$userName}'s profile, completed study topics, and contributions on HSCStack.";
        $ogImage = data_get($profileUser, 'image_url') ?: $defaultOgImage;
        $ogType = 'profile';
        $ogUrl = $userHandle ? url('/u/' . $userHandle) : url()->current();

        $jsonLdSchemas[] = [
            '@type' => 'ProfilePage',
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $userName,
                'alternateName' => "@{$userHandle}",
                'description' => $metaDescription,
                'image' => $ogImage,
                'url' => $ogUrl,
            ],
        ];
    } elseif ($pageComponent === 'Node' && (!empty($props['breadcrumb']) || !empty($props['subject']))) {
        $crumbs = $props['breadcrumb'] ?? [];
        $lastCrumb = is_array($crumbs) && count($crumbs) ? end($crumbs) : null;
        $nodeTitle = data_get($lastCrumb, 'name') ?: data_get($props['subject'], 'name', 'Curriculum');
        $metaTitle = "{$nodeTitle} - " . config('app.name', 'HSCStack');
        $ogTitle = "{$nodeTitle} - HSCStack";
        $metaDescription = "Study materials, chapter breakdown, and lecture notes for {$nodeTitle} - HSCStack.";

        if (!empty($crumbs)) {
            $breadcrumbElements = [];
            $accumulatedPath = '/' . data_get($props['subject'], 'slug');
            
            $breadcrumbElements[] = [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => data_get($props['subject'], 'name'),
                'item' => url($accumulatedPath),
            ];

            $position = 2;
            foreach ($crumbs as $crumb) {
                $accumulatedPath .= '/' . data_get($crumb, 'slug');
                $breadcrumbElements[] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => data_get($crumb, 'name'),
                    'item' => url($accumulatedPath),
                ];
            }

            $jsonLdSchemas[] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => $breadcrumbElements,
            ];
        }
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
    } elseif ($pageComponent === 'Donate') {
        $metaTitle = 'Support & Donate - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Support & Donate - HSCStack';
        $metaDescription = 'Support HSCStack to keep the platform free, ad-free, and accessible to every student in Bangladesh.';
    } elseif ($pageComponent === 'Support' || $pageComponent === 'SupportMyTickets') {
        $metaTitle = 'Support Center - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Support Center - HSCStack';
        $metaDescription = 'Submit support tickets, report bugs, or request assistance from the HSCStack team.';
    } elseif ($pageComponent === 'ai/Index') {
        $metaTitle = 'HSCStack AI - Smart Learning Assistant - ' . config('app.name', 'HSCStack');
        $ogTitle = 'HSCStack AI - Smart Learning Assistant';
        $metaDescription = 'Interactive AI Learning Assistant for HSC & SSC curriculum in Bangladesh.';
    } elseif ($pageComponent === 'Chat/Index') {
        $metaTitle = 'HSCStack Global Chat — Talk. Ask. Connect.';
        $ogTitle = 'HSCStack Global Chat — Talk. Ask. Connect.';
        $metaDescription = 'Connect with fellow students, ask questions, share ideas, get help, and join the conversation on HSCStack Global Chat.';
        $ogImage = $s3Url ? "{$s3Url}/images/og_chat.png" : url('/images/og_chat.png');
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
    } elseif ($pageComponent === 'Forum/Show' && !empty($props['post'])) {
        $post = $props['post'];
        $postTitle = data_get($post, 'title', 'Question');
        $postBody = strip_tags((string) data_get($post, 'body', ''));
        $metaTitle = "{$postTitle} - " . config('app.name', 'HSCStack');
        $ogTitle = "{$postTitle} - HSCStack Forum";
        $metaDescription = str($postBody)->limit(160, '...');
        $ogImage = data_get($post, 'image_url') ?: (data_get($post, 'image_path') ? \Illuminate\Support\Facades\Storage::url(data_get($post, 'image_path')) : $defaultOgImage);
        $ogType = 'article';
        $ogUrl = data_get($post, 'slug') ? url('/forum/questions/' . data_get($post, 'slug')) : url()->current();

        $authorName = data_get($post, 'user.name', 'HSCStack Student');
        $authorUsername = data_get($post, 'user.username');
        $authorUrl = $authorUsername ? url('/u/' . $authorUsername) : null;
        $upvoteCount = (int) data_get($post, 'upvotes_count', 0);
        $answerCount = (int) data_get($post, 'answers_count', 0);

        $questionSchema = [
            '@type' => 'QAPage',
            'mainEntity' => [
                '@type' => 'Question',
                'name' => $postTitle,
                'text' => $postBody,
                'answerCount' => $answerCount,
                'upvoteCount' => $upvoteCount,
                'dateCreated' => data_get($post, 'created_at'),
                'author' => [
                    '@type' => 'Person',
                    'name' => $authorName,
                    'url' => $authorUrl,
                ],
            ],
        ];

        $answersList = data_get($props, 'answers.data', []);
        if (!empty($answersList) && is_array($answersList)) {
            $suggestedAnswers = [];
            foreach (array_slice($answersList, 0, 5) as $ans) {
                $suggestedAnswers[] = [
                    '@type' => 'Answer',
                    'text' => strip_tags((string) data_get($ans, 'body', '')),
                    'dateCreated' => data_get($ans, 'created_at'),
                    'upvoteCount' => (int) data_get($ans, 'upvotes_count', 0),
                    'url' => $ogUrl . '#answer-' . data_get($ans, 'id'),
                    'author' => [
                        '@type' => 'Person',
                        'name' => data_get($ans, 'user.name', 'Student'),
                        'url' => data_get($ans, 'user.username') ? url('/u/' . data_get($ans, 'user.username')) : null,
                    ],
                ];
            }
            if (!empty($suggestedAnswers)) {
                $questionSchema['mainEntity']['suggestedAnswer'] = $suggestedAnswers;
            }
        }

        $jsonLdSchemas[] = $questionSchema;
    } elseif ($pageComponent === 'Forum/Index') {
        $metaTitle = 'Community Q&A Forum - Study Questions & Discussion - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Community Q&A Forum - HSCStack';
        $metaDescription = 'Ask questions, get help with difficult HSC & SSC academic problems, share answers, and collaborate with peers across Bangladesh on HSCStack Forum.';
        $ogUrl = url('/forum');
    } elseif ($pageComponent === 'Forum/Create') {
        $metaTitle = 'Ask a Question - HSCStack Forum';
        $ogTitle = 'Ask a Question - HSCStack Forum';
        $metaDescription = 'Post an academic question or study doubt on HSCStack Forum to get answers from peers and educators.';
        $ogUrl = url('/forum/ask');
    } elseif ($pageComponent === 'auth/Login' || $pageComponent === 'Login') {
        $metaTitle = 'Log In - ' . config('app.name', 'HSCStack');
        $ogTitle = 'Log In to HSCStack';
        $metaDescription = 'Log in to HSCStack with Google to access study resources, lecture notes, track learning progress, and connect with fellow students.';
        $ogUrl = url('/login');
    }
@endphp
        @if ($isNoIndex)
            <meta name="robots" content="noindex, nofollow">
        @else
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        @endif
        <link rel="canonical" href="{{ $ogUrl }}">
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

        @if (!empty($jsonLdSchemas))
            <script type="application/ld+json">
                {!! json_encode(['@context' => 'https://schema.org', '@graph' => $jsonLdSchemas], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
            </script>
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $metaTitle ?? config('app.name', 'HSCStack') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
