<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Flag,
    Settings,
    Lock,
    Unlock,
    Trash2,
    Search,
    ExternalLink,
    HelpCircle,
    Ban,
} from 'lucide-vue-next';
import { ref } from 'vue';
import ChatBanModal from '@/components/ChatBanModal.vue';
import type { ChatBanUser } from '@/components/ChatBanModal.vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';

interface ForumPostItem {
    id: number;
    title: string;
    slug: string;
    body?: string;
    curriculum: 'hsc' | 'ssc';
    is_answered: boolean;
    is_locked: boolean;
    moderation_status: 'approved' | 'pending' | 'flagged' | 'rejected';
    vote_score: number;
    answers_count: number;
    created_at: string;
    user?: {
        id: number;
        name: string;
        username: string;
    } | null;
    subject?: {
        id: number;
        name: string;
        course: string;
    } | null;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    posts: {
        data: ForumPostItem[];
        links: PaginationLinks[];
        total: number;
        current_page: number;
        last_page: number;
    };
    filters: {
        status?: string;
        search?: string;
    };
    stats: {
        totalPosts: number;
        pendingCount: number;
        flaggedCount: number;
        rejectedCount: number;
        approvedCount: number;
    };
    pendingReportsCount?: number;
}>();

const searchInput = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'all');

const setStatusFilter = (status: string) => {
    selectedStatus.value = status;
    applyFilters();
};

