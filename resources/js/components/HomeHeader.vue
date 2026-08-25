<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed } from 'vue';
const searchQuery = defineModel();
const page = usePage();
const isSsc = computed(() => page.url.startsWith('/ssc'));
</script>

<template>
    <div class="px-6 pt-16 pb-3 text-center sm:pt-24 sm:pb-6">
        <div class="mx-auto max-w-3xl">
            <h1
                class="mb-4 text-4xl font-black tracking-tight text-slate-900 sm:text-6xl lg:leading-[1.15] dark:text-gray-100"
            >
                Your knowledge, <br />
                <span
                    class="bg-gradient-to-r bg-clip-text text-transparent"
                    :class="
                        isSsc
                            ? 'from-emerald-600 via-teal-600 to-cyan-600'
                            : 'from-indigo-600 via-violet-600 to-fuchsia-600'
                    "
                >
                    perfectly organized.
                </span>
            </h1>

            <p
                class="mx-auto mb-2 max-w-xl text-base leading-relaxed font-medium text-slate-700/90 sm:text-lg dark:text-gray-300"
            >
                HSC & SSC স্টুডেন্টদের জন্য একদম ক্লিন ও ফাস্ট ফ্রি লার্নিং
                প্ল্যাটফর্ম।
            </p>

            <p
                class="mx-auto mb-8 max-w-xl text-xs leading-relaxed text-slate-500 sm:text-sm dark:text-gray-400"
            >
                A free, open-source educational repository and learning platform
                for HSC & SSC students in Bangladesh — access organized video
                lectures, lecture notes, syllabus breakdowns, and study
                materials.
            </p>

            <div class="group relative mx-auto max-w-lg">
                <div
                    class="absolute -inset-0.5 rounded-2xl bg-gradient-to-r from-indigo-500 to-fuchsia-500 opacity-0 blur-md transition duration-300 group-focus-within:opacity-10"
                ></div>

                <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search for subjects..."
                        class="w-full appearance-none rounded-2xl border border-slate-200 bg-white/90 py-4 pr-4 pl-12 text-sm font-semibold shadow-md shadow-slate-100/40 backdrop-blur-md transition-all duration-300 placeholder:text-slate-400/90 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-800/90 dark:text-gray-100 dark:shadow-gray-900/60 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:bg-gray-800 dark:focus:ring-indigo-500/20"
                    />
                    <Search
                        class="pointer-events-none absolute top-[18px] left-4 h-4.5 w-4.5 text-slate-400 transition-colors duration-300 group-focus-within:text-indigo-600 dark:text-gray-500 dark:group-focus-within:text-indigo-400"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
