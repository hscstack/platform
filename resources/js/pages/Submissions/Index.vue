<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Clock,
    CheckCircle2,
    XCircle,
    FileText,
    File,
    Image as ImageIcon,
    Video,
    Trash2,
    ExternalLink,
    AlertCircle,
    Upload,
    Loader2,
} from 'lucide-vue-next';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import { formatDateTime } from '@/lib/useDate';

interface ChangeRequest {
    id: number;
    user_id: number;
    resource_id?: number | null;
    node_id: number;
    action_type: 'create' | 'update' | 'delete';
    status: 'pending' | 'approved' | 'rejected';
    payload?: {
        title?: string;
        resource_type?: string;
        content?: string;
        external_url?: string;
        file_path?: string;
    } | null;
    rejection_reason?: string | null;
    reviewed_at?: string | null;
    created_at: string;
    resource?: {
        id: number;
        title: string;
        resource_type: string;
    } | null;
    node?: {
        id: number;
        name: string;
        slug: string;
        breadcrumb?: { id: number; name: string; slug: string }[];
        subject?: { id: number; name: string; slug: string } | null;
    } | null;
}

defineProps<{
    submissions: {
        data: ChangeRequest[];
        links: any[];
        from?: number;
        to?: number;
        total?: number;
        current_page?: number;
        last_page?: number;
    };
    counts: {
        all: number;
        pending: number;
        approved: number;
        rejected: number;
        max_pending: number;
    };
    filters: {
        status?: string;
    };
}>();

const cancellingId = ref<number | null>(null);

const handleCancel = (submission: ChangeRequest) => {
    if (!confirm('আপনি কি নিশ্চিত যে এই সাবমিশনটি বাতিল করতে চান?')) {
        return;
    }

    cancellingId.value = submission.id;

    router.delete(`/my-submissions/${submission.id}`, {
        preserveScroll: true,
        onFinish: () => {
            cancellingId.value = null;
        },
    });
};

const getResourceIcon = (type?: string) => {
    switch (type) {
        case 'image':
            return ImageIcon;
        case 'pdf':
            return File;
        case 'video':
            return Video;
        case 'note':
        default:
            return FileText;
    }
};

const getResourceTypeLabel = (type?: string) => {
    switch (type) {
        case 'image':
            return 'Image';
        case 'pdf':
            return 'PDF';
        case 'video':
            return 'Video';
        case 'note':
            return 'Note';
        default:
            return 'Resource';
    }
};

const getBreadcrumbText = (submission: ChangeRequest) => {
    if (!submission.node) {
        return '';
    }

    const crumbs = submission.node.breadcrumb || [];

    if (crumbs.length === 0) {
        return submission.node.name;
    }

    return crumbs.map((c) => c.name).join(' / ');
};
</script>

