<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    LifeBuoy,
    Search,
    Clock,
    HelpCircle,
    CheckCircle2,
    XCircle,
    MessageSquare,
    Send,
    Trash2,
    Image as ImageIcon,
    User as UserIcon,
    X,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface UserInfo {
    id: number;
    name: string;
    username: string;
    email: string;
    image_path?: string | null;
}

interface Ticket {
    id: number;
    ticket_number: string;
    user_id: number;
    user: UserInfo;
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

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    tickets: {
        data: Ticket[];
        links: PaginationLink[];
        total: number;
        current_page: number;
        last_page: number;
    };
    stats: {
        total: number;
        open: number;
        in_progress: number;
        resolved: number;
        closed: number;
    };
    filters: {
        status?: string | null;
        category?: string | null;
        search?: string | null;
    };
    categories: Record<string, string>;
}>();

const searchTerm = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'all');
const selectedCategory = ref(props.filters.category || 'all');

// Modal State
const isModalOpen = ref(false);
const activeTicket = ref<Ticket | null>(null);

const replyForm = useForm({
    admin_reply: '',
    status: 'resolved',
});

const openReplyModal = (ticket: Ticket) => {
    activeTicket.value = ticket;
    replyForm.admin_reply = ticket.admin_reply || '';
    replyForm.status = ticket.status === 'open' ? 'resolved' : ticket.status;
    isModalOpen.value = true;
};

const closeReplyModal = () => {
    isModalOpen.value = false;
    activeTicket.value = null;
    replyForm.reset();
};

