<script setup lang="ts">
import {} from '@inertiajs/vue3';
import { kButton, kCard } from 'konsta/vue';
import {
    ArrowLeft,
    Heart,
    Server,
    Globe,
    Zap,
    ExternalLink,
    HelpCircle,
    ShieldCheck,
} from 'lucide-vue-next';

const supportUrl = 'https://supportkori.com/hscstack';
</script>

<template>
    <header class="mx-auto max-w-3xl px-4 pt-12 pb-8 text-center sm:pt-16">
        <k-button clear href="/">
            <ArrowLeft
                class="h-3.5 w-3.5 transition-transform group-hover:-translate-x-0.5"
            />
            Back to Home
        </k-button>
        <h1
            class="mb-4 text-4xl leading-tight font-black tracking-tight text-slate-950 sm:text-5xl dark:text-gray-100"
        >
            Support
            <span class="text-indigo-600 dark:text-indigo-400">HSCStack</span>
        </h1>
        <p
            class="mx-auto max-w-md text-xs font-semibold tracking-wider text-slate-400 uppercase dark:text-gray-500"
        >
            ফ্রি শিক্ষামূলক প্ল্যাটফর্মটিকে সচল রাখতে সাহায্য করুন
        </p>
    </header>

    <main class="mx-auto max-w-3xl px-4 pb-20 sm:px-6">
        <div class="space-y-8">
            <!-- Hero Support Card -->
            <k-card outline>
                <div class="flex flex-col items-center text-center">
                    <div
                        class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-200 dark:shadow-indigo-500/40"
                    >
                        <Heart
                            class="h-6 w-6 fill-indigo-600 stroke-[2.2] text-white"
                        />
                    </div>
                    <h2
                        class="text-xl font-bold text-slate-900 sm:text-2xl dark:text-gray-100"
                    >
                        আমাদের প্ল্যাটফর্মটি ১০০% ফ্রি এবং বিজ্ঞাপনমুক্ত
                    </h2>
                    <p
                        class="mt-2 max-w-xl text-sm leading-relaxed font-medium text-slate-600 dark:text-gray-400"
                    >
                        HSCStack শিক্ষার্থীদের দ্বারা নির্মিত একটি অলাভজনক
                        উদ্যোগ। HSC ও SSC শিক্ষার্থীদের পড়াশোনা সহজ করতে আমরা
                        কোনো সাবস্ক্রিপশন ফি বা বিরক্তিকর পপ-আপ অ্যাড রাখি না।
                        আপনার সহযোগিতা আমাদের সার্ভার ও ডোমেইন খরচ চালাতে
                        সাহায্য করবে।
                    </p>

                    <k-button
                        fill
                        rounded
                        large
                        :href="supportUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-6"
                    >
                        <Heart class="h-4 w-4 stroke-[2.2]" />
                        Support Us on SupportKori
                        <ExternalLink class="h-4 w-4 stroke-[2.2]" />
                    </k-button>
                </div>
            </k-card>

            <!-- Detailed Breakdown -->
            <k-card outline>
                <h3
                    class="border-b border-slate-100 pb-4 text-base font-bold text-slate-900 dark:border-gray-800 dark:text-gray-100"
                >
                    কেন আপনার সাপোর্ট প্রয়োজন?
                </h3>

                <!-- Reason 1: Server Costs -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <Server class="h-4 w-4 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4
                            class="text-base font-bold text-slate-900 dark:text-gray-100"
                        >
                            ১. হাই-স্পিড সার্ভার ও হোস্টিং
                        </h4>
                        <p
                            class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                        >
                            হাজার হাজার শিক্ষার্থী যেন একসাথে কোনো লেগ ছাড়া
                            নোট, সাজেশন ও ভিডিও দেখতে পারে, সেজন্য আমাদের
                            নির্ভরযোগ্য ও দ্রুতগতির Cloud Hosting ব্যবহার করতে
                            হয়।
                        </p>
                    </div>
                </div>

                <!-- Reason 2: Domain Renewal -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <Globe class="h-4 w-4 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4
                            class="text-base font-bold text-slate-900 dark:text-gray-100"
                        >
                            ২. ডোমেইন ও ইনফাস্ট্রাকচার মেইনটেন্যান্স
                        </h4>
                        <p
                            class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                        >
                            ওয়েবসাইটের ডোমেইন নেম নবায়ন, সিকিউরিটি সার্টিফিকেট
                            (SSL) এবং ব্যাকআপ সিস্টেম সচল রাখতে নিয়মিত বাৎসরিক
                            খরচের প্রয়োজন হয়।
                        </p>
                    </div>
                </div>

                <!-- Reason 3: Distraction-free Learning -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <Zap class="h-4 w-4 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4
                            class="text-base font-bold text-slate-900 dark:text-gray-100"
                        >
                            ৩. ১০০% ক্লিন ও ডিসট্র্যাকশন-ফ্রি অভিজ্ঞতা
                        </h4>
                        <p
                            class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                        >
                            আমরা ওয়েবসাইটে কোনো গুগল অ্যাড বা থার্ড-পার্টি
                            বিজ্ঞাপন দেখাই না, যাতে পড়ালেখার সময়ে
                            শিক্ষার্থীদের মনোযোগ নষ্ট না হয়। আপনাদের সহায়তাই
                            আমাদের স্বাবলম্বী রাখে।
                        </p>
                    </div>
                </div>

                <hr class="border-slate-100 dark:border-gray-800" />

                <!-- Transparency Section -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                    >
                        <ShieldCheck class="h-4 w-4 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4
                            class="text-base font-bold text-slate-900 dark:text-gray-100"
                        >
                            স্বচ্ছতা ও নিশ্চয়তা
                        </h4>
                        <p
                            class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                        >
                            সংগৃহীত প্রতিটি টাকা শুধুমাত্র HSCStack-এর অবকাঠামো
                            উন্নয়ন, সার্ভার খরচ এবং ব্যাকএন্ড টেকনোলজি
                            মেইনটেন্যান্সে ব্যয় করা হয়।
                        </p>
                    </div>
                </div>

                <!-- Alternative Ways to Support -->
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <HelpCircle class="h-4 w-4 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4
                            class="text-base font-bold text-slate-900 dark:text-gray-100"
                        >
                            অর্থনৈতিক সাহায্য ছাড়াও যেভাবে পাশে থাকতে পারেন
                        </h4>
                        <ul
                            class="mt-2 list-disc space-y-1.5 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                        >
                            <li>
                                <strong
                                    class="text-slate-700 dark:text-gray-300"
                                    >শেয়ার করুন:</strong
                                >
                                আপনার সহপাঠী ও বন্ধুদের মাঝে HSCStack শেয়ার করে
                                দিন।
                            </li>
                            <li>
                                <strong
                                    class="text-slate-700 dark:text-gray-300"
                                    >রিসোর্স শেয়ার:</strong
                                >
                                আপনার করা লেকচার নোট বা সাজেশন আমাদের
                                প্ল্যাটফর্মে আপলোড করুন।
                            </li>
                            <li>
                                <strong
                                    class="text-slate-700 dark:text-gray-300"
                                    >ফিডব্যাক দিন:</strong
                                >
                                ওয়েবসাইটের কোনো সমস্যা চোখে পড়লে আমাদের জানান।
                            </li>
                        </ul>
                    </div>
                </div>
            </k-card>

            <!-- Bottom CTA Card -->
            <k-card outline>
                <p
                    class="text-sm font-bold text-slate-900 sm:text-base dark:text-gray-100"
                >
                    আপনার যেকোনো ছোট সাহায্যও আমাদের এই যাত্রাকে দীর্ঘস্থায়ী
                    করতে পারে ❤️
                </p>
                <div class="mt-4">
                    <k-button
                        clear
                        :href="supportUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Go to SupportKori payment page
                        <ExternalLink class="h-3.5 w-3.5" />
                    </k-button>
                </div>
            </k-card>
        </div>
    </main>
</template>
