<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Flag,
    Settings,
    Lock,
    Unlock,
    EyeOff,
    Trash2,
    Search,
    ExternalLink,
    HelpCircle,
    Filter,
} from 'lucide-vue-next';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

interface ForumPostItem {
    id: number;
    title: string;
    slug: string;
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
    subjects: Array<{ id: number; name: string; course: string }>;
    filters: {
        curriculum?: string;
        subject_id?: string;
        status?: string;
        search?: string;
        sort?: string;
    };
    stats: {
        totalPosts: number;
        pendingCount: number;
        flaggedCount: number;
        rejectedCount: number;
        approvedCount: number;
        lockedCount: number;
        pendingReportsCount: number;
    };
}>();

const searchInput = ref(props.filters.search || '');
const selectedCurriculum = ref(props.filters.curriculum || '');
const selectedSubject = ref(props.filters.subject_id || '');
const selectedStatus = ref(props.filters.status || 'all');
const showAdvancedFilters = ref(
    Boolean(
        props.filters.curriculum ||
        props.filters.subject_id ||
        props.filters.sort,
    ),
);

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
            curriculum: selectedCurriculum.value || undefined,
            subject_id: selectedSubject.value || undefined,
            sort: props.filters.sort || undefined,
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

const formatDate = (isoString?: string | null) => {
    if (!isoString) {
return '';
}

    try {
        const d = new Date(isoString);

        return d.toLocaleDateString([], {
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return '';
    }
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
                class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white p-1 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
            >
                <Link
                    href="/admin/forums"
                    class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                >
                    Discussions
                </Link>

                <Link
                    href="/admin/forums/reports"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                >
                    <Flag class="h-3.5 w-3.5 text-rose-500" />
                    <span>Reports</span>
                    <span
                        v-if="stats.pendingReportsCount > 0"
                        class="py-0.2 rounded-full bg-rose-500 px-1.5 text-[10px] font-bold text-white"
                    >
                        {{ stats.pendingReportsCount }}
                    </span>
                </Link>

                <Link
                    href="/admin/forums/settings"
                    class="flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
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

                <button
                    type="button"
                    @click="showAdvancedFilters = !showAdvancedFilters"
                    class="inline-flex cursor-pointer items-center gap-1 rounded-xl border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                    :class="{
                        'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400':
                            showAdvancedFilters,
                    }"
                >
                    <Filter class="h-3 w-3" />
                    <span>Filters</span>
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

        <!-- Optional Collapsible Filter Bar (Curriculum & Subject) -->
        <div
            v-if="showAdvancedFilters"
            class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3 text-xs dark:border-gray-800 dark:bg-gray-900/60"
        >
            <div class="flex items-center gap-2">
                <span class="font-semibold text-slate-500">Curriculum:</span>
                <select
                    v-model="selectedCurriculum"
                    @change="applyFilters"
                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    <option value="">All</option>
                    <option value="hsc">HSC</option>
                    <option value="ssc">SSC</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <span class="font-semibold text-slate-500">Subject:</span>
                <select
                    v-model="selectedSubject"
                    @change="applyFilters"
                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    <option value="">All Subjects</option>
                    <option
                        v-for="sub in subjects"
                        :key="sub.id"
                        :value="sub.id"
                    >
                        {{ sub.name }} ({{ sub.course.toUpperCase() }})
                    </option>
                    <option value="other">Unassigned</option>
                </select>
            </div>
        </div>

        <!-- Uncluttered Table -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <div v-if="posts.data.length === 0" class="py-14 text-center">
                <HelpCircle class="mx-auto h-7 w-7 text-slate-400" />
                <p
                    class="mt-2 text-xs font-bold text-slate-700 dark:text-gray-300"
                >
                    No discussions match your filter
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
                            <th class="px-4 py-3">Discussion</th>
                            <th class="px-4 py-3">Author</th>
                            <th class="px-3 py-3 text-center">Replies</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
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
                            <!-- Title & Info -->
                            <td class="max-w-md px-4 py-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <Lock
                                            v-if="post.is_locked"
                                            class="h-3.5 w-3.5 shrink-0 text-slate-400"
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
                                    <div
                                        class="flex items-center gap-2 text-[11px] text-slate-400"
                                    >
                                        <span
                                            class="py-0.2 rounded bg-slate-100 px-1.5 font-semibold text-slate-600 uppercase dark:bg-gray-800 dark:text-gray-400"
                                        >
                                            {{ post.curriculum }}
                                        </span>
                                        <span>{{
                                            post.subject?.name || 'General'
                                        }}</span>
                                        <span>•</span>
                                        <span>{{
                                            formatDate(post.created_at)
                                        }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Author -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div v-if="post.user" class="text-xs">
                                    <span
                                        class="font-medium text-slate-800 dark:text-gray-200"
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

                            <!-- Replies -->
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <span
                                    class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    {{ post.answers_count }}
                                </span>
                            </td>

                            <!-- Moderation Status -->
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold"
                                    :class="[
                                        post.moderation_status === 'approved'
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                            : post.moderation_status ===
                                                'pending'
                                              ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
                                              : post.moderation_status ===
                                                  'flagged'
                                                ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                                                : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                                    ]"
                                >
                                    {{
                                        post.moderation_status === 'approved'
                                            ? 'Live'
                                            : post.moderation_status ===
                                                'pending'
                                              ? 'Pending'
                                              : post.moderation_status ===
                                                  'flagged'
                                                ? 'Flagged'
                                                : 'Rejected'
                                    }}
                                </span>
                            </td>

                            <!-- Quick Action Buttons -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <!-- Approve button -->
                                    <button
                                        v-if="
                                            post.moderation_status !==
                                            'approved'
                                        "
                                        type="button"
                                        @click="updateStatus(post, 'approved')"
                                        class="cursor-pointer rounded-lg bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300"
                                        title="Approve & Publish"
                                    >
                                        Approve
                                    </button>

                                    <!-- Hide / Reject button -->
                                    <button
                                        v-else
                                        type="button"
                                        @click="updateStatus(post, 'rejected')"
                                        class="cursor-pointer rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-rose-600 dark:hover:bg-gray-800"
                                        title="Hide from public"
                                    >
                                        <EyeOff class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- Lock button -->
                                    <button
                                        type="button"
                                        @click="toggleLock(post)"
                                        class="cursor-pointer rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-800 dark:hover:bg-gray-800"
                                        :title="
                                            post.is_locked ? 'Unlock' : 'Lock'
                                        "
                                    >
                                        <Unlock
                                            v-if="post.is_locked"
                                            class="h-3.5 w-3.5 text-emerald-600"
                                        />
                                        <Lock v-else class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- Delete button -->
                                    <button
                                        type="button"
                                        @click="deletePost(post)"
                                        class="cursor-pointer rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                                        title="Delete"
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
                class="flex items-center justify-between border-t border-slate-100 bg-slate-50/40 px-4 py-2.5 text-xs dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="text-slate-400">
                    Page {{ posts.current_page }} of {{ posts.last_page }}
                </div>

                <div class="flex items-center gap-1">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in posts.links"
                        :key="index"
                        :href="link.url"
                        class="rounded-lg px-2 py-1 font-medium transition"
                        :class="{
                            'bg-indigo-600 text-white': link.active,
                            'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800':
                                !link.active && link.url,
                            'cursor-not-allowed text-slate-300 dark:text-gray-600':
                                !link.url,
                        }"
                    >
                        <span v-html="link.label"></span>
                    </component>
                </div>
            </div>
        </div>
    </div>
</template>