const submitReply = () => {
    if (!activeTicket.value) {
        return;
    }

    replyForm.patch(`/admin/tickets/${activeTicket.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeReplyModal();
        },
    });
};

const updateStatus = (ticket: Ticket, newStatus: string) => {
    let reply = ticket.admin_reply;

    if (!reply) {
        const defaultMessages: Record<string, string> = {
            resolved:
                'আপনার টিকেটটি পর্যালোচনা করা হয়েছে এবং বিষয়টি সমাধান করা হয়েছে। ধন্যবাদ!',
            closed: 'আপনার সাপোর্ট টিকেটটি পর্যালোচনা শেষে বন্ধ করা হয়েছে।',
            in_progress:
                'আমাদের সাপোর্ট টিম আপনার টিকেটটি পর্যালোচনা করছে এবং খুব শীঘ্রই সমাধান করা হবে।',
        };

        reply = defaultMessages[newStatus] || null;
    }

    router.patch(
        `/admin/tickets/${ticket.id}`,
        {
            status: newStatus,
            admin_reply: reply,
        },
        { preserveScroll: true },
    );
};

const deleteTicket = (ticket: Ticket) => {
    if (
        confirm(
            `Are you sure you want to permanently delete ticket ${ticket.ticket_number}?`,
        )
    ) {
        router.delete(`/admin/tickets/${ticket.id}`, {
            preserveScroll: true,
        });
    }
};

const applyFilters = () => {
    router.get(
        '/admin/tickets',
        {
            search: searchTerm.value || undefined,
            status:
                selectedStatus.value === 'all'
                    ? undefined
                    : selectedStatus.value,
            category:
                selectedCategory.value === 'all'
                    ? undefined
                    : selectedCategory.value,
        },
        { preserveState: true, replace: true },
    );
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
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Support Tickets - Admin" />

    <div class="flex w-full flex-1 flex-col space-y-6">
        <!-- Page Header & Search Bar -->
        <div
            class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div class="flex items-center gap-2.5">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-xs"
                >
                    <LifeBuoy class="h-5 w-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2
                            class="text-xl font-bold tracking-tight text-slate-900 dark:text-gray-100"
                        >
                            Support Tickets
                        </h2>
                        <span
                            class="rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400"
                        >
                            {{ stats.total }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Top Line Search Bar -->
            <div class="relative w-full sm:w-72">
                <Search
                    class="absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                />
                <input
                    v-model="searchTerm"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Search tickets, student, subject..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-1.5 pr-3 pl-8 text-xs text-slate-900 shadow-2xs placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100"
                />
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <button
                type="button"
                @click="
                    selectedStatus = 'open';
                    applyFilters();
                "
                class="flex items-center justify-between rounded-xl border p-4 text-left transition-all"
                :class="
                    selectedStatus === 'open'
                        ? 'border-amber-500 bg-amber-50/50 dark:border-amber-500/50 dark:bg-amber-500/10'
                        : 'border-slate-200/80 bg-white hover:border-slate-300 dark:border-gray-800 dark:bg-gray-900'
                "
            >
                <div>
                    <p
                        class="text-xs font-bold text-slate-500 dark:text-gray-400"
                    >
                        Open / Pending
                    </p>
                    <p
                        class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400"
                    >
                        {{ stats.open }}
                    </p>
                </div>
                <Clock class="h-6 w-6 text-amber-500/60" />
            </button>

            <button
                type="button"
                @click="
                    selectedStatus = 'in_progress';
                    applyFilters();
                "
                class="flex items-center justify-between rounded-xl border p-4 text-left transition-all"
                :class="
                    selectedStatus === 'in_progress'
                        ? 'border-blue-500 bg-blue-50/50 dark:border-blue-500/50 dark:bg-blue-500/10'
                        : 'border-slate-200/80 bg-white hover:border-slate-300 dark:border-gray-800 dark:bg-gray-900'
                "
            >
                <div>
                    <p
                        class="text-xs font-bold text-slate-500 dark:text-gray-400"
                    >
                        In Progress
                    </p>
                    <p
                        class="mt-1 text-2xl font-black text-blue-600 dark:text-blue-400"
                    >
                        {{ stats.in_progress }}
                    </p>
                </div>
                <HelpCircle class="h-6 w-6 text-blue-500/60" />
            </button>

            <button
                type="button"
                @click="
                    selectedStatus = 'resolved';
                    applyFilters();
                "
                class="flex items-center justify-between rounded-xl border p-4 text-left transition-all"
                :class="
                    selectedStatus === 'resolved'
                        ? 'border-emerald-500 bg-emerald-50/50 dark:border-emerald-500/50 dark:bg-emerald-500/10'
                        : 'border-slate-200/80 bg-white hover:border-slate-300 dark:border-gray-800 dark:bg-gray-900'
                "
            >
                <div>
                    <p
                        class="text-xs font-bold text-slate-500 dark:text-gray-400"
                    >
                        Resolved
                    </p>
                    <p
                        class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400"
                    >
                        {{ stats.resolved }}
                    </p>
                </div>
                <CheckCircle2 class="h-6 w-6 text-emerald-500/60" />
            </button>

            <button
                type="button"
                @click="
                    selectedStatus = 'closed';
                    applyFilters();
                "
                class="flex items-center justify-between rounded-xl border p-4 text-left transition-all"
                :class="
                    selectedStatus === 'closed'
                        ? 'border-slate-400 bg-slate-100 dark:border-gray-600 dark:bg-gray-800'
                        : 'border-slate-200/80 bg-white hover:border-slate-300 dark:border-gray-800 dark:bg-gray-900'
                "
            >
                <div>
                    <p
                        class="text-xs font-bold text-slate-500 dark:text-gray-400"
                    >
                        Closed
                    </p>
                    <p
                        class="mt-1 text-2xl font-black text-slate-700 dark:text-gray-300"
                    >
                        {{ stats.closed }}
                    </p>
                </div>
                <XCircle class="h-6 w-6 text-slate-400" />
            </button>
        </div>

        <!-- Ticket Table / List -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                v-if="tickets.data.length === 0"
                class="p-12 text-center text-sm text-slate-500 dark:text-gray-400"
            >
                <div
                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-gray-800 dark:text-gray-500"
                >
                    <LifeBuoy class="h-6 w-6" />
                </div>
                <p class="font-bold text-slate-800 dark:text-gray-200">
                    No tickets found
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    Try changing your search keywords or filter options.
                </p>
            </div>

            <div v-else class="divide-y divide-slate-100 dark:divide-gray-800">
                <div
                    v-for="ticket in tickets.data"
                    :key="ticket.id"
                    class="flex flex-col gap-4 p-5 transition-colors hover:bg-slate-50/70 sm:flex-row sm:items-center sm:justify-between dark:hover:bg-gray-800/50"
                >
                    <!-- Left: User & Ticket Info -->
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
                                class="rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300"
                            >
                                {{ getCategoryLabel(ticket.category) }}
                            </span>

                            <span
                                v-if="ticket.attachment_url"
                                class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                            >
                                <ImageIcon class="h-3 w-3" />
                                <span>Image</span>
                            </span>
                        </div>

                        <h4
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            {{ ticket.subject }}
                        </h4>

                        <!-- Student Metadata -->
                        <div
                            class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-gray-400"
                        >
                            <Link
                                v-if="ticket.user"
                                :href="`/u/${ticket.user.username}`"
                                class="flex items-center gap-1.5 font-medium hover:text-indigo-600 dark:hover:text-indigo-400"
                            >
                                <UserIcon class="h-3.5 w-3.5" />
                                <span
                                    >{{ ticket.user.name }} ({{
                                        ticket.user.email
                                    }})</span
                                >
                            </Link>
                            <span>&bull;</span>
                            <span
                                >Created:
                                {{ formatDate(ticket.created_at) }}</span
                            >
                        </div>
                    </div>

                    <!-- Right: Actions & Status -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Quick Status Select -->
                        <select
                            :value="ticket.status"
                            @change="
                                (e) =>
                                    updateStatus(
                                        ticket,
                                        (e.target as HTMLSelectElement).value,
                                    )
                            "
                            class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs focus:border-indigo-600 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        >
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>

                        <!-- Respond Button -->
                        <button
                            type="button"
                            @click="openReplyModal(ticket)"
                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold shadow-2xs transition-all active:scale-95"
                            :class="
                                ticket.admin_reply
                                    ? 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'
                                    : 'bg-indigo-600 text-white hover:bg-indigo-700'
                            "
                        >
                            <MessageSquare class="h-3.5 w-3.5" />
                            <span>{{
                                ticket.admin_reply ? 'Edit Reply' : 'Respond'
                            }}</span>
                        </button>

                        <!-- Delete Button -->
                        <button
                            type="button"
                            @click="deleteTicket(ticket)"
                            class="rounded-xl p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                            title="Delete Ticket"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination Links -->
            <div
                v-if="tickets.last_page > 1"
                class="flex items-center justify-between border-t border-slate-100 bg-slate-50/50 px-4 py-3 dark:border-gray-800 dark:bg-gray-900/60"
            >
                <div class="text-xs text-slate-500 dark:text-gray-400">
                    Showing page {{ tickets.current_page }} of
                    {{ tickets.last_page }}
                </div>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="(link, i) in tickets.links"
                        :key="i"
                        :href="link.url || '#'"
                        class="rounded-lg px-2.5 py-1 text-xs font-semibold transition-colors"
                        :class="
                            link.active
                                ? 'bg-indigo-600 text-white'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                        "
                    >
                        <span v-html="link.label"></span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Reply / Resolution Modal -->
        <div
            v-if="isModalOpen && activeTicket"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs"
        >
            <div
                class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-gray-800"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            {{ activeTicket.ticket_number }}
                        </span>
                        <h3
                            class="text-base font-bold text-slate-900 dark:text-gray-100"
                        >
                            {{ activeTicket.subject }}
                        </h3>
                    </div>
                    <button
                        @click="closeReplyModal"
                        class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-gray-800"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- User Query Details -->
                <div class="mt-4 space-y-4">
                    <div
                        class="rounded-xl border border-slate-200/60 bg-slate-50 p-4 dark:border-gray-800 dark:bg-gray-950/60"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <span
                                class="text-xs font-bold text-slate-700 dark:text-gray-300"
                            >
                                Submitter: {{ activeTicket.user?.name }} ({{
                                    activeTicket.user?.email
                                }})
                            </span>
                            <span class="text-[11px] text-slate-400">
                                {{ formatDate(activeTicket.created_at) }}
                            </span>
                        </div>
                        <p
                            class="text-xs leading-relaxed whitespace-pre-wrap text-slate-700 dark:text-gray-300"
                        >
                            {{ activeTicket.message }}
                        </p>

                        <!-- Attached Screenshot -->
                        <div v-if="activeTicket.attachment_url" class="mt-3">
                            <p
                                class="mb-1 text-[11px] font-bold text-slate-500"
                            >
                                Attachment:
                            </p>
                            <a
                                :href="activeTicket.attachment_url"
                                target="_blank"
                                class="inline-block overflow-hidden rounded-lg border border-slate-200 transition-opacity hover:opacity-90 dark:border-gray-700"
                            >
                                <img
                                    :src="activeTicket.attachment_url"
                                    alt="Ticket screenshot"
                                    class="max-h-48 rounded object-contain"
                                />
                            </a>
                        </div>
                    </div>

                    <!-- Reply Form -->
                    <form @submit.prevent="submitReply" class="space-y-4 pt-2">
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-bold tracking-wider text-slate-700 uppercase dark:text-gray-300"
                            >
                                Admin Response / Resolution *
                            </label>
                            <textarea
                                v-model="replyForm.admin_reply"
                                rows="5"
                                placeholder="Type your response to the student..."
                                required
                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100"
                            ></textarea>
                            <p
                                v-if="replyForm.errors.admin_reply"
                                class="mt-1 text-xs text-rose-500"
                            >
                                {{ replyForm.errors.admin_reply }}
                            </p>
                        </div>

                        <!-- Update Status Selector -->
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex items-center gap-2">
                                <label
                                    class="text-xs font-bold text-slate-700 dark:text-gray-300"
                                >
                                    Set Status to:
                                </label>
                                <select
                                    v-model="replyForm.status"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 focus:border-indigo-600 focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300"
                                >
                                    <option value="resolved">
                                        Resolved (Recommended)
                                    </option>
                                    <option value="in_progress">
                                        In Progress
                                    </option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    @click="closeReplyModal"
                                    class="rounded-xl px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="replyForm.processing"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    <Send class="h-3.5 w-3.5" />
                                    <span>{{
                                        replyForm.processing
                                            ? 'Saving...'
                                            : 'Send Response'
                                    }}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
