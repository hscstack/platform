<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentUser = computed(() => (page.props.auth as any)?.user);

const logoHref = computed(() => {
    if (currentUser.value) {
        const course = currentUser.value.curriculum || 'hsc';
        const group = currentUser.value.group || 'science';

        return `/?course=${course}&group=${group}`;
    }

    return '/';
});
</script>

<template>
    <Link :href="logoHref" class="flex items-center gap-2.5">
        <div
            class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-slate-900 shadow-sm dark:bg-gray-100"
        >
            <img
                src="/favicon.svg"
                alt="HSCStack"
                class="h-6 w-6 scale-120 object-cover"
            />
        </div>

        <div class="flex items-center gap-1.5">
            <span
                class="text-lg font-extrabold tracking-tight text-slate-900 dark:text-gray-100"
            >
                HSC<span class="text-indigo-600 dark:text-indigo-400"
                    >Stack</span
                >
            </span>
        </div>
    </Link>
</template>
