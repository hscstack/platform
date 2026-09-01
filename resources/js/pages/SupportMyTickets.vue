<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Send,
    MessageSquare,
    CheckCircle2,
    Clock,
    XCircle,
    FileText,
    ChevronDown,
    ChevronUp,
    HelpCircle,
} from 'lucide-vue-next';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';

interface UserInfo {
    id: number;
    name: string;
    username: string;
    image_path?: string;
}

interface Ticket {
    id: number;
    ticket_number: string;
    category: string;
    subject: string;
    message: string;
    attachment_url?: string | null;
    status: 'open' | 'in_progress' | 'resolved' | 'closed';
    admin_reply?: string | null;
    replied_by?: UserInfo | null;
    replied_at?: string | null;
    created_at: string;
}

const props = defineProps<{
    tickets: Ticket[];
    categories: Record<string, string>;
}>();

const expandedTicketId = ref<number | null>(
    props.tickets.length > 0 ? props.tickets[0].id : null,
);

const toggleExpand = (id: number) => {
    expandedTicketId.value = expandedTicketId.value === id ? null : id;
};

const getCategoryLabel = (key: string) => {
    return props.categories[key] || key;
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'open':
            return {
                label: 'Open',
                bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                icon: Clock,
            };
        case 'in_progress':
            return {
                label: 'In Progress',
                bg: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
                icon: HelpCircle,
            };
        case 'resolved':
            return {
                label: 'Resolved',
                bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                icon: CheckCircle2,
            };
        case 'closed':
            return {
                label: 'Closed',
                bg: 'bg-slate-500/10 text-slate-600 dark:text-gray-400 border-slate-500/20',
                icon: XCircle,
            };
        default:
            return {
                label: status,
                bg: 'bg-slate-500/10 text-slate-600 dark:text-gray-400 border-slate-500/20',
                icon: Clock,
            };
    }
};

