import { Head, Link, router } from '@inertiajs/vue3';
import {
    Ban,
    ExternalLink,
    Flag,
    HelpCircle,
    Lock,
    Search,
    Settings,
    Trash2,
    Unlock,
} from 'lucide-vue-next';
import { defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

import ChatBanModal from '@/components/ChatBanModal.vue';
import type { ChatBanUser } from '@/components/ChatBanModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginationLink } from '@/components/Pagination.vue';

interface ForumPostUser {
    id: number;
    name: string;
    username: string;
    banned_until?: string | null;
    is_banned?: boolean;
}

type ModerationStatus = 'approved' | 'pending' | 'flagged' | 'rejected';
type StatusFilter = 'all' | ModerationStatus;

interface ForumPostItem {
    id: number;
    title: string;
    slug: string;
    body?: string;
    curriculum: 'hsc' | 'ssc';
    is_answered: boolean;
    is_locked: boolean;
    moderation_status: ModerationStatus;
    vote_score: number;
    answers_count: number;
    created_at: string;
    user?: ForumPostUser | null;
    subject?: {
        id: number;
        name: string;
        course: string;
    } | null;
}

interface ForumsIndexPosts {
    data: ForumPostItem[];
    links: PaginationLink[];
    total: number;
    current_page: number;
    last_page: number;
}

interface ForumsIndexFilters {
    status?: string;
    search?: string;
}

interface ForumsIndexStats {
    totalPosts: number;
    pendingCount: number;
    flaggedCount: number;
    rejectedCount: number;
    approvedCount: number;
}

export default defineComponent({
    name: 'AdminForumsIndex',
    props: {
        posts: { type: Object as PropType<ForumsIndexPosts>, required: true },
        filters: {
            type: Object as PropType<ForumsIndexFilters>,
            required: true,
        },
        stats: { type: Object as PropType<ForumsIndexStats>, required: true },
        pendingReportsCount: {
            type: Number as PropType<number>,
            default: undefined,
        },
    },
    setup(props) {
        const searchInput = ref<string>(props.filters.search || '');
        const selectedStatus = ref<string>(props.filters.status || 'all');

        const setStatusFilter = (status: StatusFilter | string): void => {
            selectedStatus.value = status;
            applyFilters();
        };

        const applyFilters = (): void => {
            router.get(
                '/admin/forums',
                {
                    search: searchInput.value || undefined,
                    status:
                        selectedStatus.value === 'all'
                            ? undefined
                            : selectedStatus.value,
                },
                { preserveState: true, replace: true },
            );
        };

        const handleSearch = (): void => {
            applyFilters();
        };

        const onSearchInput = (e: Event) => {
            searchInput.value = (e.target as HTMLInputElement).value;
        };

        const onSearchKeyup = (e: KeyboardEvent) => {
            if (e.key === 'Enter') {
                handleSearch();
            }
        };

        const toggleLock = (post: ForumPostItem): void => {
            router.patch(
                `/admin/forums/${post.id}/lock`,
                {},
                { preserveScroll: true },
            );
        };

        const updateStatus = (
            post: ForumPostItem,
            status: ModerationStatus,
        ): void => {
            router.patch(
                `/admin/forums/${post.id}/status`,
                { moderation_status: status },
                { preserveScroll: true },
            );
        };

        const deletePost = (post: ForumPostItem): void => {
            if (
                confirm(
                    `Delete "${post.title}" permanently? This cannot be undone.`,
                )
            ) {
                router.delete(`/admin/forums/${post.id}`, {
                    preserveScroll: true,
                });
            }
        };

        const handleStatusChange = (
            post: ForumPostItem,
            event: Event,
        ): void => {
            const target = event.target as HTMLSelectElement | null;

            if (!target) {
                return;
            }

            updateStatus(post, target.value as ModerationStatus);
        };

        const isBanModalOpen = ref<boolean>(false);
        const selectedUser = ref<ChatBanUser | null>(null);

        const openBanModal = (post: ForumPostItem): void => {
            if (!post.user) {
                return;
            }

            selectedUser.value = {
                id: post.user.id,
                name: post.user.name,
                username: post.user.username,
                banned_until: post.user.banned_until || null,
                is_banned: post.user.is_banned ?? false,
            };

            isBanModalOpen.value = true;
        };

        const onStatusSelectChange = (post: ForumPostItem) => (e: Event) => {
            handleStatusChange(post, e);
        };

        const statusTabs: {
            value: StatusFilter;
            label: string;
            count: number;
        }[] = [
            { value: 'all', label: 'All', count: props.stats.totalPosts },
            {
                value: 'pending',
                label: 'Pending',
                count: props.stats.pendingCount,
            },
            {
                value: 'flagged',
                label: 'Flagged',
                count: props.stats.flaggedCount,
            },
            {
                value: 'rejected',
                label: 'Rejected',
                count: props.stats.rejectedCount,
            },
            {
                value: 'approved',
                label: 'Live',
                count: props.stats.approvedCount,
            },
        ];

        return () => (
            <>
                <Head title="Forum Management - Admin" />

                <div class="space-y-6">
                    {/* Page header */}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                    Forum Management
                                </h1>
                                {props.stats.pendingCount > 0 && (
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                        {props.stats.pendingCount} pending
                                    </span>
                                )}
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                Review community questions, moderate
                                discussions, and manage reports.
                            </p>
                        </div>

                        {/* Slim text tabs with active underline */}
                        <nav class="flex max-w-full items-center gap-1 overflow-x-auto">
                            <Link
                                href="/admin/forums"
                                class="shrink-0 cursor-pointer px-2.5 py-1.5 text-xs font-bold text-indigo-600 underline decoration-2 underline-offset-4 transition focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-indigo-400"
                            >
                                Discussions
                            </Link>
                            <Link
                                href="/admin/forums/reports"
                                class="flex shrink-0 cursor-pointer items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:text-slate-800 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-400 dark:hover:text-gray-200"
                            >
                                <Flag class="h-3.5 w-3.5 text-rose-500" />
                                <span>Reports</span>
                                {props.pendingReportsCount &&
                                    props.pendingReportsCount > 0 && (
                                        <span class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] leading-none font-bold text-white">
                                            {props.pendingReportsCount}
                                        </span>
                                    )}
                            </Link>
                            <Link
                                href="/admin/forums/settings"
                                class="flex shrink-0 cursor-pointer items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:text-slate-800 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-400 dark:hover:text-gray-200"
                            >
                                <Settings class="h-3.5 w-3.5" />
                                <span>Settings</span>
                            </Link>
                        </nav>
                    </div>

                    {/* Status segmented control + search */}
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="inline-flex max-w-full flex-wrap items-center gap-0.5 self-start rounded-xl bg-slate-100 p-1 dark:bg-gray-800">
                            {statusTabs.map((tab) => (
                                <button
                                    key={tab.value}
                                    type="button"
                                    onClick={() => setStatusFilter(tab.value)}
                                    class={[
                                        'cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95',
                                        selectedStatus.value === tab.value ||
                                        (!selectedStatus.value &&
                                            tab.value === 'all')
                                            ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-gray-100'
                                            : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
                                    ]}
                                >
                                    {tab.label} ({tab.count})
                                </button>
                            ))}
                        </div>

                        <div class="relative w-full lg:w-64">
                            <Search class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
                            <input
                                value={searchInput.value}
                                onInput={onSearchInput}
                                type="text"
                                placeholder="Search questions..."
                                onKeyup={onSearchKeyup}
                                class="h-9 w-full rounded-xl border border-slate-200 bg-white pr-3 pl-8 text-xs text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400"
                            />
                        </div>
                    </div>

                    {/* Moderation table */}
                    <div>
                        {props.posts.data.length === 0 ? (
                            <EmptyState
                                icon={HelpCircle}
                                title="No discussions match your filter"
                                description="Try changing your search keywords or filter status."
                            />
                        ) : (
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs text-slate-700 dark:text-gray-300">
                                    <thead class="sticky top-0 z-10 border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-500 uppercase dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400">
                                        <tr>
                                            <th class="px-3 py-2.5 whitespace-nowrap">
                                                Discussion
                                            </th>
                                            <th class="hidden px-3 py-2.5 whitespace-nowrap sm:table-cell">
                                                Author
                                            </th>
                                            <th class="px-3 py-2.5 whitespace-nowrap">
                                                Moderation Status
                                            </th>
                                            <th class="px-3 py-2.5 text-right whitespace-nowrap">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                                        {props.posts.data.map((post) => (
                                            <tr
                                                key={post.id}
                                                class="transition hover:bg-slate-50/60 dark:hover:bg-gray-800/40"
                                            >
                                                <td class="max-w-md min-w-[180px] px-3 py-2.5">
                                                    <div class="min-w-0 space-y-0.5">
                                                        <div class="flex min-w-0 items-center gap-1.5">
                                                            {post.is_locked && (
                                                                <Lock
                                                                    class="h-3.5 w-3.5 shrink-0 text-amber-500"
                                                                    title="Discussion locked"
                                                                />
                                                            )}
                                                            <a
                                                                href={`/forum/questions/${post.slug}`}
                                                                target="_blank"
                                                                class="min-w-0 truncate font-semibold text-slate-900 hover:text-indigo-600 hover:underline dark:text-gray-100 dark:hover:text-indigo-400"
                                                            >
                                                                {post.title}
                                                            </a>
                                                            <ExternalLink class="h-3 w-3 shrink-0 text-slate-400 dark:text-gray-500" />
                                                        </div>
                                                        {post.body && (
                                                            <p class="line-clamp-1 text-[11px] text-slate-500 dark:text-gray-400">
                                                                {post.body}
                                                            </p>
                                                        )}
                                                        <p class="text-[11px] text-slate-500 sm:hidden dark:text-gray-400">
                                                            {post.user
                                                                ? `${post.user.name} (@${post.user.username})`
                                                                : 'Anonymous'}
                                                        </p>
                                                    </div>
                                                </td>

                                                <td class="hidden max-w-[160px] px-3 py-2.5 whitespace-nowrap sm:table-cell">
                                                    {post.user ? (
                                                        <div class="max-w-[160px] truncate text-xs">
                                                            <span class="block truncate font-semibold text-slate-800 dark:text-gray-200">
                                                                {post.user.name}
                                                            </span>
                                                            <span class="block truncate text-[10px] text-slate-500 dark:text-gray-400">
                                                                @
                                                                {
                                                                    post.user
                                                                        .username
                                                                }
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <span class="text-slate-500 dark:text-gray-400">
                                                            Anonymous
                                                        </span>
                                                    )}
                                                </td>

                                                <td class="px-3 py-2.5 whitespace-nowrap">
                                                    <select
                                                        value={
                                                            post.moderation_status
                                                        }
                                                        onChange={onStatusSelectChange(
                                                            post,
                                                        )}
                                                        class={[
                                                            'h-8 cursor-pointer rounded-xl border px-2.5 text-xs font-bold transition outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 active:scale-95 dark:focus:border-indigo-400',
                                                            post.moderation_status ===
                                                            'approved'
                                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300'
                                                                : post.moderation_status ===
                                                                    'pending'
                                                                  ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300'
                                                                  : post.moderation_status ===
                                                                      'flagged'
                                                                    ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300'
                                                                    : 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300',
                                                        ]}
                                                    >
                                                        <option value="approved">
                                                            Approved (Live)
                                                        </option>
                                                        <option value="pending">
                                                            Pending Review
                                                        </option>
                                                        <option value="flagged">
                                                            Flagged (Reports)
                                                        </option>
                                                        <option value="rejected">
                                                            Rejected (Hidden)
                                                        </option>
                                                    </select>
                                                </td>

                                                <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                                    <div class="flex items-center justify-end gap-0.5">
                                                        {post.user && (
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    openBanModal(
                                                                        post,
                                                                    )
                                                                }
                                                                class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                                                title={`Moderate / Suspend @${post.user.username}`}
                                                                aria-label={`Moderate / Suspend @${post.user.username}`}
                                                            >
                                                                <Ban class="h-3.5 w-3.5" />
                                                            </button>
                                                        )}
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                toggleLock(post)
                                                            }
                                                            class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                                                            title={
                                                                post.is_locked
                                                                    ? 'Unlock discussion replies'
                                                                    : 'Lock discussion replies'
                                                            }
                                                            aria-label={
                                                                post.is_locked
                                                                    ? 'Unlock discussion replies'
                                                                    : 'Lock discussion replies'
                                                            }
                                                        >
                                                            {post.is_locked ? (
                                                                <Unlock class="h-3.5 w-3.5 text-amber-500" />
                                                            ) : (
                                                                <Lock class="h-3.5 w-3.5" />
                                                            )}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                deletePost(post)
                                                            }
                                                            class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                                            title="Delete discussion"
                                                            aria-label="Delete discussion"
                                                        >
                                                            <Trash2 class="h-3.5 w-3.5" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {props.posts.links && props.posts.links.length > 3 && (
                            <div class="border-t border-slate-100 pt-4 dark:border-gray-800">
                                <Pagination
                                    links={props.posts.links}
                                    currentPage={props.posts.current_page}
                                    lastPage={props.posts.last_page}
                                />
                            </div>
                        )}
                    </div>

                    <ChatBanModal
                        isOpen={isBanModalOpen.value}
                        user={selectedUser.value}
                        onClose={() => {
                            isBanModalOpen.value = false;
                        }}
                    />
                </div>
            </>
        );
    },
});
