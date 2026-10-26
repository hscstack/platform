<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Clock,
    Compass,
    Flame,
    Heart,
    HelpCircle,
    MessageCircle,
    MessageSquare,
    Newspaper,
    Play,
    Search,
    Share2,
    SlidersHorizontal,
    Sparkles,
    Trophy,
    UserCheck,
    Zap,
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

const quickLinks = [
    { num: '01', id: 'overview', label: 'প্ল্যাটফর্ম পরিচিতি' },
    { num: '02', id: 'finding-notes', label: 'নোট ও রিসোর্স ব্রাউজিং' },
    { num: '03', id: 'study-tracker', label: 'স্টাডি ট্র্যাকার' },
    { num: '04', id: 'forum-discussions', label: 'প্রশ্ন ও ফোরাম' },
    { num: '05', id: 'global-chat', label: 'লাইভ গ্লোবাল চ্যাট' },
    { num: '06', id: 'blogs', label: 'ব্লগ ও গাইডলাইন' },
    { num: '07', id: 'leaderboard', label: 'লিডারবোর্ড ও মিউচুয়াল' },
    { num: '08', id: 'profile-network', label: 'প্রোফাইল ও অ্যাপ্রিসিয়েশন' },
    { num: '09', id: 'sharing-notes', label: 'নোট ও রিসোর্স শেয়ার' },
    { num: '10', id: 'pwa-app', label: 'মোবাইল অ্যাপ (PWA)' },
];

const activeId = ref('overview');
let observer: IntersectionObserver | null = null;
let isManualClick = false;
let clickTimeout: ReturnType<typeof setTimeout> | null = null;

const centerNavPill = (id: string) => {
    const pill = document.getElementById(`nav-pill-${id}`);

    if (pill) {
        pill.scrollIntoView({
            behavior: 'smooth',
            inline: 'center',
            block: 'nearest',
        });
    }
};

const scrollToSection = (id: string) => {
    isManualClick = true;
    activeId.value = id;
    centerNavPill(id);

    const element = document.getElementById(id);

    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (clickTimeout) {
        clearTimeout(clickTimeout);
    }

    clickTimeout = setTimeout(() => {
        isManualClick = false;
    }, 800);
};

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (isManualClick) {
                return;
            }

            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    activeId.value = entry.target.id;
                    centerNavPill(entry.target.id);
                }
            });
        },
        {
            rootMargin: '-20% 0px -60% 0px',
            threshold: 0,
        },
    );

    quickLinks.forEach((link) => {
        const el = document.getElementById(link.id);

        if (el && observer) {
            observer.observe(el);
        }
    });
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }

    if (clickTimeout) {
        clearTimeout(clickTimeout);
    }
});
</script>

