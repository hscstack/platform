<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Trash2,
    ArrowLeft,
    Loader2,
    ChevronDown,
    User,
    MessageSquare,
    ShieldAlert,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import BaseModal from '@/components/BaseModal.vue';

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

const selectedReason = ref('');
const feedback = ref('');
const isConfirmed = ref(false);
const showConfirmModal = ref(false);

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

const openConfirmModal = () => {
    if (isTicketLimitReached.value || !isConfirmed.value) {
        return;
    }

    showConfirmModal.value = true;
};

const submitDeleteTicket = () => {
    if (isTicketLimitReached.value) {
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
        'User confirmed permanent account deletion request after reviewing legal policies and timeline notice.',
    ].join('\n');

    form.category = 'account_issue';
    form.subject = ticketSubject;
    form.message = ticketMessage;

    form.post('/support/tickets', {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmModal.value = false;
        },
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

    <div class="mx-auto w-full max-w-xl px-4 py-8 sm:px-6">
        <!-- Back Link -->
        <div class="mb-6">
            <Link
                href="/profile"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition-colors hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-100"
            >
                <ArrowLeft class="h-4 w-4" />
                <span>অ্যাকাউন্ট সেটিংসে ফিরে যান</span>
            </Link>
        </div>

        <!-- Limit Alert if user has >= 3 open tickets -->
        <div
            v-if="isTicketLimitReached"
            class="mb-6 rounded-2xl border border-amber-200 bg-amber-50/90 p-4 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
        >
            <div class="flex items-start gap-3">
                <AlertTriangle
                    class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                />
                <div class="space-y-2">
                    <p class="font-bold">
                        আপনার ইতিমধ্যে ৩টি খোলা সাপোর্ট টিকেট রয়েছে।
                    </p>
                    <p class="text-amber-800 dark:text-amber-300">
                        নতুন টিকেট খোলার আগে অনুগ্রহ করে পূর্ববর্তী টিকেটের
                        সমাধান হওয়া পর্যন্ত অপেক্ষা করুন।
                    </p>
                    <Link
                        href="/support/my-tickets"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-[11px] font-bold text-white shadow-2xs hover:bg-amber-700"
                    >
                        <MessageSquare class="h-3.5 w-3.5" />
                        <span>আমার টিকেটসমূহ</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-8 dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Header -->
            <div class="mb-6 flex items-start gap-4">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400"
                >
                    <Trash2 class="h-5 w-5" />
                </div>
                <div>
                    <h1
                        class="text-xl font-bold text-slate-900 dark:text-gray-100"
                    >
                        অ্যাকাউন্ট ডিলিট রিকোয়েস্ট
                    </h1>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                        অ্যাকাউন্ট ও সংশ্লিষ্ট সকল ডেটা স্থায়ীভাবে মুছে ফেলার
                        জন্য সরাসরি সাপোর্ট টিকেট তৈরি হবে।
                    </p>
                </div>
            </div>

            <!-- User Info Pill -->
            <div
                class="mb-6 flex items-center gap-3 rounded-xl bg-slate-50 p-3 text-xs dark:bg-gray-800/60"
            >
                <img
                    v-if="authUser?.image_url"
                    :src="authUser.image_url"
                    :alt="authUser.name"
                    class="h-8 w-8 rounded-full object-cover ring-1 ring-slate-200 dark:ring-gray-700"
                />
                <div
                    v-else
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-slate-600 dark:bg-gray-700 dark:text-gray-300"
                >
                    <User class="h-4 w-4" />
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
                            class="ml-1 text-slate-400 dark:text-gray-500"
                        >
                            • {{ authUser.email }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="openConfirmModal" class="space-y-4">
                <div
                    v-if="form.errors.general"
                    class="rounded-xl border border-rose-200 bg-rose-50/80 p-3 text-xs font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400"
                >
                    {{ form.errors.general }}
                </div>

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
                                v-for="r in reasons"
                                :key="r.value"
                                :value="r.value"
                            >
                                {{ r.label }}
                            </option>
                        </select>
                        <ChevronDown
                            class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />
                    </div>
                </div>

                <!-- Feedback Textarea (Optional) -->
                <div>
                    <label
                        for="feedback"
                        class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                    >
                        মতামত বা মন্তব্য (ঐচ্ছিক)
                    </label>
                    <textarea
                        id="feedback"
                        v-model="feedback"
                        rows="3"
                        placeholder="কোনো মতামত বা কারণ শেয়ার করতে চাইলে লিখতে পারেন..."
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
                    ></textarea>
                </div>

                <!-- Privacy & Terms Notice & Processing Time in Bengali -->
                <div
                    class="rounded-xl border border-slate-200/70 bg-slate-50/70 p-3.5 text-xs text-slate-600 dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-400"
                >
                    <p class="leading-relaxed">
                        আমরা আপনার ডেটা কীভাবে পরিচালনা ও প্রসেস করি তা
                        বিস্তারিত জানতে আমাদের
                        <Link
                            href="/privacy-policy"
                            class="font-semibold text-indigo-600 underline hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            Privacy Policy
                        </Link>
                        এবং
                        <Link
                            href="/terms-service"
                            class="font-semibold text-indigo-600 underline hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            Terms & Conditions
                        </Link>
                        পড়ুন।
                    </p>
                    <p
                        class="mt-1.5 text-[11px] text-slate-500 dark:text-gray-400"
                    >
                        অ্যাকাউন্ট ডিলিট সম্পন্ন হতে সর্বোচ্চ
                        <strong>৪৮ ঘণ্টা</strong> পর্যন্ত সময় লাগতে পারে।
                        অ্যাকাউন্ট ডিলিট হওয়ার পর আপনার ইমেইলে একটি নিশ্চিতকরণ
                        মেসেজ যাবে।
                    </p>
                </div>

                <!-- Confirmation Checkbox -->
                <div class="pt-1">
                    <label
                        class="flex cursor-pointer items-start gap-2.5 text-xs text-slate-700 dark:text-gray-300"
                    >
                        <input
                            v-model="isConfirmed"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500 dark:border-gray-700 dark:bg-gray-900"
                        />
                        <span class="leading-relaxed select-none">
                            আমি নিশ্চিত করছি যে আমি আমার অ্যাকাউন্ট মুছে ফেলার
                            জন্য সাপোর্ট টিকেট পাঠাতে চাই।
                        </span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-2.5 pt-4">
                    <Link
                        href="/profile"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        বাতিল
                    </Link>

                    <button
                        type="submit"
                        :disabled="
                            !isConfirmed ||
                            form.processing ||
                            isTicketLimitReached
                        "
                        class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-2xs transition hover:bg-rose-700 focus:ring-4 focus:ring-rose-500/20 focus:outline-none active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span>ডিলিট রিকোয়েস্ট পাঠান</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Final Confirmation Modal (Aligned with Legal Policy) -->
        <BaseModal
            :is-open="showConfirmModal"
            title="অ্যাকাউন্ট মুছে ফেলার চূড়ান্ত নিশ্চিতকরণ"
            description="সতর্কতা: এই পদক্ষেপটি স্থায়ী ও অপরিবর্তনীয়"
            max-width="md"
            @close="showConfirmModal = false"
        >
            <div class="space-y-4 p-5 text-slate-800 dark:text-gray-200">
                <div
                    class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 p-3.5 text-xs text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200"
                >
                    <AlertTriangle
                        class="h-5 w-5 shrink-0 text-rose-600 dark:text-rose-400"
                    />
                    <div class="space-y-1">
                        <p class="font-bold">আপনি কি নিশ্চিত?</p>
                        <p class="leading-relaxed">
                            অনুরোধ সাবমিট করলে আমাদের সাপোর্ট টিমের কাছে একটি
                            অফিশিয়াল টিকেট পাঠানো হবে এবং সর্বোচ্চ
                            <strong>৪৮ ঘণ্টার</strong> মধ্যে অ্যাকাউন্ট মুছে
                            ফেলা হবে।
                        </p>
                    </div>
                </div>

                <!-- Policy highlights -->
                <div
                    class="space-y-2 rounded-xl border border-slate-100 bg-slate-50/80 p-3 text-[11px] text-slate-600 dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-400"
                >
                    <div class="flex items-start gap-2">
                        <ShieldAlert
                            class="mt-0.5 h-3.5 w-3.5 shrink-0 text-rose-500"
                        />
                        <span>
                            আপনার প্রোফাইল ও ব্যক্তিগত ডেটা স্থায়ীভাবে অপসারিত
                            হবে (
                            <Link
                                href="/privacy-policy"
                                target="_blank"
                                class="font-semibold text-indigo-600 underline hover:text-indigo-700 dark:text-indigo-400"
                            >
                                Privacy Policy
                            </Link>
                            ধারা ৯)।
                        </span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-bold text-indigo-500">•</span>
                        <span>
                            আপনার পূর্বের আপলোডকৃত কনটেন্ট
                            <Link
                                href="/content-policy"
                                target="_blank"
                                class="font-semibold text-indigo-600 underline hover:text-indigo-700 dark:text-indigo-400"
                            >
                                Content Policy
                            </Link>
                            অনুযায়ী প্রসেস হবে।
                        </span>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between text-[11px] text-slate-500 dark:text-gray-400"
                >
                    <span>
                        অ্যাকাউন্ট:
                        <strong class="text-slate-800 dark:text-gray-200">
                            @{{ authUser?.username || authUser?.name }}
                        </strong>
                    </span>
                    <Link
                        href="/privacy-policy"
                        target="_blank"
                        class="text-indigo-600 underline hover:text-indigo-700 dark:text-indigo-400"
                    >
                        Privacy Policy পড়ুন
                    </Link>
                </div>
            </div>

            <div
                class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-gray-800 dark:bg-gray-900/60"
            >
                <button
                    type="button"
                    @click="showConfirmModal = false"
                    :disabled="form.processing"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    ফিরে যান
                </button>
                <button
                    type="button"
                    @click="submitDeleteTicket"
                    :disabled="form.processing"
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-rose-700 active:scale-95 disabled:opacity-50"
                >
                    <Loader2
                        v-if="form.processing"
                        class="h-3.5 w-3.5 animate-spin"
                    />
                    <Trash2 v-else class="h-3.5 w-3.5" />
                    <span>{{
                        form.processing
                            ? 'সাবমিট হচ্ছে...'
                            : 'হ্যাঁ, ডিলিট রিকোয়েস্ট পাঠান'
                    }}</span>
                </button>
            </div>
        </BaseModal>
    </div>
</template>
