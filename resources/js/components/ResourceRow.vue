<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    NotebookPen,
    Video,
    FlaskConical,
    FileText,
    ChevronRight,
    User as UserIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    resource: {
        id: number;
        title: string;
        resource_type: string;
        items_count?: number;
        description?: string | null;
        user?: {
            id: number;
            name: string;
            username: string;
        } | null;
    };
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
        case 'other':
            return {
                icon: FileText,
                label: 'অন্যান্য',
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
                </div>
            </div>
        </div>

        <!-- Right Side Badges & Arrow -->
        <div class="flex shrink-0 items-center gap-3 pl-3">
            <!-- Full Type Badge (Desktop) -->
            <span
                class="hidden rounded-lg border px-2.5 py-1 text-xs font-bold sm:inline-block"
                :class="typeConfig.badgeBg"
            >
                {{ typeConfig.label }}
            </span>

            <ChevronRight
                class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"
            />
        </div>
    </Link>
</template>
