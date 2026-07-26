<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    UserPlus,
    CheckCircle2,
    ShieldAlert,
    HelpCircle,
    MessageCircle,
    Layers
} from 'lucide-vue-next';

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
            block: 'nearest'
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

    if (clickTimeout) clearTimeout(clickTimeout);
    clickTimeout = setTimeout(() => {
        isManualClick = false;
    }, 800);
};

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (isManualClick) return;

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
            threshold: 0
        }
    );

    quickLinks.forEach((link) => {
        const el = document.getElementById(link.id);
        if (el && observer) observer.observe(el);
    });
});

onUnmounted(() => {
    if (observer) observer.disconnect();
    if (clickTimeout) clearTimeout(clickTimeout);
});
</script>

<template>
    <header class="mx-auto max-w-4xl px-4 pt-8 pb-4 text-center sm:pt-12">
        <Link
            href="/"
            class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-indigo-600 transition-colors mb-4 group"
        >
            <ArrowLeft class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
            Back to Home
        </Link>
        <h1 class="mb-3 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl">
            Contributor <span class="text-indigo-600">Guide</span>
        </h1>
        <p class="mx-auto max-w-lg text-xs sm:text-sm font-bold text-slate-500 uppercase tracking-widest">
            A Step-by-Step Manual for Managing Content
        </p>
    </header>

    <!-- Sticky Navigation Access Bar -->
    <nav class="sticky top-4 z-30 mx-auto max-w-4xl px-4 mb-10">
        <div class="rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg shadow-slate-200/50 backdrop-blur-md">

            <div class="flex items-center justify-between gap-2 mb-2.5 px-1 pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2 text-slate-900 font-extrabold text-xs tracking-wider uppercase">
                    <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-600 text-white">
                        <Layers class="h-3.5 w-3.5" />
                    </div>
                    <span>Quick Navigation Index</span>
                </div>
                <span class="text-[11px] font-bold text-slate-400">8 Chapters</span>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar scroll-smooth">
                <button
                    v-for="link in quickLinks"
                    :key="link.id"
                    :id="`nav-pill-${link.id}`"
                    @click="scrollToSection(link.id)"
                    :class="[
                        'group flex shrink-0 items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-bold transition-all active:scale-95',
                        activeId === link.id
                            ? 'border-indigo-600 bg-indigo-50 text-indigo-700 shadow-xs'
                            : 'border-slate-200 bg-slate-50/80 text-slate-800 hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-700'
                    ]"
                >
                    <span
                        :class="[
                            'flex h-5 w-5 items-center justify-center rounded-md text-[10px] font-black transition-colors',
                            activeId === link.id
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white border border-slate-200 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white group-hover:border-indigo-600'
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
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-10 space-y-12">

            <!-- Section 01: Welcome & Getting Started -->
            <section id="getting-started" class="scroll-mt-44 space-y-5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center rounded-lg bg-indigo-100 px-2.5 py-1 text-xs font-black text-indigo-700">01</span>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-950 tracking-tight">Welcome to the Team!</h2>
                </div>
                <p class="text-sm sm:text-base font-normal leading-relaxed text-slate-600">
                    Thank you for helping us keep <strong class="font-bold text-slate-900">HSCStack</strong> organized and helpful for students. This guide will walk you through how our admin panel works in simple, easy-to-understand language. No technical background is needed!
                </p>

                <div class="rounded-xl bg-indigo-50/70 border border-indigo-100 p-4 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm">
                            <UserPlus class="h-5 w-5 stroke-[2.2]" />
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wide text-indigo-950">Become an Official Contributor</h3>
                            <p class="mt-1 text-xs sm:text-sm font-medium text-indigo-800/90 leading-relaxed">
                                You must have an approved contributor account to access the admin panel.
                            </p>
                        </div>
                    </div>
                    <Link
                        href="/join"
                        class="shrink-0 w-full sm:w-auto text-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm hover:bg-indigo-500 transition-colors"
                    >
                        Apply Here
                    </Link>
                </div>

                <p class="text-sm sm:text-base font-normal leading-relaxed text-slate-600">
                    Once our team approves your request, we will assign you a specific role based on what you’ll be doing, and send you your private login credentials (username and password).
                </p>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 02: Dashboard -->
            <section id="dashboard" class="scroll-mt-44 flex gap-4 sm:gap-5 items-start">
                <div class="flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 font-black text-sm">
                    02
                </div>
                <div class="space-y-2">
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">Dashboard (Overview)</h3>
                    <p class="text-sm sm:text-base font-normal leading-relaxed text-slate-600">
                        This is your main screen right after logging in. It displays basic statistics, such as visitor traffic and quick numbers about the site.
                    </p>
                    <p class="text-xs sm:text-sm font-semibold text-slate-500 italic bg-slate-50 border border-slate-100 rounded-lg p-3 block">
                        💡 Note: You don't need to perform any actions here. It is just for viewing stats.
                    </p>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 03: Manage Contents -->
            <section id="manage-contents" class="scroll-mt-44 flex gap-4 sm:gap-5 items-start">
                <div class="flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 font-black text-sm">
                    03
                </div>
                <div class="space-y-4">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">Manage Contents</h3>
                        <p class="mt-1 text-sm sm:text-base font-normal leading-relaxed text-slate-600">
                            This is where you will spend most of your time. This section is used to manage <strong>Subjects</strong>, <strong>Folders</strong>, and individual <strong>Resources</strong>.
                        </p>
                    </div>

                    <div class="space-y-4 border-l-2 border-indigo-100 pl-3 sm:pl-6">
                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 sm:p-5 space-y-2">
                            <h4 class="text-xs font-black text-indigo-600 uppercase tracking-wider">Top Level: Subject</h4>
                            <p class="text-sm sm:text-base font-normal text-slate-700 leading-relaxed">
                                Subjects represent main academic courses like <strong class="text-slate-900">Bangla 1st Paper</strong> or <strong class="text-slate-900">English 1st Paper</strong>.
                            </p>
                            <div class="rounded-lg bg-amber-50 border border-amber-200/60 p-3 text-xs sm:text-sm text-amber-900 font-medium leading-relaxed">
                                <strong>Important Rule:</strong> Always create separate subjects for separate papers (e.g., create one for "Bangla 1st Paper" and another for "Bangla 2nd Paper"). Combining them into one makes navigation confusing for students.
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 sm:p-5 space-y-2">
                            <h4 class="text-xs font-black text-indigo-600 uppercase tracking-wider">Second Level: Folders</h4>
                            <p class="text-sm sm:text-base font-normal text-slate-700 leading-relaxed">
                                Inside a subject, you can create folders. Think of top-level folders as <strong>Chapters</strong> (for example: <em>Bangla 1st Paper → Prottupokar Chapter</em>).
                            </p>
                            <p class="text-xs sm:text-sm font-normal text-slate-600 leading-relaxed">
                                You can create sub-folders inside folders if needed. Click the <strong class="text-slate-900">"Add Folder"</strong> button to make a new one. If you don't see this button, ask an Admin to grant access.
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 sm:p-5 space-y-3">
                            <h4 class="text-xs font-black text-indigo-600 uppercase tracking-wider">Inside Folders: Resources</h4>
                            <p class="text-sm sm:text-base font-normal text-slate-700 leading-relaxed">
                                Resources are the actual study materials stored inside a folder (e.g., 1 page of a note = 1 resource).
                            </p>

                            <p class="text-xs sm:text-sm font-bold text-slate-800">Supported Resource Types:</p>
                            <ul class="space-y-2 text-xs sm:text-sm font-normal text-slate-600">
                                <li class="flex items-start gap-2">
                                    <span class="rounded bg-indigo-100 text-indigo-700 px-2 py-0.5 text-xs font-bold shrink-0 mt-0.5">Image</span>
                                    <span>Single file upload for handwritten notes or photo pages.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="rounded bg-red-100 text-red-700 px-2 py-0.5 text-xs font-bold shrink-0 mt-0.5">Video</span>
                                    <span>Only YouTube video links are supported.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="rounded bg-emerald-100 text-emerald-700 px-2 py-0.5 text-xs font-bold shrink-0 mt-0.5">PDF</span>
                                    <span>Direct links ending with <code class="bg-slate-200 px-1 rounded font-mono text-slate-800">.pdf</code>.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="rounded bg-purple-100 text-purple-700 px-2 py-0.5 text-xs font-bold shrink-0 mt-0.5">Text Note</span>
                                    <span>Written announcements or important text notes.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 04: Manage Blogs -->
            <section id="manage-blogs" class="scroll-mt-44 flex gap-4 sm:gap-5 items-start">
                <div class="flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 font-black text-sm">
                    04
                </div>
                <div class="space-y-2">
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">Manage Blogs</h3>
                    <p class="text-sm sm:text-base font-normal leading-relaxed text-slate-600">
                        Assigned blog writers can click <strong class="text-slate-900">"Create Blog"</strong> to publish articles.
                    </p>
                    <p class="text-xs sm:text-sm font-normal text-slate-600 leading-relaxed">
                        Use the toolbar at the top of the editor to format your text:
                    </p>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold text-slate-700">
                        <span class="border border-slate-200 rounded px-2 py-1 bg-slate-50"><strong>B</strong> = Bold</span>
                        <span class="border border-slate-200 rounded px-2 py-1 bg-slate-50"><em>I</em> = Italic</span>
                        <span class="border border-slate-200 rounded px-2 py-1 bg-slate-50"><strong>H1</strong> = Big Title</span>
                        <span class="border border-slate-200 rounded px-2 py-1 bg-slate-50"><strong>H2 / H3</strong> = Subheadings</span>
                    </div>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 05: Site Notice -->
            <section id="site-notice" class="scroll-mt-44 flex gap-4 sm:gap-5 items-start">
                <div class="flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 font-black text-sm">
                    05
                </div>
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">Site Notice</h3>
                        <span class="rounded bg-amber-100 px-2 py-0.5 text-[10px] sm:text-xs font-bold uppercase tracking-wider text-amber-800">Admin Only</span>
                    </div>
                    <p class="text-sm sm:text-base font-normal leading-relaxed text-slate-600">
                        Controls broadcast banners on the public landing page. Reserved for senior administrators.
                    </p>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 06: Users -->
            <section id="users" class="scroll-mt-44 flex gap-4 sm:gap-5 items-start">
                <div class="flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 font-black text-sm">
                    06
                </div>
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">User Management</h3>
                        <span class="rounded bg-amber-100 px-2 py-0.5 text-[10px] sm:text-xs font-bold uppercase tracking-wider text-amber-800">Admin Only</span>
                    </div>
                    <p class="text-sm sm:text-base font-normal leading-relaxed text-slate-600">
                        Allows team leaders to manage user accounts and assign permission roles.
                    </p>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 07: My Profile -->
            <section id="my-profile" class="scroll-mt-44 flex gap-4 sm:gap-5 items-start">
                <div class="flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 font-black text-sm">
                    07
                </div>
                <div class="space-y-2">
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">My Profile & Account Settings</h3>
                    <p class="text-sm sm:text-base font-normal leading-relaxed text-slate-600">
                        Update your account details anytime. For profile photos, upload to a service like Imgur/PostImages and paste the direct link into your profile.
                    </p>
                </div>
            </section>

            <hr class="border-slate-100" />

            <!-- Section 08: Troubleshooting -->
            <section id="troubleshooting" class="scroll-mt-44 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center rounded-lg bg-indigo-100 px-2.5 py-1 text-xs font-black text-indigo-700">08</span>
                    <h2 class="text-lg sm:text-xl font-black text-slate-950 tracking-tight flex items-center gap-2">
                        Toast Notifications & Errors
                    </h2>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 space-y-1">
                        <div class="flex items-center gap-2 text-emerald-900 font-bold text-xs sm:text-sm">
                            <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                            <span>"Success"</span>
                        </div>
                        <p class="text-xs text-emerald-800/90 font-medium leading-relaxed">
                            Your changes saved properly and are now live.
                        </p>
                    </div>

                    <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-4 space-y-1">
                        <div class="flex items-center gap-2 text-rose-900 font-bold text-xs sm:text-sm">
                            <ShieldAlert class="h-4 w-4 text-rose-600 shrink-0" />
                            <span>"Not enough permission..."</span>
                        </div>
                        <p class="text-xs text-rose-800/90 font-medium leading-relaxed">
                            Your account is missing permission. Contact an admin to request access.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Support Footer -->
            <div class="rounded-xl bg-slate-900 p-6 sm:p-8 text-center text-white space-y-3">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-800 text-emerald-400 mb-1">
                    <HelpCircle class="h-5 w-5" />
                </div>
                <h3 class="text-base sm:text-lg font-bold">Need Help or Special Access?</h3>
                <p class="text-xs sm:text-sm text-slate-300 max-w-md mx-auto font-normal leading-relaxed">
                    If you get stuck or need permission to manage a specific section, reach out directly on WhatsApp.
                </p>
                <div class="pt-2">
                    <a
                        href="https://wa.me/8801909131512"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-2 w-full sm:w-auto rounded-xl bg-emerald-500 px-5 py-3 text-xs sm:text-sm font-bold text-slate-950 shadow-sm hover:bg-emerald-400 transition-colors"
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
