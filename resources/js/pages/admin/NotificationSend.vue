<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    BellRing,
    Send,
    Users,
    User,
    GraduationCap,
    ShieldCheck,
    AtSign,
    Loader2,
    Link as LinkIcon,
    Bell,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    totalUsersCount: {
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

const showConfirmModal = ref(false);

const form = useForm({
    recipient_type: 'all' as 'all' | 'students' | 'staff' | 'single',
    recipient_username: '',
    title: '',
    message: '',
    url: '',
});

const audienceCount = computed(() => {
    switch (form.recipient_type) {
        case 'all':
            return props.totalUsersCount;
        case 'students':
            return props.studentsCount;
        case 'staff':
            return props.staffCount;
        case 'single':
            return 1;
        default:
            return 0;
    }
});

const audienceLabel = computed(() => {
    switch (form.recipient_type) {
        case 'all':
            return `All registered users (${props.totalUsersCount})`;
        case 'students':
            return `Students / General users (${props.studentsCount})`;
        case 'staff':
            return `Staff & Moderators (${props.staffCount})`;
        case 'single':
            return 'Single specific user';
        default:
            return '';
    }
});

const canSubmit = computed(() => {
    if (!form.title.trim() || !form.message.trim()) {
return false;
}

    if (form.recipient_type === 'single' && !form.recipient_username.trim()) {
return false;
}

    return true;
});

const handleOpenConfirm = () => {
    if (!canSubmit.value) {
return;
}

    showConfirmModal.value = true;
};

const submitNotification = () => {
    form.post('/admin/notifications/send', {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmModal.value = false;
            form.reset('title', 'message', 'url', 'recipient_username');
        },
    });
};
</script>

