<script setup lang="ts">
import { CheckCircle2, Clock, HelpCircle, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';

type BadgeVariant =
    | 'amber'
    | 'blue'
    | 'emerald'
    | 'rose'
    | 'purple'
    | 'indigo'
    | 'slate';

type BadgeSize = 'xs' | 'sm' | 'md';

interface PresetConfig {
    variant: BadgeVariant;
    label: string;
    icon?: Component;
}

const PRESETS: Record<string, PresetConfig> = {
    // Tickets
    open: { variant: 'amber', label: 'Open', icon: Clock },
    in_progress: { variant: 'blue', label: 'In Progress', icon: HelpCircle },
    resolved: { variant: 'emerald', label: 'Resolved', icon: CheckCircle2 },
    closed: { variant: 'slate', label: 'Closed', icon: XCircle },

    // Roles
    admin: { variant: 'rose', label: 'Admin' },
    editor: { variant: 'purple', label: 'Editor' },
    manager: { variant: 'amber', label: 'Staff' },
    staff: { variant: 'amber', label: 'Staff' },

    // Blogs
    published: { variant: 'emerald', label: 'Published' },
    draft: { variant: 'slate', label: 'Draft' },
    featured: { variant: 'amber', label: 'Featured' },

    // Curriculum
    hsc: { variant: 'indigo', label: 'HSC' },
    ssc: { variant: 'amber', label: 'SSC' },
};

const VARIANT_CLASSES: Record<BadgeVariant, string> = {
    amber: 'border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-400',
    blue: 'border-blue-500/20 bg-blue-500/10 text-blue-600 dark:text-blue-400',
    emerald:
        'border-emerald-500/20 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    rose: 'border-rose-500/20 bg-rose-500/10 text-rose-600 dark:text-rose-400',
    purple: 'border-purple-500/20 bg-purple-500/10 text-purple-600 dark:text-purple-400',
    indigo: 'border-indigo-500/20 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
    slate: 'border-slate-500/20 bg-slate-500/10 text-slate-600 dark:text-gray-400',
};

const SIZE_CLASSES: Record<BadgeSize, { badge: string; icon: string }> = {
    xs: {
        badge: 'px-1.5 py-0.5 text-[10px] font-bold',
        icon: 'h-3 w-3',
    },
    sm: {
        badge: 'px-2 py-0.5 text-[11px] font-bold',
        icon: 'h-3 w-3',
    },
    md: {
        badge: 'px-2.5 py-1 text-xs font-semibold',
        icon: 'h-3.5 w-3.5',
    },
};

const props = withDefaults(
    defineProps<{
        status?: string | null;
        variant?: BadgeVariant;
        label?: string | null;
        icon?: Component | null;
        size?: BadgeSize;
        showIcon?: boolean;
    }>(),
    {
        status: null,
        variant: undefined,
        label: null,
        icon: undefined,
        size: 'sm',
        showIcon: true,
    },
);

const normalizedKey = computed(() => props.status?.toLowerCase().trim() || '');
const preset = computed(() => PRESETS[normalizedKey.value]);

const resolvedVariant = computed<BadgeVariant>(() => {
    if (props.variant) {
        return props.variant;
    }

    return preset.value?.variant || 'slate';
});

const resolvedLabel = computed(() => {
    if (props.label) {
        return props.label;
    }

    if (preset.value?.label) {
        return preset.value.label;
    }

    return props.status || '';
});

const resolvedIcon = computed(() => {
    if (!props.showIcon) {
        return null;
    }

    if (props.icon !== undefined) {
        return props.icon;
    }

    return preset.value?.icon || null;
});

const badgeClasses = computed(() => [
    'inline-flex items-center gap-1 rounded-md border font-semibold uppercase tracking-wider',
    VARIANT_CLASSES[resolvedVariant.value],
    SIZE_CLASSES[props.size].badge,
]);

const iconClass = computed(() => SIZE_CLASSES[props.size].icon);
</script>

<template>
    <span :class="badgeClasses">
        <component :is="resolvedIcon" v-if="resolvedIcon" :class="iconClass" />
        <slot>{{ resolvedLabel }}</slot>
    </span>
</template>
