<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Megaphone,
    FolderHeart,
    MessageSquare,
    PenTool,
    Code2,
    MessageSquareShare,
    Award,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

const roles = [
    {
        id: 'promoter',
        title: 'Campus Promoter',
        description:
            'আমাদের এই উদ্যোগকে একদম রুট-লেভেলে পৌঁছে দিতে সাহায্য করুন। বিভিন্ন স্টুডেন্ট গ্রুপে কমিউনিটি অ্যাওয়ারনেস তৈরি করা, নতুন আর্কাইভ আপডেট শেয়ার করা এবং আপনার কলেজে আমাদের রিপ্রেজেন্ট করাই হবে আপনার কাজ।',
        icon: Megaphone,
        iconContainer:
            'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
    },
    {
        id: 'curator',
        title: 'Resource Curator',
        description:
            'হাই-কোয়ালিটি এবং কাজের পড়াশোনার ম্যাটেরিয়াল সংগ্রহ ও গুছিয়ে রাখা আপনার কাজ। একদম নিখুঁত বোর্ড কোশ্চেন সেট, প্র্যাক্টিক্যাল রেকর্ডস কালেক্ট করা এবং চ্যাপ্টার ওয়াইজ ডেটা টেমপ্লেট সাজাতে সাহায্য করবেন।',
        icon: FolderHeart,
        iconContainer:
            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    },
    {
        id: 'moderator',
        title: 'Social Media Moderator',
        description:
            'আমাদের ফেসবুক পেজ, গ্রুপ এবং ইনস্টাগ্রাম হ্যান্ডেল অ্যাক্টিভ ও এনগেজিং রাখা আপনার কাজ। স্টুডেন্টদের কমেন্টের ইনস্ট্যান্ট রিপ্লাই দেওয়া, কন্টেন্ট শিডিউল করা এবং মডারেশন গাইডলাইন মেইনটেইন করবেন।',
        icon: MessageSquare,
        iconContainer:
            'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
    },
    {
        id: 'writer',
        title: 'Blog Writer',
        description:
            'আমাদের অফিশিয়াল ব্লগ সেকশনে রেগুলার এডুকেশনাল টিপস, গাইডলাইন, নিউজ এবং স্টুডেন্টদের ইনস্পায়ার করার মতো কন্টেন্ট লিখবেন। কোয়ালিটি রাইটিংয়ের মাধ্যমে স্টুডেন্ট কমিউনিটির সাথে নলেজ শেয়ার করাই হবে মূল কাজ।',
        icon: PenTool,
        iconContainer:
            'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
    },
    {
        id: 'developer',
        title: 'Core Developer',
        description:
            'আমাদের প্ল্যাটফর্ম আর্কিটেকচার আরও অপ্টিমাইজ করুন। একদম হাইপার-ক্লিন ফ্রন্টএন্ড কম্পোনেন্ট ডিজাইন করা, লেআউট আই-কমফর্ট রিফাইন করা এবং নতুন রেপোজিটরি ফিচার নিয়ে কাজ করবেন।',
        icon: Code2,
        iconContainer:
            'bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400',
    },
];

const selectedRole = ref('promoter');

const currentRoleTitle = computed(() => {
    return (
        roles.find((r) => r.id === selectedRole.value)?.title || 'Team Member'
    );
});

// ডাইনামিক হোয়াটসঅ্যাপ লিংক জেনারেটর
const whatsappLink = computed(() => {
    const baseNumber = '8801909131512';
    const text = encodeURIComponent(
        `Hello, I want to join as a ${currentRoleTitle.value}.`,
    );

    return `https://wa.me/${baseNumber}?text=${text}`;
});
</script>

