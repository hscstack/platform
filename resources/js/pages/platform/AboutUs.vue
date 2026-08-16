<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import UserCard from '@/components/UserCard.vue';

defineProps({
    users: Array,
});
const goBack = () => {
    window.history.back();
};
</script>

<template>
    <header class="mx-auto max-w-4xl px-4 pt-16 pb-14 text-center sm:pt-20">
        <button
            @click="goBack"
            class="group mb-5 inline-flex items-center gap-2 text-xs font-bold text-slate-400 transition-colors hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400"
        >
            <ArrowLeft
                class="h-3.5 w-3.5 transition-transform group-hover:-translate-x-0.5"
            />
            Go Back
        </button>
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
                :id="member.id"
            />

            <div
                class="flex flex-col justify-between rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/40 p-8 text-center dark:border-gray-700 dark:bg-gray-800/40"
            >
                <div class="my-auto space-y-3 py-8">
                    <h3
                        class="text-lg font-extrabold text-slate-900 dark:text-gray-100"
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
                <Link href="/join"> Apply for a Role </Link>
            </div>
        </div>
    </main>
</template>
