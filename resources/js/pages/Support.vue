<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    Send,
    MessageSquare,
    Image as ImageIcon,
    ChevronDown,
    UserCheck,
    ArrowRight,
    XCircle,
    Clock,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineProps<{
    ticketsCount: number;
    openTicketsCount?: number;
    categories: Record<string, string>;
}>();

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const form = useForm({
    category: 'general',
    subject: '',
    message: '',
    attachment: null as File | null,
});

const previewUrl = ref<string | null>(null);

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.attachment = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const removeAttachment = () => {
    form.attachment = null;

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
};

const submitTicket = () => {
    form.post('/support/tickets', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            removeAttachment();
        },
    });
};
</script>

<template>
    <Head>
        <title>Help & Support Center - HSCStack</title>
        <meta
            name="description"
            content="Get help, submit support tickets, report bugs, or give feedback to the HSCStack team."
        />
    </Head>

    <header class="mx-auto max-w-3xl px-4 pt-4 pb-4 text-center sm:pt-6">
        <h1
            class="mb-2 text-3xl leading-tight font-black tracking-tight text-slate-950 sm:text-4xl lg:text-5xl dark:text-gray-100"
        >
            Support
            <span class="text-indigo-600 dark:text-indigo-400">Center</span>
        </h1>
        <p
            class="mx-auto max-w-md text-xs font-semibold tracking-wider text-slate-400 uppercase dark:text-gray-500"
        >
            যেকোনো সমস্যা বা মতামতের জন্য সরাসরি টিকেট খুলুন
        </p>
    </header>

    <div class="mx-auto max-w-3xl px-4 pb-16 sm:px-6">
        <!-- Unauthenticated Prompt -->
        <div
            v-if="!authUser"
            class="overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/80 via-white to-slate-50 p-6 text-center shadow-sm sm:p-8 dark:border-indigo-500/20 dark:from-indigo-500/10 dark:via-gray-900 dark:to-gray-950"
        >
            <div class="mx-auto max-w-md">
                <div
                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400"
                >
                    <UserCheck class="h-6 w-6" />
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-gray-100">
                    লগইন করে টিকেট তৈরি করুন
                </h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-gray-400">
                    টিকেট সাবমিট করতে এবং এডমিনের উত্তর দেখতে অনুগ্রহ করে আপনার
                    গুগল অ্যাকাউন্ট দিয়ে লগইন করুন।
                </p>
                <div
                    class="mt-6 flex flex-col justify-center gap-3 sm:flex-row"
                >
                    <Link
                        href="/login?redirect=/support"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-md shadow-indigo-200 transition-all hover:bg-indigo-700 hover:shadow-lg active:scale-95 dark:shadow-indigo-500/30"
                    >
                        <span>লগইন করুন</span>
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </div>

        <!-- Authenticated Support Portal -->
        <div v-else class="space-y-4">
            <!-- Navigation Tabs -->
            <div
                class="flex items-center justify-between border-b border-slate-200 dark:border-gray-800"
            >
                <div class="flex gap-2">
                    <Link
                        href="/support"
                        class="relative flex items-center gap-2 px-4 py-3 text-sm font-bold text-indigo-600 transition-colors dark:text-indigo-400"
                    >
                        <Send class="h-4 w-4" />
                        <span>নতুন টিকেট খুলুন</span>
                        <span
                            class="absolute right-0 bottom-0 left-0 h-0.5 bg-indigo-600 dark:bg-indigo-400"
                        ></span>
                    </Link>

                    <Link
                        href="/support/my-tickets"
                        class="relative flex items-center gap-2 px-4 py-3 text-sm font-bold text-slate-500 transition-colors hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <MessageSquare class="h-4 w-4" />
                        <span>আমার টিকেটসমূহ</span>
                        <span
                            v-if="ticketsCount > 0"
                            class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            {{ ticketsCount }}
                        </span>
                    </Link>
                </div>
            </div>

            <!-- Max Limit Reached Banner -->
            <div
                v-if="(openTicketsCount || 0) >= 3"
                class="rounded-2xl border border-amber-200 bg-amber-50/70 p-6 text-center shadow-xs dark:border-amber-500/30 dark:bg-amber-500/10"
            >
                <div
                    class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400"
                >
                    <Clock class="h-5 w-5" />
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-gray-100">
                    আপনি সর্বোচ্চ ৩টি সক্রিয় টিকেটের সীমায় পৌঁছেছেন
                </h3>
                <p
                    class="mt-1.5 text-xs leading-relaxed text-slate-600 dark:text-gray-400"
                >
                    আপনার ইতিমধ্যে ৩টি খোলা বা পর্যালোচনাধীন টিকেট রয়েছে। নতুন
                    টিকেট খোলার পূর্বে অ্যাডমিন কর্তৃক পূর্ববর্তী টিকেটের সমাধান
                    হওয়া পর্যন্ত অপেক্ষা করুন।
                </p>
                <div class="mt-4">
                    <Link
                        href="/support/my-tickets"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 active:scale-95"
                    >
                        <MessageSquare class="h-3.5 w-3.5" />
                        <span>আমার টিকেটসমূহ দেখুন</span>
                    </Link>
                </div>
            </div>

            <!-- Create Ticket Form Card -->
            <div
                v-else
                class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm sm:p-7 dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    v-if="form.errors.general"
                    class="mb-4 rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 text-xs font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400"
                >
                    {{ form.errors.general }}
                </div>

                <form @submit.prevent="submitTicket" class="space-y-5">
                    <!-- User Info Banner -->
                    <div
                        class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 text-xs font-medium text-slate-600 dark:bg-gray-800/60 dark:text-gray-300"
                    >
                        <img
                            :src="
                                authUser.image_path ||
                                `https://api.dicebear.com/7.x/initials/svg?seed=${authUser.name}`
                            "
                            :alt="authUser.name"
                            class="h-6 w-6 rounded-full object-cover"
                        />
                        <div>
                            <span
                                >সাবমিট করছেন:
                                <strong>{{ authUser.name }}</strong> ({{
                                    authUser.email
                                }})</span
                            >
                        </div>
                    </div>

                    <!-- Category Selector -->
                    <div>
                        <label
                            for="category"
                            class="mb-1.5 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                        >
                            ক্যাটেগরি নির্বাচন করুন *
                        </label>
                        <div class="relative">
                            <select
                                id="category"
                                v-model="form.category"
                                required
                                class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-4 py-2.5 pr-10 text-xs font-semibold text-slate-800 shadow-2xs focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200"
                            >
                                <option
                                    v-for="(label, key) in categories"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ label }}
                                </option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-slate-400"
                            />
                        </div>
                        <p
                            v-if="form.errors.category"
                            class="mt-1 text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.category }}
                        </p>
                    </div>

                    <!-- Subject Input -->
                    <div>
                        <label
                            for="subject"
                            class="mb-1.5 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                        >
                            বিষয় / Subject *
                        </label>
                        <input
                            id="subject"
                            v-model="form.subject"
                            type="text"
                            placeholder="সংক্ষেপে সমস্যার মূল কথা লিখুন (যেমন: পদার্থবিজ্ঞান অধ্যায় ২ নোট ওপেন হচ্ছে না)"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
                        />
                        <p
                            v-if="form.errors.subject"
                            class="mt-1 text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.subject }}
                        </p>
                    </div>

                    <!-- Message Textarea -->
                    <div>
                        <label
                            for="message"
                            class="mb-1.5 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                        >
                            বিস্তারিত বিবরণ *
                        </label>
                        <textarea
                            id="message"
                            v-model="form.message"
                            rows="5"
                            placeholder="সমস্যার বিস্তারিত বিবরণ দিন যাতে আমরা দ্রুত সমাধান করতে পারি..."
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
                        ></textarea>
                        <div
                            class="mt-1 flex items-center justify-between text-[11px] text-slate-400"
                        >
                            <span>কমপক্ষে ১০ অক্ষর</span>
                            <span>{{ form.message.length }} / 5000</span>
                        </div>
                        <p
                            v-if="form.errors.message"
                            class="mt-1 text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.message }}
                        </p>
                    </div>

                    <!-- File / Screenshot Attachment -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                        >
                            স্ক্রিনশট বা ছবি (ঐচ্ছিক)
                        </label>
                        <div v-if="!previewUrl" class="flex items-center">
                            <label
                                class="flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-2.5 text-xs font-semibold text-slate-600 transition-colors hover:border-indigo-500 hover:bg-indigo-50/30 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800/40 dark:text-gray-400 dark:hover:border-indigo-400 dark:hover:text-indigo-400"
                            >
                                <ImageIcon class="h-4 w-4" />
                                <span>ছবি আপলোড করুন (সর্বোচ্চ 5MB)</span>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="handleFileChange"
                                    class="hidden"
                                />
                            </label>
                        </div>
                        <!-- Preview -->
                        <div
                            v-else
                            class="relative inline-block overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2 dark:border-gray-700 dark:bg-gray-800"
                        >
                            <img
                                :src="previewUrl"
                                alt="Attachment preview"
                                class="h-24 w-auto rounded-lg object-cover"
                            />
                            <button
                                type="button"
                                @click="removeAttachment"
                                class="absolute top-3 right-3 rounded-full bg-slate-900/80 p-1 text-white shadow-md hover:bg-rose-600"
                            >
                                <XCircle class="h-4 w-4" />
                            </button>
                        </div>
                        <p
                            v-if="form.errors.attachment"
                            class="mt-1 text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.attachment }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-1">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-200 transition-all hover:bg-indigo-700 hover:shadow-lg active:scale-95 disabled:opacity-50 sm:w-auto dark:shadow-indigo-500/30"
                        >
                            <Send class="h-3.5 w-3.5" />
                            <span>{{
                                form.processing
                                    ? 'সাবমিট হচ্ছে...'
                                    : 'টিকেট জমা দিন'
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
