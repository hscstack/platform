import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    LifeBuoy,
    MessageSquare,
    Search,
    Send,
    Trash2,
    Image as ImageIcon,
} from 'lucide-vue-next';
import { defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

import BaseModal from '@/components/BaseModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatDateTime } from '@/lib/useDate';

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

interface PaginationLinkItem {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedTickets {
    data: Ticket[];
    links: PaginationLinkItem[];
    total: number;
    current_page: number;
    last_page: number;
}

interface TicketStats {
    total: number;
    open: number;
    in_progress: number;
    resolved: number;
    closed: number;
}

interface TicketFilters {
    status?: string | null;
    category?: string | null;
    search?: string | null;
}

export default defineComponent({
    name: 'AdminTickets',
    props: {
        tickets: {
            type: Object as PropType<PaginatedTickets>,
            required: true,
        },
        stats: { type: Object as PropType<TicketStats>, required: true },
        filters: { type: Object as PropType<TicketFilters>, required: true },
        categories: {
            type: Object as PropType<Record<string, string>>,
            required: true,
        },
    },
    setup(props) {
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
            replyForm.status =
                ticket.status === 'open' ? 'resolved' : ticket.status;
            isModalOpen.value = true;
        };

        const closeReplyModal = () => {
            isModalOpen.value = false;
            activeTicket.value = null;
            replyForm.reset();
        };

        /** Spread onto BaseModal: avoids the ambiguous JSX
         *  `v-model:isOpen` spelling and stays type-safe. */
        const bindReplyOpen = (): {
            isOpen: boolean;
            'onUpdate:isOpen': (value: boolean) => void;
        } => ({
            isOpen: isModalOpen.value && activeTicket.value !== null,
            'onUpdate:isOpen': (value: boolean) => {
                if (!value) {
                    closeReplyModal();
                } else {
                    isModalOpen.value = value;
                }
            },
        });

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

        const setStatusFilter = (status: string) => {
            selectedStatus.value = status;
            applyFilters();
        };

        const getCategoryLabel = (key: string) => {
            return props.categories[key] || key;
        };

        const formatDate = formatDateTime;

        const onSearchInput = (e: Event) => {
            searchTerm.value = (e.target as HTMLInputElement).value;
        };

        const onSearchKeyup = (e: KeyboardEvent) => {
            if (e.key === 'Enter') {
                applyFilters();
            }
        };

        const onReplyInput = (e: Event) => {
            replyForm.admin_reply = (e.target as HTMLTextAreaElement).value;
        };

        const onReplyStatusChange = (e: Event) => {
            replyForm.status = (e.target as HTMLSelectElement).value;
        };

        const onReplySubmit = (e: Event) => {
            e.preventDefault();
            submitReply();
        };

        const tabClass = (isActive: boolean): string =>
            isActive
                ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-gray-100'
                : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200';

        return () => {
            const active = activeTicket.value;

            return (
                <>
                    <Head title="Support Tickets - Admin" />

                    <div class="space-y-6">
                        {/* Page header */}
                        <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                        Support Tickets
                                    </h1>
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                    Reply to student requests and track
                                    resolution status.
                                </p>
                            </div>
                            <div class="relative w-full sm:w-64">
                                <Search class="absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                <input
                                    value={searchTerm.value}
                                    onInput={onSearchInput}
                                    onKeyup={onSearchKeyup}
                                    type="text"
                                    placeholder="Search tickets, student, subject..."
                                    class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 pr-3 pl-9 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none sm:w-64 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400"
                                />
                            </div>
                        </div>

                        {/* Status filter tabs */}
                        <div class="inline-flex items-center gap-0.5 rounded-xl bg-slate-100 p-1 dark:bg-gray-800">
                            <button
                                type="button"
                                onClick={() => setStatusFilter('all')}
                                class={`cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all active:scale-95 ${tabClass(selectedStatus.value === 'all')}`}
                            >
                                All ({props.stats.total})
                            </button>
                            <button
                                type="button"
                                onClick={() => setStatusFilter('open')}
                                class={`cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all active:scale-95 ${tabClass(selectedStatus.value === 'open')}`}
                            >
                                Open ({props.stats.open})
                            </button>
                            <button
                                type="button"
                                onClick={() => setStatusFilter('in_progress')}
                                class={`cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all active:scale-95 ${tabClass(selectedStatus.value === 'in_progress')}`}
                            >
                                In Progress ({props.stats.in_progress})
                            </button>
                            <button
                                type="button"
                                onClick={() => setStatusFilter('resolved')}
                                class={`cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all active:scale-95 ${tabClass(selectedStatus.value === 'resolved')}`}
                            >
                                Resolved ({props.stats.resolved})
                            </button>
                            <button
                                type="button"
                                onClick={() => setStatusFilter('closed')}
                                class={`cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all active:scale-95 ${tabClass(selectedStatus.value === 'closed')}`}
                            >
                                Closed ({props.stats.closed})
                            </button>
                        </div>

                        {/* Ticket list */}
                        <div>
                            {props.tickets.data.length === 0 ? (
                                <EmptyState
                                    icon={LifeBuoy}
                                    title="No tickets found"
                                    description="Try changing your search keywords or filter options."
                                />
                            ) : (
                                <div class="divide-y divide-slate-100 dark:divide-gray-800">
                                    {props.tickets.data.map((ticket) => (
                                        <div
                                            key={ticket.id}
                                            class="flex flex-col gap-2 py-3.5 sm:flex-row sm:items-start sm:justify-between"
                                        >
                                            <div class="min-w-0 flex-1 space-y-1">
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <span class="font-mono text-[11px] font-bold text-slate-500 dark:text-gray-400">
                                                        {ticket.ticket_number}
                                                    </span>
                                                    <StatusBadge
                                                        status={ticket.status}
                                                        size="xs"
                                                        showIcon={false}
                                                    />
                                                    <span class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400">
                                                        {getCategoryLabel(
                                                            ticket.category,
                                                        )}
                                                    </span>
                                                    {ticket.attachment_url ? (
                                                        <span class="inline-flex items-center gap-0.5 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                                                            <ImageIcon class="h-3 w-3" />
                                                            <span>
                                                                Attachment
                                                            </span>
                                                        </span>
                                                    ) : null}
                                                </div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-gray-100">
                                                    {ticket.subject}
                                                </p>
                                                <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                                    {ticket.user ? (
                                                        <Link
                                                            href={`/u/${ticket.user.username}`}
                                                            class="font-medium text-slate-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                                        >
                                                            {ticket.user.name} (
                                                            {ticket.user.email})
                                                        </Link>
                                                    ) : (
                                                        <span>
                                                            Unknown user
                                                        </span>
                                                    )}
                                                    <span>
                                                        {' '}
                                                        · Created{' '}
                                                        {formatDate(
                                                            ticket.created_at,
                                                        )}
                                                    </span>
                                                    {ticket.admin_reply ? (
                                                        <span> · Replied</span>
                                                    ) : null}
                                                </p>
                                            </div>
                                            <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                                                <select
                                                    value={ticket.status}
                                                    onChange={(e: Event) =>
                                                        updateStatus(
                                                            ticket,
                                                            (
                                                                e.target as HTMLSelectElement
                                                            ).value,
                                                        )
                                                    }
                                                    title="Update ticket status"
                                                    class="w-32 cursor-pointer rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-medium text-slate-600 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-400"
                                                >
                                                    <option value="open">
                                                        Open
                                                    </option>
                                                    <option value="in_progress">
                                                        In Progress
                                                    </option>
                                                    <option value="resolved">
                                                        Resolved
                                                    </option>
                                                    <option value="closed">
                                                        Closed
                                                    </option>
                                                </select>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        openReplyModal(ticket)
                                                    }
                                                    class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                                >
                                                    <MessageSquare class="h-3.5 w-3.5" />
                                                    <span>
                                                        {ticket.admin_reply
                                                            ? 'Edit Reply'
                                                            : 'Respond'}
                                                    </span>
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        deleteTicket(ticket)
                                                    }
                                                    title="Delete ticket"
                                                    aria-label="Delete ticket"
                                                    class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                            {props.tickets.links &&
                            props.tickets.links.length > 3 ? (
                                <div class="mt-6 border-t border-slate-100 pt-4 dark:border-gray-800">
                                    <Pagination
                                        links={props.tickets.links}
                                        currentPage={props.tickets.current_page}
                                        lastPage={props.tickets.last_page}
                                    />
                                </div>
                            ) : null}
                        </div>

                        {/* Reply / resolution modal */}
                        <BaseModal
                            {...bindReplyOpen()}
                            maxWidth="2xl"
                            position="center"
                            onClose={closeReplyModal}
                        >
                            {{
                                header: () =>
                                    active ? (
                                        <div class="flex min-w-0 items-center gap-2">
                                            <span class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-bold text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                                                {active.ticket_number}
                                            </span>
                                            <h3 class="truncate text-sm font-bold text-slate-900 dark:text-gray-100">
                                                {active.subject}
                                            </h3>
                                        </div>
                                    ) : null,
                                default: () =>
                                    active ? (
                                        <div class="space-y-4 px-4 py-4 sm:px-6">
                                            <div class="border-l-2 border-slate-200 pl-3 dark:border-gray-700">
                                                <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                                    {active.user?.name} (
                                                    {active.user?.email}) ·{' '}
                                                    {formatDate(
                                                        active.created_at,
                                                    )}
                                                </p>
                                                <p class="mt-1 text-xs leading-relaxed whitespace-pre-wrap text-slate-700 dark:text-gray-300">
                                                    {active.message}
                                                </p>
                                                {active.attachment_url ? (
                                                    <a
                                                        href={
                                                            active.attachment_url
                                                        }
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                                    >
                                                        <ImageIcon class="h-3 w-3" />
                                                        <span>
                                                            View attachment
                                                        </span>
                                                    </a>
                                                ) : null}
                                            </div>
                                            <form
                                                id="ticket-reply-form"
                                                onSubmit={onReplySubmit}
                                                class="space-y-3"
                                            >
                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300">
                                                        Your response
                                                    </label>
                                                    <textarea
                                                        value={
                                                            replyForm.admin_reply
                                                        }
                                                        onInput={onReplyInput}
                                                        rows={6}
                                                        placeholder="Type your response to the user here..."
                                                        required
                                                        class="min-h-28 w-full rounded-lg border border-slate-200 bg-white p-3 text-sm leading-relaxed text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-indigo-400"
                                                    />
                                                    {replyForm.errors
                                                        .admin_reply ? (
                                                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                                            {
                                                                replyForm.errors
                                                                    .admin_reply
                                                            }
                                                        </p>
                                                    ) : null}
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <label class="text-xs font-semibold text-slate-700 dark:text-gray-300">
                                                        Update status:
                                                    </label>
                                                    <select
                                                        value={replyForm.status}
                                                        onChange={
                                                            onReplyStatusChange
                                                        }
                                                        class="cursor-pointer rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:focus:border-indigo-400"
                                                    >
                                                        <option value="resolved">
                                                            Resolved
                                                            (Recommended)
                                                        </option>
                                                        <option value="in_progress">
                                                            In Progress
                                                        </option>
                                                        <option value="closed">
                                                            Closed
                                                        </option>
                                                    </select>
                                                </div>
                                            </form>
                                        </div>
                                    ) : null,
                                footer: () =>
                                    active ? (
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                type="button"
                                                onClick={closeReplyModal}
                                                class="cursor-pointer rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                            >
                                                Cancel
                                            </button>
                                            <button
                                                type="button"
                                                onClick={submitReply}
                                                disabled={replyForm.processing}
                                                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                            >
                                                <Send class="h-3.5 w-3.5" />
                                                <span>
                                                    {replyForm.processing
                                                        ? 'Saving...'
                                                        : 'Send response'}
                                                </span>
                                            </button>
                                        </div>
                                    ) : null,
                            }}
                        </BaseModal>
                    </div>
                </>
            );
        };
    },
});
