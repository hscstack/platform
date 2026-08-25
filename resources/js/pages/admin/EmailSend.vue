<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Loader2, Mail, Send, Users, AlertCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import HTMLEditor from '@/components/HTMLEditor.vue';

defineProps({
    recipientCount: {
        type: Number,
        default: 0,
    },
});

const showConfirmModal = ref(false);

const form = useForm({
    subject: '',
    body: '',
});

const handleSendClick = () => {
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
    });
};
</script>

<template>
    <Head title="Send Bulk Email" />

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
                            Broadcast Email
                        </h1>
                    </div>
                    <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">
                        Compose and dispatch email announcements to subscribed
                        platform users.
                    </p>
                </div>

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

            <!-- Zero Subscribers Warning -->
            <div
                v-if="recipientCount === 0"
                class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-xs font-medium text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
            >
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                <p>
                    There are currently no active users with email notifications
                    enabled.
                </p>
            </div>

            <form @submit.prevent="handleSendClick" class="space-y-6">
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
                        placeholder="e.g. New Higher Mathematics Study Guide & Question Bank Available!"
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
                        placeholder="Write your email announcement content here..."
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
                    class="flex items-center justify-between border-t border-slate-100 pt-6 dark:border-gray-800"
                >
                    <p class="text-xs text-slate-400 dark:text-gray-500">
                        Emails are queued and sent in background chunks to avoid
                        server limits.
                    </p>

                    <button
                        type="submit"
                        :disabled="form.processing || recipientCount === 0"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        <Send v-else class="h-4 w-4" />
                        <span>Send Broadcast</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                            Confirm Email Broadcast
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Queue broadcast to {{ recipientCount }} subscribed
                            users
                        </p>
                    </div>
                </div>

                <p
                    class="mb-5 text-xs leading-relaxed text-slate-600 dark:text-gray-300"
                >
                    Are you sure you want to send this broadcast? The emails
                    will be dispatched to all
                    <strong>{{ recipientCount }}</strong> users who have enabled
                    email updates.
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
