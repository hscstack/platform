<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    NotebookPen,
    Video,
    FlaskConical,
    FileText,
    BookOpen,
    ChevronRight,
    User as UserIcon,
    ArrowBigUp,
    Clock,
    AlertCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    resource: {
        id: number;
        title: string;
        resource_type: string;
        status?: 'pending' | 'approved' | 'rejected' | string;
        admin_note?: string | null;
        thumbnail?: string | null;
        items_count?: number;
        description?: string | null;
        vote_score?: number;
        upvotes_count?: number;
        downvotes_count?: number;
        created_at?: string;
        node?: {
            id: number;
            name: string;
            slug?: string;
            subject?: {
                id: number;
                name: string;
                slug?: string;
            } | null;
        } | null;
        user?: {
            id: number;
            name: string;
            username: string;
        } | null;
    };
    showStatus?: boolean;
}>();

const typeConfig = computed(() => {
    switch (props.resource.resource_type) {
        case 'video':
            return {
                icon: Video,
                label: 'ক্লাস',
                countLabel: `${props.resource.items_count || 0} ক্লাস`,
                iconBg: 'bg-rose-50 text-rose-600 border-rose-200/50 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20',
                badgeBg:
                    'bg-rose-50 text-rose-700 border-rose-200/60 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/50',
            };
        case 'practical':
            return {
                icon: FlaskConical,
                label: 'ব্যবহারিক',
                countLabel: `${props.resource.items_count || 0} আইটেম`,
                iconBg: 'bg-teal-50 text-teal-600 border-teal-200/50 dark:bg-teal-500/10 dark:text-teal-400 dark:border-teal-500/20',
                badgeBg:
                    'bg-teal-50 text-teal-700 border-teal-200/60 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-900/50',
            };
        case 'marked_book':
            return {
                icon: BookOpen,
                label: 'দাগানো বই',
                countLabel: `${props.resource.items_count || 0} পৃষ্ঠা`,
                iconBg: 'bg-emerald-50 text-emerald-600 border-emerald-200/50 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                badgeBg:
                    'bg-emerald-50 text-emerald-700 border-emerald-200/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/50',
            };
        case 'lecture_sheet':
            return {
                icon: FileText,
                label: 'লেকচার শিট',
                countLabel: `${props.resource.items_count || 0} ফাইল`,
                iconBg: 'bg-indigo-50 text-indigo-600 border-indigo-200/50 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20',
                badgeBg:
                    'bg-indigo-50 text-indigo-700 border-indigo-200/60 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-900/50',
            };
        case 'other':
            return {
                icon: FileText,
                label: 'লেকচার শিট',
                countLabel: `${props.resource.items_count || 0} ফাইল`,
                iconBg: 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
                badgeBg:
                    'bg-slate-100 text-slate-700 border-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            };
        case 'note':
        default:
            return {
                icon: NotebookPen,
                label: 'নোট',
                countLabel: `${props.resource.items_count || 0} আইটেম`,
                iconBg: 'bg-amber-50 text-amber-600 border-amber-200/50 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                badgeBg:
                    'bg-amber-50 text-amber-700 border-amber-200/60 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900/50',
            };
    }
});
</script>

<template>
    <Link
        :href="`/resources/${resource.id}`"
        class="group relative flex cursor-pointer touch-manipulation items-center justify-between bg-white px-5 py-4 transition-all duration-200 hover:bg-slate-50/60 active:scale-[0.995] sm:px-6 dark:bg-gray-900 dark:hover:bg-gray-800/50"
    >
        <div class="flex min-w-0 items-center gap-4">
            <!-- Resource Type Icon -->
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border transition-colors duration-200"
                :class="typeConfig.iconBg"
            >
                <component :is="typeConfig.icon" class="h-5 w-5 stroke-[2.2]" />
            </div>

            <!-- Title & Metadata -->
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="block text-sm font-bold text-slate-900 transition-colors group-hover:text-indigo-600 sm:text-base dark:text-gray-100 dark:group-hover:text-indigo-400"
                    >
                        {{ resource.title }}
                    </span>

                    <!-- Pending Badge -->
                    <span
                        v-if="resource.status === 'pending'"
                        class="inline-flex items-center gap-1 rounded-md border border-amber-200/80 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:border-amber-800/60 dark:bg-amber-950/50 dark:text-amber-400"
                    >
                        <Clock class="h-3 w-3 animate-pulse" />
                        <span>পর্যালোচনাধীন</span>
                    </span>

                    <!-- Rejected Badge -->
                    <span
                        v-else-if="resource.status === 'rejected'"
                        class="inline-flex items-center gap-1 rounded-md border border-rose-200/80 bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:border-rose-800/60 dark:bg-rose-950/50 dark:text-rose-400"
                    >
                        <AlertCircle class="h-3 w-3" />
                        <span>বাতিলকৃত</span>
                    </span>
                </div>

                <div
                    class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-slate-400 dark:text-gray-500"
                >
                    <!-- Type Badge (Mobile Only) -->
                    <span
                        class="rounded border px-1.5 py-0.5 text-[10px] font-bold sm:hidden"
                        :class="typeConfig.badgeBg"
                    >
                        {{ typeConfig.label }}
                    </span>

                    <!-- Item / Lecture Count -->
                    <span
                        class="font-semibold text-slate-600 dark:text-gray-300"
                    >
                        {{ typeConfig.countLabel }}
                    </span>

                    <!-- Author Info -->
                    <span v-if="resource.user" class="flex items-center gap-1">
                        <span>•</span>
                        <UserIcon class="h-3 w-3 stroke-[2]" />
                        <span>{{ resource.user.name }}</span>
                    </span>

                    <!-- Node / Subject Context -->
                    <span
                        v-if="resource.node"
                        class="flex items-center gap-1 text-slate-500 dark:text-gray-400"
                    >
                        <span>•</span>
                        <span class="max-w-[140px] truncate sm:max-w-[200px]">
                            {{
                                resource.node.subject?.name
                                    ? `${resource.node.subject.name} - `
                                    : ''
                            }}{{ resource.node.name }}
                        </span>
                    </span>
                </div>

                <!-- Rejection note if rejected and present -->
                <div
                    v-if="resource.status === 'rejected' && resource.admin_note"
                    class="mt-2 rounded-lg bg-rose-50 px-2.5 py-1 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-300"
                >
                    <span class="font-bold">বাতিলের কারণ:</span>
                    {{ resource.admin_note }}
                </div>
            </div>
        </div>

        <!-- Right Side: Type Badge, Vote Score Pill & Arrow -->
        <div class="flex shrink-0 items-center gap-2.5 pl-3 sm:gap-3">
            <!-- Full Type Badge (Desktop) -->
            <span
                class="hidden rounded-lg border px-2.5 py-1 text-xs font-bold sm:inline-block"
                :class="typeConfig.badgeBg"
            >
                {{ typeConfig.label }}
            </span>

            <!-- Read-Only Vote Score Pill (End of Row) -->
            <div
                class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-bold transition-colors"
                :class="
                    (resource.vote_score || 0) > 0
                        ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400'
                        : (resource.vote_score || 0) < 0
                          ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400'
                          : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'
                "
                :title="`ভোট স্কোর: ${resource.vote_score || 0}`"
            >
                <ArrowBigUp
                    class="h-3.5 w-3.5"
                    :class="
                        (resource.vote_score || 0) > 0 ? 'fill-current' : ''
                    "
                />
                <span class="tabular-nums">{{ resource.vote_score || 0 }}</span>
            </div>

            <ChevronRight
                class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"
            />
        </div>
    </Link>
</template>
