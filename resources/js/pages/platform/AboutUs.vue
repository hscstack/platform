<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import UserCard from '@/components/UserCard.vue';

defineProps({
    users: Array,
});
</script>

<template>
    <Head>
        <title>About Us & Core Team</title>
        <meta
            name="description"
            content="Meet the creators, developers, campus promoters, and resource curators behind HSCStack."
        />
        <meta property="og:title" content="About Us & Core Team - HSCStack" />
        <meta
            property="og:description"
            content="Meet the creators, developers, campus promoters, and resource curators behind HSCStack."
        />
    </Head>

    <header class="mx-auto max-w-4xl px-4 pt-8 pb-10 text-center sm:pt-12">
        <h1
            class="text-4xl font-black tracking-tight text-slate-950 sm:text-6xl dark:text-gray-100"
        >
            The Team Behind <br class="hidden sm:inline" />
            <span class="text-indigo-600">The Archive.</span>
        </h1>
    </header>

    <main class="mx-auto max-w-6xl px-4 pb-32 sm:px-6">
        <div
            class="grid grid-cols-1 justify-center gap-8 md:grid-cols-2 lg:grid-cols-3"
        >
            <UserCard
                v-for="member in users"
                :key="member.name"
                :member="member"
            />

            <div
                class="flex flex-col justify-between rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-6 text-center shadow-xs dark:border-gray-800 dark:bg-gray-900/40"
            >
                <div class="my-auto space-y-2.5 py-6">
                    <h3
                        class="text-base font-extrabold text-slate-900 dark:text-gray-100"
                    >
                        Want your card here?
                    </h3>
                    <p
                        class="mx-auto max-w-xs text-xs leading-relaxed font-medium text-slate-400 dark:text-gray-500"
                    >
                        We are currently looking for active Campus Promoters,
                        Core Developers, and Resource Curators across colleges
                        nationwide.
                    </p>
                </div>
                <Link
                    href="/join"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-slate-950 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition-all hover:bg-slate-800 active:scale-[0.98] dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                >
                    Apply for a Role
                </Link>
            </div>
        </div>
    </main>
</template>