<template>
    <!-- Header -->
    <header class="mx-auto max-w-3xl px-4 pt-12 pb-10 text-center sm:pt-16">
        <Link
            href="/"
            class="group mb-4 inline-flex items-center gap-2 text-xs font-bold text-slate-500 transition-colors hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
        >
            <ArrowLeft
                class="h-3.5 w-3.5 transition-transform group-hover:-translate-x-0.5"
            />
            Back to Home
        </Link>
        <h1
            class="mb-4 text-4xl leading-tight font-black tracking-tight text-slate-950 sm:text-5xl dark:text-gray-100"
        >
            Build the ultimate archive, <br />
            <span class="text-indigo-600">together.</span>
        </h1>
        <p
            class="mx-auto mb-4 max-w-md text-sm font-medium text-slate-800 sm:text-base dark:text-gray-200"
        >
            সারাদেশের HSC & SSC স্টুডেন্টদের কাছে প্রিমিয়াম ও ডিস্ট্রাকশন-ফ্রি
            লার্নিং এক্সপেরিয়েন্স পৌঁছে দিতে আমাদের সাহায্য করুন। নিচের যেকোনো
            একটি রোল সিলেক্ট করে আজই যুক্ত হোন।
        </p>
    </header>

    <main class="mx-auto max-w-6xl px-4 pb-20 sm:px-6">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-5">
            <!-- Roles Selector Grid Left -->
            <div class="space-y-4 lg:col-span-3">
                <h2
                    class="mb-2 text-xs font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                >
                    Open Roles
                </h2>

                <div
                    v-for="role in roles"
                    :key="role.id"
                    @click="selectedRole = role.id"
                    :class="[
                        selectedRole === role.id
                            ? 'border-indigo-500 bg-indigo-50/10 ring-4 ring-indigo-500/5 dark:border-indigo-500 dark:bg-indigo-500/10 dark:ring-indigo-500/10'
                            : 'border-slate-200 bg-white hover:border-slate-300 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-gray-600',
                        'relative flex cursor-pointer touch-manipulation gap-4 rounded-xl border p-5 transition-all duration-200 active:scale-[0.99] sm:active:scale-100',
                    ]"
                >
                    <div
                        :class="[
                            role.iconContainer,
                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                        ]"
                    >
                        <component
                            :is="role.icon"
                            class="h-5 w-5 stroke-[2.2]"
                        />
                    </div>

                    <div class="min-w-0 pr-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-gray-100"
                            >
                                {{ role.title }}
                            </h3>
                            <span
                                v-if="selectedRole === role.id"
                                class="inline-flex items-center rounded-md border border-indigo-100 bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-indigo-700 uppercase dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300"
                            >
                                Selected
                            </span>
                        </div>
                        <p
                            class="mt-1 text-sm leading-relaxed font-medium text-slate-700 dark:text-gray-300"
                        >
                            <template v-if="role.id === 'writer'">
                                আমাদের অফিশিয়াল
                                <Link
                                    href="/blogs"
                                    class="font-bold text-indigo-600 underline decoration-indigo-200 underline-offset-2 hover:text-indigo-700 dark:text-indigo-400 dark:decoration-indigo-500/50 dark:hover:text-indigo-300"
                                    >blog</Link
                                >
                                সেকশনে রেগুলার এডুকেশনাল টিপস, গাইডলাইন, নিউজ
                                এবং স্টুডেন্টদের ইনস্পায়ার করার মতো কন্টেন্ট
                                লিখবেন। কোয়ালিটি রাইটিংয়ের মাধ্যমে স্টুডেন্ট
                                কমিউনিটির সাথে নলেজ শেয়ার করাই হবে মূল কাজ।
                            </template>
                            <template v-else>
                                {{ role.description }}
                            </template>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Contextual CTA Right Panel -->
            <div class="lg:col-span-2">
                <div
                    class="sticky top-6 space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div>
                        <h2
                            class="mb-1 text-base font-bold text-slate-900 dark:text-gray-100"
                        >
                            How to Apply
                        </h2>
                        <p
                            class="text-xs font-medium text-slate-900 dark:text-gray-100"
                        >
                            আপনার ব্যাকগ্রাউন্ড বা আগ্রহের কথা জানিয়ে সরাসরি
                            আমাদের মেসেজ দিন।
                        </p>
                    </div>

                    <!-- Recognition Benefit Box -->
                    <div
                        class="flex items-start gap-3.5 rounded-xl border border-indigo-100 bg-indigo-50/30 p-4 dark:border-indigo-500/30 dark:bg-indigo-500/10"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400"
                        >
                            <Award class="h-4 w-4 stroke-[2.2]" />
                        </div>
                        <div>
                            <h4
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                About Us Recognition
                            </h4>
                            <p
                                class="mt-1 text-xs leading-relaxed font-medium text-slate-800 dark:text-gray-200"
                            >
                                আমাদের অনুমোদিত প্রমোটর, কিউরেটর, মডারেটর,
                                রাইটার এবং ডেভেলপারদের অবদানকে সম্মান জানাতে
                                আমাদের অফিশিয়াল
                                <strong
                                    class="text-blue-800 underline dark:text-blue-300"
                                    ><Link href="/about-us"
                                        >About Us</Link
                                    ></strong
                                >
                                পেজে একটি ডেডিকেটেড প্রোফাইল কার্ড স্থায়ীভাবে
                                যুক্ত করা হবে।
                            </p>
                        </div>
                    </div>

                    <div
                        class="border-t border-slate-100 pt-2 dark:border-gray-800"
                    >
                        <p
                            class="mb-3 text-xs font-semibold text-slate-400 dark:text-gray-500"
                        >
                            Ready to pitch for
                            <span class="font-bold text-indigo-600">{{
                                currentRoleTitle
                            }}</span
                            >?
                        </p>

                        <a
                            :href="whatsappLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-500/20 focus:outline-none active:scale-[0.98]"
                        >
                            <MessageSquareShare class="h-4 w-4" />
                            Message on WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>
