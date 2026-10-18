<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Trash2,
    ArrowLeft,
    ArrowRight,
    Loader2,
    ChevronDown,
    User,
    ShieldAlert,
    Clock,
    MailCheck,
    CheckCircle2,
    FileText,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface UserInfo {
    id: number;
    name: string;
    username: string;
    email?: string;
    image_url?: string | null;
}

const props = defineProps<{
    user?: UserInfo;
    openTicketsCount?: number;
}>();

const page = usePage();
const authUser = computed<UserInfo>(
    () => (props.user || page.props.auth?.user) as UserInfo,
);

const isTicketLimitReached = computed(() => (props.openTicketsCount ?? 0) >= 3);

// Stepper State (1: সতর্কতা, 2: কারণ, 3: প্রক্রিয়া, 4: নিশ্চিতকরণ)
const currentStep = ref(1);

const selectedReason = ref('');
const feedback = ref('');
const isConfirmed = ref(false);

const reasons = [
    {
        value: 'exams_done',
        label: 'পরীক্ষা শেষ / আর প্রয়োজন নেই',
    },
    {
        value: 'switched_platform',
        label: 'অন্য প্ল্যাটফর্ম বা মাধ্যমে পড়াশোনা করছি',
    },
    {
        value: 'privacy_concerns',
        label: 'প্রাইভেসি বা তথ্যের সুরক্ষা সংক্রান্ত কারণ',
    },
    {
        value: 'technical_issues',
        label: 'বাগ বা কোনো টেকনিক্যাল সমস্যা',
    },
    {
        value: 'too_many_notifications',
        label: 'অতিরিক্ত নোটিফিকেশন বা ইমেইল',
    },
    {
        value: 'other',
        label: 'অন্যান্য কারণ',
    },
];

const form = useForm({
    category: 'account_issue',
    subject: '',
    message: '',
    general: '' as string | undefined,
});

const goToNextStep = () => {
    if (currentStep.value < 4) {
        currentStep.value++;
    }
};

const goToPrevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submitDeleteTicket = () => {
    if (isTicketLimitReached.value || !isConfirmed.value) {
        return;
    }

    const reasonLabel =
        reasons.find((r) => r.value === selectedReason.value)?.label ||
        selectedReason.value ||
        'কোনো কারণ উল্লেখ করা হয়নি';

    const ticketSubject = 'Account Deletion Request';

    const ticketMessage = [
        '=== ACCOUNT DELETION REQUEST ===',
        '',
        `Reason: ${reasonLabel}`,
        `Feedback/Note: ${feedback.value.trim() || 'None provided'}`,
        '',
        'User completed 4-step deletion request flow agreeing to 48h signed email confirmation.',
    ].join('\n');

    form.category = 'account_issue';
    form.subject = ticketSubject;
    form.message = ticketMessage;

    form.post('/support/tickets', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head>
        <title>Delete Account - HSCStack</title>
        <meta
            name="description"
            content="Delete account request on HSCStack."
        />
    </Head>

    <div class="mx-auto w-full max-w-xl px-3 py-3 sm:px-6 sm:py-8">
        <!-- Back Link & Header Row -->
        <div class="mb-3 flex items-center justify-between sm:mb-4">
            <Link
                href="/profile"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition-colors hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-100"
            >
                <ArrowLeft class="h-3.5 w-3.5" />
                <span>অ্যাকাউন্ট সেটিংসে ফিরে যান</span>
            </Link>
        </div>

        <!-- Limit Alert if user has >= 3 open tickets -->
        <div
            v-if="isTicketLimitReached"
            class="mb-3 rounded-2xl border border-amber-200 bg-amber-50/90 p-3.5 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
        >
            <div class="flex items-start gap-2.5">
                <AlertTriangle
                    class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                />
                <div class="space-y-1">
                    <p class="font-bold">সাপোর্ট টিকিট সীমা পূর্ণ হয়েছে</p>
                    <p class="leading-relaxed">
                        আপনার ইতিমধ্যে ৩টি সক্রিয় সাপোর্ট টিকিট উন্মুক্ত
                        রয়েছে। নতুন রিকোয়েস্ট পাঠাতে পূর্বের টিকিট নিষ্পত্তি
                        হওয়া পর্যন্ত অপেক্ষা করুন।
                    </p>
                    <Link
                        href="/support"
                        class="mt-1.5 inline-flex items-center gap-1 font-semibold text-amber-800 underline hover:text-amber-950 dark:text-amber-300 dark:hover:text-amber-200"
                    >
                        <span>আমার টিকিটগুলো দেখুন</span>
                        <ArrowLeft class="h-3 w-3 rotate-180" />
                    </Link>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4.5 shadow-sm sm:p-7 dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Header -->
            <div
                class="mb-4 flex items-center gap-3 sm:mb-6 sm:items-start sm:gap-4"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 sm:h-11 sm:w-11 dark:bg-rose-500/15 dark:text-rose-400"
                >
                    <Trash2 class="h-5 w-5" />
                </div>
                <div>
                    <h1
                        class="text-lg font-bold text-slate-900 sm:text-xl dark:text-gray-100"
                    >
                        অ্যাকাউন্ট ডিলিট রিকোয়েস্ট
                    </h1>
                    <p
                        class="text-[11px] text-slate-500 sm:text-xs dark:text-gray-400"
                    >
                        অ্যাকাউন্ট ও সংশ্লিষ্ট সকল ডেটা স্থায়ীভাবে মুছে ফেলার
                        জন্য আবেদন জমা নেওয়া হবে।
                    </p>
                </div>
            </div>

            <!-- 4-Step Progress Indicator -->
            <div
                class="mb-4 rounded-xl border border-slate-100 bg-slate-50/70 p-2.5 sm:mb-6 sm:p-3 dark:border-gray-800 dark:bg-gray-800/40"
            >
                <div class="flex items-center justify-between">
                    <!-- Step 1: সতর্কতা -->
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold transition-colors"
                            :class="[
                                currentStep === 1
                                    ? 'bg-rose-600 text-white shadow-xs'
                                    : currentStep > 1
                                      ? 'bg-emerald-600 text-white'
                                      : 'bg-slate-200 text-slate-600 dark:bg-gray-700 dark:text-gray-400',
                            ]"
                        >
                            <CheckCircle2
                                v-if="currentStep > 1"
                                class="h-3.5 w-3.5"
                            />
                            <span v-else>১</span>
                        </div>
                        <span
                            class="text-xs font-medium"
                            :class="[
                                currentStep === 1
                                    ? 'font-bold text-slate-900 dark:text-white'
                                    : 'text-slate-500 dark:text-gray-400',
                            ]"
                        >
                            সতর্কতা
                        </span>
                    </div>

                    <div
                        class="h-0.5 w-4 bg-slate-200 sm:w-8 dark:bg-gray-700"
                    ></div>

                    <!-- Step 2: কারণ -->
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold transition-colors"
                            :class="[
                                currentStep === 2
                                    ? 'bg-rose-600 text-white shadow-xs'
                                    : currentStep > 2
                                      ? 'bg-emerald-600 text-white'
                                      : 'bg-slate-200 text-slate-600 dark:bg-gray-700 dark:text-gray-400',
                            ]"
                        >
                            <CheckCircle2
                                v-if="currentStep > 2"
                                class="h-3.5 w-3.5"
                            />
                            <span v-else>২</span>
                        </div>
                        <span
                            class="text-xs font-medium"
                            :class="[
                                currentStep === 2
                                    ? 'font-bold text-slate-900 dark:text-white'
                                    : 'text-slate-500 dark:text-gray-400',
                            ]"
                        >
                            কারণ
                        </span>
                    </div>

                    <div
                        class="h-0.5 w-4 bg-slate-200 sm:w-8 dark:bg-gray-700"
                    ></div>

                    <!-- Step 3: প্রক্রিয়া -->
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold transition-colors"
                            :class="[
                                currentStep === 3
                                    ? 'bg-rose-600 text-white shadow-xs'
                                    : currentStep > 3
                                      ? 'bg-emerald-600 text-white'
                                      : 'bg-slate-200 text-slate-600 dark:bg-gray-700 dark:text-gray-400',
                            ]"
                        >
                            <CheckCircle2
                                v-if="currentStep > 3"
                                class="h-3.5 w-3.5"
                            />
                            <span v-else>৩</span>
                        </div>
                        <span
                            class="text-xs font-medium"
                            :class="[
                                currentStep === 3
                                    ? 'font-bold text-slate-900 dark:text-white'
                                    : 'text-slate-500 dark:text-gray-400',
                            ]"
                        >
                            প্রক্রিয়া
                        </span>
                    </div>

                    <div
                        class="h-0.5 w-4 bg-slate-200 sm:w-8 dark:bg-gray-700"
                    ></div>

                    <!-- Step 4: নিশ্চিতকরণ -->
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold transition-colors"
                            :class="[
                                currentStep === 4
                                    ? 'bg-rose-600 text-white shadow-xs'
                                    : 'bg-slate-200 text-slate-600 dark:bg-gray-700 dark:text-gray-400',
                            ]"
                        >
                            <span>৪</span>
                        </div>
                        <span
                            class="text-xs font-medium"
                            :class="[
                                currentStep === 4
                                    ? 'font-bold text-slate-900 dark:text-white'
                                    : 'text-slate-500 dark:text-gray-400',
                            ]"
                        >
                            নিশ্চিতকরণ
                        </span>
                    </div>
                </div>
            </div>

            <!-- User Info Pill -->
            <div
                class="mb-4 flex items-center gap-2.5 rounded-xl bg-slate-50 p-2.5 text-xs sm:p-3 dark:bg-gray-800/60"
            >
                <img
                    v-if="authUser?.image_url"
                    :src="authUser.image_url"
                    :alt="authUser.name"
                    class="h-7 w-7 rounded-full object-cover ring-1 ring-slate-200 sm:h-8 sm:w-8 dark:ring-gray-700"
                />
                <div
                    v-else
                    class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 text-slate-600 sm:h-8 sm:w-8 dark:bg-gray-700 dark:text-gray-300"
                >
                    <User class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <p
                        class="truncate font-bold text-slate-900 dark:text-gray-100"
                    >
                        {{ authUser?.name }}
                    </p>
                    <p
                        class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                    >
                        @{{ authUser?.username }}
                        <span
                            v-if="authUser?.email"
                            class="ml-1 font-mono text-slate-400 dark:text-gray-500"
                        >
                            • {{ authUser.email }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Error banner -->
            <div
                v-if="form.errors.general"
                class="mb-4 rounded-xl border border-rose-200 bg-rose-50/80 p-3 text-xs font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400"
            >
                {{ form.errors.general }}
            </div>

            <!-- STEP 1: সতর্কতা (Warnings & Data loss scope) -->
            <div
                v-show="currentStep === 1"
                class="flex min-h-[360px] flex-col justify-between"
            >
                <div class="space-y-4">
                    <div
                        class="rounded-xl border border-rose-200/80 bg-rose-50/50 p-4 text-xs text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/20 dark:text-rose-200"
                    >
                        <div class="flex items-start gap-2.5">
                            <ShieldAlert
                                class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                            />
                            <div class="space-y-2 leading-relaxed">
                                <p class="font-bold">গুরুত্বপূর্ণ সতর্কতা:</p>
                                <ul
                                    class="list-inside list-disc space-y-1.5 pl-0.5"
                                >
                                    <li>
                                        আপনার প্রোফাইল, স্টাডি ট্র্যাকার ও
                                        ব্যক্তিগত তথ্যসহ যাবতীয় সবকিছু
                                        স্থায়ীভাবে মুছে যাবে।
                                    </li>
                                    <li>
                                        আমাদের
                                        <Link
                                            href="/terms-service"
                                            target="_blank"
                                            class="font-semibold underline hover:text-rose-950 dark:hover:text-white"
                                            >Terms & Conditions</Link
                                        >
                                        ও
                                        <Link
                                            href="/privacy-policy"
                                            target="_blank"
                                            class="font-semibold underline hover:text-rose-950 dark:hover:text-white"
                                            >Privacy Policy</Link
                                        >
                                        অনুসারে আপনার আপলোড করা রিসোর্স ও নোট
                                        প্ল্যাটফর্মে অক্ষত থাকবে।
                                    </li>
                                    <li>
                                        অ্যাকাউন্ট মুছে ফেলার প্রক্রিয়া সম্পন্ন
                                        হওয়ার পর তা কোনোভাবেই পূর্বাবস্থায়
                                        ফিরিয়ে আনা সম্ভব হবে না।
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4 dark:border-gray-800"
                >
                    <Link
                        href="/profile"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        বাতিল
                    </Link>
                    <button
                        type="button"
                        @click="goToNextStep"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                    >
                        <span>পরবর্তী ধাপ</span>
                        <ArrowRight class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>

            <!-- STEP 2: কারণ (Reason & Feedback) -->
            <div
                v-show="currentStep === 2"
                class="flex min-h-[360px] flex-col justify-between"
            >
                <div class="space-y-4">
                    <!-- Reason dropdown (Optional) -->
                    <div>
                        <label
                            for="reason"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            কারণ নির্বাচন করুন (ঐচ্ছিক)
                        </label>
                        <div class="relative">
                            <select
                                id="reason"
                                v-model="selectedReason"
                                class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-xs text-slate-800 shadow-2xs focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200"
                            >
                                <option value="">
                                    -- কারণ বেছে নিন (ঐচ্ছিক) --
                                </option>
                                <option
                                    v-for="reason in reasons"
                                    :key="reason.value"
                                    :value="reason.value"
                                >
                                    {{ reason.label }}
                                </option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                            />
                        </div>
                    </div>

                    <!-- Feedback / Notes (Optional) -->
                    <div>
                        <label
                            for="feedback"
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            মতামত বা অতিরিক্ত তথ্য (ঐচ্ছিক)
                        </label>
                        <textarea
                            id="feedback"
                            v-model="feedback"
                            rows="4"
                            maxlength="1000"
                            class="w-full rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-800 placeholder-slate-400 shadow-2xs focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200 dark:placeholder-gray-500"
                            placeholder="প্ল্যাটফর্ম সম্পর্কে আপনার কোনো অভিযোগ বা পরামর্শ থাকলে জানাতে পারেন..."
                        ></textarea>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between gap-2.5 border-t border-slate-100 pt-4 dark:border-gray-800"
                >
                    <button
                        type="button"
                        @click="goToPrevStep"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        <span>পূর্ববর্তী ধাপ</span>
                    </button>
                    <button
                        type="button"
                        @click="goToNextStep"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                    >
                        <span>পরবর্তী ধাপ</span>
                        <ArrowRight class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>

            <!-- STEP 3: প্রক্রিয়া (Process Timeline) -->
            <div
                v-show="currentStep === 3"
                class="flex min-h-[360px] flex-col justify-between"
            >
                <div class="space-y-3">
                    <div
                        class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3.5 text-xs text-indigo-950 dark:border-indigo-900/40 dark:bg-indigo-950/20 dark:text-indigo-200"
                    >
                        <p
                            class="text-xs leading-relaxed text-slate-700 dark:text-gray-300"
                        >
                            ব্যবহারকারীদের নিরাপত্তার স্বার্থে নিচের পদ্ধতি
                            অনুসারে অ্যাকাউন্ট মুছে ফেলা হয়:
                        </p>
                    </div>

                    <!-- Process Timeline Cards -->
                    <div class="space-y-2.5">
                        <div
                            class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-gray-800 dark:bg-gray-800/40"
                        >
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400"
                            >
                                <Clock class="h-3.5 w-3.5" />
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold text-slate-900 dark:text-white"
                                >
                                    ১. আবেদন পর্যালোচনা (সর্বোচ্চ ৪৮ ঘণ্টা)
                                </p>
                                <p
                                    class="mt-0.5 text-[11px] leading-relaxed text-slate-500 dark:text-gray-400"
                                >
                                    আপনার অনুরোধটি পাওয়ার পর অ্যাডমিন টিম
                                    সর্বোচ্চ ৪৮ ঘণ্টার মধ্যে প্রসেস করবে।
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-gray-800 dark:bg-gray-800/40"
                        >
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400"
                            >
                                <MailCheck class="h-3.5 w-3.5" />
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold text-slate-900 dark:text-white"
                                >
                                    ২. ইমেইলে সিকিউর কনফার্মেশন লিংক
                                </p>
                                <p
                                    class="mt-0.5 text-[11px] leading-relaxed text-slate-500 dark:text-gray-400"
                                >
                                    সর্বোচ্চ ৪৮ ঘণ্টার মধ্যে আপনার ইমেইলে একটি
                                    সুরক্ষিত কনফার্মেশন লিংক পাঠানো হবে। ইনবক্সে
                                    না পেলে অনুগ্রহ করে স্প্যাম (Spam/Junk)
                                    ফোল্ডার চেক করুন।
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-gray-800 dark:bg-gray-800/40"
                        >
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </div>
                            <div>
                                <p
                                    class="text-xs font-bold text-slate-900 dark:text-white"
                                >
                                    ৩. চূড়ান্ত ডিলিট সম্পন্ন
                                </p>
                                <p
                                    class="mt-0.5 text-[11px] leading-relaxed text-slate-500 dark:text-gray-400"
                                >
                                    লিংকে ক্লিক করে কনফার্ম করলে তাৎক্ষণিকভাবে
                                    অ্যাকাউন্ট স্থায়ীভাবে ডিলিট হয়ে যাবে।
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between gap-2.5 border-t border-slate-100 pt-4 dark:border-gray-800"
                >
                    <button
                        type="button"
                        @click="goToPrevStep"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        <span>পূর্ববর্তী ধাপ</span>
                    </button>
                    <button
                        type="button"
                        @click="goToNextStep"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                    >
                        <span>এগিয়ে যান</span>
                        <ArrowRight class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>

            <!-- STEP 4: নিশ্চিতকরণ (Final Consent & Submit) -->
            <div
                v-show="currentStep === 4"
                class="flex min-h-[360px] flex-col justify-between"
            >
                <div class="space-y-4">
                    <!-- Legal info summary -->
                    <div
                        class="space-y-2 rounded-xl border border-slate-100 bg-slate-50/80 p-3.5 text-xs text-slate-600 dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-400"
                    >
                        <div
                            class="flex items-center gap-1.5 font-semibold text-slate-900 dark:text-white"
                        >
                            <FileText class="h-4 w-4 text-slate-500" />
                            <span>আইনি নির্দেশিকা ও পলিসি:</span>
                        </div>
                        <p class="text-[11px] leading-relaxed">
                            মুছে ফেলার আবেদন জমা করার পূর্বে অনুগ্রহ করে আমাদের
                            <Link
                                href="/privacy-policy"
                                target="_blank"
                                class="font-semibold text-indigo-600 underline hover:text-indigo-700 dark:text-indigo-400"
                            >
                                Privacy Policy
                            </Link>
                            এবং
                            <Link
                                href="/terms-service"
                                target="_blank"
                                class="font-semibold text-indigo-600 underline hover:text-indigo-700 dark:text-indigo-400"
                            >
                                Terms & Conditions
                            </Link>
                            পড়ুন।
                        </p>
                    </div>

                    <!-- Confirmation Checkbox -->
                    <div
                        class="rounded-xl border border-rose-100 bg-rose-50/40 p-3.5 dark:border-rose-900/30 dark:bg-rose-950/20"
                    >
                        <label
                            class="flex cursor-pointer items-start gap-2.5 text-xs text-slate-700 dark:text-gray-300"
                        >
                            <input
                                v-model="isConfirmed"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500 dark:border-gray-700 dark:bg-gray-900"
                            />
                            <span
                                class="leading-relaxed font-medium text-slate-900 select-none dark:text-gray-100"
                            >
                                আমি নিশ্চিত করছি যে আমি আমার অ্যাকাউন্ট মুছে
                                ফেলার আবেদন করতে চাই।
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div
                    class="flex items-center justify-between gap-2.5 border-t border-slate-100 pt-4 dark:border-gray-800"
                >
                    <button
                        type="button"
                        @click="goToPrevStep"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        <span>পূর্ববর্তী ধাপ</span>
                    </button>

                    <button
                        type="button"
                        @click="submitDeleteTicket"
                        :disabled="
                            !isConfirmed ||
                            form.processing ||
                            isTicketLimitReached
                        "
                        class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-2xs transition hover:bg-rose-700 focus:ring-4 focus:ring-rose-500/20 focus:outline-none active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <Trash2 v-else class="h-3.5 w-3.5" />
                        <span>{{
                            form.processing
                                ? 'সাবমিট হচ্ছে...'
                                : 'ডিলিট রিকোয়েস্ট পাঠান'
                        }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
