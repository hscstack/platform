<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isSsc = computed(() => page.url.startsWith('/ssc'));
</script>

<template>
    <Link href="/" class="flex items-center gap-2.5">
        <div
            class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-slate-900 shadow-sm"
        >
            <img
                src="/favicon.svg"
                alt="HSCStack"
                class="h-6 w-6 scale-110 object-cover"
            />
        </div>

        <div class="flex items-center gap-1.5">
            <span class="text-lg font-extrabold tracking-tight text-slate-900">
                HSC
                <span :class="isSsc ? 'text-emerald-600' : 'text-indigo-600'"
                    >Stack</span
                >
            </span>

            <span
                v-if="isSsc"
                class="rounded border border-emerald-200 bg-emerald-100 px-1.5 py-0.5 text-[10px] font-black tracking-wider text-emerald-700 uppercase"
            >
                SSC
            </span>
        </div>
    </Link>
</template>
