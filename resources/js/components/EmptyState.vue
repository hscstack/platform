<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FolderSearch, ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';
import { usePermissions } from '@/lib/usePermissions';

interface Props {
    title?: string;
    description?: string;
    showCta?: boolean;
    ctaPrompt?: string;
    ctaText?: string;
    ctaLink?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'কোনো রিসোর্স পাওয়া যায়নি',
    description: 'শীঘ্রই এখানে নতুন স্টাডি ম্যাটেরিয়াল ও নোট আপলোড করা হবে।',
    showCta: true,
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
</script>

<template>
    <div
        class="flex flex-col items-center justify-center px-4 py-12 text-center sm:py-16"
    >
        <!-- Icon Badge -->
        <div
            class="mb-4 flex h-13 w-13 items-center justify-center rounded-2xl border border-slate-200/80 bg-slate-50 text-indigo-600 shadow-2xs dark:border-gray-800 dark:bg-gray-900/90 dark:text-indigo-400"
        >
            <FolderSearch class="h-6 w-6 stroke-[1.8]" />
        </div>

        <!-- Title & Subtitle -->
        <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">
            {{ title }}
        </h3>
        <p
            class="mt-1.5 max-w-sm text-xs leading-relaxed font-medium text-slate-500 dark:text-gray-400"
        >
            {{ description }}
        </p>

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
