<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    Loader2,
    Mail,
    Send,
    Users,
    GraduationCap,
    ShieldCheck,
    AlertCircle,
    CheckCircle2,
    Eye,
    X,
    Upload,
    Trash2,
    Download,
    ChevronDown,
} from 'lucide-vue-next';

import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import HTMLEditor from '@/components/HTMLEditor.vue';

const props = defineProps({
    recipientCount: {
        type: Number,
        default: 0,
    },
    studentsCount: {
        type: Number,
        default: 0,
    },
    staffCount: {
        type: Number,
        default: 0,
    },
});

const page = usePage();
const appName = computed(() => (page.props as any).appName || 'HSCStack');

const showConfirmModal = ref(false);
const showPreviewModal = ref(false);
const isImportDropdownOpen = ref(false);
const isImporting = ref(false);

const importDropdownRef = ref<HTMLElement | null>(null);
const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    recipients: '',
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

// Click outside handler for import dropdown
const handleClickOutside = (event: MouseEvent) => {
    if (
        importDropdownRef.value &&
        !importDropdownRef.value.contains(event.target as Node)
    ) {
        isImportDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Parse and analyze recipient emails in real-time
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const recipientStats = computed(() => {
    const raw = form.recipients.trim();
    if (!raw) {
        return {
            validEmails: [] as string[],
            invalidItems: [] as string[],
            totalLines: 0,
            duplicateCount: 0,
        };
    }

    const tokens = raw
        .split(/[\r\n,;]+/)
        .map((t) => t.trim())
        .filter(Boolean);
    const seen = new Set<string>();
    const validEmails: string[] = [];
    const invalidItems: string[] = [];
    let duplicateCount = 0;

    for (const token of tokens) {
        const lower = token.toLowerCase();
        if (emailRegex.test(lower)) {
            if (seen.has(lower)) {
                duplicateCount++;
            } else {
                seen.add(lower);
                validEmails.push(lower);
            }
        } else {
            invalidItems.push(token);
        }
    }

    return {
        validEmails,
        invalidItems,
        totalLines: tokens.length,
        duplicateCount,
    };
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

// Import helper
const importSubscribers = async (type: 'all' | 'students' | 'staff') => {
    if (isImporting.value) return;
    isImporting.value = true;
    isImportDropdownOpen.value = false;

    try {
        const response = await fetch(`/admin/emails/recipients?type=${type}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            const importedList: string[] = data.emails || [];

            // Merge with existing emails avoiding duplicates
            const currentTokens = form.recipients
                .split(/[\r\n,;]+/)
                .map((t) => t.trim().toLowerCase())
                .filter(Boolean);

            const mergedSet = new Set([...currentTokens, ...importedList]);
            form.recipients = Array.from(mergedSet).join('\n');
        }
    } catch (e) {
        console.error('Failed to import recipients:', e);
    } finally {
        isImporting.value = false;
    }
};

const cleanAndFormatRecipients = () => {
    const valid = recipientStats.value.validEmails;
    form.recipients = valid.join('\n');
};

const handleSendClick = () => {
    if (recipientStats.value.validEmails.length === 0) {
        form.setError(
            'recipients',
            'Please provide at least one valid recipient email.',
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

    <div class="flex w-full flex-1 flex-col">
        <!-- Header -->
        <div
            class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-center dark:border-gray-800"
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
                    Compose and dispatch email announcements to custom recipient
                    lists or imported platform subscribers.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Preview Trigger Button -->
                <button
                    type="button"
                    @click="showPreviewModal = true"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-slate-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <Eye class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
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
                            Total Subscribers:
                        </span>
                        <span
                            class="ml-1 font-bold text-slate-900 dark:text-gray-100"
                        >
                            {{ recipientCount }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="handleSendClick" class="space-y-6">
            <!-- Recipients Box with Import Dropdown Toolbar -->
            <div
                class="rounded-3xl border border-slate-200/90 bg-white p-5 shadow-xs sm:p-6 dark:border-gray-800 dark:bg-gray-950"
            >
                <div
                    class="mb-3 flex flex-col justify-between gap-3 sm:flex-row sm:items-center"
                >
                    <div>
                        <label
                            for="recipients"
                            class="block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                        >
                            Recipients (One email per line)
                        </label>
                        <p
                            class="mt-0.5 text-xs text-slate-400 dark:text-gray-500"
                        >
                            Paste any third-party list, single email, or import
                            registered platform subscribers.
                        </p>
                    </div>

                    <!-- Actions Toolbar: Import Dropdown & Clear -->
                    <div class="flex items-center gap-2">
                        <!-- Import Dropdown -->
                        <div ref="importDropdownRef" class="relative">
                            <button
                                type="button"
                                :disabled="isImporting"
                                @click="
                                    isImportDropdownOpen = !isImportDropdownOpen
                                "
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-100 hover:text-slate-900 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                                <Loader2
                                    v-if="isImporting"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <Download v-else class="h-3.5 w-3.5" />
                                <span>Import Subscribers</span>
                                <ChevronDown
                                    class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                                    :class="{
                                        'rotate-180': isImportDropdownOpen,
                                    }"
                                />
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-if="isImportDropdownOpen"
                                class="absolute right-0 z-30 mt-1.5 w-60 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl dark:border-gray-800 dark:bg-gray-900"
                            >
                                <button
                                    type="button"
                                    :disabled="recipientCount === 0"
                                    @click="importSubscribers('all')"
                                    class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-xs font-semibold text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-600 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400"
                                >
                                    <div class="flex items-center gap-2">
                                        <Users class="h-4 w-4 text-indigo-500" />
                                        <span>All Subscribed</span>
                                    </div>
                                    <span
                                        class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                                    >
                                        {{ recipientCount }}
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    :disabled="studentsCount === 0"
                                    @click="importSubscribers('students')"
                                    class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-xs font-semibold text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-600 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400"
                                >
                                    <div class="flex items-center gap-2">
                                        <GraduationCap
                                            class="h-4 w-4 text-indigo-500"
                                        />
                                        <span>Students (Non-Staff)</span>
                                    </div>
                                    <span
                                        class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                                    >
                                        {{ studentsCount }}
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    :disabled="staffCount === 0"
                                    @click="importSubscribers('staff')"
                                    class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-xs font-semibold text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-600 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400"
                                >
                                    <div class="flex items-center gap-2">
                                        <ShieldCheck
                                            class="h-4 w-4 text-indigo-500"
                                        />
                                        <span>Staff Members</span>
                                    </div>
                                    <span
                                        class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                                    >
                                        {{ staffCount }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Clear Button -->
                        <button
                            v-if="form.recipients.trim()"
                            type="button"
                            @click="form.recipients = ''"
                            class="inline-flex items-center gap-1 rounded-xl p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                            title="Clear all recipients"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Textarea for raw emails -->
                <textarea
                    id="recipients"
                    v-model="form.recipients"
                    rows="6"
                    placeholder="user1@example.com&#10;user2@gmail.com&#10;custom-lead@domain.com"
                    :disabled="form.processing || isImporting"
                    class="w-full rounded-2xl border bg-slate-50/70 p-4 font-mono text-xs leading-relaxed text-slate-900 transition outline-none placeholder:text-slate-400 focus:bg-white focus:ring-4 dark:bg-gray-900/80 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:bg-gray-950"
                    :class="
                        form.errors.recipients
                            ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/10'
                            : 'border-slate-200 focus:border-indigo-600 focus:ring-indigo-600/10 dark:border-gray-700'
                    "
                ></textarea>

                <p
                    v-if="form.errors.recipients"
                    class="mt-1.5 text-xs font-medium text-rose-600"
                >
                    {{ form.errors.recipients }}
                </p>

                <!-- Live Parsing Status Bar -->
                <div
                    class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs dark:border-gray-800"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            <span
                                >{{ recipientStats.validEmails.length }} valid
                                unique</span
                            >
                        </span>

                        <span
                            v-if="recipientStats.duplicateCount > 0"
                            class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 font-semibold text-slate-600 dark:bg-gray-800 dark:text-gray-400"
                        >
                            {{ recipientStats.duplicateCount }} duplicate{{
                                recipientStats.duplicateCount > 1 ? 's' : ''
                            }}
                            auto-merged
                        </span>

                        <span
                            v-if="recipientStats.invalidItems.length > 0"
                            class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2.5 py-1 font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-400"
                        >
                            <AlertCircle class="h-3.5 w-3.5" />
                            <span
                                >{{ recipientStats.invalidItems.length }}
                                invalid ignored</span
                            >
                        </span>
                    </div>

                    <button
                        v-if="
                            recipientStats.duplicateCount > 0 ||
                            recipientStats.invalidItems.length > 0
                        "
                        type="button"
                        @click="cleanAndFormatRecipients"
                        class="text-xs font-bold text-indigo-600 underline hover:text-indigo-700 dark:text-indigo-400"
                    >
                        Format & Clean List
                    </button>
                </div>
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
                    class="w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:ring-4 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
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
                    Emails are queued and delivered asynchronously. Duplicates
                    and invalid addresses are automatically stripped.
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
                            recipientStats.validEmails.length === 0
                        "
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        <Send v-else class="h-4 w-4" />
                        <span>
                            Send to {{ recipientStats.validEmails.length }}
                            {{
                                recipientStats.validEmails.length === 1
                                    ? 'Recipient'
                                    : 'Recipients'
                            }}
                        </span>
                    </button>
                </div>
            </div>
        </form>
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
                                {{ recipientStats.validEmails.length }} unique
                                recipient{{
                                    recipientStats.validEmails.length === 1
                                        ? ''
                                        : 's'
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
                                <img
                                    src="/favicon.svg"
                                    alt="HSCStack"
                                    class="h-8 w-8 rounded-lg shadow-xs"
                                />
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
                                    recipientStats.validEmails.length === 1
                                        ? recipientStats.validEmails[0]
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
                                class="mt-3 text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                Need help or want to manage your preferences? Visit our
                                <span
                                    class="text-indigo-600 underline dark:text-indigo-400"
                                    >Support Center</span
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
                            Confirm Email Queue
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Dispatching to
                            {{ recipientStats.validEmails.length }} unique
                            recipient{{
                                recipientStats.validEmails.length === 1
                                    ? ''
                                    : 's'
                            }}
                        </p>
                    </div>
                </div>

                <p
                    class="mb-5 text-xs leading-relaxed text-slate-600 dark:text-gray-300"
                >
                    Are you sure you want to send this broadcast? The emails
                    will be queued asynchronously.
                    <span
                        v-if="recipientStats.duplicateCount > 0"
                        class="mt-1 block font-medium text-slate-400"
                    >
                        ({{ recipientStats.duplicateCount }} duplicates were
                        detected and will only receive 1 email).
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
