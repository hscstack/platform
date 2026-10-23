<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Clock,
    CheckCircle2,
    XCircle,
    FileText,
    Image as ImageIcon,
    Video,
    File,
    FlaskConical,
    BookOpen,
    ExternalLink,
} from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';

interface Submission {
    id: number;
    resource_type: string;
    title: string;
    description?: string | null;
    status: 'pending' | 'approved' | 'rejected';
    admin_note?: string | null;
    created_at: string;
    reviewed_at?: string | null;
    node?: {
        id: number;
        name: string;
        slug: string;
        subject?: {
            id: number;
            name: string;
            slug: string;
        } | null;
        breadcrumb?: Array<{
            name: string;
            slug?: string;
        }>;
    } | null;
}

interface PaginationData<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

defineProps<{
    submissions: PaginationData<Submission>;
    quota: {
        used: number;
        max: number;
        is_verified: boolean;
    };
    counts: {
        all: number;
        pending: number;
        approved: number;
        rejected: number;
    };
    currentStatus: string;
}>();

const tabs = [
    { key: 'all', label: 'সবগুলো' },
    { key: 'pending', label: 'পর্যালোচনাধীন' },
    { key: 'approved', label: 'অনুমোদিত' },
    { key: 'rejected', label: 'বাতিলকৃত' },
] as const;

const formatDate = (dateString: string) => {
    try {
        const date = new Date(dateString);

        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    } catch {
        return dateString;
    }
};

const getResourceIcon = (type?: string) => {
    switch (type) {
        case 'practical':
            return FlaskConical;
        case 'marked_book':
            return BookOpen;
        case 'video':
            return Video;
        case 'pdf':
            return File;
        case 'image':
            return ImageIcon;
        default:
            return FileText;
    }
};

const getNodePath = (node?: Submission['node']): string[] => {
    if (!node) {
        return [];
    }

    const segments: string[] = [];

    if (node.subject?.name) {
        segments.push(node.subject.name);
    }

    if (node.breadcrumb && node.breadcrumb.length > 0) {
        for (const crumb of node.breadcrumb) {
            segments.push(crumb.name);
        }
    } else if (node.name) {
        segments.push(node.name);
    }

    if (segments.length > 2) {
        return [
            '...',
            segments[segments.length - 2],
            segments[segments.length - 1],
        ];
    }

    return segments;
};
</script>

