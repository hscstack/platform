<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import UserCard from '@/components/UserCard.vue';

interface Member {
    id: number;
    name: string;
    username?: string;
    title?: string | null;
    about?: string | null;
    institution?: string | null;
    image_url?: string | null;
    priority?: number;
    is_board_member?: boolean;
}

const props = defineProps<{
    users?: Member[];
}>();

const boardMembers = computed(() => {
    return (props.users || [])
        .filter((u) => Boolean(u.is_board_member))
        .sort((a, b) => {
            const priorityA = a.priority ?? 0;
            const priorityB = b.priority ?? 0;

            if (priorityA !== priorityB) {
                return priorityB - priorityA;
            }

            return a.id - b.id;
        });
});

const communityContributors = computed(() => {
    return (props.users || [])
        .filter((u) => !u.is_board_member)
        .sort((a, b) => {
            const priorityA = a.priority ?? 0;
            const priorityB = b.priority ?? 0;

            if (priorityA !== priorityB) {
                return priorityB - priorityA;
            }

            return a.id - b.id;
        });
});
</script>

<template>
    <Head>
        <title>About Us & Team - HSCStack</title>
        <meta
            name="description"
            content="Meet the Board of Directors, contributors, developers, and campus promoters behind HSCStack."
        />
        <meta property="og:title" content="About Us & Team - HSCStack" />
        <meta
            property="og:description"
            content="Meet the Board of Directors, contributors, developers, and campus promoters behind HSCStack."
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

    <main class="mx-auto max-w-6xl space-y-16 px-4 pb-32 sm:px-6">
        <!-- Group 1: Board of Directors (Company Members) -->
        <section v-if="boardMembers.length > 0" class="space-y-6">
            <div class="text-center sm:text-left">
                <h2
                    class="text-2xl font-black tracking-tight text-slate-950 sm:text-3xl dark:text-white"
                >
                    Board of Directors
                </h2>
                <p
                    class="mt-1 text-xs text-slate-500 sm:text-sm dark:text-gray-400"
                >
                    HSCStack-এর সার্বিক দিকনির্দেশনা, কৌশলগত সিদ্ধান্ত এবং
                    পরিচালনায় নিয়োজিত কোর টিম।
                </p>
            </div>

            <div
                class="grid grid-cols-1 justify-center gap-8 md:grid-cols-2 lg:grid-cols-3"
            >
                <UserCard
                    v-for="member in boardMembers"
                    :key="member.id"
                    :member="member"
                />
            </div>
        </section>

        <!-- Group 2: Community Contributors & Maintainers -->
        <section class="space-y-6">
            <div class="text-center sm:text-left">
                <h2
                    class="text-2xl font-black tracking-tight text-slate-950 sm:text-3xl dark:text-white"
                >
                    Community Contributors
                </h2>
                <p
                    class="mt-1 text-xs text-slate-500 sm:text-sm dark:text-gray-400"
                >
                    দেশব্যাপী ছড়িয়ে থাকা উদ্যমী স্বেচ্ছাসেবী শিক্ষার্থীরা,
                    যাঁরা প্রতিনিয়ত HSCStack-কে আরও সমৃদ্ধ করতে অবদান রাখছেন।
                </p>
            </div>

            <div
                class="grid grid-cols-1 justify-center gap-8 md:grid-cols-2 lg:grid-cols-3"
            >
                <UserCard
                    v-for="member in communityContributors"
                    :key="member.id"
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
                            We are currently looking for active Campus
                            Promoters, Core Developers, and Resource Curators
                            across colleges nationwide.
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
        </section>
    </main>
</template>
