<script setup lang="ts">
import { FileText, ExternalLink } from 'lucide-vue-next';
import { ref, computed } from 'vue';

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

const currentItem = computed(() => {
    return props.items[activeIndex.value] || props.items[0];
});

const currentUrl = computed(() => {
    return currentItem.value?.url || currentItem.value?.source || '';
});

// Convert Google Drive view links to direct embeddable preview format
const embedUrl = computed(() => {
    const url = currentUrl.value;

    if (!url) {
        return '';
    }

    // Match Google Drive file ID
    const driveMatch = url.match(
        /(?:drive\.google\.com\/(?:file\/d\/|open\?id=))([a-zA-Z0-9_-]+)/,
    );

    if (driveMatch && driveMatch[1]) {
        return `https://drive.google.com/file/d/${driveMatch[1]}/preview`;
    }

    // Direct PDF URL or storage link -> use Google Docs PDF Viewer embed
    return `https://docs.google.com/viewer?url=${encodeURIComponent(url)}&embedded=true`;
});
</script>

<template>
    <div class="mx-auto w-full space-y-4">
        <!-- Multi-part PDF selector (If resource has multiple PDF volumes) -->
        <div
            v-if="items.length > 1"
            class="flex items-center gap-2 overflow-x-auto pb-1"
        >
            <button
                v-for="(item, index) in items"
                :key="item.id || index"
                type="button"
                @click="activeIndex = index"
                class="inline-flex shrink-0 cursor-pointer items-center gap-2 rounded-xl px-3.5 py-1.5 text-xs font-bold transition active:scale-95"
                :class="
                    activeIndex === index
                        ? 'bg-slate-900 text-white shadow-xs dark:bg-white dark:text-gray-900'
                        : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800'
                "
            >
                <FileText class="h-3.5 w-3.5" />
                <span>{{ item.title || `ফাইল ০${index + 1}` }}</span>
            </button>
        </div>

        <!-- PDF Reader Container -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Reader Header Bar -->
            <div
                class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 py-2.5 dark:border-gray-800 dark:bg-gray-800/60"
            >
                <div
                    class="flex min-w-0 items-center gap-2 text-xs font-bold text-slate-700 dark:text-gray-300"
                >
                    <FileText
                        class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                    />
                    <span class="truncate">{{
                        currentItem?.title || 'পিডিএফ ডকুমেন্ট'
                    }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="currentUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200/80 bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:hover:text-indigo-400"
                        title="নতুন ট্যাবে বা ড্রাইভ অ্যাপে খুলুন"
                    >
                        <ExternalLink class="h-3.5 w-3.5" />
                        <span>নতুন ট্যাবে খুলুন</span>
                    </a>
                </div>
            </div>

            <!-- PDF Embed Frame -->
            <div
                class="relative h-[88vh] min-h-[720px] w-full bg-slate-100 dark:bg-gray-950"
            >
                <iframe
                    v-if="embedUrl"
                    :key="embedUrl"
                    :src="embedUrl"
                    class="h-full w-full border-0"
                    allow="autoplay; fullscreen"
                    allowfullscreen
                    loading="lazy"
                ></iframe>

                <div
                    v-else
                    class="flex h-full w-full flex-col items-center justify-center gap-2 p-6 text-center text-slate-400"
                >
                    <FileText
                        class="h-10 w-10 text-slate-300 dark:text-gray-600"
                    />
                    <p
                        class="text-sm font-semibold text-slate-600 dark:text-gray-300"
                    >
                        পিডিএফ লিংকটি লোড করা সম্ভব হয়নি
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
