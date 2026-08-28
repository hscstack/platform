<!-- Content Policy -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    FileText,
    Users,
    ShieldAlert,
    MessageSquare,
    Share2,
    UserMinus,
    Youtube,
    PenLine,
    ThumbsUp,
    Copyright,
    RefreshCw,
} from 'lucide-vue-next';
</script>

<template>
    <Head>
        <title>Content & Copyright Policy</title>
        <meta
            name="description"
            content="Content and Copyright Policy of HSCStack. Information on educational resource fair use, DMCA notices, and contributor content guidelines."
        />
        <meta
            property="og:title"
            content="Content & Copyright Policy - HSCStack"
        />
    </Head>

    <header class="mx-auto max-w-3xl px-4 pt-8 pb-6 text-center sm:pt-12">
        <h1
            class="mb-4 text-4xl leading-tight font-black tracking-tight text-slate-950 sm:text-5xl dark:text-gray-100"
        >
            Content <span class="text-indigo-600">Policy</span>
        </h1>
        <p
            class="mx-auto max-w-md text-xs font-semibold tracking-wider text-slate-400 uppercase dark:text-gray-500"
        >
            সর্বশেষ আপডেট: আগস্ট ২০২৬
        </p>
    </header>

    <main class="mx-auto max-w-3xl px-4 pb-20 sm:px-6">
        <div
            class="space-y-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-700 dark:bg-gray-900"
        >
            <p
                class="text-sm leading-relaxed font-medium text-slate-600 dark:text-gray-400"
            >
                HSCStack-এ প্রকাশিত সব ধরনের কনটেন্ট কীভাবে সংগ্রহ, প্রদর্শন এবং
                পরিচালনা করা হয়, এই Content Policy তা ব্যাখ্যা করে।
                প্ল্যাটফর্মে কোনো কিছু submit বা browse করার আগে দয়া করে এটি
                পড়ে নিন।
            </p>

            <hr class="border-slate-100 dark:border-gray-800" />

            <!-- Section 1 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <FileText class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ১. আমাদের কনটেন্টের ধরন
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack-এ বিভিন্ন ধরনের শিক্ষামূলক কনটেন্ট থাকতে পারে,
                        যার মধ্যে রয়েছে:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>৫ ধরনের রিসোর্স (Notes, Questions, PDFs, Images, Videos)</li>
                        <li>Embedded YouTube classes (Custom Native Player-এর মাধ্যমে)</li>
                        <li>Contributor-written blogs and articles</li>
                        <li>External references and links</li>
                        <li>Live chat messages</li>
                    </ul>
                    <p
                        class="mt-2 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        আমরা শিক্ষার্থীদের জন্য সহজলভ্য ও সংগঠিত শিক্ষামূলক
                        রিসোর্স তৈরি করার চেষ্টা করি।
                    </p>
                </div>
            </div>

            <!-- Section 2 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Users class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ২. Contributor Submitted Content
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        কন্ট্রিবিউটররা HSCStack-এ নোট বা যেকোনো শিক্ষামূলক উপকরণ (Questions, PDFs, Images, Videos ইত্যাদি) জমা দেওয়ার মাধ্যমে নিশ্চিত করেন যে:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>তারা সেই কনটেন্ট জমা দেওয়ার অধিকার রাখেন।</li>
                        <li>
                            কনটেন্টটি তাদের নিজস্ব তৈরি অথবা তারা সেটি শেয়ার
                            করার অনুমতি পেয়েছেন।
                        </li>
                        <li>
                            জমাকৃত কনটেন্ট কোনো তৃতীয় পক্ষের স্বত্ব লঙ্ঘন করে
                            না।
                        </li>
                    </ul>
                    <p
                        class="mt-2 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack-এ প্রকাশিত রিসোর্সগুলো শিক্ষার্থীদের শেখার সুবিধার
                        জন্য প্রদান করা হয়। কোনো কন্ট্রিবিউটর কনটেন্ট জমা দিলে (S3/Cloudflare R2-তে আপলোড বা এক্সটার্নাল লিংকের মাধ্যমে),
                        HSCStack সেই কনটেন্ট প্ল্যাটফর্মে সংরক্ষণ, প্রদর্শন এবং
                        শিক্ষার্থীদের কাছে বিতরণের অনুমতি পায় — তবে কনটেন্টের
                        মূল মালিকানা কন্ট্রিবিউটরের কাছেই থাকে।
                    </p>
                </div>
            </div>

            <!-- Section 3 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <ShieldAlert class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৩. Prohibited Content
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        শিক্ষার্থী এবং প্ল্যাটফর্ম উভয়ের নিরাপত্তার স্বার্থে,
                        HSCStack-এ নিচের ধরনের কনটেন্ট জমা দেওয়া বা প্রকাশ করা
                        নিষিদ্ধ:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>
                            কপিরাইট লঙ্ঘনকারী বা চুরি করা (stolen) কনটেন্ট।
                        </li>
                        <li>
                            লাইভ চ্যাটে অশালীন ভাষা (profanity), হয়রানি, বিদ্বেষমূলক বক্তব্য (hate speech) বা হুমকি।
                        </li>
                        <li>
                            ইচ্ছাকৃতভাবে বিভ্রান্তিকর বা ভুল শিক্ষামূলক তথ্যসম্বলিত (misleading academic content) কনটেন্ট।
                        </li>
                        <li>
                            কারো ব্যক্তিগত বা সংবেদনশীল তথ্য শেয়ার করা বা ডক্সিং (doxxing)।
                        </li>
                        <li>
                            ক্ষতিকর ফাইল, ম্যালওয়্যার, বা সন্দেহজনক
                            এক্সিকিউটেবল সম্বলিত আপলোড।
                        </li>
                        <li>
                            স্প্যাম, প্রোমোশনাল, বা পুনরাবৃত্তিমূলক (repetitive)
                            submission।
                        </li>
                    </ul>
                    <p
                        class="mt-2 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        এই নীতিমালা লঙ্ঘনকারী কনটেন্ট আমরা পূর্বনোটিশ ছাড়াই
                        সরিয়ে ফেলতে পারি, এবং গুরুতর বা বারবার লঙ্ঘনের ক্ষেত্রে
                        সংশ্লিষ্ট কনট্রিবিউটর অ্যাকাউন্ট স্থগিত বা বাতিল করা হতে
                        পারে — যেমনটি আমাদের
                        <Link
                            href="/terms-service"
                            class="text-indigo-600 underline dark:text-indigo-400"
                            >Terms & Conditions</Link
                        >-এ ব্যাখ্যা করা হয়েছে।
                    </p>
                </div>
            </div>

            <!-- Section 4 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <MessageSquare class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৪. লাইভ চ্যাট কনটেন্ট নীতিমালা
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack-এর রিয়েল-টাইম লাইভ চ্যাটে শিক্ষার্থীদের একটি সুস্থ পরিবেশ বজায় রাখার জন্য আমাদের একটি স্বয়ংক্রিয় মডারেশন সিস্টেম রয়েছে:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>
                            <strong>Smart Profanity Filter:</strong> আমাদের সিস্টেমে স্বয়ংক্রিয়ভাবে গালিগালাজ শনাক্ত করার প্রযুক্তি রয়েছে, যা leet-speak (যেমন @->a, $->s, 1->i) এবং পুনরাবৃত্তিমূলক অক্ষর (repeated char collapse) শনাক্ত করতে সক্ষম। কনফিগারযোগ্য নিষিদ্ধ শব্দগুলো স্বয়ংক্রিয়ভাবে ফিল্টার হয়ে যায়।
                        </li>
                        <li>
                            <strong>Community Reporting:</strong> চ্যাটের কোনো মেসেজ আপত্তিকর মনে হলে ব্যবহারকারীরা নির্দিষ্ট কারণ উল্লেখ করে সেটি রিপোর্ট করতে পারেন।
                        </li>
                        <li>
                            <strong>Auto-Suspension:</strong> কোনো ব্যবহারকারী ৫টি বা তার বেশি রিপোর্ট পেলে সিস্টেম স্বয়ংক্রিয়ভাবে তাকে ২৪ ঘণ্টার জন্য চ্যাট থেকে সাসপেন্ড করবে।
                        </li>
                        <li>
                            <strong>Message Pruning:</strong> প্ল্যাটফর্মের গতি ঠিক রাখতে চ্যাটে রোলিং মেসেজ প্রুনিং ব্যবহার করা হয়, যেখানে সর্বশেষ প্রায় ২০০টি মেসেজ সংরক্ষিত থাকে।
                        </li>
                        <li>
                            <strong>Staff Moderation:</strong> অ্যাডমিন এবং মডারেটররা যেকোনো সময় আপত্তিকর মেসেজ ডিলিট করার বা ব্যবহারকারীকে ব্যান করার অধিকার রাখেন।
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Section 5 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Share2 class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৫. Attribution এবং Redistribution
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack কন্ট্রিবিউটরদের অবদানকে সম্মান করে এবং যেখানে
                        সম্ভব, সাইটে কন্ট্রিবিউটরের নাম বা প্রোফাইল প্রদর্শন করা
                        হবে। তবে:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>
                            HSCStack থেকে ডাউনলোড করা ফাইল শিক্ষার্থীরা ব্যক্তিগত
                            ও শিক্ষামূলক ব্যবহারের জন্য ব্যবহার করতে পারবেন।
                        </li>
                        <li>
                            ডাউনলোড করা ফাইল পুনরায় শেয়ার করা হলে HSCStack বা
                            মূল কন্ট্রিবিউটরের attribution বজায় রাখা
                            বাধ্যতামূলক নয়, যদি না মূল কনটেন্টের লাইসেন্সে অন্য
                            কোনো শর্ত উল্লেখ থাকে।
                        </li>
                        <li>
                            HSCStack-এ প্রকাশিত কনটেন্টের ওপর কন্ট্রিবিউটর তাদের
                            জমা দেওয়া কনটেন্টের মূল মালিকানা ধরে রাখলেও,
                            HSCStack-এ প্রকাশের মাধ্যমে HSCStack-কে সেই কনটেন্ট
                            সংরক্ষণ, প্রদর্শন এবং শিক্ষামূলক উদ্দেশ্যে বিতরণের
                            অনুমতি প্রদান করেন।
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Section 6 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <UserMinus class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৬. Contributor Account Removal
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        কোনো কন্ট্রিবিউটর তাদের অ্যাকাউন্ট মুছে ফেললে:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>তাদের প্রোফাইল তথ্য অপসারণ করা হতে পারে।</li>
                        <li>
                            পূর্বে প্রকাশিত কনটেন্ট সবসময় স্বয়ংক্রিয়ভাবে মুছে
                            ফেলা হবে না।
                        </li>
                        <li>
                            শিক্ষার্থীদের সুবিধার জন্য কিছু কনটেন্ট HSCStack-এ
                            "Unknown User" বা অনুরূপ attribution-এর অধীনে রাখা
                            হতে পারে।
                        </li>
                    </ul>
                    <p
                        class="mt-2 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        কন্টেন্ট অপসারণের অনুরোধ করা যেতে পারে, তবে প্রতিটি
                        অনুরোধ HSCStack পরিস্থিতি অনুযায়ী পর্যালোচনা করবে।
                        অপসারণের অনুরোধ পরিস্থিতি, কনটেন্টের ধরন এবং
                        শিক্ষার্থীদের উপকারিতা বিবেচনা করে পর্যালোচনা করা হবে।
                    </p>
                </div>
            </div>

            <!-- Section 7 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Youtube class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৭. Embedded YouTube Content
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack কিছু ক্ষেত্রে YouTube-এ প্রকাশিত শিক্ষামূলক
                        ভিডিও ক্লাস আমাদের Custom Native Player-এর মাধ্যমে embed করতে পারে। আমরা:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>ভিডিওতে কোনো পরিবর্তন করি না।</li>
                        <li>ভিডিও নিজের সার্ভারে সংরক্ষণ করি না।</li>
                        <li>
                            শুধুমাত্র YouTube-এর official embedding system
                            ব্যবহার করি।
                        </li>
                    </ul>
                    <p
                        class="mt-2 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        YouTube-এর embedding feature ব্যবহার করে কোনো ভিডিও
                        embed করা হলে, ভিডিও মালিক তাদের YouTube channel
                        settings থেকে embedding disable করতে পারেন। যদি কোনো
                        ভিডিও মালিক মনে করেন যে কোনো embed করা ভিডিও অপসারণ করা
                        উচিত, তারা আমাদের সাথে যোগাযোগ করতে পারেন। আমরা অনুরোধটি
                        পর্যালোচনা করব।
                    </p>
                </div>
            </div>

            <!-- Section 8 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <PenLine class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৮. Blogs এবং Articles
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack-এ প্রকাশিত ব্লগ ও আর্টিকেল সাধারণত
                        কন্ট্রিবিউটরদের নিজস্ব লেখা। কন্ট্রিবিউটররা নিশ্চিত
                        করবেন যে:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>লেখাটি তাদের নিজস্ব।</li>
                        <li>
                            অন্যের লেখা বা ধারণা ব্যবহার করলে যথাযথ reference
                            প্রদান করা হয়েছে।
                        </li>
                        <li>
                            বিভ্রান্তিকর বা ইচ্ছাকৃত ভুল তথ্য প্রদান করা হয়নি।
                        </li>
                    </ul>
                    <p
                        class="mt-2 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        ব্লগগুলোতে পাঠকরা <strong>Love reactions</strong> দিতে পারেন এবং <strong>Threaded comments</strong>-এর মাধ্যমে মতামত জানাতে পারেন। আমরা গঠনমূলক আলোচনা এবং শালীন ভাষা ব্যবহারের জন্য উৎসাহিত করি। এছাড়াও, নির্দিষ্ট মাইলফলক অর্জনে কন্ট্রিবিউটররা নোটিফিকেশন পাবেন।
                    </p>
                </div>
            </div>

            <!-- Section 9 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <ThumbsUp class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৯. কমিউনিটি ইন্টারঅ্যাকশন
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack-এ একটি সহায়ক ও ইতিবাচক শিক্ষামূলক পরিবেশ গড়ে তোলার জন্য কমিউনিটির ইন্টারঅ্যাকশন অত্যন্ত গুরুত্বপূর্ণ:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li><strong>Folder Voting:</strong> ব্যবহারকারীরা ফোল্ডার আপভোট বা ডাউনভোট করার ক্ষেত্রে সততা বজায় রাখবেন। গঠনমূলক মূল্যায়ন অন্য শিক্ষার্থীদের সঠিক কনটেন্ট খুঁজে পেতে সাহায্য করবে।</li>
                        <li><strong>Appreciations:</strong> প্রোফাইলে অন্যদের প্রশংসা (appreciations) প্রদান করার ক্ষেত্রে সম্মানজনক ভাষা ব্যবহার করতে হবে।</li>
                        <li><strong>Profile Content:</strong> ব্যবহারকারীর প্রোফাইলের কনটেন্ট এবং কার্যকলাপের সম্পূর্ণ দায়ভার ওই ব্যবহারকারীর।</li>
                    </ul>
                </div>
            </div>

            <!-- Section 10 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Copyright class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ১০. Copyright এবং Removal Requests
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        যদি কোনো ব্যক্তি মনে করেন HSCStack-এ থাকা কোনো কনটেন্ট
                        তাদের অধিকার লঙ্ঘন করছে, তারা আমাদের সাথে যোগাযোগ করতে
                        পারেন। আমরা অভিযোগ পর্যালোচনা করে প্রয়োজন অনুযায়ী
                        ব্যবস্থা নিতে পারি, যার মধ্যে থাকতে পারে:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>কনটেন্ট পরিবর্তন</li>
                        <li>Attribution update</li>
                        <li>কনটেন্ট অপসারণ</li>
                    </ul>
                </div>
            </div>

            <!-- Section 11 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <RefreshCw class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ১১. Content Review and Modification
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack অ্যাডমিন প্যানেলের মাধ্যমে প্ল্যাটফর্মের কনটেন্ট পরিচালনার ক্ষমতা রাখে:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>অ্যাডমিন বা এডিটররা যেকোনো সাবজেক্ট, নোড, রিসোর্স বা ব্লগ তৈরি, সম্পাদনা বা ডিলিট করতে পারেন।</li>
                        <li>প্রয়োজন অনুযায়ী Bulk operations পরিচালনা করা হতে পারে।</li>
                        <li>প্ল্যাটফর্মের মান বজায় রাখার জন্য কনটেন্ট যাচাই করতে পারে এবং যেকোনো submission reject করতে পারে।</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</template>
