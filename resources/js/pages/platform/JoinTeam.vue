<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    FolderHeart,
    PenTool,
    LifeBuoy,
    MessageSquare,
    Share2,
    Megaphone,
    Code2,
    ShieldCheck,
    Award,
    Mail,
    Send,
    ArrowRight,
    X,
    Sparkles,
    Check,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AuthModal from '@/components/AuthModal.vue';

interface Role {
    id: string;
    title: string;
    bengaliTitle: string;
    description: string;
    responsibilities: string[];
    icon: any;
    badgeStyle: string;
    iconStyle: string;
}

const page = usePage();
const authUser = computed(
    () => page.props.auth?.user as { name?: string; email?: string } | null,
);

const roles: Role[] = [
    {
        id: 'curator',
        title: 'Resource Curator',
        bengaliTitle: 'রিসোর্স কিউরেটর',
        description:
            'হাই-কোয়ালিটি পড়ার ম্যাটেরিয়াল, চ্যাপ্টার-ওয়াইজ নোটস ও প্রশ্নব্যাংক গুছিয়ে রাখা আপনার কাজ।',
        responsibilities: [
            'নিখুঁত বোর্ড প্রশ্ন, মডেল টেস্ট ও সেরা হ্যান্ডনোট সংগ্রহ করা',
            'চ্যাপ্টার ও বিষয় অনুযায়ী রিসোর্স নিখুঁতভাবে সাজানো ও রিচেক করা',
            'প্র্যাক্টিক্যাল রেকর্ডস ও টেস্ট পেপার ডেটা গুছিয়ে রাখা',
        ],
        icon: FolderHeart,
        badgeStyle:
            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        iconStyle:
            'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
    },
    {
        id: 'writer',
        title: 'Blog Writer',
        bengaliTitle: 'ব্লগ রাইটার',
        description:
            'অফিশিয়াল ব্লগে পড়ালেখার স্ট্র্যাটেজি, গাইডলাইন ও ইনস্পায়ারিং শিক্ষামূলক আর্টিকেল লিখবেন।',
        responsibilities: [
            'HSC/SSC শিক্ষার্থীদের পড়াশোনার কার্যকরী টিপস ও গাইডলাইন লেখা',
            'সহজ ও প্রাঞ্জল ভাষায় গুরুত্বপূর্ণ বিষয় বিশ্লেষণ করা',
            'অফিশিয়াল ব্লগ সেকশনে নিয়মিত কোয়ালিটি কন্টেন্ট প্রকাশ করা',
        ],
        icon: PenTool,
        badgeStyle:
            'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400',
        iconStyle:
            'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
    },
    {
        id: 'internal-moderator',
        title: 'Internal Moderator',
        bengaliTitle: 'ইন্টারনাল মডারেটর',
        description:
            'স্টুডেন্ট সাপোর্ট টিকেট পরিচালনা, সমস্যার দ্রুত সমাধান ও অভ্যন্তরীণ প্ল্যাটফর্ম সহায়তা দেওয়া।',
        responsibilities: [
            'শিক্ষার্থীদের জমা দেওয়া সাপোর্ট টিকেট ও ইস্যু রিভিউ করে সমাধান দেওয়া',
            'কোনো রিসোর্স বা লিংকে সমস্যা থাকলে তা দ্রুত রিপোর্ট ও ফিক্স নিশ্চিত করা',
            'ইউজার এক্সপেরিয়েন্স নিরবচ্ছিন্ন রাখতে কোর টিমের সাথে সরাসরি কাজ করা',
        ],
        icon: LifeBuoy,
        badgeStyle:
            'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-400',
        iconStyle:
            'bg-cyan-50 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-400',
    },
    {
        id: 'chat-moderator',
        title: 'Global Chat Moderator',
        bengaliTitle: 'গ্লোবাল চ্যাট মডারেটর',
        description:
            'লাইভ গ্লোবাল চ্যাটে স্টুডেন্টদের সহায়তা করা, স্প্যামিং রোধ ও ফ্রেন্ডলি পরিবেশ রক্ষা করা।',
        responsibilities: [
            'গ্লোবাল চ্যাটে শিক্ষার্থীদের প্রশ্ন ও আলোচনায় আন্তরিক সহায়তা দেওয়া',
            'স্প্যামিং বা আচরণবিধি বিরোধী মেসেজ দ্রুত মডারেট করা',
            'ইতিবাচক, প্রাণবন্ত ও শিক্ষার্থীবান্ধব আলোচনা বজায় রাখা',
        ],
        icon: MessageSquare,
        badgeStyle:
            'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
        iconStyle:
            'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
    },
    {
        id: 'social-moderator',
        title: 'Social Media Moderator',
        bengaliTitle: 'সোশ্যাল মিডিয়া মডারেটর',
        description:
            'আমাদের অফিসিয়াল ফেসবুক পেজ, গ্রুপ ও সোশ্যাল কমিউনিটিতে শিক্ষার্থীদের সাহায্য ও পোস্ট মডারেট করা।',
        responsibilities: [
            'ফেসবুক গ্রুপ ও পেজের মেসেজ এবং কমেন্টে দ্রুত সহায়তা দেওয়া',
            'কমিউনিটিতে তথ্যবহুল পোস্ট ও গুরুত্বপূর্ণ নোটিশ প্রচার করা',
            'গ্রুপের পোস্ট মডারেশন ও স্প্যামিং রোধে সক্রিয় থাকা',
        ],
        icon: Share2,
        badgeStyle:
            'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400',
        iconStyle:
            'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
    },
    {
        id: 'promoter',
        title: 'Campus Promoter',
        bengaliTitle: 'ক্যাম্পাস প্রমোটর',
        description:
            'আমাদের এই অলাভজনক উদ্যোগকে একদম তৃণমূল পর্যায়ের শিক্ষার্থীদের কাছে পৌঁছে দিতে সাহায্য করুন।',
        responsibilities: [
            'বিভিন্ন college ও স্টুডেন্ট গ্রুপে প্ল্যাটফর্মের ফ্রি রিসোর্স ও আপডেট শেয়ার করা',
            'নিজের কলেজে HSCStack-এর প্রতিনিধি হিসেবে শিক্ষার্থীদের গাইড করা',
            'স্টুডেন্টদের প্রয়োজনীয় স্টাডি মেটেরিয়ালের চাহিদা টিমে জানানো',
        ],
        icon: Megaphone,
        badgeStyle:
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
        iconStyle:
            'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
    },
    {
        id: 'developer',
        title: 'Core Developer',
        bengaliTitle: 'কোর ডেভেলপার',
        description:
            'আমাদের ওপেন সোর্স প্ল্যাটফর্ম আর্কিটেকচার, পারফরম্যান্স ও UI কম্পোনেন্ট উন্নত করতে কাজ করবেন।',
        responsibilities: [
            'সুপার-ফাস্ট Vue / Inertia / Tailwind ফ্রন্টএন্ড ফিচার তৈরি করা',
            'মোবাইল ফ্রেন্ডলিনেস ও রেস্পনসিভ এক্সপেরিয়েন্স সর্বোচ্চ মানে রাখা',
            'নতুন ফিচার ও ডেটাবেজ অপ্টিমাইজেশনে সরাসরি অবদান রাখা',
        ],
        icon: Code2,
        badgeStyle:
            'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
        iconStyle:
            'bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400',
    },
];

