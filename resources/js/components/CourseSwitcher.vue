<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

// Determines the active tab based on current URL path
const isSsc = computed(() => page.url.startsWith('/ssc'));
</script>

<template>
    <div class="mb-8 flex justify-center">
        <div class="inline-flex rounded-xl bg-slate-100 p-1.5 shadow-inner">
            <Link
                href="/"
                :class="[
                    'rounded-lg px-6 py-2 text-sm font-bold transition-all duration-200',
                    !isSsc
                        ? 'bg-white text-indigo-600 shadow-sm'
                        : 'text-slate-600 hover:text-slate-900',
                ]"
            >
                HSC
            </Link>
            <Link
                href="/ssc"
                :class="[
                    'rounded-lg px-6 py-2 text-sm font-bold transition-all duration-200',
                    isSsc
                        ? 'bg-white text-indigo-600 shadow-sm'
                        : 'text-slate-600 hover:text-slate-900',
                ]"
            >
                SSC
            </Link>
        </div>
    </div>
</template>
