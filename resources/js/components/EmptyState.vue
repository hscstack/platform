<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, FolderSearch } from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';
import { usePermissions } from '@/lib/usePermissions';

interface Props {
    icon?: Component | null;
    title?: string;
    description?: string | null;
    variant?: 'simple' | 'card' | 'dashed';
    showCta?: boolean;
    ctaPrompt?: string;
    ctaText?: string;
    ctaLink?: string;
}

const props = withDefaults(defineProps<Props>(), {
    icon: () => FolderSearch,
    title: 'কোনো তথ্য পাওয়া যায়নি',
    description: null,
    variant: 'simple',
    showCta: false,
    ctaPrompt: 'তোমার নিজের নোট বা লেকচার শেয়ার করতে চাও?',
    ctaText: 'কন্ট্রিবিউটর হও',
    ctaLink: '/join',
});

const page = usePage();
const { can } = usePermissions();

const isAlreadyContributor = computed(() => {
    return Boolean(
        (page.props.auth as any)?.can_access_admin ||
        can('create resources') ||
        can('create nodes'),
    );
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'dashed':
            return 'rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-8 sm:p-12 dark:border-gray-800 dark:bg-gray-900/40';
        case 'card':
            return 'rounded-2xl border border-slate-200/80 bg-white p-8 sm:p-12 shadow-2xs dark:border-gray-800 dark:bg-gray-900';
        case 'simple':
        default:
            return 'px-4 py-12 sm:py-16';
    }
});
</script>

<template>
    <div
        class="flex flex-col items-center justify-center text-center"
        :class="variantClasses"
    >
        <!-- Icon Badge -->
        <div v-if="$slots.icon || icon" class="mb-4">
            <slot name="icon">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200/80 bg-slate-50 text-indigo-600 shadow-2xs dark:border-gray-800 dark:bg-gray-900/90 dark:text-indigo-400"
                >
                    <component
                        :is="icon"
                        v-if="icon"
                        class="h-6 w-6 stroke-[1.8]"
                    />
                </div>
            </slot>
        </div>

        <!-- Title -->
        <slot name="title">
            <h3
                v-if="title"
                class="text-base font-bold text-slate-900 dark:text-gray-100"
            >
                {{ title }}
            </h3>
        </slot>

        <!-- Description -->
        <slot name="description">
            <p
                v-if="description"
                class="mt-1.5 max-w-sm text-xs leading-relaxed font-medium text-slate-500 dark:text-gray-400"
            >
                {{ description }}
            </p>
        </slot>

        <!-- Action Buttons Slot -->
        <div v-if="$slots.actions || $slots.default" class="mt-5">
            <slot name="actions">
                <slot />
            </slot>
        </div>

        <!-- Contributor CTA Card (Hidden for existing contributors/admins) -->
        <div
            v-if="showCta && !isAlreadyContributor"
            class="mt-6 flex flex-col items-center gap-2.5 rounded-2xl border border-indigo-100/80 bg-indigo-50/50 p-3.5 sm:flex-row sm:gap-3.5 sm:px-4 sm:py-2.5 dark:border-indigo-900/30 dark:bg-indigo-950/20"
        >
            <span class="text-xs font-medium text-slate-600 dark:text-gray-300">
                {{ ctaPrompt }}
            </span>
            <Link
                :href="ctaLink"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-500"
            >
                <span>{{ ctaText }}</span>
                <ArrowRight class="h-3.5 w-3.5" />
            </Link>
        </div>
    </div>
</template>