<template>
    <Head title="Broadcast Alert | Admin" />

    <div class="flex w-full flex-1 flex-col pb-12">
        <!-- Page Header -->
        <div
            class="mb-8 flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div>
                <h1
                    class="flex items-center gap-2.5 text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-100"
                >
                    <BellRing
                        class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                    />
                    <span>Broadcast In-App Alert</span>
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                    Push instant in-app notifications directly to users'
                    notification bells.
                </p>
            </div>

            <!-- Stats Badge -->
            <div
                class="flex items-center gap-2 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-2.5 text-xs font-semibold text-indigo-900 dark:border-indigo-900/50 dark:bg-indigo-950/30 dark:text-indigo-300"
            >
                <Users class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                <span>{{ totalUsersCount }} total users reachable</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <!-- Composer Form -->
            <div class="space-y-6 lg:col-span-7">
                <!-- Target Audience Selection -->
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <label
                        class="mb-3 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >
                        Target Audience
                    </label>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <button
                            type="button"
                            @click="form.recipient_type = 'all'"
                            class="flex flex-col items-center justify-center gap-2 rounded-xl border p-3 text-center transition-all"
                            :class="
                                form.recipient_type === 'all'
                                    ? 'border-indigo-600 bg-indigo-50/60 text-indigo-700 shadow-xs dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-300'
                                    : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800'
                            "
                        >
                            <Users class="h-5 w-5" />
                            <span class="text-xs font-bold">All Users</span>
                            <span class="text-[10px] text-slate-400"
                                >({{ totalUsersCount }})</span
                            >
                        </button>

                        <button
                            type="button"
                            @click="form.recipient_type = 'students'"
                            class="flex flex-col items-center justify-center gap-2 rounded-xl border p-3 text-center transition-all"
                            :class="
                                form.recipient_type === 'students'
                                    ? 'border-indigo-600 bg-indigo-50/60 text-indigo-700 shadow-xs dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-300'
                                    : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800'
                            "
                        >
                            <GraduationCap class="h-5 w-5" />
                            <span class="text-xs font-bold">Students</span>
                            <span class="text-[10px] text-slate-400"
                                >({{ studentsCount }})</span
                            >
                        </button>

                        <button
                            type="button"
                            @click="form.recipient_type = 'staff'"
                            class="flex flex-col items-center justify-center gap-2 rounded-xl border p-3 text-center transition-all"
                            :class="
                                form.recipient_type === 'staff'
                                    ? 'border-indigo-600 bg-indigo-50/60 text-indigo-700 shadow-xs dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-300'
                                    : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800'
                            "
                        >
                            <ShieldCheck class="h-5 w-5" />
                            <span class="text-xs font-bold">Staff/Admins</span>
                            <span class="text-[10px] text-slate-400"
                                >({{ staffCount }})</span
                            >
                        </button>

                        <button
                            type="button"
                            @click="form.recipient_type = 'single'"
                            class="flex flex-col items-center justify-center gap-2 rounded-xl border p-3 text-center transition-all"
                            :class="
                                form.recipient_type === 'single'
                                    ? 'border-indigo-600 bg-indigo-50/60 text-indigo-700 shadow-xs dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-300'
                                    : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800'
                            "
                        >
                            <User class="h-5 w-5" />
                            <span class="text-xs font-bold">Single User</span>
                            <span class="text-[10px] text-slate-400"
                                >(Direct)</span
                            >
                        </button>
                    </div>

                    <!-- Single User Username Input -->
                    <div
                        v-if="form.recipient_type === 'single'"
                        class="mt-4 border-t border-slate-100 pt-4 dark:border-gray-800"
                    >
                        <label
                            class="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                        >
                            Recipient Username
                        </label>
                        <div class="relative">
                            <AtSign
                                class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                            />
                            <input
                                v-model="form.recipient_username"
                                type="text"
                                placeholder="student_123"
                                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-4 pl-9 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <p
                            v-if="form.errors.recipient_username"
                            class="mt-1 text-xs font-medium text-rose-600"
                        >
                            {{ form.errors.recipient_username }}
                        </p>
                    </div>
                </div>

                <!-- Notification Content -->
                <div
                    class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- Title -->
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label
                                class="block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                            >
                                Notification Title *
                            </label>
                            <span class="text-[11px] text-slate-400">
                                {{ form.title.length }}/150
                            </span>
                        </div>
                        <input
                            v-model="form.title"
                            type="text"
                            maxlength="150"
                            placeholder="e.g., Scheduled Maintenance or New Study Resources Released"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.title"
                            class="mt-1 text-xs font-medium text-rose-600"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <!-- Message Body -->
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label
                                class="block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                            >
                                Message Content *
                            </label>
                            <span class="text-[11px] text-slate-400">
                                {{ form.message.length }}/500
                            </span>
                        </div>
                        <textarea
                            v-model="form.message"
                            rows="4"
                            maxlength="500"
                            placeholder="Type the message that will be shown in the notification card..."
                            class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-sm leading-relaxed text-slate-900 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        ></textarea>
                        <p
                            v-if="form.errors.message"
                            class="mt-1 text-xs font-medium text-rose-600"
                        >
                            {{ form.errors.message }}
                        </p>
                    </div>

                    <!-- Optional Target URL -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                        >
                            Click Action URL (Optional)
                        </label>
                        <div class="relative">
                            <LinkIcon
                                class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                            />
                            <input
                                v-model="form.url"
                                type="text"
                                placeholder="/forum or /blogs/physics-formula-sheet"
                                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-4 pl-9 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            />
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <button
                    type="button"
                    @click="handleOpenConfirm"
                    :disabled="!canSubmit || form.processing"
                    class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-md transition-all hover:bg-indigo-700 hover:shadow-lg active:scale-98 disabled:opacity-50"
                >
                    <Send class="h-4 w-4" />
                    <span>Broadcast Notification to {{ audienceLabel }}</span>
                </button>
            </div>

            <!-- Live Preview Card Column -->
            <div class="space-y-4 lg:col-span-5">
                <div class="sticky top-20 space-y-3">
                    <p
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >
                        Live Dropdown Preview
                    </p>

                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xl dark:border-gray-800 dark:bg-gray-900"
                    >
                        <!-- Mock Dropdown Header -->
                        <div
                            class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2.5 dark:border-gray-800"
                        >
                            <span
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                Notifications
                            </span>
                            <span
                                class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400"
                            >
                                1 new
                            </span>
                        </div>

                        <!-- Mock Notification Item Card -->
                        <div
                            class="flex items-start gap-3 rounded-xl border border-indigo-100/60 bg-indigo-50/50 p-3.5 dark:border-indigo-900/40 dark:bg-indigo-950/20"
                        >
                            <div
                                class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/60 dark:text-indigo-400"
                            >
                                <Bell class="h-4 w-4" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-center justify-between gap-1"
                                >
                                    <p
                                        class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                                    >
                                        {{
                                            form.title ||
                                            'Your notification title here'
                                        }}
                                    </p>
                                    <span
                                        class="h-2 w-2 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400"
                                    />
                                </div>

                                <p
                                    class="mt-1 text-[11px] leading-relaxed text-slate-600 dark:text-gray-400"
                                >
                                    {{
                                        form.message ||
                                        'The message description will preview here in real-time as you type.'
                                    }}
                                </p>

                                <div
                                    class="mt-2 flex items-center justify-between"
                                >
                                    <span
                                        class="text-[10px] font-medium text-slate-400 dark:text-gray-500"
                                    >
                                        Just now
                                    </span>
                                    <span
                                        v-if="form.url"
                                        class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400"
                                    >
                                        View &rarr;
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p
                            class="mt-3 text-center text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            This is an accurate preview of how users will see
                            this notification.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="showConfirmModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
            >
                <div
                    class="animate-in zoom-in-95 w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/60 dark:text-indigo-400"
                        >
                            <BellRing class="h-5 w-5" />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-gray-100"
                            >
                                Confirm Notification Broadcast
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                You are about to send this alert to
                                <strong
                                    class="text-indigo-600 dark:text-indigo-400"
                                    >{{ audienceCount }} users</strong
                                >.
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-4 space-y-1 rounded-xl bg-slate-50 p-3 text-xs text-slate-600 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <p><strong>Title:</strong> {{ form.title }}</p>
                        <p class="line-clamp-2">
                            <strong>Message:</strong> {{ form.message }}
                        </p>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="showConfirmModal = false"
                            class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="submitNotification"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="h-3.5 w-3.5 animate-spin"
                            />
                            <span>{{
                                form.processing
                                    ? 'Broadcasting...'
                                    : 'Yes, Send Now'
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
