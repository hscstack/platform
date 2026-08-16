<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, Home } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    subject: Object,
    breadcrumb: Array,
});

const breadcrumbs = computed(() => {
    const subjectPath = props.subject?.slug ? `/${props.subject.slug}` : '';
    let path = '';

    return (props.breadcrumb || []).map((crumb) => {
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
        class="mb-6 no-scrollbar flex items-center space-x-2 overflow-x-auto text-xs font-semibold tracking-wider whitespace-nowrap text-slate-400 uppercase dark:text-gray-500"
    >
        <Link
            href="/"
            class="mr-0.5 flex items-center rounded-lg p-1 transition-colors hover:bg-slate-100/80 hover:text-indigo-600 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
        >
            <Home class="h-4 w-4 stroke-[2.2]" />
        </Link>

        <ChevronRight
            class="h-3.5 w-3.5 shrink-0 stroke-[2.5] text-slate-300 dark:text-gray-600"
        />

        <Link
            :href="`/${props.subject?.slug || ''}`"
            class="px-1 whitespace-nowrap transition-colors hover:text-indigo-600 dark:hover:text-indigo-400"
        >
            {{ props.subject?.name }}
        </Link>

        <template
            v-for="(crumb, index) in breadcrumbs"
            :key="`${crumb.name}-${index}`"
        >
            <ChevronRight
                class="h-3.5 w-3.5 shrink-0 stroke-[2.5] text-slate-300 dark:text-gray-600"
            />

            <Link
                :href="crumb.link"
                class="px-1 whitespace-nowrap transition-colors hover:text-indigo-600 dark:hover:text-indigo-400"
            >
                {{ crumb.name }}
            </Link>
        </template>
    </nav>
</template>
