<script setup lang="ts">
import { Play, ListVideo, ExternalLink, Youtube } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import YouTubePlayer from '@/components/YouTubePlayer.vue';

interface ResourceItem {
    id: number;
    title?: string | null;
    source: string;
    url: string;
    format: string;
}

const props = defineProps<{
    items: ResourceItem[];
}>();

const activeIndex = ref(0);

const currentLecture = computed(() => {
    return props.items[activeIndex.value] || props.items[0];
});

const currentLectureYouTubeUrl = computed(() => {
    const raw = currentLecture.value?.url || currentLecture.value?.source;

    if (!raw) {
        return '';
    }

    if (raw.startsWith('http://') || raw.startsWith('https://')) {
        return raw;
    }

    return `https://www.youtube.com/watch?v=${raw}`;
});

const selectLecture = (index: number) => {
    if (index >= 0 && index < props.items.length) {
        activeIndex.value = index;
    }
};
</script>

<template>
    <div class="mx-auto w-full space-y-6">
        <!-- 1. The Video Player (Expanded to match full container) -->
        <div class="space-y-3">
            <div
                class="overflow-hidden rounded-2xl border border-slate-200/90 bg-black shadow-sm dark:border-gray-800"
            >
                <YouTubePlayer
                    :key="currentLecture?.url || currentLecture?.source"
                    :url="currentLecture?.url || currentLecture?.source"
                />
            </div>

            <!-- Active Video Bar: Current Lecture Title & Open in YouTube action -->
            <div
                class="flex flex-wrap items-center justify-between gap-3 px-1 py-0.5"
            >
                <div class="min-w-0 flex-1">
                    <h3
                        class="truncate text-sm font-bold text-slate-800 sm:text-base dark:text-gray-100"
                    >
                        {{
                            currentLecture?.title ||
                            `লেকচার ০${activeIndex + 1}`
                        }}
                    </h3>
                </div>

                <a
                    v-if="currentLectureYouTubeUrl"
                    :href="currentLectureYouTubeUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-rose-300 hover:bg-rose-50/50 hover:text-rose-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-rose-900/40 dark:hover:bg-rose-950/30 dark:hover:text-rose-400"
                >
                    <Youtube
                        class="h-3.5 w-3.5 text-rose-600 dark:text-rose-500"
                    />
                    <span>Open in YouTube</span>
                    <ExternalLink
                        class="h-3 w-3 text-slate-400 dark:text-gray-500"
                    />
                </a>
            </div>
        </div>

        <!-- 2. Playlist / Lecture Series Drawer (If multiple lectures exist) -->
        <div
            v-if="items.length > 1"
            class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-5 py-3 dark:border-gray-800 dark:bg-gray-800/50"
            >
                <div
                    class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                >
                    <ListVideo class="h-4 w-4 text-rose-500" />
                    <span>সকল লেকচার তালিকা ({{ items.length }} টি)</span>
                </div>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-gray-800/60">
                <button
                    v-for="(item, index) in items"
                    :key="item.id || index"
                    type="button"
                    @click="selectLecture(index)"
                    class="flex w-full cursor-pointer items-center justify-between px-5 py-3.5 text-left transition hover:bg-slate-50/70 dark:hover:bg-gray-800/40"
                    :class="
                        activeIndex === index
                            ? 'bg-rose-50/50 dark:bg-rose-950/20'
                            : ''
                    "
                >
                    <div class="flex min-w-0 items-center gap-3.5">
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold transition"
                            :class="
                                activeIndex === index
                                    ? 'bg-rose-600 text-white shadow-xs dark:bg-rose-500'
                                    : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            <Play
                                v-if="activeIndex === index"
                                class="ml-0.5 h-3 w-3 fill-current"
                            />
                            <span v-else>{{ index + 1 }}</span>
                        </span>

                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold transition"
                                :class="
                                    activeIndex === index
                                        ? 'font-bold text-rose-700 dark:text-rose-300'
                                        : 'text-slate-700 dark:text-gray-200'
                                "
                            >
                                {{ item.title || `লেকচার ০${index + 1}` }}
                            </p>
                        </div>
                    </div>

                    <span
                        v-if="activeIndex === index"
                        class="ml-2 shrink-0 rounded-md border border-rose-200/80 bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-300"
                    >
                        চলছে
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
