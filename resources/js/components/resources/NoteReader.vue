<script setup lang="ts">
import { ref, computed } from 'vue';
import ImageViewerModal from '@/components/ImageViewerModal.vue';

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

// Lightbox state with navigation
const activeIndex = ref(0);
const isLightboxOpen = ref(false);

const activeItem = computed(
    () => props.items[activeIndex.value] || props.items[0],
);
const hasPrev = computed(() => activeIndex.value > 0);
const hasNext = computed(() => activeIndex.value < props.items.length - 1);

const openLightbox = (index: number) => {
    activeIndex.value = index;
    isLightboxOpen.value = true;
};

const goToPrev = () => {
    if (hasPrev.value) {
        activeIndex.value--;
    }
};

const goToNext = () => {
    if (hasNext.value) {
        activeIndex.value++;
    }
};
</script>

<template>
    <div class="relative mx-auto w-full sm:max-w-xl md:max-w-2xl">
        <!-- Minimal Top Info -->
        <div
            class="mb-4 flex items-center justify-between px-1 text-xs text-slate-400 dark:text-gray-500"
        >
            <span class="font-semibold text-slate-600 dark:text-gray-300">
                মোট {{ items.length }} টি পৃষ্ঠা
            </span>
            <span class="text-[11px]"> (ছবিতে ক্লিক করে জুম করুন) </span>
        </div>

        <!-- Continuous Document Pages (Clean, natural document flow) -->
        <div class="space-y-6 sm:space-y-8">
            <div
                v-for="(item, index) in items"
                :key="item.id || index"
                class="group"
            >
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs transition hover:border-slate-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                >
                    <img
                        :src="item.url || item.source"
                        :alt="item.title || `পৃষ্ঠা ${index + 1}`"
                        loading="lazy"
                        @click="openLightbox(index)"
                        class="mx-auto block w-full cursor-zoom-in rounded-2xl object-contain"
                    />
                </div>

                <!-- Page indicator under each image -->
                <p
                    class="mt-2 text-center text-xs font-semibold text-slate-400 dark:text-gray-500"
                >
                    পৃষ্ঠা {{ index + 1 }} / {{ items.length }}
                </p>
            </div>
        </div>

        <!-- Lightbox Modal for Pin-Point Zoom & Pan with Navigation -->
        <ImageViewerModal
            v-model="isLightboxOpen"
            :src="activeItem?.url || activeItem?.source || ''"
            :title="activeItem?.title || `পৃষ্ঠা ${activeIndex + 1}`"
            :has-prev="hasPrev"
            :has-next="hasNext"
            :page-indicator="`পৃষ্ঠা ${activeIndex + 1} / ${items.length}`"
            @prev="goToPrev"
            @next="goToNext"
            @close="isLightboxOpen = false"
        />
    </div>
</template>