const applyFilters = () => {
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

const handleSearch = () => {
    applyFilters();
};

const toggleLock = (post: ForumPostItem) => {
    router.patch(`/admin/forums/${post.id}/lock`, {}, { preserveScroll: true });
};

const updateStatus = (
    post: ForumPostItem,
    status: 'approved' | 'pending' | 'flagged' | 'rejected',
) => {
    router.patch(
        `/admin/forums/${post.id}/status`,
        { moderation_status: status },
        { preserveScroll: true },
    );
};

const deletePost = (post: ForumPostItem) => {
    if (confirm(`Delete "${post.title}" permanently? This cannot be undone.`)) {
        router.delete(`/admin/forums/${post.id}`, {
            preserveScroll: true,
        });
    }
};

const isBanModalOpen = ref(false);
const selectedUser = ref<ChatBanUser | null>(null);

const openBanModal = (post: ForumPostItem) => {
    if (!post.user) {
        return;
    }

    selectedUser.value = {
        id: post.user.id,
        name: post.user.name,
        username: post.user.username,
        banned_until: (post.user as any).banned_until || null,
        is_banned: (post.user as any).is_banned ?? false,
    };

    isBanModalOpen.value = true;
};
</script>

<template>
    <Head title="Forum Management - Admin" />

    <div class="space-y-5">
        <!-- Minimal Top Header -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl dark:text-gray-100"
                >
                    Forum Management
                </h1>
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    Review community questions, moderate discussions, and manage
                    reports.
                </p>
            </div>

            <!-- Header Quick Tabs -->
            <div
                class="flex max-w-full items-center gap-1.5 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <Link
                    href="/admin/forums"
                    class="shrink-0 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                >
                    Discussions
                </Link>

                <Link
                    href="/admin/forums/reports"
                    class="flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                >
                    <Flag class="h-3.5 w-3.5 text-rose-500" />
                    <span>Reports</span>
                    <span
                        v-if="pendingReportsCount && pendingReportsCount > 0"
                        class="py-0.2 rounded-full bg-rose-500 px-1.5 text-[10px] font-bold text-white"
                    >
                        {{ pendingReportsCount }}
                    </span>
                </Link>

                <Link
                    href="/admin/forums/settings"
                    class="flex shrink-0 items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                >
                    <Settings class="h-3.5 w-3.5" />
                    <span>Settings</span>
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
                    @click="setStatusFilter('all')"
                    class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        selectedStatus === 'all' || !selectedStatus
                            ? 'bg-slate-900 text-white dark:bg-gray-100 dark:text-gray-900'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
                    ]"
                >
                    All ({{ stats.totalPosts }})
                </button>

                <button
                    type="button"
                    @click="setStatusFilter('pending')"
                    class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        selectedStatus === 'pending'
                            ? 'bg-amber-600 text-white'
                            : 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300',
                    ]"
                >
                    Pending ({{ stats.pendingCount }})
                </button>

                <button
                    type="button"
                    @click="setStatusFilter('flagged')"
                    class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        selectedStatus === 'flagged'
                            ? 'bg-rose-600 text-white'
                            : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300',
                    ]"
                >
                    Flagged ({{ stats.flaggedCount }})
                </button>

                <button
                    type="button"
                    @click="setStatusFilter('rejected')"
                    class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        selectedStatus === 'rejected'
                            ? 'bg-red-600 text-white'
                            : 'bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300',
                    ]"
                >
                    Rejected ({{ stats.rejectedCount }})
                </button>

                <button
                    type="button"
                    @click="setStatusFilter('approved')"
                    class="cursor-pointer rounded-xl px-3 py-1.5 text-xs font-bold transition"
                    :class="[
                        selectedStatus === 'approved'
                            ? 'bg-emerald-600 text-white'
                            : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300',
                    ]"
                >
                    Live ({{ stats.approvedCount }})
                </button>
            </div>

            <!-- Search Field -->
            <div class="relative w-full sm:w-64">
                <Search
                    class="pointer-events-none absolute top-2 left-2.5 h-3.5 w-3.5 text-slate-400"
                />
                <input
                    v-model="searchInput"
                    type="text"
                    placeholder="Search questions..."
                    @keyup.enter="handleSearch"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-1.5 pr-3 pl-8 text-xs text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                />
            </div>
        </div>

        <!-- Clean Moderation Table -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <EmptyState
                v-if="posts.data.length === 0"
                :icon="HelpCircle"
                title="No discussions match your filter"
                description="Try changing your search keywords or filter status."
            />

            <div v-else class="overflow-x-auto">
                <table
                    class="w-full text-left text-xs text-slate-700 dark:text-gray-300"
                >
                    <thead
                        class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-500 uppercase dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400"
                    >
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap">
                                Discussion
                            </th>
                            <th class="px-4 py-3 whitespace-nowrap">Author</th>
                            <th class="px-4 py-3 whitespace-nowrap">
                                Moderation Status
                            </th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-100 dark:divide-gray-800"
                    >
                        <tr
                            v-for="post in posts.data"
                            :key="post.id"
                            class="transition hover:bg-slate-50/60 dark:hover:bg-gray-800/40"
                        >
                            <!-- Title & Snippet -->
                            <td class="max-w-xl min-w-[220px] px-4 py-3.5">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <Lock
                                            v-if="post.is_locked"
                                            class="h-3.5 w-3.5 shrink-0 text-amber-500"
                                            title="Discussion locked"
                                        />
                                        <a
                                            :href="`/forum/questions/${post.slug}`"
                                            target="_blank"
                                            class="font-semibold text-slate-900 hover:text-indigo-600 hover:underline dark:text-gray-100 dark:hover:text-indigo-400"
                                        >
                                            {{ post.title }}
                                        </a>
                                        <ExternalLink
                                            class="h-3 w-3 shrink-0 text-slate-400"
                                        />
                                    </div>

                                    <!-- Content snippet -->
                                    <p
                                        v-if="post.body"
                                        class="line-clamp-1 text-[11px] text-slate-500 dark:text-gray-400"
                                    >
                                        {{ post.body }}
                                    </p>
                                </div>
                            </td>

                            <!-- Author -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div v-if="post.user" class="text-xs">
                                    <span
                                        class="font-semibold text-slate-800 dark:text-gray-200"
                                        >{{ post.user.name }}</span
                                    >
                                    <span
                                        class="block text-[10px] text-slate-400"
                                        >@{{ post.user.username }}</span
                                    >
                                </div>
                                <span v-else class="text-slate-400"
                                    >Anonymous</span
                                >
                            </td>

                            <!-- Inline Moderation Status Dropdown -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <select
                                    :value="post.moderation_status"
                                    @change="
                                        (e) =>
                                            updateStatus(
                                                post,
                                                (e.target as HTMLSelectElement)
                                                    .value as any,
                                            )
                                    "
                                    class="cursor-pointer rounded-xl border px-2.5 py-1 text-xs font-bold transition outline-none"
                                    :class="[
                                        post.moderation_status === 'approved'
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300'
                                            : post.moderation_status ===
                                                'pending'
                                              ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300'
                                              : post.moderation_status ===
                                                  'flagged'
                                                ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300'
                                                : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300',
                                    ]"
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

                            <!-- Actions -->
                            <td
                                class="px-4 py-3.5 text-right whitespace-nowrap"
                            >
                                <div
                                    class="flex items-center justify-end gap-1.5"
                                >
                                    <!-- Suspend Author button -->
                                    <button
                                        v-if="post.user"
                                        type="button"
                                        @click="openBanModal(post)"
                                        class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                        :title="`Moderate / Suspend @${post.user.username}`"
                                    >
                                        <Ban class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- Lock button -->
                                    <button
                                        type="button"
                                        @click="toggleLock(post)"
                                        class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-800 dark:hover:bg-gray-800"
                                        :title="
                                            post.is_locked
                                                ? 'Unlock discussion replies'
                                                : 'Lock discussion replies'
                                        "
                                    >
                                        <Unlock
                                            v-if="post.is_locked"
                                            class="h-3.5 w-3.5 text-amber-500"
                                        />
                                        <Lock v-else class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- Delete button -->
                                    <button
                                        type="button"
                                        @click="deletePost(post)"
                                        class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                                        title="Delete discussion"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="posts.links && posts.links.length > 3"
                class="border-t border-slate-100 bg-slate-50/40 px-4 py-1 dark:border-gray-800 dark:bg-gray-900"
            >
                <Pagination
                    :links="posts.links"
                    :current-page="posts.current_page"
                    :last-page="posts.last_page"
                />
            </div>
        </div>

        <!-- Suspension Modal -->
        <ChatBanModal
            :is-open="isBanModalOpen"
            :user="selectedUser"
            @close="isBanModalOpen = false"
        />
    </div>
</template>
