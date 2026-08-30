<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ChevronDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const page = usePage();
const isSsc = computed(() => page.url.startsWith('/ssc'));
const isMobileExpanded = ref(false);

const toggleMobileExpand = () => {
    isMobileExpanded.value = !isMobileExpanded.value;
};

const setCoursePreference = (course: 'hsc' | 'ssc') => {
    try {
        localStorage.setItem('preferred_course', course);
        // Set cookie valid for 1 year so server can directly render preferred course without double-refresh
        document.cookie = `preferred_course=${course};path=/;max-age=31536000;SameSite=Lax`;
    } catch {
        // ignore storage errors
    }
};
</script>

<template>
    <div
        class="mb-6 rounded-xl border border-slate-200 bg-white p-3.5 shadow-xs transition-all sm:p-5 dark:border-gray-700 dark:bg-gray-900"
    >
        <div
            class="flex cursor-pointer items-center justify-between gap-3 sm:cursor-default"
            @click="toggleMobileExpand"
        >
            <!-- Large Browsing Indicator -->
            <div class="flex min-w-0 items-center gap-3">
                <span
                    class="inline-flex shrink-0 items-center justify-center rounded-lg px-2.5 py-1 text-lg font-black text-white shadow-xs sm:px-3.5 sm:py-1.5 sm:text-2xl"
                    :class="isSsc ? 'bg-emerald-600' : 'bg-indigo-600'"
                >
                    {{ isSsc ? 'SSC' : 'HSC' }}
                </span>
                <div class="flex min-w-0 flex-col">
                    <span
                        class="text-[10px] font-bold tracking-wider text-slate-400 uppercase sm:text-xs dark:text-gray-500"
                    >
                        Active Repository
                    </span>
                    <p
                        class="text-xs leading-snug font-extrabold text-slate-900 sm:text-base dark:text-gray-100"
                    >
                        আপনি <span>{{ isSsc ? 'SSC' : 'HSC' }}</span> এর
                        কন্টেন্টগুলো দেখছেন
                    </p>
                </div>
            </div>

            <!-- Mobile Toggle Trigger Icon (Chevron) -->
            <div
                class="flex h-8 w-8 shrink-0 items-center justify-center text-slate-400 sm:hidden dark:text-gray-400"
                :title="isMobileExpanded ? 'সংকোচন করুন' : 'পরিবর্তন করুন'"
            >
                <ChevronDown
                    class="h-4 w-4 transition-transform duration-200"
                    :class="{
                        'rotate-180 text-slate-700 dark:text-gray-200':
                            isMobileExpanded,
                    }"
                />
            </div>

            <!-- Desktop Switch Action Button (Always visible on sm and up) -->
            <Link
                :href="isSsc ? '/' : '/ssc'"
                preserve-scroll
                @click="setCoursePreference(isSsc ? 'hsc' : 'ssc')"
                :class="[
                    'group hidden shrink-0 items-center justify-center gap-2 rounded-lg border px-5 py-2.5 text-xs font-bold shadow-xs transition-all active:scale-[0.98] sm:inline-flex',
                    isSsc
                        ? 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white active:bg-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500 dark:hover:text-white'
                        : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white active:bg-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500 dark:hover:text-white',
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

        <!-- Mobile Expandable Switch Action Area (Glitch-Free CSS Grid Accordion) -->
        <div
            class="grid transition-all duration-200 ease-in-out sm:hidden"
            :class="
                isMobileExpanded
                    ? 'mt-3 grid-rows-[1fr] border-t border-slate-100 pt-3 opacity-100 dark:border-gray-800'
                    : 'grid-rows-[0fr] opacity-0'
            "
        >
            <div class="overflow-hidden">
                <Link
                    :href="isSsc ? '/' : '/ssc'"
                    preserve-scroll
                    @click="setCoursePreference(isSsc ? 'hsc' : 'ssc')"
                    :class="[
                        'group flex w-full items-center justify-center gap-2 rounded-lg border px-4 py-2.5 text-xs font-bold shadow-xs transition-all active:scale-[0.98]',
                        isSsc
                            ? 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white active:bg-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500 dark:hover:text-white'
                            : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white active:bg-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500 dark:hover:text-white',
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
        </div>
    </div>
</template>
