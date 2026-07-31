<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    UserPlus,
    CheckCircle2,
    ShieldAlert,
    HelpCircle,
    MessageCircle,
    Layers,
} from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';

const quickLinks = [
    { num: '01', id: 'getting-started', label: 'Start Here' },
    { num: '02', id: 'dashboard', label: 'Dashboard' },
    { num: '03', id: 'manage-contents', label: 'Manage Contents' },
    { num: '04', id: 'manage-blogs', label: 'Blogs' },
    { num: '05', id: 'site-notice', label: 'Site Notice' },
    { num: '06', id: 'users', label: 'Users' },
    { num: '07', id: 'my-profile', label: 'My Profile' },
    { num: '08', id: 'troubleshooting', label: 'Toast Messages' },
];

const activeId = ref('getting-started');
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
            // Adjusted target box to match sticky header offset
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
    <header class="mx-auto max-w-4xl px-4 pt-8 pb-4 text-center sm:pt-12">
        <Link
            href="/"
            class="group mb-4 inline-flex items-center gap-2 text-sm font-bold text-slate-500 transition-colors hover:text-indigo-600"
        >
            <ArrowLeft
                class="h-4 w-4 transition-transform group-hover:-translate-x-1"
            />
            Back to Home
        </Link>
        <h1
            class="mb-3 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl"
        >
            Contributor <span class="text-indigo-600">Guide</span>
        </h1>
        <p
            class="mx-auto max-w-lg text-xs font-bold tracking-widest text-slate-500 uppercase sm:text-sm"
        >
            Content Management-এর ধাপে ধাপে সম্পূর্ণ নির্দেশিকা
        </p>
    </header>

    <!-- Sticky Navigation Access Bar -->
    <nav class="sticky top-4 z-30 mx-auto mb-10 max-w-4xl px-4">
        <div
            class="rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg shadow-slate-200/50 backdrop-blur-md"
        >
            <div
                class="mb-2.5 flex items-center justify-between gap-2 border-b border-slate-100 px-1 pb-2"
            >
                <div
                    class="flex items-center gap-2 text-xs font-extrabold tracking-wider text-slate-900 uppercase"
                >
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-600 text-white"
                    >
                        <Layers class="h-3.5 w-3.5" />
                    </div>
                    <span>Quick Navigation Index</span>
                </div>
                <span class="text-[11px] font-bold text-slate-400"
                    >8 Chapters</span
                >
            </div>

            <div
                class="no-scrollbar flex items-center gap-2 overflow-x-auto scroll-smooth pb-1"
            >
                <button
                    v-for="link in quickLinks"
                    :key="link.id"
                    :id="`nav-pill-${link.id}`"
                    @click="scrollToSection(link.id)"
                    :class="[
                        'group flex shrink-0 items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-bold transition-all active:scale-95',
                        activeId === link.id
                            ? 'border-indigo-600 bg-indigo-50 text-indigo-700 shadow-xs'
                            : 'border-slate-200 bg-slate-50/80 text-slate-800 hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-700',
                    ]"
                >
                    <span
                        :class="[
                            'flex h-5 w-5 items-center justify-center rounded-md text-[10px] font-black transition-colors',
                            activeId === link.id
                                ? 'bg-indigo-600 text-white'
                                : 'border border-slate-200 bg-white text-indigo-600 group-hover:border-indigo-600 group-hover:bg-indigo-600 group-hover:text-white',
                        ]"
                    >
                        {{ link.num }}
                    </span>
                    <span class="whitespace-nowrap">{{ link.label }}</span>
                </button>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-4xl px-4 pb-24 sm:px-6">
        <div
            class="space-y-12 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-10"
        >
            <!-- Section 01: Welcome & Getting Started -->
            <section id="getting-started" class="scroll-mt-44 space-y-5">
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-100 px-2.5 py-1 text-xs font-black text-indigo-700"
                        >01</span
                    >
                    <h2
                        class="text-xl font-black tracking-tight text-slate-950 sm:text-2xl"
                    >
                        Welcome to the Team!
                    </h2>
                </div>
                <p
                    class="text-sm leading-relaxed font-normal text-slate-600 sm:text-base"
                >
                    <strong class="font-bold text-slate-900">HSCStack</strong>
                    -কে আরও গোছানো এবং শিক্ষার্থীদের জন্য আরও উপকারী করে তুলতে
                    আপনার অবদানের জন্য ধন্যবাদ। এই গাইডে আমাদের admin panel
                    কীভাবে কাজ করে তা সহজ ভাষায় ব্যাখ্যা করা হয়েছে। এটি বুঝতে
                    কোনো technical জ্ঞানের প্রয়োজন নেই।
                </p>

                <div
                    class="flex flex-col items-start justify-between gap-4 rounded-xl border border-indigo-100 bg-indigo-50/70 p-4 sm:flex-row sm:items-center sm:p-6"
                >
                    <div class="flex items-start gap-3.5">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm sm:h-11 sm:w-11"
                        >
                            <UserPlus class="h-5 w-5 stroke-[2.2]" />
                        </div>
                        <div>
                            <h3
                                class="text-xs font-bold tracking-wide text-indigo-950 uppercase sm:text-sm"
                            >
                                Become an Official Contributor
                            </h3>
                            <p
                                class="mt-1 text-xs leading-relaxed font-medium text-indigo-800/90 sm:text-sm"
                            >
                                Admin panel ব্যবহার করার জন্য আপনার একটি
                                approved contributor account থাকা আবশ্যক।
                            </p>
                        </div>
                    </div>
                    <Link
                        href="/join"
                        class="w-full shrink-0 rounded-xl bg-indigo-600 px-5 py-2.5 text-center text-xs font-bold text-white shadow-sm transition-colors hover:bg-indigo-500 sm:w-auto sm:text-sm"
                    >
                        Apply Here
                    </Link>
                </div>

                <p
                    class="text-sm leading-relaxed font-normal text-slate-600 sm:text-base"
                >
                    আপনার account approve হওয়ার পর, আপনি কী ধরনের কাজ করবেন তার
                    ভিত্তিতে আপনাকে একটি নির্দিষ্ট role প্রদান করা হবে, এবং সেই
                    সাথে আপনার ব্যক্তিগত login credentials (email ও password)
                    পাঠানো হবে।
                </p>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 02: Dashboard -->
            <section
                id="dashboard"
                class="flex scroll-mt-44 items-start gap-4 sm:gap-5"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-black text-indigo-600 sm:h-11 sm:w-11"
                >
                    02
                </div>
                <div class="space-y-2">
                    <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                        Dashboard (Overview)
                    </h3>
                    <p
                        class="text-sm leading-relaxed font-normal text-slate-600 sm:text-base"
                    >
                        Login করার পর এটিই হবে আপনার প্রথম screen। এখানে visitor
                        traffic এবং ওয়েবসাইটের বিভিন্ন basic statistics দেখতে
                        পাবেন।
                    </p>
                    <p
                        class="block rounded-lg border border-slate-100 bg-slate-50 p-3 text-xs font-semibold text-slate-500 italic sm:text-sm"
                    >
                        💡 Note: এখানে আপনার কোনো action নেওয়ার প্রয়োজন নেই।
                        এটি শুধু statistics দেখার জন্য।
                    </p>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 03: Manage Contents -->
            <section
                id="manage-contents"
                class="flex scroll-mt-44 items-start gap-4 sm:gap-5"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-black text-indigo-600 sm:h-11 sm:w-11"
                >
                    03
                </div>
                <div class="space-y-4">
                    <div>
                        <h3
                            class="text-base font-bold text-slate-900 sm:text-lg"
                        >
                            Manage Contents
                        </h3>
                        <p
                            class="mt-1 text-sm leading-relaxed font-normal text-slate-600 sm:text-base"
                        >
                            আপনি সবচেয়ে বেশি সময় এই section-এ কাজ করবেন। এখানে
                            <strong>Subjects</strong>, <strong>Folders</strong>,
                            এবং individual <strong>Resources</strong> manage করা
                            হয়।
                        </p>
                    </div>

                    <div
                        class="space-y-4 border-l-2 border-indigo-100 pl-3 sm:pl-6"
                    >
                        <div
                            class="space-y-2 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 sm:p-5"
                        >
                            <h4
                                class="text-xs font-black tracking-wider text-indigo-600 uppercase"
                            >
                                Top Level: Subject
                            </h4>
                            <p
                                class="text-sm leading-relaxed font-normal text-slate-700 sm:text-base"
                            >
                                Subject বলতে মূল academic course বোঝায়, যেমন:
                                <strong class="text-slate-900"
                                    >Bangla 1st Paper</strong
                                >
                                অথবা
                                <strong class="text-slate-900"
                                    >English 1st Paper</strong
                                >।
                            </p>
                            <div
                                class="rounded-lg border border-amber-200/60 bg-amber-50 p-3 text-xs leading-relaxed font-medium text-amber-900 sm:text-sm"
                            >
                                <strong>Important Rule:</strong> প্রতিটি
                                paper-এর জন্য আলাদা Subject তৈরি করুন। (যেমন:
                                Bangla 1st Paper-এর জন্য একটি Subject এবং Bangla
                                2nd Paper-এর জন্য সম্পূর্ণ আলাদা একটি Subject
                                থাকবে।) দুটি একসাথে রাখলে শিক্ষার্থীদের বুঝতে
                                অসুবিধা হতে পারে ।
                            </div>
                        </div>

                        <div
                            class="space-y-2 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 sm:p-5"
                        >
                            <h4
                                class="text-xs font-black tracking-wider text-indigo-600 uppercase"
                            >
                                Second Level: Folders
                            </h4>
                            <p
                                class="text-sm leading-relaxed font-normal text-slate-700 sm:text-base"
                            >
                                একটি Subject-এর ভেতরে Folder তৈরি করা যায়।
                                এক্ষেত্রে Folder-কে
                                <strong>Chapter</strong> হিসেবে ভাবতে পারেন
                                (উদাহরণ:
                                <em
                                    >Bangla 1st Paper → বই পড়া প্রবন্ধ
                                    Folder</em
                                >)।
                            </p>
                            <p
                                class="text-xs leading-relaxed font-normal text-slate-600 sm:text-sm"
                            >
                                নতুন Folder তৈরি করতে
                                <strong class="text-slate-900"
                                    >"Add Folder"</strong
                                >
                                বাটন ব্যবহার করুন।
                            </p>
                        </div>

                        <div
                            class="space-y-3 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 sm:p-5"
                        >
                            <h4
                                class="text-xs font-black tracking-wider text-indigo-600 uppercase"
                            >
                                Folder-এর ভেতরে: Resources
                            </h4>
                            <p
                                class="text-sm leading-relaxed font-normal text-slate-700 sm:text-base"
                            >
                                Resource হলো আসল study material, যা শিক্ষার্থীরা
                                ব্যবহার করবে। যেমন: একটি handwritten note-এর ১টি
                                page = ১টি Resource। সব নোট বা ক্লাস একসাথে
                                আপলোড না করে, ফোল্ডার অনুযায়ী ভাগ করে আপলোড
                                করুন।
                            </p>

                            <p
                                class="text-xs font-bold text-slate-800 sm:text-sm"
                            >
                                Supported Resource Types:
                            </p>
                            <ul
                                class="space-y-2 text-xs font-normal text-slate-600 sm:text-sm"
                            >
                                <li class="flex items-start gap-2">
                                    <span
                                        class="mt-0.5 shrink-0 rounded bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-700"
                                        >Image</span
                                    >
                                    <span
                                        >Handwritten note বা photo page-এর জন্য
                                        single image upload।</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="mt-0.5 shrink-0 rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700"
                                        >Video</span
                                    >
                                    <span
                                        >শুধু YouTube video link সাপোর্ট করা
                                        হয়।</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="mt-0.5 shrink-0 rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700"
                                        >PDF</span
                                    >
                                    <span
                                        >এমন direct link যা
                                        <code
                                            class="rounded bg-slate-200 px-1 font-mono text-slate-800"
                                            >.pdf</code
                                        >
                                        দিয়ে শেষ হয়।</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="mt-0.5 shrink-0 rounded bg-purple-100 px-2 py-0.5 text-xs font-bold text-purple-700"
                                        >Text Note</span
                                    >
                                    <span
                                        >লিখিত announcement বা গুরুত্বপূর্ণ text
                                        note-এর জন্য।</span
                                    >
                                </li>
                            </ul>
                            <p
                                class="text-sm leading-relaxed font-normal text-slate-700 sm:text-base"
                            >
                                দ্রুত content management-এর জন্য Bulk Upload
                                Tools ব্যবহার করতে পারেন:
                            </p>

                            <ul
                                class="space-y-2 text-xs font-normal text-slate-600 sm:text-sm"
                            >
                                <li class="flex items-start gap-2">
                                    <span
                                        class="mt-0.5 shrink-0 rounded bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-700"
                                        >Bulk Images</span
                                    >
                                    <span
                                        >একসাথে অনেকগুলো image page আপলোড করতে
                                        পারবেন, ফলে প্রতিটি image আলাদাভাবে add
                                        করার প্রয়োজন হবে না। শিক্ষার্থীদের
                                        confusion এড়াতে "auto serial number"
                                        অপশনটি ব্যবহার করুন, যাতে image-গুলো
                                        সঠিক ক্রমে নামকরণ হয়।</span
                                    >
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="mt-0.5 shrink-0 rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700"
                                        >Bulk Videos</span
                                    >
                                    <span
                                        >YouTube playlist থেকে সব video
                                        স্বয়ংক্রিয়ভাবে import করতে পারবেন, ফলে
                                        প্রতিটি video link ম্যানুয়ালি add করতে
                                        হবে না।</span
                                    >
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 04: Manage Blogs -->
            <section
                id="manage-blogs"
                class="flex scroll-mt-44 items-start gap-4 sm:gap-5"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-black text-indigo-600 sm:h-11 sm:w-11"
                >
                    04
                </div>
                <div class="space-y-2">
                    <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                        Manage Blogs
                    </h3>
                    <p
                        class="text-sm leading-relaxed font-normal text-slate-600 sm:text-base"
                    >
                        যেসব contributor-কে blog লেখার দায়িত্ব দেওয়া হয়েছে,
                        তারা
                        <strong class="text-slate-900">"Create Blog"</strong>
                        ব্যবহার করে article publish করতে পারবেন।
                    </p>
                    <p
                        class="text-xs leading-relaxed font-normal text-slate-600 sm:text-sm"
                    >
                        Editor-এর উপরের toolbar ব্যবহার করে লেখা ফরম্যাট করুন:
                    </p>
                    <div
                        class="flex flex-wrap gap-2 text-xs font-semibold text-slate-700"
                    >
                        <span
                            class="rounded border border-slate-200 bg-slate-50 px-2 py-1"
                            ><strong>B</strong> = Bold</span
                        >
                        <span
                            class="rounded border border-slate-200 bg-slate-50 px-2 py-1"
                            ><em>I</em> = Italic</span
                        >
                        <span
                            class="rounded border border-slate-200 bg-slate-50 px-2 py-1"
                            ><strong>H1</strong> = Big Title</span
                        >
                        <span
                            class="rounded border border-slate-200 bg-slate-50 px-2 py-1"
                            ><strong>H2 / H3</strong> = Subheadings</span
                        >
                    </div>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 05: Site Notice -->
            <section
                id="site-notice"
                class="flex scroll-mt-44 items-start gap-4 sm:gap-5"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-sm font-black text-amber-700 sm:h-11 sm:w-11"
                >
                    05
                </div>
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3
                            class="text-base font-bold text-slate-900 sm:text-lg"
                        >
                            Site Notice
                        </h3>
                        <span
                            class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold tracking-wider text-amber-800 uppercase sm:text-xs"
                            >শুধুমাত্র Admin-এর জন্য</span
                        >
                    </div>
                    <p
                        class="text-sm leading-relaxed font-normal text-slate-600 sm:text-base"
                    >
                        Public landing page-এর broadcast banner manage করার জন্য
                        এটি ব্যবহার করা হয়। এই section শুধুমাত্র senior
                        administrator-দের জন্য সংরক্ষিত।
                    </p>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 06: Users -->
            <section
                id="users"
                class="flex scroll-mt-44 items-start gap-4 sm:gap-5"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-sm font-black text-amber-700 sm:h-11 sm:w-11"
                >
                    06
                </div>
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3
                            class="text-base font-bold text-slate-900 sm:text-lg"
                        >
                            User Management
                        </h3>
                        <span
                            class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold tracking-wider text-amber-800 uppercase sm:text-xs"
                            >শুধুমাত্র Admin-এর জন্য</span
                        >
                    </div>
                    <p
                        class="text-sm leading-relaxed font-normal text-slate-600 sm:text-base"
                    >
                        Senior administrator-রা এখান থেকে user account manage
                        করতে এবং permission role assign করতে পারবেন।
                    </p>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 07: My Profile -->
            <section
                id="my-profile"
                class="flex scroll-mt-44 items-start gap-4 sm:gap-5"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-black text-indigo-600 sm:h-11 sm:w-11"
                >
                    07
                </div>
                <div class="space-y-2">
                    <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                        My Profile & Account Settings
                    </h3>
                    <p
                        class="text-sm leading-relaxed font-normal text-slate-600 sm:text-base"
                    >
                        যেকোনো সময় আপনি আপনার account details update করতে
                        পারবেন। Profile photo-এর জন্য Imgur বা PostImages-এর মতো
                        কোনো service-এ ছবি আপলোড করে, সেই direct link আপনার
                        profile-এ ব্যবহার করুন।
                    </p>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 08: Troubleshooting -->
            <section id="troubleshooting" class="scroll-mt-44 space-y-4">
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-100 px-2.5 py-1 text-xs font-black text-indigo-700"
                        >08</span
                    >
                    <h2
                        class="flex items-center gap-2 text-lg font-black tracking-tight text-slate-950 sm:text-xl"
                    >
                        Toast Notifications & Errors
                    </h2>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div
                        class="space-y-1 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4"
                    >
                        <div
                            class="flex items-center gap-2 text-xs font-bold text-emerald-900 sm:text-sm"
                        >
                            <CheckCircle2
                                class="h-4 w-4 shrink-0 text-emerald-600"
                            />
                            <span>"Success"</span>
                        </div>
                        <p
                            class="text-xs leading-relaxed font-medium text-emerald-800/90"
                        >
                            আপনার changes সফলভাবে save হয়েছে এবং এখন live আছে।
                        </p>
                    </div>

                    <div
                        class="space-y-1 rounded-xl border border-rose-200 bg-rose-50/50 p-4"
                    >
                        <div
                            class="flex items-center gap-2 text-xs font-bold text-rose-900 sm:text-sm"
                        >
                            <ShieldAlert
                                class="h-4 w-4 shrink-0 text-rose-600"
                            />
                            <span>"Not enough permission..."</span>
                        </div>
                        <p
                            class="text-xs leading-relaxed font-medium text-rose-800/90"
                        >
                            এই কাজের জন্য প্রয়োজনীয় permission আপনার account-এ
                            নেই। Access পাওয়ার জন্য Admin-এর সাথে যোগাযোগ করুন।
                        </p>
                    </div>
                </div>
            </section>

            <!-- Support Footer -->
            <div
                class="space-y-3 rounded-xl bg-slate-900 p-6 text-center text-white sm:p-8"
            >
                <div
                    class="mb-1 inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-800 text-emerald-400"
                >
                    <HelpCircle class="h-5 w-5" />
                </div>
                <h3 class="text-base font-bold sm:text-lg">
                    Help বা বিশেষ Access প্রয়োজন?
                </h3>
                <p
                    class="mx-auto max-w-md text-xs leading-relaxed font-normal text-slate-300 sm:text-sm"
                >
                    কোনো সমস্যায় পড়লে বা কোনো specific section manage করার
                    permission প্রয়োজন হলে, সরাসরি WhatsApp-এ যোগাযোগ করুন।
                </p>
                <div class="pt-2">
                    <a
                        href="https://wa.me/8801909131512"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-xs font-bold text-slate-950 shadow-sm transition-colors hover:bg-emerald-400 sm:w-auto sm:text-sm"
                    >
                        <MessageCircle class="h-4 w-4" />
                        Chat on WhatsApp
                    </a>
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