<template>
    <Head>
        <title>ব্যবহার নির্দেশিকা ও টিউটোরিয়াল - HSCStack</title>
        <meta
            name="description"
            content="HSCStack প্ল্যাটফর্মের সকল ফিচার কীভাবে সহজে ব্যবহার করবেন — নোটস খোঁজা, স্টাডি ট্র্যাকার, ফোরাম ও লিডারবোর্ডের পূর্ণাঙ্গ নির্দেশিকা।"
        />
        <meta
            property="og:title"
            content="ব্যবহার নির্দেশিকা ও টিউটোরিয়াল - HSCStack"
        />
        <meta
            property="og:description"
            content="HSCStack প্ল্যাটফর্মের সকল ফিচার কীভাবে সহজে ব্যবহার করবেন তার ধাপে ধাপে নির্দেশিকা।"
        />
    </Head>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
        <!-- Hero Header -->
        <header class="mx-auto max-w-3xl text-center">
            <h1
                class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl dark:text-white"
            >
                HSCStack ব্যবহার
                <span class="text-indigo-600 dark:text-indigo-400"
                    >নির্দেশিকা</span
                >
            </h1>
            <p
                class="mt-3 text-sm leading-relaxed text-slate-600 sm:text-base dark:text-gray-300"
            >
                হ্যান্ডরিটেন নোট সংগ্রহ, পড়াশোনার সময় ট্র্যাকিং, সহপাঠীদের
                প্রশ্ন সমাধান এবং লিডারবোর্ডে যুক্ত হওয়ার সম্পূর্ণ গাইডলাইন।
            </p>
        </header>

        <!-- Sticky Quick Navigation Bar -->
        <nav class="sticky top-4 z-30 mx-auto mt-8 mb-10 max-w-4xl">
            <div
                class="rounded-2xl border border-slate-200/90 bg-white/95 p-2.5 shadow-lg shadow-slate-200/40 backdrop-blur-md dark:border-gray-800 dark:bg-gray-900/95 dark:shadow-black/40"
            >
                <div
                    class="no-scrollbar flex items-center gap-1.5 overflow-x-auto px-1 py-1"
                >
                    <button
                        v-for="link in quickLinks"
                        :key="link.id"
                        :id="`nav-pill-${link.id}`"
                        type="button"
                        @click="scrollToSection(link.id)"
                        class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                        :class="[
                            activeId === link.id
                                ? 'bg-indigo-600 font-bold text-white shadow-xs dark:bg-indigo-500'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200',
                        ]"
                    >
                        <span class="text-[10px] opacity-70">{{
                            link.num
                        }}</span>
                        <span>{{ link.label }}</span>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Guide Sections Content -->
        <div class="mx-auto max-w-4xl space-y-12 sm:space-y-16">
            <!-- 01. Overview -->
            <section id="overview" class="scroll-mt-32 space-y-5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >01</span
                    >
                    <h2
                        class="text-2xl font-black text-slate-900 dark:text-white"
                    >
                        প্ল্যাটফর্ম পরিচিতি ও শুরু করবেন যেভাবে
                    </h2>
                </div>
                <p
                    class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-gray-300"
                >
                    HSCStack বাংলাদেশের HSC ও SSC শিক্ষার্থীদের জন্য উন্মুক্ত
                    একাডেমিক রিসোর্স প্ল্যাটফর্ম। এখানে কোনো পে-ওয়াল
                    নেই—প্রতিটি অধ্যায়ের গোছানো ক্লাস লেকচার, হ্যান্ডরিটেন নোট,
                    বোর্ড প্রশ্ন ও সাজেশন্স সবার জন্য সম্পূর্ণ ফ্রি।
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <Compass class="h-5 w-5" />
                        </div>
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-white"
                        >
                            ১. কারিকুলাম ও গ্রুপ সিলেক্ট
                        </h3>
                        <p
                            class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            হোমপেজে গিয়ে আপনার কোর্স (HSC বা SSC) এবং গ্রুপ
                            (বিজ্ঞান, মানবিক, ব্যবসায় শিক্ষা) নির্বাচন করুন।
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                        >
                            <UserCheck class="h-5 w-5" />
                        </div>
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-white"
                        >
                            ২. ফ্রি অ্যাকাউন্ট লগইন
                        </h3>
                        <p
                            class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            নোট পড়ার জন্য অ্যাকাউন্টের বাধ্যবাধকতা নেই। তবে পড়ার
                            সময় ট্র্যাক করা, প্রশ্ন করা বা নোট শেয়ার করতে গুগল
                            দিয়ে লগইন করুন।
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                        >
                            <Zap class="h-5 w-5" />
                        </div>
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-white"
                        >
                            ৩. দ্রুত শর্টকাট নেভিগেশন
                        </h3>
                        <p
                            class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            মোবাইলে নিচের ন্যাভিগেশন বার এবং ডেস্কটপে সাইডবার
                            থেকে সরাসরি ফোরাম, ট্র্যাকার, লিডারবোর্ড ও নোটস
                            পেইজে যেতে পারবেন।
                        </p>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100 dark:border-gray-800" />

            <!-- 02. Finding Notes -->
            <section id="finding-notes" class="scroll-mt-32 space-y-5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >02</span
                    >
                    <h2
                        class="text-2xl font-black text-slate-900 dark:text-white"
                    >
                        নোট ও রিসোর্স ব্রাউজিং
                    </h2>
                </div>
                <p
                    class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-gray-300"
                >
                    আপনার সিলেবাসের প্রতিটি অধ্যায়ের নোট এবং ক্লাস সিরিজ সহজে
                    খুঁজে পাওয়ার কৌশল:
                </p>

                <div
                    class="space-y-4 rounded-2xl border border-slate-200/90 bg-white p-6 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-start gap-3.5">
                        <div
                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400"
                        >
                            <BookOpen class="h-4 w-4" />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold text-slate-900 dark:text-white"
                            >
                                চ্যাপ্টার অনুযায়ী নেভিগেশন (Subjects & Folders)
                            </h3>
                            <p
                                class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                হোমপেজ থেকে সাবজেক্ট কার্ডে ক্লিক করলেই প্রতিটি
                                পেপার এবং পেপারের ভেতরের অধ্যায়গুলো (Folders)
                                দেখতে পাবেন। যেকোনো অধ্যায়ে ক্লিক করলে তার
                                সবগুলো হ্যান্ডরিটেন পৃষ্ঠা, ভিডিও ক্লাস বা
                                পিডিএফ লোড হবে।
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-start gap-3.5 border-t border-slate-100 pt-4 dark:border-gray-800"
                    >
                        <div
                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400"
                        >
                            <Play class="h-4 w-4" />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold text-slate-900 dark:text-white"
                            >
                                ফুল-স্ক্রিন নোট রিডার ও ভিডিও প্লেয়ার
                            </h3>
                            <p
                                class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                হ্যান্ডরিটেন নোটে ক্লিক করলে ইন্টার‍্যাক্টিভ নোট
                                রিডারে ওপেন হবে। জুম ইন, ফুলস্ক্রিন, ডাউনলোড এবং
                                পরের পৃষ্ঠায় যাওয়ার মসৃণ অভিজ্ঞতা পাবেন। ভিডিও
                                প্লেয়ারে রয়েছে ডিস্ট্র্যাকশন-ফ্রি টিউটোরিয়াল
                                দেখার সুবিধা।
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-start gap-3.5 border-t border-slate-100 pt-4 dark:border-gray-800"
                    >
                        <div
                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400"
                        >
                            <Search class="h-4 w-4" />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold text-slate-900 dark:text-white"
                            >
                                সার্বিক সার্চ সুবিধা
                            </h3>
                            <p
                                class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                সার্চ বারে যেকোনো অধ্যায় বা টপিকের নাম লিখলেই
                                তাৎক্ষণিকভাবে সংশ্লিষ্ট নোটস ও রিসোর্স আপনার
                                সামনে চলে আসবে।
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100 dark:border-gray-800" />

            <!-- 03. Study Tracker -->
            <section id="study-tracker" class="scroll-mt-32 space-y-5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >03</span
                    >
                    <h2
                        class="text-2xl font-black text-slate-900 dark:text-white"
                    >
                        স্টাডি ট্র্যাকার ও ডেইলি স্ট্রাইক
                    </h2>
                </div>
                <p
                    class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-gray-300"
                >
                    নিজে পড়ার অভ্যাস ধরে রাখতে এবং নিয়মিত অগ্রগতি ট্র্যাক করতে
                    রয়েছে স্মার্ট স্টাডি ট্র্যাকার (<Link
                        href="/tracker"
                        class="font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                        >/tracker</Link
                    >):
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="flex items-center gap-2.5 font-bold text-slate-900 dark:text-white"
                        >
                            <Clock
                                class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                            />
                            <span>টাইমার বা স্টপওয়াচ চালু করুন</span>
                        </div>
                        <p
                            class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            কোন বিষয়ের কোন অধ্যায় পড়ছেন তা সিলেক্ট করে টাইমার অন
                            করে পড়তে বসুন। আপনার পড়ার সময় সেকেন্ডে সেকেন্ডে
                            নিখুঁতভাবে রেকর্ড হবে।
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="flex items-center gap-2.5 font-bold text-slate-900 dark:text-white"
                        >
                            <Flame class="h-4 w-4 text-amber-500" />
                            <span>স্টাডি হিটম্যাপ ও স্ট্রিক (Streak)</span>
                        </div>
                        <p
                            class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            গিটহাব স্টাইলের হিটম্যাপে সারা বছরের পড়াশোনার
                            তীব্রতা ফুটে উঠবে। প্রতিদিন পড়ার ধারাবাহিকতা বজায়
                            রাখলে স্ট্রিক বাড়তে থাকবে।
                        </p>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100 dark:border-gray-800" />

            <!-- 04. Forum Discussions -->
            <section id="forum-discussions" class="scroll-mt-32 space-y-5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >04</span
                    >
                    <h2
                        class="text-2xl font-black text-slate-900 dark:text-white"
                    >
                        প্রশ্ন ও কমিউনিটি ফোরাম
                    </h2>
                </div>
                <p
                    class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-gray-300"
                >
                    পড়াশোনার কোনো টপিক বুঝতে সমস্যা হলে বা কোনো ম্যাথ সলভে আটকে
                    গেলে সরাসরি ফোরামে (<Link
                        href="/forum"
                        class="font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                        >/forum</Link
                    >) শেয়ার করুন:
                </p>

                <div
                    class="space-y-3 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <ul
                        class="space-y-3 text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-gray-300"
                    >
                        <li class="flex items-start gap-2.5">
                            <span
                                class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400"
                            ></span>
                            <span
                                ><strong>ছবি ও গণিতসহ প্রশ্ন করুন:</strong>
                                খাতার জটিল সমীকরণ বা অংকের ছবি তুলে এক ক্লিকেই
                                ফোরামে প্রশ্ন পোস্ট করতে পারবেন।</span
                            >
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span
                                class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400"
                            ></span>
                            <span
                                ><strong>সহপাঠীদের সাহায্য করুন:</strong> অন্য
                                কারও প্রশ্নের সঠিক সমাধান জানা থাকলে উত্তর দিন।
                                ভালো উত্তরে আপভোট পাওয়ার মাধ্যমে আপনার প্রোফাইল
                                রেপুটেশন বৃদ্ধি পাবে।</span
                            >
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span
                                class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400"
                            ></span>
                            <span
                                ><strong>আলোচনা সেকশন:</strong> প্রতিটি নোটের
                                নিচে ডেডিকেটেড কমেন্ট সেকশন রয়েছে যেখানে নোট
                                সংক্রান্ত যেকোনো প্রশ্ন বা মতামত শেয়ার করা
                                যায়।</span
                            >
                        </li>
                    </ul>
                </div>
            </section>

            <hr class="border-slate-100 dark:border-gray-800" />

            <!-- 05. Global Chat -->
            <section id="global-chat" class="scroll-mt-32 space-y-5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >05</span
                    >
                    <h2
                        class="text-2xl font-black text-slate-900 dark:text-white"
                    >
                        লাইভ গ্লোবাল চ্যাট ও স্টাডি কমিউনিকেশন
                    </h2>
                </div>
                <p
                    class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-gray-300"
                >
                    দেশের যেকোনো প্রান্তের এইচএসসি ও এসএসসি সহপাঠীদের সাথে
                    তাৎক্ষণিক বার্তা আদান-প্রদান করতে পারেন প্ল্যাটফর্মের
                    গ্লোবাল চ্যাটে (<Link
                        href="/chat"
                        class="font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                        >/chat</Link
                    >):
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="flex items-center gap-2.5 font-bold text-slate-900 dark:text-white"
                        >
                            <MessageCircle
                                class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                            />
                            <span>রিয়েল-টাইম আলোচনা</span>
                        </div>
                        <p
                            class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            পরীক্ষার প্রস্তুতি, জটিল কনসেপ্ট বা পড়াশোনার ডেইলি
                            টিপস নিয়ে সক্রিয় শিক্ষার্থীদের সাথে সরাসরি গ্রুপ
                            চ্যাট করুন।
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="flex items-center gap-2.5 font-bold text-slate-900 dark:text-white"
                        >
                            <Zap class="h-4 w-4 text-amber-500" />
                            <span>কমিউনিটি নিয়মাবলী ও পরিবেশ</span>
                        </div>
                        <p
                            class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                        >
                            পড়াশোনাবান্ধব পরিবেশ বজায় রাখতে চ্যাটে শালীন ভাষা
                            ব্যবহার করুন এবং স্প্যামিং বা অনাকাঙ্ক্ষিত লিংক
                            শেয়ার করা থেকে বিরত থাকুন।
                        </p>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100 dark:border-gray-800" />

            <!-- 06. Educational Blogs -->
            <section id="blogs" class="scroll-mt-32 space-y-5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >06</span
                    >
                    <h2
                        class="text-2xl font-black text-slate-900 dark:text-white"
                    >
                        এডুকেশনাল ব্লগ ও স্টাডি গাইডলাইন
                    </h2>
                </div>
                <p
                    class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-gray-300"
                >
                    বোর্ড পরীক্ষা ও এডমিশন প্রস্তুতির জরুরি টিপস, স্ট্র্যাটেজি
                    এবং সেরা আর্টিকেল পড়ুন আমাদের ব্লগ সেকশনে (<Link
                        href="/blogs"
                        class="font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                        >/blogs</Link
                    >):
                </p>

                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <h3
                                class="flex items-center gap-2 text-xs font-bold tracking-wide text-slate-900 uppercase sm:text-sm dark:text-white"
                            >
                                <Newspaper
                                    class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                                />
                                <span>বিষয়ভিত্তিক গাইড ও বিশ্লেষণ</span>
                            </h3>
                            <p
                                class="text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                ফিজিক্স, কেমিস্ট্রি, বায়োলজি ও গণিতের মতো বিষয়ে
                                কীভাবে পূর্ণাঙ্গ প্রস্তুতি নেওয়া যায় এবং সাধারণ
                                ভুলগুলো এড়িয়ে চলা যায় তার বিশদ বিশ্লেষণ।
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h3
                                class="flex items-center gap-2 text-xs font-bold tracking-wide text-slate-900 uppercase sm:text-sm dark:text-white"
                            >
                                <Sparkles class="h-4 w-4 text-emerald-500" />
                                <span>এডমিশন ও পরীক্ষার কৌশল</span>
                            </h3>
                            <p
                                class="text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                অভিজ্ঞ মেন্টর ও সিনিয়রদের পরামর্শ, টাইম
                                ম্যানেজমেন্ট টেকনিক এবং মানসিকভাবে সুস্থ থেকে
                                পরীক্ষার সেরা প্রস্তুতি নেওয়ার উপায়।
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100 dark:border-gray-800" />

            <!-- 07. Leaderboard & Mutual -->
            <section id="leaderboard" class="scroll-mt-32 space-y-5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >07</span
                    >
                    <h2
                        class="text-2xl font-black text-slate-900 dark:text-white"
                    >
                        লিডারবোর্ড ও মিউচুয়াল ফিল্টার
                    </h2>
                </div>
                <p
                    class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-gray-300"
                >
                    লিডারবোর্ড (<Link
                        href="/leaderboard"
                        class="font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                        >/leaderboard</Link
                    >) দেশের শিক্ষার্থীদের সাথে আপনার পড়াশোনার সুস্থ প্রতিযোগিতা
                    ও কন্ট্রিবিউশন তুলে ধরে।
                </p>

                <div
                    class="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div
                            class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/50"
                        >
                            <h3
                                class="flex items-center gap-2 text-xs font-bold text-slate-900 sm:text-sm dark:text-white"
                            >
                                <Trophy class="h-4 w-4 text-amber-500" />
                                <span
                                    >শীর্ষ শিক্ষার্থী ও শীর্ষ কন্ট্রিবিউটর</span
                                >
                            </h3>
                            <p
                                class="mt-1 text-xs text-slate-500 dark:text-gray-400"
                            >
                                পড়ার সময়সীমা অনুযায়ী "শীর্ষ শিক্ষার্থী" এবং নোট
                                শেয়ার করার ভিত্তিতে "শীর্ষ কন্ট্রিবিউটর"
                                র‍্যাঙ্কিং দেখতে পারবেন।
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/50"
                        >
                            <h3
                                class="flex items-center gap-2 text-xs font-bold text-slate-900 sm:text-sm dark:text-white"
                            >
                                <SlidersHorizontal
                                    class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                                />
                                <span>কাস্টমাইজ ফিল্টার ও চয়েস মেমোরি</span>
                            </h3>
                            <p
                                class="mt-1 text-xs text-slate-500 dark:text-gray-400"
                            >
                                কারিকুলাম, বিভাগ বা ব্যাচ দিয়ে ফিল্টার করতে
                                পারেন। "আমার এই পছন্দটি মনে রাখুন" টিক দিয়ে
                                রাখলে প্রতিবার আপনার ব্যাচের র‍্যাঙ্কিং
                                স্বয়ংক্রিয়ভাবে দেখাবে।
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 dark:border-indigo-500/20 dark:bg-indigo-950/30"
                    >
                        <h4
                            class="text-xs font-bold text-indigo-950 uppercase dark:text-indigo-300"
                        >
                            💡 মিউচুয়াল (Mutual) সোশ্যাল স্কোপ কী?
                        </h4>
                        <p
                            class="mt-1 text-xs leading-relaxed text-indigo-900/90 dark:text-indigo-200/90"
                        >
                            লিডারবোর্ডের "মিউচুয়াল" সিলেক্ট করলে আপনার
                            এপ্রিশিয়েটর অথবা আপনি যাদের এপ্রিশিয়েট করছেন
                            (অনুসরণ করছেন) উভয় তালিকার বন্ধুরা একসাথে যুক্ত হয়ে
                            একটি ব্যক্তিগত ফ্রেন্ড সার্কেল লিডারবোর্ড তৈরি হবে।
                        </p>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100 dark:border-gray-800" />

            <!-- 08. Profile & Appreciations -->
            <section id="profile-network" class="scroll-mt-32 space-y-5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >08</span
                    >
                    <h2
                        class="text-2xl font-black text-slate-900 dark:text-white"
                    >
                        প্রোফাইল, অ্যাপ্রিসিয়েশন ও সোশ্যাল লিংকস
                    </h2>
                </div>
                <p
                    class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-gray-300"
                >
                    আপনার ব্যক্তিগত স্টুডেন্ট পোর্টফোলিও সাজিয়ে নিন:
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <Heart class="h-5 w-5 text-rose-500" />
                        <h3
                            class="mt-3 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            অ্যাপ্রিসিয়েশন নেটওয়ার্ক
                        </h3>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-gray-400"
                        >
                            সহপাঠীদের নোট বা পড়াশোনা ভালো লাগলে তাদের প্রোফাইলে
                            "Appreciate" দিন। উভয়পক্ষ যুক্ত হলে মিউচুয়াল স্টাডি
                            সার্কেল গড়ে উঠবে।
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <Share2 class="h-5 w-5 text-indigo-500" />
                        <h3
                            class="mt-3 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            সোশ্যাল ও ওয়েবসাইট লিংক
                        </h3>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-gray-400"
                        >
                            প্রোফাইল এডিট পেইজে আপনার ফেসবুক, ইনস্টাগ্রাম,
                            গিটহাব ও ব্যক্তিগত ওয়েবসাইটের লিংক যোগ করে সহপাঠীদের
                            সাথে কানেক্টেড থাকুন।
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                    >
                        <UserCheck class="h-5 w-5 text-emerald-500" />
                        <h3
                            class="mt-3 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            ভেরিফাইড ব্যাজ
                        </h3>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-gray-400"
                        >
                            বিশ্বস্ত কন্ট্রিবিউটর এবং শীর্ষ সহায়তাকারীদের
                            প্রোফাইলে ভেরিফাইড মেম্বার ব্যাজ প্রদর্শিত হয়।
                        </p>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100 dark:border-gray-800" />

            <!-- 09. Sharing Notes -->
            <section id="sharing-notes" class="scroll-mt-32 space-y-5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >09</span
                    >
                    <h2
                        class="text-2xl font-black text-slate-900 dark:text-white"
                    >
                        নোট ও রিসোর্স শেয়ার করবেন যেভাবে
                    </h2>
                </div>
                <p
                    class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-gray-300"
                >
                    আপনার নিজের তৈরি করা সুন্দর ক্লাস লেকচার বা হ্যান্ডরিটেন নোট
                    আপলোড করে দেশের হাজারো শিক্ষার্থীর উপকারে আসতে পারেন:
                </p>

                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white"
                                >১</span
                            >
                            <p
                                class="text-xs text-slate-700 sm:text-sm dark:text-gray-300"
                            >
                                <strong>নোট পেইজে যান:</strong>
                                <Link
                                    href="/notes"
                                    class="font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                                    >/notes</Link
                                >
                                পেইজে বা লিডারবোর্ডের "নোট শেয়ার করুন" বাটনে
                                ক্লিক করুন।
                            </p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white"
                                >২</span
                            >
                            <p
                                class="text-xs text-slate-700 sm:text-sm dark:text-gray-300"
                            >
                                <strong>অধ্যায় ও টাইটেল নির্ধারণ:</strong> কোন
                                বিষয়ের কোন অধ্যায়ের নোট তা সিলেক্ট করে স্পষ্ট
                                শিরোনাম দিন (যেমন:
                                <em
                                    >"ভৌত আলোকবিজ্ঞান - ব্যাতিচার ও অপবর্তন
                                    হ্যান্ডনোট"</em
                                >)।
                            </p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white"
                                >৩</span
                            >
                            <p
                                class="text-xs text-slate-700 sm:text-sm dark:text-gray-300"
                            >
                                <strong>ছবি বা ডকুমেন্ট আপলোড:</strong> খাতার
                                পৃষ্ঠার স্পষ্ট ছবি তুলে আপলোড করুন। সাবমিশনের পর
                                তা প্ল্যাটফর্মে যুক্ত হবে এবং কন্ট্রিবিউটর
                                লিডারবোর্ডে আপনার পয়েন্ট যোগ হবে।
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100 dark:border-gray-800" />

            <!-- 10. PWA App -->
            <section id="pwa-app" class="scroll-mt-32 space-y-5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >10</span
                    >
                    <h2
                        class="text-2xl font-black text-slate-900 dark:text-white"
                    >
                        মোবাইল অ্যাপ হিসেবে ব্যবহার (PWA)
                    </h2>
                </div>
                <p
                    class="text-sm leading-relaxed text-slate-600 sm:text-base dark:text-gray-300"
                >
                    কোনো ভারী অ্যাপ ডাউনলোড না করেই ক্রোম বা সাফারি ব্রাউজার
                    থেকে সরাসরি অ্যাপ হিসেবে ইনস্টল করে নেওয়া যায়:
                </p>

                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <h3
                                class="text-xs font-bold tracking-wide text-slate-900 uppercase sm:text-sm dark:text-white"
                            >
                                অ্যান্ড্রয়েড (Chrome):
                            </h3>
                            <p
                                class="text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                ব্রাউজারের উপরে ভেসে ওঠা
                                <strong>"অ্যাপ ইনস্টল করুন"</strong> বাটনে ক্লিক
                                করুন, অথবা থ্রি-ডট মেনু থেকে
                                <strong
                                    >"Add to Home Screen / Install app"</strong
                                >
                                চাপুন।
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h3
                                class="text-xs font-bold tracking-wide text-slate-900 uppercase sm:text-sm dark:text-white"
                            >
                                আইফোন (Safari):
                            </h3>
                            <p
                                class="text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                সাফারির নিচের শেয়ার আইকন (<Share2
                                    class="inline h-3.5 w-3.5"
                                />) ট্যাপ করে একটু নিচে স্ক্রল করে
                                <strong>"Add to Home Screen"</strong> অপশনে চাপ
                                দিন।
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Need Help Card -->
            <div
                class="rounded-3xl border border-slate-200/90 bg-gradient-to-br from-slate-900 to-indigo-950 p-6 text-center text-white shadow-lg sm:p-10 dark:border-gray-800"
            >
                <div
                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-indigo-300 backdrop-blur-md"
                >
                    <HelpCircle class="h-6 w-6" />
                </div>
                <h3 class="text-lg font-bold sm:text-2xl">
                    কোনো সাহায্য বা প্রশ্ন আছে?
                </h3>
                <p
                    class="mx-auto mt-2 max-w-lg text-xs leading-relaxed text-slate-300 sm:text-sm"
                >
                    প্ল্যাটফর্ম ব্যবহারে কোনো টেকনিক্যাল সমস্যা হলে বা যেকোনো
                    ফিডব্যাক জানাতে সরাসরি আমাদের ফেসবুক পেজে মেসেজ করতে পারেন।
                </p>
                <div
                    class="mt-6 flex flex-wrap items-center justify-center gap-3"
                >
                    <a
                        href="https://facebook.com/hscstackbd"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-500 active:scale-95"
                    >
                        <MessageCircle class="h-4 w-4" />
                        <span>ফেসবুকে যোগাযোগ করুন</span>
                    </a>
                    <Link
                        href="/forum"
                        class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-2.5 text-xs font-bold text-white backdrop-blur-md transition hover:bg-white/20 active:scale-95"
                    >
                        <MessageSquare class="h-4 w-4" />
                        <span>ফোরামে প্রশ্ন করুন</span>
                    </Link>
                </div>
            </div>
        </div>
    </main>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
