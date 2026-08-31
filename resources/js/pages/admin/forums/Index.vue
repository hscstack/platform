<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    MessageSquareText,
    Flag,
    Settings,
    Lock,
    Unlock,
    Eye,
    EyeOff,
    Trash2,
    Search,
    ExternalLink,
    CheckCircle2,
    HelpCircle,
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
    is_published: boolean;
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
    node?: {
        id: number;
        name: string;
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
        unpublishedCount: number;
        lockedCount: number;
        pendingReportsCount: number;
    };
}>();

const searchInput = ref(props.filters.search || '');
const selectedCurriculum = ref(props.filters.curriculum || '');
const selectedSubject = ref(props.filters.subject_id || '');
const selectedStatus = ref(props.filters.status || '');
const selectedSort = ref(props.filters.sort || 'recent');

const applyFilters = () => {
    router.get(
        '/admin/forums',
        {
            search: searchInput.value || undefined,
            curriculum: selectedCurriculum.value || undefined,
            subject_id: selectedSubject.value || undefined,
            status: selectedStatus.value || undefined,
            sort:
                selectedSort.value !== 'recent'
                    ? selectedSort.value
                    : undefined,
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

const togglePublish = (post: ForumPostItem) => {
    router.patch(
        `/admin/forums/${post.id}/publish`,
        {},
        { preserveScroll: true },
    );
};

const deletePost = (post: ForumPostItem) => {
    if (
        confirm(
            `Are you sure you want to delete discussion: "${post.title}"? This cannot be undone.`,
        )
    ) {
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
            year: 'numeric',
        });
    } catch {
        return '';
    }
};
</script>

<template>
    <Head title="Forum Discussions - Admin Panel" />

    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                >
                    <MessageSquareText class="h-5 w-5" />
                </div>
                <div>
                    <h1
                        class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                    >
                        Forum Discussions Management
                    </h1>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Moderate community discussions, lock replies, and manage
                        visibility.
                    </p>
                </div>
            </div>
        </div>

        <!-- Navigation Sub-Tabs -->
        <div
            class="flex items-center gap-2 border-b border-slate-200 dark:border-gray-800"
        >
            <Link
                href="/admin/forums"
                class="flex items-center gap-2 border-b-2 border-indigo-600 px-4 py-2.5 text-xs font-bold text-indigo-600 transition-all dark:border-indigo-400 dark:text-indigo-400"
            >
                <MessageSquareText class="h-4 w-4" />
                <span>All Discussions</span>
            </Link>

            <Link
                href="/admin/forums/reports"
                class="flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-xs font-bold text-slate-500 transition-all hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <Flag class="h-4 w-4 text-rose-500" />
                <span>Reported Content</span>
                <span
                    v-if="stats.pendingReportsCount > 0"
                    class="py-0.2 rounded-full bg-rose-500 px-1.5 text-[10px] font-bold text-white"
                >
                    {{ stats.pendingReportsCount }}
                </span>
            </Link>

            <Link
                href="/admin/forums/settings"
                class="flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-xs font-bold text-slate-500 transition-all hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <Settings class="h-4 w-4" />
                <span>Forum Settings</span>
            </Link>
        </div>

        <!-- Metrics Stats -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div
                class="rounded-2xl border border-slate-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold text-slate-500 dark:text-gray-400"
                        >Total Discussions</span
                    >
                    <MessageSquareText class="h-4 w-4 text-indigo-500" />
                </div>
                <p
                    class="mt-2 text-2xl font-bold text-slate-900 dark:text-gray-100"
                >
                    {{ stats.totalPosts }}
                </p>
                <span class="text-[11px] text-slate-400 dark:text-gray-500"
                    >Across all curriculums</span
                >
            </div>

            <div
                class="rounded-2xl border border-slate-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold text-slate-500 dark:text-gray-400"
                        >Unpublished / Hidden</span
                    >
                    <EyeOff class="h-4 w-4 text-amber-500" />
                </div>
                <p
                    class="mt-2 text-2xl font-bold text-slate-900 dark:text-gray-100"
                >
                    {{ stats.unpublishedCount }}
                </p>
                <span class="text-[11px] text-slate-400 dark:text-gray-500"
                    >Hidden from public view</span
                >
            </div>

            <div
                class="rounded-2xl border border-slate-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold text-slate-500 dark:text-gray-400"
                        >Locked Threads</span
                    >
                    <Lock class="h-4 w-4 text-slate-500" />
                </div>
                <p
                    class="mt-2 text-2xl font-bold text-slate-900 dark:text-gray-100"
                >
                    {{ stats.lockedCount }}
                </p>
                <span class="text-[11px] text-slate-400 dark:text-gray-500"
                    >New replies disabled</span
                >
            </div>

            <div
                class="rounded-2xl border border-slate-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold text-slate-500 dark:text-gray-400"
                        >Pending Reports</span
                    >
                    <Flag class="h-4 w-4 text-rose-500" />
                </div>
                <p
                    class="mt-2 text-2xl font-bold text-slate-900 dark:text-gray-100"
                >
                    {{ stats.pendingReportsCount }}
                </p>
                <span class="text-[11px] text-slate-400 dark:text-gray-500"
                    >Awaiting review</span
                >
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div
            class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-900/60"
        >
            <div class="flex flex-1 flex-wrap items-center gap-2.5">
                <!-- Search Input -->
                <div class="relative min-w-[200px] flex-1 sm:max-w-xs">
                    <Search
                        class="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-slate-400 dark:text-gray-500"
                    />
                    <input
                        v-model="searchInput"
                        type="text"
                        placeholder="Search title, content, author..."
                        @keyup.enter="handleSearch"
                        class="w-full rounded-xl border border-slate-200 bg-white py-1.5 pr-3 pl-9 text-xs text-slate-900 shadow-2xs transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                    />
                </div>

                <!-- Curriculum Select -->
                <select
                    v-model="selectedCurriculum"
                    @change="applyFilters"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    <option value="">All Curriculums</option>
                    <option value="hsc">HSC</option>
                    <option value="ssc">SSC</option>
                </select>

                <!-- Subject Select -->
                <select
                    v-model="selectedSubject"
                    @change="applyFilters"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    <option value="">All Subjects</option>
                    <option
                        v-for="sub in subjects"
                        :key="sub.id"
                        :value="sub.id"
                    >
                        {{ sub.name }} ({{ sub.course.toUpperCase() }})
                    </option>
                    <option value="other">Unassigned / Other</option>
                </select>

                <!-- Status Filter -->
                <select
                    v-model="selectedStatus"
                    @change="applyFilters"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    <option value="">All Statuses</option>
                    <option value="published">Published Only</option>
                    <option value="unpublished">Unpublished / Hidden</option>
                    <option value="locked">Locked Only</option>
                    <option value="unlocked">Unlocked Only</option>
                    <option value="answered">Answered</option>
                    <option value="unanswered">Unanswered</option>
                </select>
            </div>

            <!-- Sort Select -->
            <div class="flex items-center gap-2">
                <select
                    v-model="selectedSort"
                    @change="applyFilters"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    <option value="recent">Sort: Newest First</option>
                    <option value="answers">Sort: Most Answers</option>
                    <option value="votes">Sort: Top Voted</option>
                </select>
            </div>
        </div>

        <!-- Discussions Data Table -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <div v-if="posts.data.length === 0" class="py-16 text-center">
                <HelpCircle
                    class="mx-auto h-8 w-8 text-slate-400 dark:text-gray-500"
                />
                <h3
                    class="mt-2 text-sm font-bold text-slate-800 dark:text-gray-200"
                >
                    No discussions found
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                    Try adjusting your search criteria or filter options.
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table
                    class="w-full text-left text-xs text-slate-700 dark:text-gray-300"
                >
                    <thead
                        class="border-b border-slate-200 bg-slate-50/70 text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400"
                    >
                        <tr>
                            <th class="px-4 py-3 sm:px-6">
                                Discussion / Title
                            </th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Author</th>
                            <th class="px-4 py-3 text-center">Answers</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right sm:px-6">
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
                            class="transition-colors hover:bg-slate-50/80 dark:hover:bg-gray-800/40"
                        >
                            <!-- Title & Slug -->
                            <td class="px-4 py-3.5 sm:px-6">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <a
                                            :href="`/forum/questions/${post.slug}`"
                                            target="_blank"
                                            class="font-bold text-slate-900 hover:text-indigo-600 hover:underline dark:text-gray-100 dark:hover:text-indigo-400"
                                        >
                                            {{ post.title }}
                                        </a>
                                        <ExternalLink
                                            class="h-3 w-3 text-slate-400"
                                        />
                                    </div>
                                    <div
                                        class="flex items-center gap-2 text-[10px] text-slate-400 dark:text-gray-500"
                                    >
                                        <span
                                            >Posted
                                            {{
                                                formatDate(post.created_at)
                                            }}</span
                                        >
                                        <span>•</span>
                                        <span>{{ post.vote_score }} votes</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Subject / Curriculum -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="flex flex-col gap-0.5">
                                    <span
                                        class="inline-flex w-fit items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 uppercase dark:bg-indigo-950/60 dark:text-indigo-300"
                                    >
                                        {{ post.curriculum }}
                                    </span>
                                    <span
                                        class="text-xs font-medium text-slate-600 dark:text-gray-400"
                                    >
                                        {{
                                            post.subject?.name ||
                                            'General / Other'
                                        }}
                                    </span>
                                </div>
                            </td>

                            <!-- Author -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div v-if="post.user" class="flex flex-col">
                                    <span
                                        class="font-semibold text-slate-900 dark:text-gray-100"
                                    >
                                        {{ post.user.name }}
                                    </span>
                                    <span class="text-[10px] text-slate-400">
                                        @{{ post.user.username }}
                                    </span>
                                </div>
                                <span v-else class="text-slate-400"
                                    >Anonymous</span
                                >
                            </td>

                            <!-- Answers -->
                            <td
                                class="px-4 py-3.5 text-center whitespace-nowrap"
                            >
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                    :class="
                                        post.answers_count > 0
                                            ? 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-300'
                                            : 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300'
                                    "
                                >
                                    {{ post.answers_count }}
                                </span>
                            </td>

                            <!-- Status Badges -->
                            <td
                                class="px-4 py-3.5 text-center whitespace-nowrap"
                            >
                                <div
                                    class="flex flex-wrap items-center justify-center gap-1.5"
                                >
                                    <!-- Published badge -->
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase"
                                        :class="
                                            post.is_published
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                                        "
                                    >
                                        {{
                                            post.is_published
                                                ? 'Published'
                                                : 'Hidden'
                                        }}
                                    </span>

                                    <!-- Locked badge -->
                                    <span
                                        v-if="post.is_locked"
                                        class="inline-flex items-center rounded-md bg-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-700 dark:bg-gray-800 dark:text-gray-300"
                                    >
                                        <Lock class="mr-1 h-2.5 w-2.5" /> Locked
                                    </span>

                                    <!-- Answered badge -->
                                    <span
                                        v-if="post.is_answered"
                                        class="inline-flex items-center rounded-md bg-teal-50 px-1.5 py-0.5 text-[10px] font-bold text-teal-700 dark:bg-teal-950/60 dark:text-teal-300"
                                    >
                                        <CheckCircle2
                                            class="mr-1 h-2.5 w-2.5"
                                        />
                                        Solved
                                    </span>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td
                                class="px-4 py-3.5 text-right whitespace-nowrap sm:px-6"
                            >
                                <div
                                    class="flex items-center justify-end gap-1.5"
                                >
                                    <!-- Toggle Lock -->
                                    <button
                                        type="button"
                                        @click="toggleLock(post)"
                                        class="inline-flex cursor-pointer items-center rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                                        :title="
                                            post.is_locked
                                                ? 'Unlock discussion replies'
                                                : 'Lock discussion replies'
                                        "
                                    >
                                        <Unlock
                                            v-if="post.is_locked"
                                            class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                                        />
                                        <Lock
                                            v-else
                                            class="h-4 w-4 text-slate-400"
                                        />
                                    </button>

                                    <!-- Toggle Publish -->
                                    <button
                                        type="button"
                                        @click="togglePublish(post)"
                                        class="inline-flex cursor-pointer items-center rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                                        :title="
                                            post.is_published
                                                ? 'Unpublish / Hide discussion'
                                                : 'Publish / Restore discussion'
                                        "
                                    >
                                        <EyeOff
                                            v-if="post.is_published"
                                            class="h-4 w-4 text-slate-400"
                                        />
                                        <Eye
                                            v-else
                                            class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                                        />
                                    </button>

                                    <!-- Delete -->
                                    <button
                                        type="button"
                                        @click="deletePost(post)"
                                        class="inline-flex cursor-pointer items-center rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                        title="Delete discussion"
                                    >
                                        <Trash2 class="h-4 w-4" />
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
                class="flex items-center justify-between border-t border-slate-200 bg-slate-50/50 px-4 py-3 sm:px-6 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="text-xs text-slate-500 dark:text-gray-400">
                    Showing page {{ posts.current_page }} of
                    {{ posts.last_page }} ({{ posts.total }} total)
                </div>

                <div class="flex items-center gap-1">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in posts.links"
                        :key="index"
                        :href="link.url"
                        class="rounded-lg px-2.5 py-1 text-xs font-medium transition"
                        :class="{
                            'bg-indigo-600 text-white': link.active,
                            'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300':
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
