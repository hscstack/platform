<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Scale, ArrowLeft, FileText, HelpCircle, AlertCircle, Ban } from 'lucide-vue-next';
</script>

<template>
    <header class="mx-auto max-w-3xl px-4 pt-12 pb-8 text-center sm:pt-16">
        <Link
            href="/"
            class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-indigo-600 transition-colors mb-4 group"
        >
            <ArrowLeft class="h-3.5 w-3.5 transition-transform group-hover:-translate-x-0.5" />
            হোমে ফিরে যান
        </Link>
        <h1 class="mb-4 text-4xl leading-tight font-black tracking-tight text-slate-950 sm:text-5xl">
            Terms & <span class="text-indigo-600">Conditions</span>
        </h1>
        <p class="mx-auto max-w-md text-xs font-semibold text-slate-400 uppercase tracking-wider">
            সর্বশেষ আপডেট: জুলাই ২০২৬
        </p>
    </header>

    <main class="mx-auto max-w-3xl px-4 pb-20 sm:px-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 space-y-8">

            <p class="text-sm font-medium leading-relaxed text-slate-600">
                এই আর্কাইভ অ্যাক্সেস বা ব্রাউজ করার মাধ্যমে, আপনি নিচের Terms & Conditions মেনে চলতে এবং এতে বাউন্ড থাকতে সম্মত হচ্ছেন। এই নিয়মের কোনো অংশের সাথে আপনি একমত না হলে, আমাদের educational directory ব্যবহার থেকে বিরত থাকুন।
            </p>

            <hr class="border-slate-100" />

            <!-- Section 1 -->
            <div class="flex gap-4 items-start">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <FileText class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">১. সার্ভিসের বর্ণনা</h2>
                    <p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-500">
                        এই প্ল্যাটফর্ম HSC & SSC স্টুডেন্টদের জন্য একটা fast, distraction-free স্টাডি আর্কাইভ হিসেবে কাজ করে। আমরা হোস্ট করি:
                    </p>
                    <ul class="list-disc pl-5 mt-2 text-sm font-medium text-slate-500 space-y-1">
                        <li>ব্যক্তিগতভাবে হাতে লেখা নোট, প্র্যাকটিক্যাল রেকর্ড, এবং structural গাইড।</li>
                        <li>সিলেবাস-এলাইনড publicly available কনটেন্ট।</li>
                        <li>আগের পরীক্ষার সাইকেলের ঐতিহাসিক public question bank।</li>
                    </ul>
                </div>
            </div>

            <!-- Section 2 -->
            <div class="flex gap-4 items-start">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <Scale class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">২. Intellectual Property ও Fair Use</h2>
                    <p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-500">
                        আমাদের original টেক্সট লেআউট, সিস্টেম স্ক্রিপ্ট, এবং আমাদের নিজস্বভাবে লেখা নোটের মালিকানা আমাদের। কনট্রিবিউটরদের জমা দেওয়া নোট বা কনটেন্টের মূল মালিকানা সংশ্লিষ্ট কনট্রিবিউটরের কাছেই থাকে — এই ক্ষেত্রে তারা শুধুমাত্র HSCStack-কে সংরক্ষণ, প্রদর্শন ও বিতরণের একটি লাইসেন্স প্রদান করেন, যেমনটি আমাদের
                        <Link
                            href="/content-policy"
                            class="text-indigo-600 underline"
                            >Content Policy</Link
                        >-তে বিস্তারিত ব্যাখ্যা করা হয়েছে। স্টুডেন্টদের প্রাইভেট স্টাডি ব্যবহারের জন্য অ্যাক্সেস দেওয়া হয়। অফিশিয়াল state board evaluation প্রশ্ন বা public question structure সম্পূর্ণভাবে <strong class="text-slate-700">Fair Use নীতির</strong> আওতায় শেয়ার করা হয়, স্পষ্ট educational backup হিসেবে। অফিশিয়াল বোর্ড আইটেমের ওপর আমরা কোনো dynamic entity right দাবি করি না। প্ল্যাটফর্মের ডেটা বাল্ক scrape করা বা resell করা কঠোরভাবে নিষিদ্ধ।
                    </p>
                </div>
            </div>

            <!-- Section 3 -->
            <div class="flex gap-4 items-start">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <HelpCircle class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">৩. এডুকেশনাল Accuracy সংক্রান্ত Disclaimer</h2>
                    <p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-500">
                        আমরা clean curation এবং accuracy বজায় রাখার চেষ্টা করলেও, সবকিছু "as-is" ভিত্তিতে শেয়ার করা হয়। আমরা কোনো performance warranty দিই না, এবং সঠিক লার্নিং প্যারামিটার বজায় রাখতে ব্যবহারকারীদের উচিত অফিশিয়াল টেক্সটবুকের সাথে ডেটা স্ট্রিম মিলিয়ে দেখা।
                    </p>
                </div>
            </div>

            <!-- Section 4 -->
            <div class="flex gap-4 items-start">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <AlertCircle class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">৪. দায়বদ্ধতার সীমাবদ্ধতা</h2>
                    <p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-500">
                        আর্কাইভাল কনটেন্ট ব্যবহারের কারণে সরাসরি বা পরোক্ষভাবে ঘটা কোনো internal data outage, গ্রেডিং ডিসক্রেপ্যান্সি, বা contextual error-এর জন্য আমাদের প্ল্যাটফর্ম এবং ডেভেলপমেন্ট মেইনটেইনাররা কোনো আইনি দায়বদ্ধতার সম্মুখীন হবে না।
                    </p>
                </div>
            </div>

            <!-- Section 5 (NEW) -->
            <div class="flex gap-4 items-start">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <Ban class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">৫. অ্যাকাউন্ট স্থগিতকরণ ও বাতিলকরণ</h2>
                    <p class="mt-1.5 text-sm font-medium leading-relaxed text-slate-500">
                        HSCStack এই Terms & Conditions, আমাদের
                        <Link
                            href="/content-policy"
                            class="text-indigo-600 underline"
                            >Content Policy</Link
                        >, অথবা প্ল্যাটফর্মের জন্য নিরাপত্তাঝুঁকি তৈরি করে এমন যেকোনো অ্যাকাউন্ট (কনট্রিবিউটর বা ব্যবহারকারী) সীমিত, স্থগিত বা স্থায়ীভাবে বাতিল করার অধিকার সংরক্ষণ করে। এর মধ্যে থাকতে পারে — কপিরাইট লঙ্ঘন, স্প্যাম বা প্রতারণামূলক submission, ক্ষতিকর ফাইল আপলোড, অথবা প্ল্যাটফর্মের সিস্টেম বা অন্য ব্যবহারকারীদের ক্ষতি করার চেষ্টা। সিদ্ধান্ত নেওয়ার আগে HSCStack পরিস্থিতি অনুযায়ী পর্যালোচনা করার চেষ্টা করবে, তবে গুরুতর নিরাপত্তা ঝুঁকির ক্ষেত্রে তাৎক্ষণিক ব্যবস্থা নেওয়া হতে পারে।
                    </p>
                </div>
            </div>

        </div>
    </main>
</template>
