<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    Loader2,
    Mail,
    Send,
    Users,
    User,
    AlertCircle,
    AtSign,
    Eye,
    X,
    Upload,
    Trash2,
} from 'lucide-vue-next';

import { computed, ref } from 'vue';
import HTMLEditor from '@/components/HTMLEditor.vue';

const props = defineProps({
    recipientCount: {
        type: Number,
        default: 0,
    },
});

const page = usePage();
const appName = computed(() => (page.props as any).appName || 'HSCStack');

const showConfirmModal = ref(false);
const showPreviewModal = ref(false);

const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    recipient_type: 'all' as 'all' | 'single',
    recipient_email: '',
    subject: '',
    body: '',
    image: null as File | null,
});

const formattedCurrentDate = computed(() => {
    return new Date().toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const handleImageSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const handleRemoveImage = () => {
    form.image = null;
    imagePreview.value = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const handleSendClick = () => {
    if (form.recipient_type === 'single' && !form.recipient_email.trim()) {
        form.setError(
            'recipient_email',
            'Please provide a valid recipient email.',
        );

        return;
    }

    if (!form.subject.trim() || !form.body.trim()) {
        form.validate();

        return;
    }

    showConfirmModal.value = true;
};

const submitForm = () => {
    showConfirmModal.value = false;
    form.post('/admin/emails/send', {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Send Email" />

    <div
        class="flex min-h-full w-full flex-col justify-start bg-slate-50 p-6 lg:p-10 dark:bg-gray-950"
    >
        <div
            class="mx-auto w-full max-w-4xl rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs md:p-10 dark:border-gray-700 dark:bg-gray-900"
        >
            <!-- Header -->
            <div
                class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center dark:border-gray-800"
            >
                <div>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            <Mail class="h-5 w-5" />
                        </div>
                        <h1
                            class="text-2xl font-bold text-slate-900 dark:text-gray-100"
                        >
                            Send Email
                        </h1>
                    </div>
                    <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">
                        Compose and dispatch email announcements to subscribed
                        users or individual accounts.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Preview Trigger Button -->
                    <button
                        type="button"
                        @click="showPreviewModal = true"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-slate-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        <Eye
                            class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                        />
                        <span>Preview</span>
                    </button>

                    <!-- Recipient Count Badge -->
                    <div
                        class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-gray-800 dark:bg-gray-950"
                    >
                        <Users
                            class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                        />
                        <div class="text-xs">
                            <span
                                class="font-semibold text-slate-500 dark:text-gray-400"
                            >
                                Subscribers:
                            </span>
                            <span
                                class="ml-1 font-bold text-slate-900 dark:text-gray-100"
                            >
                                {{ recipientCount }} users
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="handleSendClick" class="space-y-6">
                <!-- Recipient Target Selection -->
                <div>
                    <label
                        class="mb-2 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >
                        Recipient Target
                    </label>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <!-- Bulk to all subscribed -->
                        <button
                            type="button"
                            @click="form.recipient_type = 'all'"
                            class="flex items-center gap-3 rounded-2xl border p-4 text-left transition"
                            :class="
                                form.recipient_type === 'all'
                                    ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-600/10 dark:border-indigo-500 dark:bg-indigo-500/10'
                                    : 'border-slate-200 bg-white hover:border-slate-300 dark:border-gray-700 dark:bg-gray-950/40 dark:hover:border-gray-600'
                            "
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                :class="
                                    form.recipient_type === 'all'
                                        ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                        : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'
                                "
                            >
                                <Users class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <p
                                    class="text-sm font-bold text-slate-900 dark:text-gray-100"
                                >
                                    All Subscribed Users
                                </p>
                                <p
                                    class="text-xs text-slate-500 dark:text-gray-400"
                                >
                                    Bulk broadcast ({{ recipientCount }}
                                    recipients)
                                </p>
                            </div>
                        </button>

                        <!-- Single User -->
                        <button
                            type="button"
                            @click="form.recipient_type = 'single'"
                            class="flex items-center gap-3 rounded-2xl border p-4 text-left transition"
                            :class="
                                form.recipient_type === 'single'
                                    ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-600/10 dark:border-indigo-500 dark:bg-indigo-500/10'
                                    : 'border-slate-200 bg-white hover:border-slate-300 dark:border-gray-700 dark:bg-gray-950/40 dark:hover:border-gray-600'
                            "
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                :class="
                                    form.recipient_type === 'single'
                                        ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                        : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'
                                "
                            >
                                <User class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <p
                                    class="text-sm font-bold text-slate-900 dark:text-gray-100"
                                >
                                    Single User
                                </p>
                                <p
                                    class="text-xs text-slate-500 dark:text-gray-400"
                                >
                                    Send to an individual email address
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Single Recipient Email Field -->
                <div v-if="form.recipient_type === 'single'">
                    <label
                        for="recipient_email"
                        class="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >
                        Recipient Email Address
                    </label>
                    <div class="relative">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400"
                        >
                            <AtSign class="h-4 w-4" />
                        </div>
                        <input
                            v-model="form.recipient_email"
                            type="email"
                            id="recipient_email"
                            required
                            placeholder="user@example.com"
                            :disabled="form.processing"
                            class="w-full rounded-xl border py-3 pr-4 pl-10 text-sm transition outline-none focus:ring-4 dark:bg-gray-950 dark:text-gray-100"
                            :class="
                                form.errors.recipient_email
                                    ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/10'
                                    : 'border-slate-200 focus:border-indigo-600 focus:ring-indigo-600/10 dark:border-gray-700'
                            "
                        />
                    </div>
                    <p
                        v-if="form.errors.recipient_email"
                        class="mt-1.5 text-xs font-medium text-rose-600"
                    >
                        {{ form.errors.recipient_email }}
                    </p>
                </div>

                <!-- Zero Subscribers Warning for Bulk mode -->
                <div
                    v-if="form.recipient_type === 'all' && recipientCount === 0"
                    class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-xs font-medium text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
                >
                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                    <p>
                        There are currently no active users with email
                        notifications enabled.
                    </p>
                </div>

                <!-- Subject -->
                <div>
                    <label
                        for="subject"
                        class="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >
                        Email Subject
                    </label>
                    <input
                        v-model="form.subject"
                        type="text"
                        id="subject"
                        required
                        placeholder="e.g. Important Announcement: New Resources & Learning Updates"
                        :disabled="form.processing"
                        class="w-full rounded-xl border px-4 py-3 text-sm transition outline-none focus:ring-4 dark:bg-gray-950 dark:text-gray-100"
                        :class="
                            form.errors.subject
                                ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/10'
                                : 'border-slate-200 focus:border-indigo-600 focus:ring-indigo-600/10 dark:border-gray-700'
                        "
                    />
                    <p
                        v-if="form.errors.subject"
                        class="mt-1.5 text-xs font-medium text-rose-600"
                    >
                        {{ form.errors.subject }}
                    </p>
                </div>

                <!-- Banner/Cover Image Upload -->
                <div>
                    <label
                        class="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >
                        Cover / Banner Image (Optional)
                    </label>

                    <input
                        ref="fileInput"
                        type="file"
                        id="email_image_upload"
                        class="hidden"
                        accept="image/png,image/jpeg,image/jpg,image/webp"
                        :disabled="form.processing"
                        @change="handleImageSelect"
                    />

                    <!-- Image Preview -->
                    <div
                        v-if="imagePreview"
                        class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-2 dark:border-gray-700 dark:bg-gray-950"
                    >
                        <img
                            :src="imagePreview"
                            alt="Banner preview"
                            class="max-h-56 w-full rounded-xl object-cover"
                        />
                        <div
                            class="mt-2 flex items-center justify-between px-2 py-1"
                        >
                            <span
                                class="max-w-xs truncate text-xs font-medium text-slate-600 dark:text-gray-300"
                            >
                                {{ form.image?.name }}
                            </span>
                            <div class="flex items-center gap-2">
                                <label
                                    for="email_image_upload"
                                    class="cursor-pointer rounded-lg bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-300 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                >
                                    Change
                                </label>
                                <button
                                    type="button"
                                    @click="handleRemoveImage"
                                    class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-rose-700"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Dropzone when no image -->
                    <div
                        v-else
                        class="rounded-2xl border-2 border-dashed bg-slate-50/50 p-6 text-center transition dark:bg-gray-950/50"
                        :class="
                            form.errors.image
                                ? 'border-rose-300 bg-rose-50/20 dark:border-rose-500/30 dark:bg-rose-500/10'
                                : 'border-slate-200 dark:border-gray-700 dark:hover:bg-gray-900'
                        "
                    >
                        <label
                            for="email_image_upload"
                            class="flex cursor-pointer flex-col items-center justify-center"
                        >
                            <div
                                class="mb-2 rounded-full border border-slate-100 bg-white p-3 text-slate-400 shadow-xs dark:border-gray-800 dark:bg-gray-900 dark:text-gray-500"
                            >
                                <Upload class="h-5 w-5" />
                            </div>
                            <span
                                class="text-center text-sm font-medium text-slate-700 dark:text-gray-300"
                            >
                                Click to upload header banner image
                            </span>
                            <span
                                class="mt-1 text-xs text-slate-400 dark:text-gray-500"
                            >
                                PNG, JPG or WEBP (Max 5MB)
                            </span>
                        </label>
                    </div>

                    <p
                        v-if="form.errors.image"
                        class="mt-1.5 text-xs font-medium text-rose-600"
                    >
                        {{ form.errors.image }}
                    </p>
                </div>

                <!-- Rich Email Body (HTMLEditor) -->
                <div>
                    <label
                        class="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >
                        Email Body (HTML)
                    </label>

                    <HTMLEditor
                        v-model="form.body"
                        :error="form.errors.body"
                        placeholder="Write your email content here... (supports links, quotes, and lists)"
                    />

                    <p
                        v-if="form.errors.body"
                        class="mt-1.5 text-xs font-medium text-rose-600"
                    >
                        {{ form.errors.body }}
                    </p>
                </div>

                <!-- Action Footer -->
                <div
                    class="flex flex-col gap-4 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
                >
                    <p class="text-xs text-slate-400 dark:text-gray-500">
                        Emails and image assets are stored and delivered
                        asynchronously in the background.
                    </p>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="showPreviewModal = true"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            <Eye class="h-3.5 w-3.5" />
                            <span>Preview</span>
                        </button>

                        <button
                            type="submit"
                            :disabled="
                                form.processing ||
                                (form.recipient_type === 'all' &&
                                    recipientCount === 0)
                            "
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin"
                            />
                            <Send v-else class="h-4 w-4" />
                            <span>
                                {{
                                    form.recipient_type === 'single'
                                        ? 'Send Email'
                                        : 'Send Broadcast'
                                }}
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Live PC / Desktop Email Client Preview Modal -->
    <Teleport to="body">
        <div
            v-if="showPreviewModal"
            class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-3 backdrop-blur-xs sm:p-6 dark:bg-black/70"
        >
            <div
                class="relative my-auto w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200/90 bg-slate-100 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Desktop Window Titlebar -->
                <div
                    class="flex items-center justify-between border-b border-slate-200/80 bg-white/80 px-4 py-3 backdrop-blur-md dark:border-gray-800 dark:bg-gray-950/80"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-block h-3 w-3 rounded-full bg-rose-500/80"
                        ></span>
                        <span
                            class="inline-block h-3 w-3 rounded-full bg-amber-500/80"
                        ></span>
                        <span
                            class="inline-block h-3 w-3 rounded-full bg-emerald-500/80"
                        ></span>
                        <div
                            class="ml-2 flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-gray-300"
                        >
                            <Mail
                                class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                            />
                            <span>Email Preview</span>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="showPreviewModal = false"
                        class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Email Client Header Info -->
                <div
                    class="border-b border-slate-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-950"
                >
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        {{ form.subject.trim() || '(No subject specified)' }}
                    </h2>

                    <div
                        class="mt-2 space-y-1 text-xs text-slate-500 dark:text-gray-400"
                    >
                        <div class="flex items-center gap-2">
                            <span class="w-12 font-semibold text-slate-400"
                                >From:</span
                            >
                            <span
                                class="font-medium text-slate-800 dark:text-gray-200"
                            >
                                {{ appName }} &lt;team@hscstack.com&gt;
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-12 font-semibold text-slate-400"
                                >To:</span
                            >
                            <span
                                class="font-medium text-slate-800 dark:text-gray-200"
                            >
                                {{
                                    form.recipient_type === 'single'
                                        ? form.recipient_email ||
                                          '(Recipient Email)'
                                        : `All Subscribed Users (${props.recipientCount} recipients)`
                                }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-12 font-semibold text-slate-400"
                                >Date:</span
                            >
                            <span>{{ formattedCurrentDate }}</span>
                        </div>
                    </div>
                </div>

                <!-- Email Body Container (Exact 600px Template Rendering) -->
                <div
                    class="max-h-[65vh] overflow-y-auto bg-slate-100/90 p-4 sm:p-6 dark:bg-gray-900"
                >
                    <div
                        class="mx-auto max-w-[580px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-950"
                    >
                        <!-- Template Header with Logo -->
                        <div
                            class="border-b border-slate-100 bg-white py-5 text-center dark:border-gray-800 dark:bg-gray-950"
                        >
                            <div class="inline-flex items-center gap-2.5">
                                <div
                                    class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-slate-900 shadow-xs dark:bg-gray-100"
                                >
                                    <img
                                        src="/favicon.svg"
                                        alt="HSCStack"
                                        class="h-6 w-6 scale-120 object-cover"
                                    />
                                </div>
                                <span
                                    class="text-lg font-extrabold tracking-tight text-slate-900 dark:text-gray-100"
                                >
                                    HSC<span
                                        class="text-indigo-600 dark:text-indigo-400"
                                        >Stack</span
                                    >
                                </span>
                            </div>
                        </div>

                        <!-- Template Content -->
                        <div
                            class="p-7 text-sm leading-relaxed text-slate-700 dark:text-gray-300"
                        >
                            <p
                                class="mb-4 font-semibold text-slate-900 dark:text-gray-100"
                            >
                                Hello
                                {{
                                    form.recipient_type === 'single'
                                        ? 'User'
                                        : '[Recipient Name]'
                                }},
                            </p>

                            <!-- Banner Image in Preview -->
                            <div v-if="imagePreview" class="mb-6 text-center">
                                <img
                                    :src="imagePreview"
                                    alt="Announcement preview"
                                    class="max-h-64 w-full rounded-2xl border border-slate-200 object-cover dark:border-gray-800"
                                />
                            </div>

                            <!-- Rendered HTML Content -->
                            <div
                                v-if="form.body.trim()"
                                class="email-rendered-preview space-y-3"
                                v-html="form.body"
                            ></div>
                            <div
                                v-else
                                class="py-8 text-center text-xs text-slate-400 italic dark:text-gray-500"
                            >
                                Your composed email content will appear here...
                            </div>
                        </div>

                        <!-- Template Footer -->
                        <div
                            class="border-t border-slate-100 bg-slate-50/90 px-6 py-6 text-center text-xs text-slate-500 dark:border-gray-800 dark:bg-gray-900/90 dark:text-gray-400"
                        >
                            <p
                                class="mb-2 font-semibold text-slate-700 dark:text-gray-200"
                            >
                                HSCStack &mdash; The Open Learning Platform
                            </p>

                            <div
                                class="my-2 flex items-center justify-center gap-3 text-xs font-medium text-indigo-600 dark:text-indigo-400"
                            >
                                <span>Visit Platform</span>
                                <span>&bull;</span>
                                <span>Read Blogs</span>
                                <span>&bull;</span>
                                <span>Support Us</span>
                            </div>

                            <p
                                class="mt-3 mb-1 text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                You are receiving this email because you have an
                                active account on HSCStack.
                            </p>
                            <p
                                class="text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                Manage your email preferences anytime in your
                                <span
                                    class="text-indigo-600 underline dark:text-indigo-400"
                                    >Account Settings</span
                                >.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Preview Footer Modal Action -->
                <div
                    class="flex items-center justify-end border-t border-slate-200/80 bg-white/80 px-5 py-3 dark:border-gray-800 dark:bg-gray-950/80"
                >
                    <button
                        type="button"
                        @click="showPreviewModal = false"
                        class="rounded-xl bg-slate-900 px-5 py-2 text-xs font-bold text-white transition hover:bg-slate-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        Done Previewing
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Send Confirmation Modal -->
    <Teleport to="body">
        <div
            v-if="showConfirmModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs dark:bg-black/60"
        >
            <div
                class="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="mb-4 flex items-center gap-3.5">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        <Send class="h-6 w-6" />
                    </div>
                    <div>
                        <h3
                            class="text-base font-bold text-slate-900 dark:text-gray-100"
                        >
                            {{
                                form.recipient_type === 'single'
                                    ? 'Confirm Single Email'
                                    : 'Confirm Email Broadcast'
                            }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            {{
                                form.recipient_type === 'single'
                                    ? `Direct email to ${form.recipient_email}`
                                    : `Queue broadcast to ${props.recipientCount} subscribed users`
                            }}
                        </p>
                    </div>
                </div>

                <p
                    class="mb-5 text-xs leading-relaxed text-slate-600 dark:text-gray-300"
                >
                    <span v-if="form.recipient_type === 'single'">
                        Are you sure you want to send this email to
                        <strong>{{ form.recipient_email }}</strong
                        >?
                    </span>
                    <span v-else>
                        Are you sure you want to send this broadcast? The emails
                        will be dispatched to all
                        <strong>{{ props.recipientCount }}</strong> users who
                        have enabled email updates.
                    </span>
                </p>

                <div class="flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="showConfirmModal = false"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="submitForm"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        <Send class="h-3.5 w-3.5" />
                        <span>Confirm & Send</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
:deep(.email-rendered-preview h1) {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 1rem 0 0.5rem;
}

:deep(.email-rendered-preview h2) {
    font-size: 1.15rem;
    font-weight: 600;
    margin: 0.85rem 0 0.4rem;
}

:deep(.email-rendered-preview h3) {
    font-size: 1rem;
    font-weight: 600;
    margin: 0.75rem 0 0.35rem;
}

:deep(.email-rendered-preview ul) {
    list-style-type: disc;
    padding-left: 1.25rem;
    margin-bottom: 0.75rem;
}

:deep(.email-rendered-preview ol) {
    list-style-type: decimal;
    padding-left: 1.25rem;
    margin-bottom: 0.75rem;
}

:deep(.email-rendered-preview blockquote) {
    border-left: 3px solid #cbd5e1;
    padding-left: 1rem;
    color: #64748b;
    font-style: italic;
    margin: 0.75rem 0;
}

:deep(.email-rendered-preview a) {
    color: #4f46e5;
    text-decoration: underline;
}
</style>