<template>
    <Head>
        <title>My Submissions - HSCStack</title>
        <meta
            name="description"
            content="Track the review status of your shared educational notes and resources."
        />
    </Head>

    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-8">
        <!-- Page Header -->
        <div class="mb-6">
            <h1
                class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
            >
                My
                <span class="text-indigo-600 dark:text-indigo-400"
                    >Submissions</span
                >
            </h1>
            <p
                class="mt-1 text-xs font-medium text-slate-500 dark:text-gray-400"
            >
                আপনার আপলোডকৃত নোট, পিডিএফ ও কন্টেন্টের পর্যালোচনার বর্তমান
                অবস্থা
            </p>
        </div>

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
                            পর্যালোচনাধীন কোটা (Pending Quota)
                        </span>
                        <span
                            class="rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-bold text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400"
                        >
                            {{ counts.pending }} / {{ counts.max_pending }}
                        </span>
                    </div>
                    <p
                        class="mt-1 text-[11px] text-slate-500 dark:text-gray-400"
                    >
                        একবারে সর্বোচ্চ {{ counts.max_pending }}টি কন্টেন্ট
                        পর্যালোচনাধীন রাখতে পারবেন। মডারেটর অনুমোদন করলে কোটা
                        আবার খালি হবে।
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
                                counts.pending >= counts.max_pending
                                    ? 'bg-rose-500'
                                    : counts.pending > counts.max_pending * 0.7
                                      ? 'bg-amber-500'
                                      : 'bg-indigo-600'
                            "
                            :style="{
                                width: `${Math.min(100, (counts.pending / counts.max_pending) * 100)}%`,
                            }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div
            class="mb-5 flex flex-wrap items-center gap-1.5 border-b border-slate-200 pb-3 dark:border-gray-800"
        >
            <Link
                href="/my-submissions?status=all"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                :class="
                    filters.status === 'all' || !filters.status
                        ? 'bg-indigo-600 text-white shadow-2xs'
                        : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                "
            >
                <span>সবগুলো (All)</span>
                <span
                    class="py-0.2 rounded-full px-1.5 text-[10px]"
                    :class="
                        filters.status === 'all' || !filters.status
                            ? 'bg-white/20 text-white'
                            : 'bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-gray-300'
                    "
                >
                    {{ counts.all }}
                </span>
            </Link>

            <Link
                href="/my-submissions?status=pending"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                :class="
                    filters.status === 'pending'
                        ? 'bg-amber-500 text-white shadow-2xs'
                        : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                "
            >
                <Clock class="h-3.5 w-3.5" />
                <span>পর্যালোচনাধীন (Pending)</span>
                <span
                    class="py-0.2 rounded-full px-1.5 text-[10px]"
                    :class="
                        filters.status === 'pending'
                            ? 'bg-white/20 text-white'
                            : 'bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-gray-300'
                    "
                >
                    {{ counts.pending }}
                </span>
            </Link>

            <Link
                href="/my-submissions?status=approved"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                :class="
                    filters.status === 'approved'
                        ? 'bg-emerald-600 text-white shadow-2xs'
                        : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                "
            >
                <CheckCircle2 class="h-3.5 w-3.5" />
                <span>অনুমোদিত (Live)</span>
                <span
                    class="py-0.2 rounded-full px-1.5 text-[10px]"
                    :class="
                        filters.status === 'approved'
                            ? 'bg-white/20 text-white'
                            : 'bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-gray-300'
                    "
                >
                    {{ counts.approved }}
                </span>
            </Link>

            <Link
                href="/my-submissions?status=rejected"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                :class="
                    filters.status === 'rejected'
                        ? 'bg-rose-600 text-white shadow-2xs'
                        : 'text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800'
                "
            >
                <XCircle class="h-3.5 w-3.5" />
                <span>প্রত্যাখ্যাত (Rejected)</span>
                <span
                    class="py-0.2 rounded-full px-1.5 text-[10px]"
                    :class="
                        filters.status === 'rejected'
                            ? 'bg-white/20 text-white'
                            : 'bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-gray-300'
                    "
                >
                    {{ counts.rejected }}
                </span>
            </Link>
        </div>

        <!-- Submissions List -->
        <div
            v-if="submissions.data && submissions.data.length > 0"
            class="space-y-3"
        >
            <div
                v-for="sub in submissions.data"
                :key="sub.id"
                class="group rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs transition hover:border-slate-300 sm:p-5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <!-- Left Column: Icon + Details -->
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            :class="{
                                'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400':
                                    (sub.payload?.resource_type ||
                                        sub.resource?.resource_type) ===
                                    'image',
                                'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400':
                                    (sub.payload?.resource_type ||
                                        sub.resource?.resource_type) === 'pdf',
                                'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400':
                                    (sub.payload?.resource_type ||
                                        sub.resource?.resource_type) ===
                                    'video',
                                'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400':
                                    (sub.payload?.resource_type ||
                                        sub.resource?.resource_type) ===
                                        'note' ||
                                    !(
                                        sub.payload?.resource_type ||
                                        sub.resource?.resource_type
                                    ),
                            }"
                        >
                            <component
                                :is="
                                    getResourceIcon(
                                        sub.payload?.resource_type ||
                                            sub.resource?.resource_type,
                                    )
                                "
                                class="h-5 w-5"
                            />
                        </div>

                        <div class="min-w-0 flex-1">
                            <!-- Title & Action Badges -->
                            <div class="flex flex-wrap items-center gap-2">
                                <h3
                                    class="truncate text-sm font-bold text-slate-900 sm:text-base dark:text-gray-100"
                                >
                                    {{
                                        sub.payload?.title ||
                                        sub.resource?.title ||
                                        'Untitled Resource'
                                    }}
                                </h3>

                                <span
                                    class="rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase"
                                    :class="{
                                        'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300':
                                            sub.action_type === 'create',
                                        'bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300':
                                            sub.action_type === 'update',
                                        'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300':
                                            sub.action_type === 'delete',
                                    }"
                                >
                                    {{ sub.action_type }}
                                </span>

                                <span
                                    class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    {{
                                        getResourceTypeLabel(
                                            sub.payload?.resource_type ||
                                                sub.resource?.resource_type,
                                        )
                                    }}
                                </span>
                            </div>

                            <!-- Breadcrumbs / Target Folder -->
                            <p
                                v-if="getBreadcrumbText(sub)"
                                class="mt-1 flex items-center gap-1 truncate text-xs text-slate-500 dark:text-gray-400"
                            >
                                <span class="truncate">{{
                                    getBreadcrumbText(sub)
                                }}</span>
                            </p>

                            <!-- Submission Date -->
                            <p
                                class="mt-1 text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                জমা দেওয়া হয়েছে:
                                {{ formatDateTime(sub.created_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- Right Column: Status Badge & Actions -->
                    <div class="flex shrink-0 items-center gap-2">
                        <!-- Pending Badge -->
                        <span
                            v-if="sub.status === 'pending'"
                            class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300"
                        >
                            <Clock class="h-3.5 w-3.5" />
                            <span>পর্যালোচনাধীন</span>
                        </span>

                        <!-- Approved Badge -->
                        <span
                            v-else-if="sub.status === 'approved'"
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300"
                        >
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            <span>অনুমোদিত</span>
                        </span>

                        <!-- Rejected Badge -->
                        <span
                            v-else-if="sub.status === 'rejected'"
                            class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300"
                        >
                            <XCircle class="h-3.5 w-3.5" />
                            <span>প্রত্যাখ্যাত</span>
                        </span>

                        <!-- Live Link if approved -->
                        <Link
                            v-if="sub.status === 'approved' && sub.resource_id"
                            :href="`/resources/${sub.resource_id}`"
                            class="dark:hover:bg-gray-750 inline-flex cursor-pointer items-center gap-1 rounded-xl border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <span>দেখুন</span>
                            <ExternalLink class="h-3 w-3" />
                        </Link>

                        <!-- Cancel Button if pending -->
                        <button
                            v-if="sub.status === 'pending'"
                            type="button"
                            @click="handleCancel(sub)"
                            :disabled="cancellingId === sub.id"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-xl border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-700 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-rose-400 dark:hover:bg-rose-950/30"
                            title="সাবমিশনটি বাতিল করুন"
                        >
                            <Loader2
                                v-if="cancellingId === sub.id"
                                class="h-3.5 w-3.5 animate-spin"
                            />
                            <Trash2 v-else class="h-3.5 w-3.5" />
                            <span>বাতিল</span>
                        </button>
                    </div>
                </div>

                <!-- Rejection Notice Banner -->
                <div
                    v-if="sub.status === 'rejected'"
                    class="mt-3.5 rounded-xl border border-rose-100 bg-rose-50/70 p-3 text-xs leading-relaxed text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-200"
                >
                    <div class="flex items-start gap-2">
                        <AlertCircle
                            class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                        />
                        <div>
                            <span class="font-bold">প্রত্যাখ্যানের কারণ:</span>
                            {{
                                sub.rejection_reason ||
                                'কোনো নির্দিষ্ট কারণ উল্লেখ করা হয়নি।'
                            }}
                            <div
                                class="mt-1 text-[11px] text-rose-700/80 dark:text-rose-300/80"
                            >
                                অনুগ্রহ করে নির্দেশিকা মেনে পুনরায় সঠিক ফাইল বা
                                নোট সাবমিট করুন।
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination
                v-if="submissions.links && submissions.links.length > 3"
                :links="submissions.links"
                :from="submissions.from"
                :to="submissions.to"
                :total="submissions.total"
                :current-page="submissions.current_page"
                :last-page="submissions.last_page"
            />
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            variant="card"
            title="কোনো সাবমিশন পাওয়া যায়নি"
            :description="
                filters.status === 'pending'
                    ? 'আপনার বর্তমানে কোনো কন্টেন্ট পর্যালোচনাধীন নেই।'
                    : filters.status === 'approved'
                      ? 'এখনো কোনো কন্টেন্ট অনুমোদিত হয়নি।'
                      : filters.status === 'rejected'
                        ? 'কোনো প্রত্যাখ্যাত সাবমিশন নেই।'
                        : 'আপনি এখনো কোনো নোট বা রিসোর্স আপলোড করেননি।'
            "
        >
            <template #actions>
                <Link
                    href="/"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700"
                >
                    <Upload class="h-3.5 w-3.5" />
                    <span>কন্টেন্ট এক্সপ্লোর ও আপলোড করুন</span>
                </Link>
            </template>
        </EmptyState>
    </div>
</template>