const formatDate = (dateString: string) => {
    if (!dateString) {
        return '';
    }

    const date = new Date(dateString);

    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head>
        <title>My Support Tickets - HSCStack</title>
        <meta
            name="description"
            content="Track your submitted support tickets and view responses from the HSCStack team."
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
            আপনার সাবমিট করা টিকেট ও এডমিন উত্তরের তালিকা
        </p>
    </header>

    <div class="mx-auto max-w-3xl px-4 pb-16 sm:px-6">
        <div class="space-y-4">
            <!-- Navigation Tabs -->
            <div
                class="flex items-center justify-between border-b border-slate-200 dark:border-gray-800"
            >
                <div class="flex gap-2">
                    <Link
                        href="/support"
                        class="relative flex items-center gap-2 px-4 py-3 text-sm font-bold text-slate-500 transition-colors hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <Send class="h-4 w-4" />
                        <span>নতুন টিকেট খুলুন</span>
                    </Link>

                    <Link
                        href="/support/my-tickets"
                        class="relative flex items-center gap-2 px-4 py-3 text-sm font-bold text-indigo-600 transition-colors dark:text-indigo-400"
                    >
                        <MessageSquare class="h-4 w-4" />
                        <span>আমার টিকেটসমূহ</span>
                        <span
                            v-if="tickets.length > 0"
                            class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300"
                        >
                            {{ tickets.length }}
                        </span>
                        <span
                            class="absolute right-0 bottom-0 left-0 h-0.5 bg-indigo-600 dark:bg-indigo-400"
                        ></span>
                    </Link>
                </div>
            </div>

            <!-- Tickets List -->
            <div class="space-y-4">
                <EmptyState
                    v-if="tickets.length === 0"
                    :icon="FileText"
                    variant="card"
                    title="কোনো টিকেট পাওয়া যায়নি"
                    description="আপনার কোনো খোলা বা অতীত টিকেট নেই।"
                >
                    <Link
                        href="/support"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-indigo-700"
                    >
                        <Send class="h-3.5 w-3.5" />
                        <span>প্রথম টিকেট তৈরি করুন</span>
                    </Link>
                </EmptyState>

                <div
                    v-for="ticket in tickets"
                    :key="ticket.id"
                    class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-all dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- Ticket Header / Summary Row -->
                    <div
                        @click="toggleExpand(ticket.id)"
                        class="flex cursor-pointer flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-bold text-slate-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    {{ ticket.ticket_number }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[11px] font-bold"
                                    :class="getStatusBadge(ticket.status).bg"
                                >
                                    <component
                                        :is="getStatusBadge(ticket.status).icon"
                                        class="h-3 w-3"
                                    />
                                    <span>{{
                                        getStatusBadge(ticket.status).label
                                    }}</span>
                                </span>
                                <span
                                    class="rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300"
                                >
                                    {{ getCategoryLabel(ticket.category) }}
                                </span>
                            </div>
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-gray-100"
                            >
                                {{ ticket.subject }}
                            </h3>
                            <p
                                class="text-xs text-slate-400 dark:text-gray-500"
                            >
                                সাবমিট করা হয়েছে:
                                {{ formatDate(ticket.created_at) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <span
                                v-if="ticket.admin_reply"
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400"
                            >
                                <CheckCircle2 class="h-3.5 w-3.5" />
                                <span>উত্তর দেওয়া হয়েছে</span>
                            </span>
                            <button
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-gray-800"
                            >
                                <ChevronUp
                                    v-if="expandedTicketId === ticket.id"
                                    class="h-5 w-5"
                                />
                                <ChevronDown v-else class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Expanded Details -->
                    <div
                        v-if="expandedTicketId === ticket.id"
                        class="border-t border-slate-100 bg-slate-50/50 p-5 sm:p-6 dark:border-gray-800 dark:bg-gray-950/40"
                    >
                        <!-- User's Query -->
                        <div class="space-y-3">
                            <h4
                                class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                            >
                                আপনার বার্তা
                            </h4>
                            <div
                                class="rounded-xl border border-slate-200/60 bg-white p-4 text-xs leading-relaxed whitespace-pre-wrap text-slate-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            >
                                {{ ticket.message }}
                            </div>

                            <!-- Attachment if any -->
                            <div v-if="ticket.attachment_url" class="mt-3">
                                <p
                                    class="mb-1 text-xs font-bold text-slate-500 dark:text-gray-400"
                                >
                                    সংযুক্ত ছবি:
                                </p>
                                <a
                                    :href="ticket.attachment_url"
                                    target="_blank"
                                    class="inline-block overflow-hidden rounded-xl border border-slate-200 transition-opacity hover:opacity-90 dark:border-gray-700"
                                >
                                    <img
                                        :src="ticket.attachment_url"
                                        alt="Ticket attachment"
                                        class="max-h-60 rounded-lg object-contain"
                                    />
                                </a>
                            </div>
                        </div>

                        <!-- Admin Reply Section -->
                        <div
                            class="mt-6 border-t border-slate-200/80 pt-6 dark:border-gray-800"
                        >
                            <h4
                                class="mb-3 text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                            >
                                এডমিনের সমাধান / উত্তর
                            </h4>

                            <div
                                v-if="ticket.admin_reply"
                                class="rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/50 via-white to-indigo-50/30 p-5 shadow-xs dark:border-indigo-500/20 dark:from-indigo-950/20 dark:via-gray-900 dark:to-indigo-950/10"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white shadow-xs"
                                        >
                                            HS
                                        </div>
                                        <div>
                                            <span
                                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                                            >
                                                HSCStack Support Team
                                            </span>
                                            <span
                                                v-if="ticket.replied_by"
                                                class="ml-1 text-[11px] text-slate-500 dark:text-gray-400"
                                            >
                                                ({{ ticket.replied_by.name }})
                                            </span>
                                        </div>
                                    </div>
                                    <span
                                        v-if="ticket.replied_at"
                                        class="text-[11px] font-medium text-slate-400 dark:text-gray-500"
                                    >
                                        {{ formatDate(ticket.replied_at) }}
                                    </span>
                                </div>
                                <p
                                    class="text-xs leading-relaxed whitespace-pre-wrap text-slate-800 dark:text-gray-200"
                                >
                                    {{ ticket.admin_reply }}
                                </p>
                            </div>

                            <!-- If resolved without custom reply -->
                            <div
                                v-else-if="ticket.status === 'resolved'"
                                class="flex items-center gap-3 rounded-xl border border-dashed border-emerald-200 bg-emerald-50/50 p-4 text-xs font-medium text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/5 dark:text-emerald-300"
                            >
                                <CheckCircle2
                                    class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                                />
                                <span>
                                    আপনার টিকেটটি পর্যালোচনা করা হয়েছে এবং
                                    বিষয়টি সমাধান করা হয়েছে।
                                </span>
                            </div>

                            <!-- If closed without custom reply -->
                            <div
                                v-else-if="ticket.status === 'closed'"
                                class="flex items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-xs font-medium text-slate-700 dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-400"
                            >
                                <XCircle
                                    class="h-5 w-5 shrink-0 text-slate-500 dark:text-gray-400"
                                />
                                <span>
                                    আপনার সাপোর্ট টিকেটটি পর্যালোচনা শেষে বন্ধ
                                    করা হয়েছে।
                                </span>
                            </div>

                            <!-- Pending / Waiting for review -->
                            <div
                                v-else
                                class="flex items-center gap-3 rounded-xl border border-dashed border-amber-200 bg-amber-50/50 p-4 text-xs font-medium text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/5 dark:text-amber-300"
                            >
                                <Clock
                                    class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"
                                />
                                <span>
                                    আমাদের সাপোর্ট টিম আপনার টিকেটটি পর্যালোচনা
                                    করছে। এডমিন উত্তর দেওয়ার সাথে সাথেই এখানে তা
                                    দেখতে পাবেন।
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
