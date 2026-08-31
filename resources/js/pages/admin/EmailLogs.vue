<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Mail,
    Search,
    X,
    CheckCircle2,
    AlertCircle,
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
    <Head title="Email Logs - Admin" />

    <div class="space-y-5">
        <!-- Minimal Top Header -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl dark:text-gray-100"
                >
                    Email Management
                </h1>
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    Compose broadcast announcements and view real-time email
                    delivery logs.
                </p>
            </div>

            <!-- Header Quick Tabs -->
            <div
                class="flex max-w-full items-center gap-1.5 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <Link
                    href="/admin/emails/send"
                    class="flex shrink-0 items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                >
                    <span>Send Email</span>
                </Link>

                <Link
                    href="/admin/emails/logs"
                    class="flex shrink-0 items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                >
                    <Mail class="h-3.5 w-3.5" />
                    <span>Delivery Logs</span>
                </Link>
            </div>
        </div>

        <!-- Integrated Status Pills & Search Bar -->
        <div
            class="flex flex-col gap-3 rounded-2xl border border-slate-200/90 bg-white p-3.5 shadow-2xs sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Status Tabs -->
            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    type="button"
                    @click="setStatus('all')"
                    class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        activeStatus === 'all' || !activeStatus
                            ? 'bg-slate-900 text-white dark:bg-gray-100 dark:text-gray-900'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
                    ]"
                >
                    All ({{ stats.total }})
                </button>

                <button
                    type="button"
                    @click="setStatus('sent')"
                    class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        activeStatus === 'sent'
                            ? 'bg-emerald-600 text-white'
                            : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300',
                    ]"
                >
                    Sent ({{ stats.sent }})
                </button>

                <button
                    type="button"
                    @click="setStatus('failed')"
                    class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        activeStatus === 'failed'
                            ? 'bg-rose-600 text-white'
                            : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300',
                    ]"
                >
                    Failed ({{ stats.failed }})
                </button>
            </div>

            <!-- Search Bar -->
            <div class="relative w-full sm:w-64">
                <Search
                    class="pointer-events-none absolute top-2 left-2.5 h-3.5 w-3.5 text-slate-400"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search recipient or subject..."
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-1.5 pr-8 pl-8 text-xs text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                />
                <button
                    v-if="searchQuery"
                    type="button"
                    @click="clearSearch"
                    class="absolute top-2 right-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-gray-200"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>

        <!-- Clean Table -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <div v-if="logs.data.length === 0" class="py-14 text-center">
                <Inbox class="mx-auto h-7 w-7 text-slate-400" />
                <p
                    class="mt-2 text-xs font-bold text-slate-700 dark:text-gray-300"
                >
                    No email logs match your filter
                </p>
                <p class="text-[11px] text-slate-400 dark:text-gray-500">
                    Try clearing the search or changing status filter.
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table
                    class="w-full text-left text-xs text-slate-700 dark:text-gray-300"
                >
                    <thead
                        class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-500 uppercase dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400"
                    >
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap">
                                Recipient
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap">Subject</th>
                            <th class="px-4 py-3 whitespace-nowrap">Status</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">
                                Date & Time
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-100 dark:divide-gray-800"
                    >
                        <tr
                            v-for="log in logs.data"
                            :key="log.id"
                            class="transition hover:bg-slate-50/50 dark:hover:bg-gray-800/40"
                        >
                            <td
                                class="px-4 py-3 font-medium text-slate-900 dark:text-gray-100"
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
                                class="max-w-md px-4 py-3 font-medium text-slate-700 dark:text-gray-300"
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
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase"
                                    :class="
                                        log.status === 'sent'
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
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
                                class="px-4 py-3 text-right whitespace-nowrap text-slate-500 dark:text-gray-400"
                            >
                                {{ formatDate(log.sent_at || log.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div
                v-if="logs.links.length > 3"
                class="flex items-center justify-between border-t border-slate-100 px-4 py-3 dark:border-gray-800"
            >
                <span class="text-xs text-slate-500 dark:text-gray-400">
                    Showing {{ logs.data.length }} of {{ logs.total }} logs
                </span>

                <div class="flex items-center gap-1">
                    <template v-for="(link, i) in logs.links" :key="i">
                        <span
                            v-if="!link.url"
                            class="rounded-lg px-2.5 py-1 text-xs text-slate-300 dark:text-gray-600"
                        >
                            <span v-html="link.label" />
                        </span>
                        <Link
                            v-else
                            :href="link.url"
                            class="rounded-lg px-2.5 py-1 text-xs font-semibold transition"
                            :class="
                                link.active
                                    ? 'bg-indigo-600 text-white shadow-2xs dark:bg-indigo-500'
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
