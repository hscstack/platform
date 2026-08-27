<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, ArrowRight, Users } from 'lucide-vue-next';
</script>

<template>
    <Head>
        <title>Our Products & Open Source Projects</title>
        <meta
            name="description"
            content="Explore educational platforms, open-source web applications, and learning tools developed by the HSCStack team."
        />
        <meta
            property="og:title"
            content="Our Products & Open Source Projects - HSCStack"
        />
        <meta
            property="og:description"
            content="Explore educational platforms, open-source web applications, and learning tools developed by the HSCStack team."
        />
    </Head>

    <header
        class="mx-auto max-w-4xl px-4 pt-8 pb-6 text-center sm:pt-12 sm:pb-10"
    >
        <h1
            class="mb-3 text-3xl font-black tracking-tight text-slate-950 sm:text-5xl dark:text-gray-100"
        >
            Our
            <span class="text-indigo-600 dark:text-indigo-400">Products</span>
        </h1>

        <p
            class="mx-auto max-w-md text-sm font-medium text-slate-500 dark:text-gray-400"
        >
            Explore platforms and tools built by HSCStack
        </p>
    </header>

    <main class="mx-auto max-w-5xl px-4 pb-20 sm:px-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:gap-8">
            <!-- Product 1: Main Platform -->
            <div
                class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
            >
                <div>
                    <div
                        class="aspect-[16/9] overflow-hidden rounded-xl bg-slate-100 dark:bg-gray-800"
                    >
                        <img
                            src="https://cdn.hscstack.site/images/og.png"
                            alt="HSCStack Main Platform"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-2">
                        <h2
                            class="text-xl font-bold text-slate-900 dark:text-gray-100"
                        >
                            HSCStack
                        </h2>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300"
                        >
                            <Users class="h-3.5 w-3.5" />
                            1000+ Users
                        </span>
                    </div>

                    <p
                        class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-gray-400"
                    >
                        HSC ও SSC শিক্ষার্থীদের পড়াশোনা গোছানোর জন্য সম্পূর্ণ
                        ফ্রি ও পরিচ্ছন্ন স্টাডি রিপোজিটরি। বিষয় ও অধ্যায়ভিত্তিক
                        হ্যান্ডনোট, লেকচার ক্লাস ও রিসোর্স।
                    </p>
                </div>

                <div
                    class="mt-6 border-t border-slate-100 pt-4 dark:border-gray-800"
                >
                    <Link
                        href="/"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-xs transition-all hover:bg-indigo-700 active:scale-98"
                    >
                        <span>Visit Platform</span>
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>

            <!-- Product 2: ResultRank -->
            <div
                class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
            >
                <div>
                    <div
                        class="aspect-[16/9] overflow-hidden rounded-xl bg-slate-100 dark:bg-gray-800"
                    >
                        <img
                            src="https://cdn.hscstack.site/images/og_resultrank.png"
                            alt="ResultRank"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-2">
                        <h2
                            class="text-xl font-bold text-slate-900 dark:text-gray-100"
                        >
                            ResultRank
                        </h2>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"
                        >
                            <Users class="h-3.5 w-3.5" />
                            2500+ Users
                        </span>
                    </div>

                    <p
                        class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-gray-400"
                    >
                        বাংলাদেশের সকল শিক্ষা বোর্ডের মেধা তালিকা, সেরা
                        প্রতিষ্ঠান ও বিস্তারিত GPA পরিসংখ্যান বিশ্লেষণ করুন একটি
                        সমন্বিত প্ল্যাটফর্মে।
                    </p>
                </div>

                <div
                    class="mt-6 border-t border-slate-100 pt-4 dark:border-gray-800"
                >
                    <a
                        href="https://resultrank.hscstack.site"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-98"
                    >
                        <span>Visit ResultRank</span>
                        <ExternalLink class="h-4 w-4" />
                    </a>
                </div>
            </div>
        </div>
    </main>
</template>