// Modal State & Form
const showAuthModal = ref(false);
const isModalOpen = ref(false);
const formNote = ref('');
const selectedRoleIds = ref<string[]>([]);

const ticketForm = useForm<{
    category: string;
    subject: string;
    message: string;
    general?: string;
}>({
    category: 'apply_role',
    subject: '',
    message: '',
});

const openApplyModal = (role?: Role) => {
    if (!authUser.value) {
        showAuthModal.value = true;

        return;
    }

    if (role) {
        selectedRoleIds.value = [role.id];
    } else if (selectedRoleIds.value.length === 0) {
        selectedRoleIds.value = ['curator'];
    }

    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    ticketForm.clearErrors();
};

const toggleRoleSelection = (roleId: string) => {
    if (selectedRoleIds.value.includes(roleId)) {
        if (selectedRoleIds.value.length > 1) {
            selectedRoleIds.value = selectedRoleIds.value.filter(
                (id) => id !== roleId,
            );
        }
    } else {
        selectedRoleIds.value.push(roleId);
    }
};

const submitModalApplication = () => {
    if (!authUser.value) {
        showAuthModal.value = true;

        return;
    }

    const selectedRoles = roles.filter((r) =>
        selectedRoleIds.value.includes(r.id),
    );
    const roleTitles = selectedRoles
        .map((r) => `${r.title} (${r.bengaliTitle})`)
        .join(', ');

    const subject =
        selectedRoles.length === 1
            ? `Application for ${selectedRoles[0].title}`
            : `Application for Roles: ${selectedRoles.map((r) => r.title).join(', ')}`;

    let message = `আসসালামু আলাইকুম, আমি HSCStack-এ কন্ট্রিবিউটর হিসেবে যুক্ত হতে আগ্রহী।\n\n`;
    message += `আবেদনের রোল: ${roleTitles}\n\n`;
    message += `অভিজ্ঞতা ও পরিকল্পনা:\n${formNote.value.trim()}\n\n`;
    message += `ধন্যবাদ!`;

    ticketForm.category = 'apply_role';
    ticketForm.subject = subject;
    ticketForm.message = message;

    ticketForm.post('/support/tickets', {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};
</script>

<template>
    <Head>
        <title>Join the Team - Become a Contributor</title>
        <meta
            name="description"
            content="Join HSCStack as a Resource Curator, Blog Writer, Internal Moderator, Global Chat Moderator, Social Media Moderator, Campus Promoter, or Core Developer."
        />
        <meta
            property="og:title"
            content="Join the Team - Become a Contributor - HSCStack"
        />
        <meta
            property="og:description"
            content="Join HSCStack as a Resource Curator, Blog Writer, Internal Moderator, Global Chat Moderator, Social Media Moderator, Campus Promoter, or Core Developer."
        />
    </Head>

    <!-- Header -->
    <header
        class="mx-auto max-w-4xl px-4 pt-8 pb-8 text-center sm:pt-14 sm:pb-10"
    >
        <h1
            class="text-3xl font-black tracking-tight text-slate-950 sm:text-5xl dark:text-gray-100"
        >
            Build the Ultimate Archive, <br class="hidden sm:inline" />
            <span class="text-indigo-600 dark:text-indigo-400">Together.</span>
        </h1>
        <p
            class="mx-auto mt-3 max-w-lg text-sm leading-relaxed font-medium text-slate-600 sm:text-base dark:text-gray-300"
        >
            সারাদেশের HSC & SSC স্টুডেন্টদের কাছে প্রিমিয়াম ও বিজ্ঞাপনমুক্ত
            পড়াশোনার অভিজ্ঞতা পৌঁছে দিতে আমাদের সাথে যুক্ত হোন।
        </p>
    </header>

    <main class="mx-auto max-w-4xl px-4 pb-24 sm:px-6">
        <!-- Compact Contributor Perks Strip -->
        <div
            class="mb-6 rounded-2xl border border-slate-200 bg-white p-3 sm:p-3.5 dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                class="grid grid-cols-1 gap-2.5 sm:grid-cols-3 sm:gap-3 sm:divide-x sm:divide-slate-100 dark:sm:divide-gray-800"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <ShieldCheck class="h-4 w-4" :stroke-width="2.2" />
                    </div>
                    <div class="min-w-0">
                        <h3
                            class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                        >
                            Verified Profile Badge
                        </h3>
                        <p
                            class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                        >
                            প্রোফাইলে স্পেশাল ভেরিফাইড ব্যাজ
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 sm:pl-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                    >
                        <Mail class="h-4 w-4" :stroke-width="2.2" />
                    </div>
                    <div class="min-w-0">
                        <h3
                            class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                        >
                            yourname@hscstack.site
                        </h3>
                        <p
                            class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                        >
                            অফিশিয়াল ব্র্যান্ডেড টিম ইমেইল
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 sm:pl-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400"
                    >
                        <Award class="h-4 w-4" :stroke-width="2.2" />
                    </div>
                    <div class="min-w-0">
                        <h3
                            class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                        >
                            About Us Recognition
                        </h3>
                        <p
                            class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                        >
                            <Link
                                href="/about-us"
                                class="hover:text-indigo-600 dark:hover:text-indigo-400"
                                >About Us</Link
                            >
                            পেজে স্থায়ী ফিচার
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Open Roles Section -->
        <div
            class="mb-4 flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2
                    class="text-xs font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                >
                    Open Positions
                </h2>
                <p
                    class="text-xs font-medium text-slate-500 dark:text-gray-400"
                >
                    একাধিক রোলেও আবেদন করা যাবে
                </p>
            </div>
            <span
                class="self-start rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700 sm:self-auto dark:bg-indigo-500/10 dark:text-indigo-400"
            >
                {{ roles.length }} Roles Available
            </span>
        </div>

        <!-- Role Cards Grid -->
        <div class="space-y-4">
            <div
                v-for="role in roles"
                :key="role.id"
                class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 transition-all duration-150 hover:border-slate-300 sm:p-6 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
            >
                <div>
                    <!-- Card Header -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    role.iconStyle,
                                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl',
                                ]"
                            >
                                <component
                                    :is="role.icon"
                                    class="h-5 w-5 stroke-[2.2]"
                                />
                            </div>

                            <div>
                                <h3
                                    class="text-base font-bold text-slate-900 dark:text-gray-100"
                                >
                                    {{ role.title }}
                                </h3>
                                <span
                                    class="inline-block text-[11px] font-semibold text-slate-400 dark:text-gray-500"
                                >
                                    {{ role.bengaliTitle }}
                                </span>
                            </div>
                        </div>

                        <span
                            :class="[
                                role.badgeStyle,
                                'hidden rounded-md px-2 py-0.5 text-[11px] font-bold sm:inline-block',
                            ]"
                        >
                            Open
                        </span>
                    </div>

                    <!-- Role Description -->
                    <p
                        class="mt-3.5 text-xs leading-relaxed font-medium text-slate-700 sm:text-sm dark:text-gray-300"
                    >
                        <template v-if="role.id === 'writer'">
                            আমাদের অফিশিয়াল
                            <Link
                                href="/blogs"
                                class="font-bold text-indigo-600 underline underline-offset-2 hover:text-indigo-700 dark:text-indigo-400"
                                >ব্লগ</Link
                            >
                            সেকশনে পড়ালেখার স্ট্র্যাটেজি, গাইডলাইন ও
                            শিক্ষার্থীদের জন্য ইনস্পায়ারিং শিক্ষামূলক আর্টিকেল
                            লিখবেন।
                        </template>
                        <template v-else>
                            {{ role.description }}
                        </template>
                    </p>

                    <!-- Responsibilities Bullet List -->
                    <ul
                        class="mt-3.5 space-y-1.5 border-t border-slate-100 pt-3 dark:border-gray-800"
                    >
                        <li
                            v-for="(resp, i) in role.responsibilities"
                            :key="i"
                            class="flex items-start gap-2 text-xs text-slate-600 dark:text-gray-400"
                        >
                            <span
                                class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500/70"
                            ></span>
                            <span>{{ resp }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Apply Action -->
                <div
                    class="mt-5 border-t border-slate-100 pt-4 dark:border-gray-800"
                >
                    <button
                        type="button"
                        @click="openApplyModal(role)"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white transition-all hover:bg-slate-800 active:scale-[0.99] sm:w-auto dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        <Send class="h-3.5 w-3.5" />
                        <span>Apply as {{ role.title }}</span>
                        <ArrowRight class="h-3 w-3 opacity-60" />
                    </button>
                </div>
            </div>
        </div>

        <!-- General Pitch / Contact Footer Card -->
        <div
            class="mt-8 flex flex-col items-center justify-between gap-4 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-6 text-center sm:flex-row sm:p-7 sm:text-left dark:border-gray-800 dark:bg-gray-900/40"
        >
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-gray-100">
                    Applying for multiple roles or have an idea?
                </h3>
                <p
                    class="mt-1 max-w-md text-xs leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                >
                    একসাথে একাধিক রোলে কাজ করতে চাইলে বা অন্য কোনো স্পেশাল
                    আইডিয়া নিয়ে অবদান রাখতে সরাসরি আবেদন করুন।
                </p>
            </div>

            <button
                type="button"
                @click="openApplyModal()"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-2xs transition-all hover:bg-indigo-700 active:scale-95"
            >
                <Send class="h-4 w-4" />
                <span>Apply for Roles</span>
                <ArrowRight class="h-3.5 w-3.5" />
            </button>
        </div>
    </main>

    <!-- Application Modal -->
    <div
        v-if="isModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs"
        @click.self="closeModal"
    >
        <div
            class="w-full max-w-lg overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl transition-all dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Modal Header -->
            <div
                class="flex items-center justify-between border-b border-slate-100 p-5 dark:border-gray-800"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <Sparkles class="h-4.5 w-4.5" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            Contributor Application
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            তথ্যগুলো পূরণ করে সরাসরি টিকেট খুলুন
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="closeModal"
                    class="rounded-xl p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                >
                    <X class="h-4.5 w-4.5" />
                </button>
            </div>

            <!-- Modal Form Body -->
            <form @submit.prevent="submitModalApplication" class="p-5 sm:p-6">
                <!-- Errors Banner -->
                <div
                    v-if="ticketForm.errors.general"
                    class="mb-4 rounded-xl border border-rose-200 bg-rose-50/90 p-3 text-xs font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400"
                >
                    {{ ticketForm.errors.general }}
                </div>

                <!-- Role Selector Pills -->
                <div class="mb-4">
                    <label
                        class="mb-2 block text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        যেসব রোলে কাজ করতে চান (একাধিক সিলেক্ট করা যাবে) *
                    </label>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="role in roles"
                            :key="role.id"
                            type="button"
                            @click="toggleRoleSelection(role.id)"
                            class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-bold transition-all"
                            :class="[
                                selectedRoleIds.includes(role.id)
                                    ? 'border-indigo-600 bg-indigo-600 text-white shadow-xs'
                                    : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600',
                            ]"
                        >
                            <component
                                :is="
                                    selectedRoleIds.includes(role.id)
                                        ? Check
                                        : role.icon
                                "
                                class="h-3.5 w-3.5"
                            />
                            <span>{{ role.title }}</span>
                        </button>
                    </div>
                </div>

                <!-- Profile Completeness Notice for Trust -->
                <div
                    class="mb-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-xs leading-relaxed text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
                >
                    <ShieldCheck
                        class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                    />
                    <div>
                        <p class="font-bold">প্রোফাইল আপডেট রাখার অনুরোধ:</p>
                        <p
                            class="mt-0.5 text-[11px] text-amber-800 dark:text-amber-400/90"
                        >
                            আপনার প্রোফাইল তথ্য (নাম, ছবি, শিক্ষা প্রতিষ্ঠান ও
                            যোগাযোগ) এডমিন টিম সরাসরি রিভিউ করবে। আবেদন করার আগে
                            প্রোফাইল সম্পূর্ণ নিশ্চিত করুন।
                            <Link
                                href="/me"
                                target="_blank"
                                class="ml-1 font-bold underline hover:text-amber-950 dark:hover:text-amber-200"
                            >
                                প্রোফাইল দেখুন/এডিট করুন
                            </Link>
                        </p>
                    </div>
                </div>

                <!-- Input: Experience & Note -->
                <div class="mb-5">
                    <label
                        for="apply-note"
                        class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        আপনার আগ্রহ, অভিজ্ঞতা বা পরিকল্পনা *
                    </label>
                    <textarea
                        id="apply-note"
                        v-model="formNote"
                        rows="4"
                        required
                        placeholder="কেন এই রোলে কাজ করতে চান বা আপনার কোনো পূর্ব অভিজ্ঞতা ও পরিকল্পনা থাকলে সংক্ষেপে লিখুন..."
                        class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-xs font-medium text-slate-900 transition focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:bg-gray-900"
                    ></textarea>
                </div>

                <!-- Modal Action Buttons -->
                <div class="flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="closeModal"
                        class="rounded-xl px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                    >
                        বাতিল
                    </button>
                    <button
                        type="submit"
                        :disabled="ticketForm.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition-all hover:bg-indigo-700 active:scale-95 disabled:opacity-50"
                    >
                        <Send class="h-3.5 w-3.5" />
                        <span>{{
                            ticketForm.processing
                                ? 'আবেদন পাঠানো হচ্ছে...'
                                : 'Apply Now'
                        }}</span>
                        <ArrowRight
                            v-if="!ticketForm.processing"
                            class="h-3.5 w-3.5 opacity-80"
                        />
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Auth Modal for Guests -->
    <AuthModal
        v-model="showAuthModal"
        title="Sign in to Apply"
        message="কন্ট্রিবিউটর রোলের জন্য আবেদন করতে প্রথমে আপনার একাউন্টে লগইন করুন।"
    />
</template>
