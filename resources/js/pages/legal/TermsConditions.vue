<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Scale,
    FileText,
    HelpCircle,
    AlertCircle,
    Ban,
    UserCircle,
    Users,
    MessageSquare,
    BookOpen,
    ThumbsUp,
    RefreshCw,
} from 'lucide-vue-next';
</script>

<template>
    <Head>
        <title>Terms & Conditions</title>
        <meta
            name="description"
            content="Terms and Conditions of use for the HSCStack open educational platform."
        />
        <meta property="og:title" content="Terms & Conditions - HSCStack" />
    </Head>

    <header class="mx-auto max-w-3xl px-4 pt-8 pb-6 text-center sm:pt-12">
        <h1
            class="mb-4 text-4xl leading-tight font-black tracking-tight text-slate-950 sm:text-5xl dark:text-gray-100"
        >
            Terms & <span class="text-indigo-600">Conditions</span>
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
                এই প্ল্যাটফর্মটি অ্যাক্সেস বা ব্যবহার করার মাধ্যমে, আপনি নিচের
                Terms & Conditions মেনে চলতে এবং এতে বাউন্ড থাকতে সম্মত হচ্ছেন।
                এই নিয়মের কোনো অংশের সাথে আপনি একমত না হলে, আমাদের প্ল্যাটফর্ম
                ব্যবহার থেকে বিরত থাকুন।
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
                        ১. সার্ভিসের বর্ণনা
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack হলো HSC ও SSC স্টুডেন্টদের জন্য একটি
                        comprehensive open-learning প্ল্যাটফর্ম। এটি শুধু একটি
                        সাধারণ আর্কাইভ নয়, বরং এখানে রয়েছে:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>Dual curriculum (HSC ও SSC) ভিত্তিক কন্টেন্ট।</li>
                        <li>
                            ৫ ধরনের স্ট্রাকচার্ড রিসোর্স: নোটস, প্রশ্ন, PDF, ছবি
                            এবং ভিডিও।
                        </li>
                        <li>
                            রিয়েল-টাইম কমিউনিটি লাইভ চ্যাট, এডুকেশনাল ব্লগ এবং
                            ইউজার প্রোফাইল।
                        </li>
                        <li>
                            রিসোর্স কমপ্লিশন ট্র্যাকিং এবং এআই অ্যাসিস্ট্যান্ট
                            সুবিধা।
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Section 2 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <UserCircle class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ২. অ্যাকাউন্ট ও প্রমাণীকরণ
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        আমাদের প্ল্যাটফর্মে গুগল ওঅথ (Google OAuth 2.0) এর
                        মাধ্যমে নিরাপদ লগইন ব্যবস্থা রয়েছে, সাধারণ পাসওয়ার্ড
                        রেজিস্ট্রেশনের প্রয়োজন নেই। নতুন অ্যাকাউন্টের ক্ষেত্রে
                        স্বয়ংক্রিয়ভাবে একটি ইউজারনেম (যেমন: student_1234) তৈরি
                        হয়। ব্যবহারকারীরা তাদের প্রোফাইল কাস্টমাইজ করতে পারবেন।
                        নিজের অ্যাকাউন্টের নিরাপত্তা এবং অ্যাকাউন্টের অধীনে
                        হওয়া সকল কার্যকলাপের দায়ভার সম্পূর্ণভাবে ব্যবহারকারীর
                        নিজের।
                    </p>
                </div>
            </div>

            <!-- Section 3 -->
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
                        ৩. ব্যবহারকারীর আচরণবিধি
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        প্ল্যাটফর্মে একটি সুস্থ শিক্ষার পরিবেশ বজায় রাখতে সকল
                        ব্যবহারকারীকে সম্মানজনক আচরণ করতে হবে। স্প্যামিং,
                        হয়রানি বা কাউকে আক্রমণ করে কথা বলা সম্পূর্ণ নিষিদ্ধ।
                        প্ল্যাটফর্মের চ্যাট এবং অন্যান্য অংশে profanity filter
                        স্বয়ংক্রিয়ভাবে কাজ করে। এই নিয়মাবলী ভঙ্গ করলে যেকোনো
                        সময় অ্যাকাউন্ট সাসপেন্ড বা বাতিল করা হতে পারে।
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
                        ৪. লাইভ চ্যাট নিয়মাবলী
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        কমিউনিটি লাইভ চ্যাটে অশালীন বা আপত্তিকর শব্দ ব্যবহার করা
                        কঠোরভাবে নিষিদ্ধ, যা স্বয়ংক্রিয় profanity filter
                        দ্বারা নিয়ন্ত্রিত। স্প্যাম রোধে মেসেজ পাঠানোর মাঝে
                        নির্দিষ্ট সময়ের cooldown বা রেট লিমিট প্রযোজ্য। চ্যাটে
                        আপত্তিকর মেসেজ দেখলে ব্যবহারকারীরা রিপোর্ট করতে পারবেন।
                        ৫ বা ততোধিক রিপোর্ট জমা হলে স্বয়ংক্রিয়ভাবে ২৪ ঘণ্টার
                        জন্য চ্যাট থেকে ব্যান করা হবে। প্ল্যাটফর্মের স্টাফ বা
                        মডারেটরদের যেকোনো মেসেজ ডিলিট করা বা ব্যবহারকারীকে ব্যান
                        করার সম্পূর্ণ অধিকার রয়েছে।
                    </p>
                </div>
            </div>

            <!-- Section 5 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Scale class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৫. Intellectual Property ও Fair Use
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        আমাদের original টেক্সট লেআউট, সিস্টেম স্ক্রিপ্ট, কোড এবং
                        নিজস্ব ব্লগ আর্টিকেলের মালিকানা আমাদের। কনট্রিবিউটরদের
                        জমা দেওয়া নোট, ব্লগ বা অন্যান্য কনটেন্টের মূল মালিকানা
                        সংশ্লিষ্ট কনট্রিবিউটরের কাছেই থাকে — তবে তারা
                        HSCStack-কে এগুলো সংরক্ষণ, প্রদর্শন ও বিতরণের একটি
                        লাইসেন্স প্রদান করেন, যেমনটি আমাদের
                        <Link
                            href="/content-policy"
                            class="text-indigo-600 underline dark:text-indigo-400"
                            >Content Policy</Link
                        >-তে বিস্তারিত ব্যাখ্যা করা হয়েছে। অফিশিয়াল বোর্ডের
                        প্রশ্ন বা পাবলিক question structure সম্পূর্ণভাবে
                        <strong class="text-slate-700 dark:text-gray-300"
                            >Fair Use নীতির</strong
                        >
                        আওতায় শেয়ার করা হয়, স্পষ্ট educational backup হিসেবে।
                        প্ল্যাটফর্মের ডেটা বাল্ক scrape করা বা resell করা
                        কঠোরভাবে নিষিদ্ধ।
                    </p>
                </div>
            </div>

            <!-- Section 6 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <BookOpen class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৬. কনটেন্ট ও ব্লগ
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        ব্লগ সেকশনে শিক্ষামূলক ও তথ্যবহুল আর্টিকেল পাবলিশ করার
                        সুযোগ রয়েছে। ব্লগে অন্য ব্যবহারকারীদের আর্টিকেলে কমেন্ট
                        করা এবং রিঅ্যাকশন (love) দেওয়ার ব্যবস্থা আছে।
                        ব্যবহারকারীদের উচিত মানসম্মত ও গঠনমূলক ব্লগ পাবলিশ করা
                        এবং কমেন্ট সেকশনে ভদ্রতা বজায় রাখা। অন্যের কপিরাইটযুক্ত
                        লেখা নিজের নামে পাবলিশ করা যাবে না।
                    </p>
                </div>
            </div>

            <!-- Section 7 -->
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
                        ৭. কমিউনিটি ফিচার
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack-এ রয়েছে ফোল্ডার ভোটিং (আপভোট/ডাউনভোট),
                        প্রোফাইল অ্যাক্টিভিটি স্ট্রিম, সাজেস্টেড মেম্বারস এবং
                        ইউজার অ্যাপ্রিসিয়েশন (appreciations)। এই ফিচারগুলো একে
                        অপরকে সাহায্য করার জন্য ডিজাইন করা হয়েছে। কমিউনিটির
                        ভোটিং সিস্টেম বা অ্যাপ্রিসিয়েশন ফিচারের কোনো অপব্যবহার
                        বা ম্যানিপুলেশন করা কঠোরভাবে নিষিদ্ধ।
                    </p>
                </div>
            </div>

            <!-- Section 8 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <HelpCircle class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৮. এডুকেশনাল Accuracy সংক্রান্ত Disclaimer
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        আমরা clean curation এবং accuracy বজায় রাখার সর্বোচ্চ
                        চেষ্টা করলেও, সবকিছু "as-is" ভিত্তিতে শেয়ার করা হয়।
                        আমরা কোনো performance warranty বা শতভাগ সঠিকতার
                        গ্যারান্টি দিই না। সঠিক লার্নিং প্যারামিটার বজায় রাখতে
                        ব্যবহারকারীদের উচিত অফিশিয়াল টেক্সটবুক এবং শিক্ষকদের
                        গাইডলাইনের সাথে ডেটা মিলিয়ে দেখা।
                    </p>
                </div>
            </div>

            <!-- Section 9 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <AlertCircle class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৯. দায়বদ্ধতার সীমাবদ্ধতা
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        প্ল্যাটফর্মে থাকা কোনো রিসোর্স, রিয়েল-টাইম লাইভ চ্যাট
                        বা থার্ড-পার্টি কনটেন্ট ব্যবহারের কারণে সরাসরি বা
                        পরোক্ষভাবে ঘটা কোনো অ্যাকাডেমিক ক্ষতি, গ্রেডিং
                        ডিসক্রেপ্যান্সি, বা ডেটা হারানোর জন্য আমাদের প্ল্যাটফর্ম
                        এবং ডেভেলপাররা কোনো আইনি দায়বদ্ধতার সম্মুখীন হবেন না।
                        প্ল্যাটফর্মটি ব্যবহারকারীকে নিজ দায়িত্বে ব্যবহার করতে
                        হবে।
                    </p>
                </div>
            </div>

            <!-- Section 10 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Ban class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ১০. অ্যাকাউন্ট স্থগিতকরণ ও বাতিলকরণ
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        আমাদের Terms & Conditions,
                        <Link
                            href="/content-policy"
                            class="text-indigo-600 underline dark:text-indigo-400"
                            >Content Policy</Link
                        >, অথবা প্ল্যাটফর্মের নিরাপত্তা বিঘ্নিত করলে মডারেটর বা
                        অ্যাডমিন যেকোনো অ্যাকাউন্ট (ছাত্র বা কনট্রিবিউটর) স্থগিত
                        বা বাতিল করার অধিকার রাখেন। লাইভ চ্যাটে স্বয়ংক্রিয়
                        ব্যান ছাড়াও মডারেটররা ম্যানুয়ালি ব্যবস্থা নিতে পারেন।
                        গুরুতর নিয়মভঙ্গ, স্প্যামিং বা কপিরাইট লঙ্ঘনের ক্ষেত্রে
                        কোনো পূর্ব নোটিশ ছাড়াই কন্টেন্ট রিমুভ বা অ্যাকাউন্ট
                        স্থায়ীভাবে ডিলিট করা হতে পারে।
                    </p>
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
                        ১১. পরিবর্তন ও আপডেট
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        HSCStack যেকোনো সময় এই Terms & Conditions আপডেট বা
                        পরিবর্তন করার অধিকার সংরক্ষণ করে। গুরুত্বপূর্ণ কোনো
                        পরিবর্তন হলে তা প্ল্যাটফর্মে নোটিফিকেশন বা ঘোষণার
                        মাধ্যমে জানিয়ে দেওয়া হবে। পরিবর্তিত শর্তাবলী মেনে
                        নিয়ে প্ল্যাটফর্ম ব্যবহার চালিয়ে যাওয়া মানে আপনি নতুন
                        শর্তাবলীতে সম্মত।
                    </p>
                </div>
            </div>
        </div>
    </main>
</template>
