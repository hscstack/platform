<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, Home } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        subject?: any;
        breadcrumb?: any[];
        excludeLast?: boolean;
    }>(),
    {
        subject: () => ({}),
        breadcrumb: () => [],
        excludeLast: false,
    },
);

const parentBreadcrumbs = computed(() => {
    const subjectPath = props.subject?.slug ? `/${props.subject.slug}` : '';
    const rawCrumbs = (props.breadcrumb || []) as any[];

    // On Node pages where the last crumb is rendered as the H1 title, exclude the last item.
    // On Resource pages, all node crumbs are parents of the resource, so render all of them.
    const crumbsToRender = props.excludeLast
        ? rawCrumbs.slice(0, -1)
        : rawCrumbs;
    let path = '';

    return crumbsToRender.map((crumb) => {
        path += `/${crumb.slug}`;

        return {
            name: crumb.name,
            link: `${subjectPath}${path}`,
        };
    });
});
</script>

<template>
    <nav
        class="no-scrollbar flex items-center space-x-1 overflow-x-auto text-sm font-semibold tracking-normal whitespace-nowrap text-slate-500 dark:text-gray-400"
    >
        <Link
            href="/"
            class="flex items-center gap-1.5 rounded-lg px-2 py-1 transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
            title="হোম পেজে ফিরে যান"
        >
            <Home class="h-4 w-4 stroke-[2.2]" />
            <span>হোম</span>
        </Link>

        <!-- Only show subject link in breadcrumb if we are inside a node (meaning subject is a parent) -->
        <template v-if="(props.breadcrumb || []).length > 0">
            <ChevronRight
                class="h-4 w-4 shrink-0 stroke-[2.2] text-slate-300 dark:text-gray-600"
            />

            <Link
                :href="`/${props.subject?.slug || ''}`"
                class="rounded-lg px-2 py-1 whitespace-nowrap transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
            >
                {{ props.subject?.name }}
            </Link>
        </template>

        <!-- Parent node levels -->
        <template
            v-for="(crumb, index) in parentBreadcrumbs"
            :key="`${crumb.name}-${index}`"
        >
            <ChevronRight
                class="h-4 w-4 shrink-0 stroke-[2.2] text-slate-300 dark:text-gray-600"
            />

            <Link
                :href="crumb.link"
                class="rounded-lg px-2 py-1 whitespace-nowrap transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
            >
                {{ crumb.name }}
            </Link>
        </template>
    </nav>
</template>
