<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const isSsc = computed(() => page.url.startsWith('/ssc'));
</script>

<template>
    <div
        class="mb-6 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between sm:p-5"
    >
        <!-- Large Browsing Indicator -->
        <div class="flex items-center gap-3">
            <span
                class="inline-flex shrink-0 items-center justify-center rounded-lg px-3 py-1 text-xl font-black text-white shadow-xs sm:px-3.5 sm:py-1.5 sm:text-2xl"
                :class="isSsc ? 'bg-emerald-600' : 'bg-indigo-600'"
            >
                {{ isSsc ? 'SSC' : 'HSC' }}
            </span>
            <div class="flex min-w-0 flex-col">
                <span
                    class="text-[11px] font-bold tracking-wider text-slate-400 uppercase sm:text-xs"
                >
                    Active Repository
                </span>
                <p
                    class="text-sm leading-snug font-extrabold text-slate-900 sm:text-base"
                >
                    আপনি <span>{{ isSsc ? 'SSC' : 'HSC' }}</span> এর
                    কন্টেন্টগুলো দেখছেন
                </p>
            </div>
        </div>

        <!-- Prominent Switch Action Button -->
        <Link
            :href="isSsc ? '/' : '/ssc'"
            :class="[
                'group inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-lg border px-5 py-2.5 text-xs font-bold shadow-xs transition-all active:scale-[0.98] sm:w-auto',
                isSsc
                    ? 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white active:bg-indigo-700'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white active:bg-emerald-700',
            ]"
        >
            <span>
                <strong>{{ isSsc ? 'HSC ' : 'SSC ' }}</strong>
                বিভাগে পরিবর্তন করুন
            </span>
            <ArrowRight
                class="h-4 w-4 shrink-0 transition-transform group-hover:translate-x-1"
            />
        </Link>
    </div>
</template>
