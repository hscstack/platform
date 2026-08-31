<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Shield,
    Eye,
    Lock,
    Cookie,
    Text,
    Trash,
    LocateIcon,
    PersonStanding,
    Mail,
    MessageSquare,
} from 'lucide-vue-next';
</script>

<template>
    <Head>
        <title>Privacy Policy</title>
        <meta
            name="description"
            content="Privacy Policy for HSCStack. Learn how we handle your data, protect user privacy, and ensure transparent practices."
        />
        <meta property="og:title" content="Privacy Policy - HSCStack" />
    </Head>

    <header class="mx-auto max-w-3xl px-4 pt-8 pb-6 text-center sm:pt-12">
        <h1
            class="mb-4 text-4xl leading-tight font-black tracking-tight text-slate-950 sm:text-5xl dark:text-gray-100"
        >
            Privacy <span class="text-indigo-600">Policy</span>
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
                        প্ল্যাটফর্মের ফিচারগুলো নিরবচ্ছিন্নভাবে প্রদানের জন্য
                        আমরা কিছু নির্দিষ্ট তথ্য সংগ্রহ করি:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li>
                            <strong class="text-slate-700 dark:text-gray-300"
                                >অ্যাকাউন্ট ডেটা:</strong
                            >
                            Google OAuth 2.0 এর মাধ্যমে লগইন করার সময় আপনার
                            নাম, ইমেইল, Google ID এবং প্রোফাইল ছবি সংগ্রহ করা
                            হয়।
                        </li>
                        <li>
                            <strong class="text-slate-700 dark:text-gray-300"
                                >প্রোফাইল ডেটা:</strong
                            >
                            আপনার বায়ো, শিক্ষাপ্রতিষ্ঠানের নাম, অ্যাভাটার এবং
                            সোশ্যাল লিংক (Facebook, Instagram, GitHub)।
                        </li>
                        <li>
                            <strong class="text-slate-700 dark:text-gray-300"
                                >ইউজার অ্যাক্টিভিটি:</strong
                            >
                            চ্যাট মেসেজ, ফোরামের প্রশ্ন ও উত্তর, আপলোডকৃত ছবির
                            ফাইল, ফোরামের আপভোট ও ডাউনভোট, রিসোর্স কমপ্লিশন
                            স্ট্যাটাস, ফোল্ডার ভোটিং, অ্যাপ্রিসিয়েশন, ব্লগ
                            কমেন্ট ও রিঅ্যাকশন এবং আপলোড করা ফাইলসমূহ।
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
                        একটি সুরক্ষিত এবং seamless অভিজ্ঞতা নিশ্চিত করতে আমরা
                        কুকিজ ও লোকাল স্টোরেজ ব্যবহার করি। এর মধ্যে রয়েছে
                        Session Cookies এবং CSRF টোকেন যা সিকিউরিটির জন্য জরুরি।
                        পাশাপাশি আপনার সিলেক্ট করা কোর্সের জন্য preferred_course
                        কুকি এবং আমাদের PWA অ্যাপের জন্য Service Worker ব্যবহার
                        করা হয়। আমরা কোনো ধরনের থার্ড-পার্টি অ্যাডভার্টাইজিং
                        কুকিজ ব্যবহার করি না।
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
                        প্ল্যাটফর্মের কার্যক্রম পরিচালনার জন্য আমরা কিছু
                        নির্ভরযোগ্য থার্ড-পার্টি সার্ভিস ব্যবহার করি:
                    </p>
                    <ul
                        class="mt-2 list-disc space-y-1 pl-5 text-sm font-medium text-slate-500 dark:text-gray-400"
                    >
                        <li><strong>অথেন্টিকেশন:</strong> Google OAuth</li>
                        <li>
                            <strong>রিয়েল-টাইম সার্ভিস:</strong> Pusher
                            (WebSockets)
                        </li>
                        <li>
                            <strong>স্টোরেজ:</strong> AWS S3 / Cloudflare R2
                        </li>
                        <li><strong>অ্যানালিটিক্স:</strong> PostHog</li>
                        <li>
                            <strong>অন্যান্য:</strong> Short.io (লিংক শর্টেনার),
                            YouTube (এম্বেড করা ভিডিও) এবং নির্ভরযোগ্য ইমেইল
                            প্রোভাইডার (Postmark/Resend/SES)।
                        </li>
                    </ul>
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
                        আপনার ডেটার সর্বোচ্চ নিরাপত্তা নিশ্চিত করতে আমরা HTTPS
                        এনক্রিপশন, সিকিউর ক্লাউড স্টোরেজ, মজবুত পাসওয়ার্ড
                        হ্যাশিং এবং প্রোডাকশন লেভেলের সেফগার্ড ব্যবহার করি।
                        স্প্যাম রোধে রয়েছে রেট লিমিটিং ব্যবস্থা। এছাড়া ডেটা লস
                        এড়াতে আমাদের ডাটাবেস এবং ফাইলগুলো স্বয়ংক্রিয়ভাবে
                        Google Drive-এ ব্যাকআপ নেওয়া হয়। তবে অনলাইনে কোনো
                        সিস্টেমই ১০০% সুরক্ষিত নয়।
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
                        ৫. কনট্রিবিউটর ও ব্যবহারকারী তথ্য
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        Google sign-in থেকে প্রাপ্ত আপনার পাবলিক প্রোফাইল তথ্য
                        (যেমন: নাম, ইউজারনেম, প্রোফাইল ছবি, শিক্ষা প্রতিষ্ঠান ও
                        বায়ো), ফোরামে পোস্টকৃত প্রশ্ন ও উত্তর, এবং আপলোড করা
                        কনটেন্ট অন্যান্য শিক্ষার্থীদের জন্য উন্মুক্ত ও দৃশ্যমান
                        থাকবে। আপনার ব্যক্তিগত ইমেইল বা Google ID কখনোই
                        সর্বসাধারণের জন্য প্রকাশ করা হয় না। আপনি চাইলে সেটিংস
                        থেকে প্রোফাইল ভিজিবিলিটি নিয়ন্ত্রণ করতে পারেন এবং ইমেইল
                        প্রেফারেন্স (receive_emails) অন/অফ করতে পারেন।
                        প্ল্যাটফর্মে কোনো প্রশ্ন, উত্তর বা কনটেন্ট আপলোড করলে
                        তার সাথে আপনার নাম ও প্রোফাইল সংযুক্ত (Attribution)
                        থাকে।
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
                        প্ল্যাটফর্ম উন্নত করার উদ্দেশ্যে আমরা মূলত PostHog
                        ব্যবহার করি যা pageviews, unique visitors, active users
                        এবং referrers সম্পর্কে ধারণা দেয়। পাশাপাশি Cloudflare ও
                        আমাদের ইন্টার্নাল অ্যানালিটিক্স কাজ করে। এসব ডেটা
                        সম্পূর্ণ রূপে non-personal এবং শুধু এগ্রিগেট আকারে
                        ব্যবহৃত হয়।
                    </p>
                </div>
            </div>

            <!-- Section 7 -->
            <div class="flex items-start gap-4">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    <Mail class="h-4 w-4 stroke-[2.2]" />
                </div>
                <div>
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        ৭. ইমেইল কমিউনিকেশন
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        ব্যবহারকারীদের সাথে যোগাযোগ রাখার জন্য আমরা ওয়েলকাম
                        ইমেইল, নির্দিষ্ট মাইলস্টোন নোটিফিকেশন, ব্লগে কমেন্ট
                        অ্যালার্ট এবং গুরুত্বপূর্ণ ঘোষণা (bulk announcements)
                        পাঠিয়ে থাকি। আপনি চাইলে যেকোনো সময় আপনার অ্যাকাউন্ট
                        সেটিংস বা ইমেইলের নিচে থাকা আনসাবস্ক্রাইব লিংকে ক্লিক
                        করে এসব ইমেইল বন্ধ করতে পারেন।
                    </p>
                </div>
            </div>

            <!-- Section 8 -->
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
                        ৮. লাইভ চ্যাট ডেটা
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        আমাদের রিয়েল-টাইম লাইভ চ্যাটে পাঠানো মেসেজ, রিপ্লাই এবং
                        রিঅ্যাকশন ডাটাবেসে সেভ করা হয়। চ্যাটের পারফরম্যান্স ঠিক
                        রাখতে আমরা সাধারণত নির্দিষ্ট সংখ্যক (যেমন ~২০০) লেটেস্ট
                        মেসেজ রেখে পুরনো মেসেজ মুছে ফেলি (rolling pruning)।
                        এছাড়া চ্যাটের সুষ্ঠু পরিবেশ বজায় রাখার জন্য ইউজার
                        রিপোর্ট সেভ করা হয় এবং নিয়ম ভাঙলে অটো-ব্যান মেকানিজম
                        রয়েছে। শুধুমাত্র প্ল্যাটফর্মের মডারেটর বা স্টাফরা
                        নিয়মভঙ্গকারী মেসেজ ডিলিট করতে পারেন।
                    </p>
                </div>
            </div>

            <!-- Section 9 -->
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
                        ৯. ব্যক্তিগত তথ্য মুছে ফেলার অনুরোধ
                    </h2>
                    <p
                        class="mt-1.5 text-sm leading-relaxed font-medium text-slate-500 dark:text-gray-400"
                    >
                        আপনি চাইলে যেকোনো সময় আপনার অ্যাকাউন্ট ডিলিট করার
                        অনুরোধ করতে পারেন। অ্যাকাউন্ট মুছে ফেললে আপনার প্রোফাইল
                        তথ্য এবং পার্সোনাল ডেটা মুছে যাবে, তবে পূর্বে আপলোড করা
                        রিসোর্স বা কনটেন্ট প্ল্যাটফর্মে থেকে যেতে পারে (কনটেন্ট
                        রিটেনশন পলিসি অনুযায়ী)। বিস্তারিত আমাদের Content
                        Policy-তে দেখুন।
                    </p>
                </div>
            </div>

            <!-- Section 10 -->
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
                        ১০. কনটেন্ট সম্পর্কিত তথ্য
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
