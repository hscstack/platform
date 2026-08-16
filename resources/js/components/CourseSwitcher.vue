<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { kBlock, kSegmented, kButton } from 'konsta/vue';
import { computed } from 'vue';

const page = usePage();
const isSsc = computed(() => page.url.startsWith('/ssc'));

const switchCurriculum = (target: 'hsc' | 'ssc') => {
    if (target === 'ssc' && !isSsc.value) {
        router.visit('/ssc');
    } else if (target === 'hsc' && isSsc.value) {
        router.visit('/');
    }
};
</script>

<template>
    <k-block class="my-4">
        <div class="mb-4 flex items-center gap-3">
            <span
                class="inline-flex shrink-0 items-center justify-center rounded-lg px-3 py-1 text-xl font-black text-white shadow-xs sm:px-3.5 sm:py-1.5 sm:text-2xl"
                :class="isSsc ? 'bg-emerald-600' : 'bg-indigo-600'"
            >
                {{ isSsc ? 'SSC' : 'HSC' }}
            </span>
            <div class="flex min-w-0 flex-col">
                <span
                    class="text-[11px] font-bold tracking-wider text-slate-400 uppercase sm:text-xs dark:text-gray-500"
                >
                    Active Repository
                </span>
                <p
                    class="text-sm leading-snug font-extrabold text-slate-900 sm:text-base dark:text-gray-100"
                >
                    আপনি <span>{{ isSsc ? 'SSC' : 'HSC' }}</span> এর
                    কন্টেন্টগুলো দেখছেন
                </p>
            </div>
        </div>

        <k-segmented strong>
            <k-button
                :clear="isSsc"
                :tonal="!isSsc"
                @click="switchCurriculum('hsc')"
            >
                HSC
            </k-button>
            <k-button
                :clear="!isSsc"
                :tonal="isSsc"
                @click="switchCurriculum('ssc')"
            >
                SSC
            </k-button>
        </k-segmented>
    </k-block>
</template>
