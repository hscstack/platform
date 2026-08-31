<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Mail,
    Search,
    X,
    CheckCircle2,
    AlertCircle,
    Send,
    Inbox,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface LogItem {
    id: number;
    recipient_email: string;
    recipient_name?: string | null;
    subject: string;
    status: 'sent' | 'failed';
    error_message?: string | null;
    sent_at?: string | null;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedLogs {
    data: LogItem[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    logs: PaginatedLogs;
    filters: {
        search?: string | null;
        status?: string | null;
    };
    stats: {
        total: number;
        sent: number;
        failed: number;
    };
}>();

const searchQuery = ref(props.filters.search || '');
const activeStatus = ref(props.filters.status || 'all');

let searchDebounceTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/admin/emails/logs',
        {
            search: searchQuery.value || undefined,
            status:
                activeStatus.value !== 'all' ? activeStatus.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(searchQuery, () => {
    if (searchDebounceTimeout) {
        clearTimeout(searchDebounceTimeout);
    }

    searchDebounceTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

const setStatus = (status: string) => {
    activeStatus.value = status;
    applyFilters();
};

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters();
};

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) {
        return '';
    }

    try {
        return new Date(dateStr).toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <Head title="Email Logs — Admin" />

    <div class="mx-auto space-y-6">
        <!-- Emails Admin Header & Tab System -->
        <div class="space-y-4">
            <div>
                <h1
                    class="text-2xl font-black text-slate-900 dark:text-gray-100"
                >
                    Emails
                </h1>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                    Compose broadcast announcements and view email delivery
                    logs.
                </p>
            </div>

            <!-- Tab Navigation Bar -->
            <div
                class="flex items-center gap-2 border-b border-slate-200 pb-3 dark:border-gray-800"
            >
                <Link
                    href="/admin/emails/send"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition"
                    :class="
                        $page.url.startsWith('/admin/emails/send')
                            ? 'bg-slate-900 text-white dark:bg-gray-100 dark:text-gray-900'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'
                    "
                >
                    <Send class="h-3.5 w-3.5" />
                    <span>Send Broadcast</span>
                </Link>

                <Link
                    href="/admin/emails/logs"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition"
                    :class="
                        $page.url.startsWith('/admin/emails/logs')
                            ? 'bg-slate-900 text-white dark:bg-gray-100 dark:text-gray-900'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'
                    "
                >
                    <Mail class="h-3.5 w-3.5" />
                    <span>Delivery Logs</span>
                </Link>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold text-slate-500 uppercase dark:text-gray-400"
                        >Total Attempts</span
                    >
                    <Inbox class="h-4 w-4 text-slate-400" />
                </div>
                <p
                    class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-gray-100"
                >
                    {{ stats.total }}
                </p>
            </div>

            <div
                class="rounded-2xl border border-emerald-200/80 bg-emerald-50/50 p-4 shadow-xs dark:border-emerald-900/60 dark:bg-emerald-950/30"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold text-emerald-700 uppercase dark:text-emerald-300"
                        >Delivered (Sent)</span
                    >
                    <CheckCircle2
                        class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                    />
                </div>
                <p
                    class="mt-2 text-2xl font-extrabold text-emerald-700 dark:text-emerald-300"
                >
                    {{ stats.sent }}
                </p>
            </div>

            <div
                class="rounded-2xl border border-rose-200/80 bg-rose-50/50 p-4 shadow-xs dark:border-rose-900/60 dark:bg-rose-950/30"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold text-rose-700 uppercase dark:text-rose-300"
                        >Failed Attempts</span
                    >
                    <AlertCircle
                        class="h-4 w-4 text-rose-600 dark:text-rose-400"
                    />
                </div>
                <p
                    class="mt-2 text-2xl font-extrabold text-rose-700 dark:text-rose-300"
                >
                    {{ stats.failed }}
                </p>
            </div>
        </div>

        <!-- Controls: Search & Status Filters -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <!-- Search Input -->
            <div class="relative w-full max-w-sm">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search recipient or subject..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-2 pr-8 pl-9 text-xs font-semibold text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-indigo-400"
                />
                <Search
                    class="pointer-events-none absolute top-2.5 left-3 h-3.5 w-3.5 text-slate-400"
                />
                <button
                    v-if="searchQuery"
                    type="button"
                    @click="clearSearch"
                    class="absolute top-2.5 right-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-gray-200"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>

            <!-- Status Filter Tabs -->
            <div
                class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 p-1 dark:border-gray-800 dark:bg-gray-900"
            >
                <button
                    type="button"
                    @click="setStatus('all')"
                    class="rounded-lg px-3 py-1 text-xs font-bold transition"
                    :class="
                        activeStatus === 'all'
                            ? 'bg-white text-indigo-600 shadow-xs dark:bg-gray-800 dark:text-indigo-400'
                            : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200'
                    "
                >
                    All
                </button>
                <button
                    type="button"
                    @click="setStatus('sent')"
                    class="rounded-lg px-3 py-1 text-xs font-bold transition"
                    :class="
                        activeStatus === 'sent'
                            ? 'bg-white text-emerald-600 shadow-xs dark:bg-gray-800 dark:text-emerald-400'
                            : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200'
                    "
                >
                    Sent ({{ stats.sent }})
                </button>
                <button
                    type="button"
                    @click="setStatus('failed')"
                    class="rounded-lg px-3 py-1 text-xs font-bold transition"
                    :class="
                        activeStatus === 'failed'
                            ? 'bg-white text-rose-600 shadow-xs dark:bg-gray-800 dark:text-rose-400'
                            : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200'
                    "
                >
                    Failed ({{ stats.failed }})
                </button>
            </div>
        </div>

        <!-- Logs Table -->
        <div
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr
                            class="border-b border-slate-100 bg-slate-50/70 text-slate-500 dark:border-gray-800 dark:bg-gray-950/50 dark:text-gray-400"
                        >
                            <th class="py-3 pr-4 pl-6 font-bold">Recipient</th>
                            <th class="py-3 pr-4 font-bold">Subject</th>
                            <th class="py-3 pr-4 font-bold">Status</th>
                            <th class="py-3 pr-6 font-bold">Date & Time</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-100 dark:divide-gray-800/60"
                    >
                        <tr
                            v-for="log in logs.data"
                            :key="log.id"
                            class="transition hover:bg-slate-50/50 dark:hover:bg-gray-800/40"
                        >
                            <td
                                class="py-3.5 pr-4 pl-6 font-medium text-slate-800 dark:text-gray-200"
                            >
                                {{ log.recipient_email }}
                                <span
                                    v-if="log.recipient_name"
                                    class="block text-[11px] text-slate-400 dark:text-gray-500"
                                >
                                    {{ log.recipient_name }}
                                </span>
                            </td>
                            <td
                                class="max-w-md py-3.5 pr-4 font-semibold text-slate-700 dark:text-gray-300"
                            >
                                <div class="line-clamp-2">
                                    {{ log.subject }}
                                </div>
                                <div
                                    v-if="log.error_message"
                                    class="mt-1 text-[11px] font-normal text-rose-600 dark:text-rose-400"
                                >
                                    {{ log.error_message }}
                                </div>
                            </td>
                            <td class="py-3.5 pr-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase"
                                    :class="
                                        log.status === 'sent'
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400'
                                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400'
                                    "
                                >
                                    <CheckCircle2
                                        v-if="log.status === 'sent'"
                                        class="h-3 w-3"
                                    />
                                    <AlertCircle v-else class="h-3 w-3" />
                                    <span>{{ log.status }}</span>
                                </span>
                            </td>
                            <td
                                class="py-3.5 pr-6 whitespace-nowrap text-slate-400 dark:text-gray-500"
                            >
                                {{ formatDate(log.sent_at || log.created_at) }}
                            </td>
                        </tr>

                        <tr v-if="logs.data.length === 0">
                            <td
                                colspan="4"
                                class="py-12 text-center text-slate-400 dark:text-gray-500"
                            >
                                <Mail
                                    class="mx-auto mb-2 h-8 w-8 text-slate-300 dark:text-gray-600"
                                />
                                <p class="font-semibold">No email logs found</p>
                                <p class="text-[11px]">
                                    No delivery records match your filter
                                    criteria.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div
                v-if="logs.links.length > 3"
                class="flex items-center justify-between border-t border-slate-100 px-6 py-4 dark:border-gray-800"
            >
                <span class="text-xs text-slate-400 dark:text-gray-500">
                    Showing {{ logs.data.length }} of {{ logs.total }} logs
                </span>

                <div class="flex items-center gap-1">
                    <template v-for="(link, i) in logs.links" :key="i">
                        <span
                            v-if="!link.url"
                            class="rounded-lg px-3 py-1.5 text-xs text-slate-300 dark:text-gray-600"
                            v-html="link.label"
                        />
                        <Link
                            v-else
                            :href="link.url"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                            :class="
                                link.active
                                    ? 'bg-indigo-600 text-white shadow-xs dark:bg-indigo-500'
                                    : 'text-slate-600 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-800'
                            "
                        >
                            <span v-html="link.label" />
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