<template>
    <Head title="My Submissions - HSCStack" />

    <main
        class="mx-auto max-w-5xl px-3.5 py-4 font-sans text-slate-700 selection:bg-indigo-50 sm:px-6 sm:py-8 lg:px-8 dark:text-gray-300 dark:selection:bg-indigo-500/30"
    >
        <!-- Header -->
        <header
            class="mb-3.5 flex items-center justify-between gap-3 sm:mb-6 sm:border-b sm:border-slate-100 sm:pb-5 dark:sm:border-gray-800"
        >
            <div>
                <h1
                    class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                >
                    My
                    <span class="text-indigo-600 dark:text-indigo-400"
                        >Submissions</span
                    >
                </h1>
                <p
                    class="text-xs text-slate-500 sm:mt-1 sm:text-sm dark:text-gray-400"
                >
                    আপনার আপলোডকৃত কন্টেন্ট, মডারেশন স্ট্যাটাস ও কোটা ট্র্যাকার
                </p>
            </div>
        </header>

        <!-- Quota Usage Card -->
        <div
            class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:p-5 dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs font-bold text-slate-700 dark:text-gray-300"
                        >
                            পর্যালোচনাধীন কোটা
                        </span>
                        <span
                            class="rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-bold text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400"
                        >
                            {{ quota.used }} / {{ quota.max }}
                        </span>
                    </div>
                    <p
                        class="mt-1 text-[11px] text-slate-500 dark:text-gray-400"
                    >
                        বর্তমানে পর্যালোচনাধীন কন্টেন্ট সংখ্যা কোটায় যুক্ত হয়।
                        অনুমোদিত বা বাতিল হলে কোটা খালি হয়ে যায়। ভেরিফাইড
                        মেম্বারদের কোটা বেশি।
                    </p>
                </div>

                <!-- Progress Bar -->
                <div class="w-full sm:w-48">
                    <div
                        class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-gray-800"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="
                                quota.used >= quota.max
                                    ? 'bg-rose-500'
                                    : quota.used > quota.max * 0.7
                                      ? 'bg-amber-500'
                                      : 'bg-indigo-600'
                            "
                            :style="{
                                width: `${Math.min(100, (quota.used / quota.max) * 100)}%`,
                            }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Filter Tabs -->
        <div
            class="mb-6 flex flex-wrap items-center gap-2 border-b border-slate-200/80 pb-4 dark:border-gray-800"
        >
            <Link
                v-for="tab in tabs"
                :key="tab.key"
                :href="
                    tab.key === 'all'
                        ? '/my-submissions'
                        : `/my-submissions?status=${tab.key}`
                "
                preserve-scroll
                class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition-all"
                :class="[
                    currentStatus === tab.key
                        ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400'
                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800/80 dark:hover:text-gray-200',
                ]"
            >
                <span>{{ tab.label }}</span>
                <span
                    class="rounded-lg px-1.5 py-0.5 text-[10px] font-bold"
                    :class="[
                        currentStatus === tab.key
                            ? 'bg-indigo-200/60 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300'
                            : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400',
                    ]"
                >
                    {{ counts[tab.key] }}
                </span>
            </Link>
        </div>

        <!-- Submissions List -->
        <div v-if="submissions.data.length > 0" class="space-y-3">
            <Link
                v-for="item in submissions.data"
                :key="item.id"
                :href="`/resources/${item.id}`"
                class="group block rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs transition hover:border-indigo-300 hover:shadow-xs dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-700/60"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="flex items-start gap-3">
                        <!-- Icon -->
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-600 transition group-hover:bg-indigo-50 group-hover:text-indigo-600 dark:bg-gray-800 dark:text-gray-300 dark:group-hover:bg-indigo-950/60 dark:group-hover:text-indigo-400"
                        >
                            <component
                                :is="getResourceIcon(item.resource_type)"
                                class="h-5 w-5"
                            />
                        </div>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3
                                    class="text-sm font-bold text-slate-900 transition group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                                >
                                    {{ item.title }}
                                </h3>

                                <span
                                    class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700 uppercase dark:bg-gray-800 dark:text-gray-300"
                                >
                                    {{ item.resource_type }}
                                </span>
                            </div>

                            <div
                                class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500 dark:text-gray-400"
                            >
                                <span
                                    v-if="getNodePath(item.node).length > 0"
                                    class="inline-flex flex-wrap items-center gap-1 font-medium text-slate-600 dark:text-gray-300"
                                >
                                    <span class="text-slate-400">in</span>
                                    <span
                                        class="inline-flex flex-wrap items-center gap-1"
                                    >
                                        <template
                                            v-for="(seg, sIdx) in getNodePath(
                                                item.node,
                                            )"
                                            :key="sIdx"
                                        >
                                            <span
                                                v-if="sIdx > 0"
                                                class="text-slate-400 dark:text-gray-600"
                                                >/</span
                                            >
                                            <span>{{ seg }}</span>
                                        </template>
                                    </span>
                                </span>

                                <span v-if="getNodePath(item.node).length > 0"
                                    >·</span
                                >
                                <span>{{ formatDate(item.created_at) }}</span>
                            </div>

                            <!-- Rejection Reason if any -->
                            <div
                                v-if="
                                    item.status === 'rejected' &&
                                    item.admin_note
                                "
                                class="mt-2 rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-300"
                            >
                                <span class="font-bold">বাতিলের কারণ:</span>
                                {{ item.admin_note }}
                            </div>
                        </div>
                    </div>

                    <!-- Status Pill & Action Hint -->
                    <div
                        class="flex items-center gap-2 self-start sm:self-auto"
                    >
                        <!-- Pending -->
                        <template v-if="item.status === 'pending'">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-950/60 dark:text-amber-400"
                            >
                                <Clock class="h-3.5 w-3.5" />
                                পর্যালোচনাধীন
                            </span>
                        </template>

                        <!-- Approved -->
                        <template v-else-if="item.status === 'approved'">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400"
                            >
                                <CheckCircle2 class="h-3.5 w-3.5" />
                                অনুমোদিত
                            </span>
                        </template>

                        <!-- Rejected -->
                        <template v-else-if="item.status === 'rejected'">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 dark:bg-rose-950/60 dark:text-rose-400"
                            >
                                <XCircle class="h-3.5 w-3.5" />
                                বাতিলকৃত
                            </span>
                        </template>

                        <ExternalLink
                            class="h-4 w-4 text-slate-400 opacity-0 transition group-hover:opacity-100 dark:text-gray-500"
                        />
                    </div>
                </div>
            </Link>

            <!-- Pagination -->
            <div class="pt-4">
                <Pagination
                    :links="submissions.links"
                    :from="submissions.from"
                    :to="submissions.to"
                    :total="submissions.total"
                />
            </div>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            title="কোনো সাবমিশন পাওয়া যায়নি"
            description="আপনার কোনো কন্টেন্ট সাবমিশন এই তালিকায় নেই।"
        />
    </main>
</template>
