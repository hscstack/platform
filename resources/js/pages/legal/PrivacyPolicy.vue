<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Shield,
    ArrowLeft,
    Eye,
    Lock,
    Cookie,
    Text,
    Trash,
    LocateIcon,
    PersonStanding,
} from 'lucide-vue-next';
</script>

<template>
    <header class="mx-auto max-w-3xl px-4 pt-12 pb-8 text-center sm:pt-16">
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
            Privacy <span class="text-indigo-600">Policy</span>
        </h1>
        <p
            class="mx-auto max-w-md text-xs font-semibold tracking-wider text-slate-400 uppercase dark:text-gray-500"
        >
            সর্বশেষ আপডেট: জুলাই ২০২৬
        </p>
    </header>

    <main class="mx-auto max-w-3xl px-4 pb-20 sm:px-6">
        <div
            class="space-y-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-700 dark:bg-gray-900"
        >
            <p
                class="text-sm leading-relaxed font-medium text-slate-600 dark:text-gray-400"
            >
                আমাদের প্ল্যাটফর্মে স্বাগতম। HSC & SSC স্টুডেন্টদের জন্য
                প্রিমিয়াম, কিউরেটেড এবং distraction-free স্টাডি ম্যাটেরিয়াল
                দেওয়ার ব্যাপারে আমরা নিবেদিত। এই প্রাইভেসি পলিসিতে ব্যাখ্যা করা
                হয়েছে, আমাদের ওয়েবসাইট ব্যবহার করার সময় আপনার তথ্য আমরা
                কীভাবে সংগ্রহ, ব্যবহার এবং সুরক্ষিত করি।
            </p>

            <hr class="border-slate-100 dark:border-gray-800" />

            <!-- Section 1 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Eye class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ১. আমরা যে তথ্য সংগ্রহ করি
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        আমরা জিনিসগুলো fast এবং clean রাখার চেষ্টা করি । একটা
                        ভালো experience দেওয়ার জন্য যতটুকু প্রয়োজন, ঠিক ততটুকু
                        তথ্যই আমরা সংগ্রহ করি:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>
                            <strong class="text-slate-700 dark:text-gray-300"
                                >Usage Data:</strong
                            >
                            layout পারফরম্যান্স অপটিমাইজ করার জন্য ব্রাউজার টাইপ
                            এবং ভিজিট করা পেজের মতো non-personal ডেটা।
                        </li>
                        <li>
                            <strong class="text-slate-700 dark:text-gray-300"
                                >Search Queries:</strong
                            >
                            সাবজেক্ট instantly ফিল্টার করার জন্য আপনার সার্চ
                            ইনপুট আপনার ডিভাইসেই locally প্রসেস করা হয়।
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Section 2 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Cookie class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ২. কুকিজ এবং ট্র্যাকিং
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        আপনার visual preference (যেমন লেআউট সাইজিং বা cached
                        data) মনে রাখার জন্য আমরা lightweight local storage বা
                        কুকিজ ব্যবহার করি, যাতে ব্রাউজিং সেশনটা seamless হয়।
                        আমরা তৃতীয় পক্ষের বিজ্ঞাপন বা cross-site advertising
                        tracking এর মাধ্যমে আপনাকে অনুসরণ করি না। আমরা কিছু
                        privacy-focused analytics টুলও ব্যবহার করি, যেগুলোর
                        বিস্তারিত বর্ণনা ৬ নম্বর সেকশনে ("অ্যানালিটিক্স ও
                        ট্র্যাকিং") দেওয়া আছে।
                    </p>
                </div>
            </div>

            <!-- Section 3 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Shield class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৩. থার্ড-পার্টি সার্ভিস
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        অনলাইনে থাকতে এবং দ্রুত পারফর্ম করতে আমাদের আর্কাইভ
                        modern hosting platform এবং lightweight analytics টুল
                        ব্যবহার করে। এই থার্ড পার্টিগুলো আপনার basic operational
                        data নিরাপদে এবং কড়া security compliance নিয়ম মেনে
                        হ্যান্ডেল করে।
                    </p>
                </div>
            </div>

            <!-- Section 4 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Lock class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৪. ডেটা সিকিউরিটি
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        প্ল্যাটফর্মের safety নিশ্চিত করতে আমরা strict, clean
                        codebase practice অনুসরণ করি। এই ডিজিটাল আর্কাইভ
                        সুরক্ষিত রাখতে আমরা যথাসাধ্য চেষ্টা করলেও, মনে
                        রাখবেন—অনলাইনে absolute নেটওয়ার্ক সিকিউরিটি বলে কিছু
                        নেই।
                    </p>
                </div>
            </div>

            <!-- Section 5 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <PersonStanding class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৫. কনট্রিবিউটর তথ্য
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        কনট্রিবিউটর হিসেবে কাজ করার জন্য আপনি যে তথ্য প্রদান
                        করেন (যেমন নাম, ইমেইল বা প্রোফাইল তথ্য), তা কনট্রিবিউটর
                        management, কনটেন্ট attribution এবং প্ল্যাটফর্ম
                        পরিচালনার জন্য ব্যবহার করা হয়। কনট্রিবিউটররা তাদের
                        অ্যাকাউন্ট মুছে ফেলার অনুরোধ করতে পারেন, তবে অ্যাকাউন্ট
                        মুছে ফেলার অর্থ এই নয় যে তাদের পূর্বে প্রকাশিত কনটেন্টও
                        স্বয়ংক্রিয়ভাবে মুছে যাবে — বিস্তারিত জানতে আমাদের
                        <Link
                            href="/content-policy"
                            class="text-indigo-600 underline dark:text-indigo-400"
                            >Content Policy
                        </Link>
                        দেখুন।
                    </p>
                </div>
            </div>

            <!-- Section 6 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <LocateIcon class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৬. অ্যানালিটিক্স ও ট্র্যাকিং
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        ব্যবহারকারীর অভিজ্ঞতা এবং প্ল্যাটফর্মের কার্যকারিতা
                        উন্নত করার জন্য আমরা বিভিন্ন analytics এবং performance
                        monitoring tool ব্যবহার করতে পারি। এর মধ্যে Cloudflare
                        Analytics, Microsoft Clarity এবং আমাদের নিজস্ব internal
                        analytics system অন্তর্ভুক্ত হতে পারে। এসব সিস্টেম
                        সাধারণত ভিজিট, ব্রাউজার, ডিভাইস, পেজ interaction এবং
                        ট্রাফিক pattern-এর মতো non-personal usage data সংগ্রহ
                        করে। আমরা এই তথ্য কোনো নির্দিষ্ট ব্যক্তিকে শনাক্ত করার
                        উদ্দেশ্যে ব্যবহার করি না।
                    </p>
                </div>
            </div>

            <!-- Section 7 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Trash class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৭. ব্যক্তিগত তথ্য মুছে ফেলার অনুরোধ
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack সাধারণ দর্শকদের কাছ থেকে কোনো ব্যক্তিগত
                        অ্যাকাউন্ট তথ্য সংগ্রহ বা সংরক্ষণ করে না। তাই সাধারণ
                        ব্যবহারকারীদের জন্য আলাদা data deletion request প্রযোজ্য
                        নয়। তবে কনট্রিবিউটর অ্যাকাউন্টের ক্ষেত্রে,
                        ব্যবহারকারীরা তাদের অ্যাকাউন্ট মুছে ফেলার অনুরোধ করতে
                        পারেন — যদিও তাদের পূর্বে প্রকাশিত কনটেন্ট প্ল্যাটফর্মে
                        থেকে যেতে পারে, যেমনটি Content Policy-তে ব্যাখ্যা করা
                        হয়েছে।
                    </p>
                </div>
            </div>

            <!-- Section 8 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Text class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৮. কনটেন্ট সম্পর্কিত তথ্য
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack-এ প্রকাশিত শিক্ষামূলক কনটেন্ট, তৃতীয় পক্ষের
                        রিসোর্স, কনটেন্ট মালিকানা এবং অপসারণ নীতিমালা সম্পর্কে
                        বিস্তারিত জানতে আমাদের
                        <Link
                            href="/content-policy"
                            class="text-indigo-600 underline dark:text-indigo-400"
                            >Content Policy
                        </Link>
                        দেখুন।
                    </p>
                </div>
            </div>
        </div>
    </main>
</template>
