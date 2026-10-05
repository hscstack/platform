<script setup lang="ts">
import 'vidstack/player/styles/default/theme.css';
import 'vidstack/player/styles/default/layouts/video.css';
import 'vidstack/player';
import 'vidstack/player/layouts';
import 'vidstack/player/ui';
import { AlertCircle, ExternalLink } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    url: {
        type: String,
        required: true,
    },
});

const videoId = computed(() => {
    if (!props.url) {
        return null;
    }

    try {
        const parsed = new URL(props.url);

        if (parsed.hostname.includes('youtube.com')) {
            if (parsed.pathname === '/watch') {
                return parsed.searchParams.get('v');
            }

            if (parsed.pathname.startsWith('/embed/')) {
                return parsed.pathname.split('/embed/')[1]?.split('?')[0];
            }

            if (parsed.pathname.startsWith('/shorts/')) {
                return parsed.pathname.split('/shorts/')[1]?.split('?')[0];
            }
        }

        if (parsed.hostname === 'youtu.be') {
            return parsed.pathname.slice(1)?.split('?')[0];
        }

        return null;
    } catch {
        const match = props.url.match(
            /(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/,
        );

        return match ? match[1] : null;
    }
});

const mediaSrc = computed(() => {
    if (!videoId.value) {
        return null;
    }

    return `youtube/${videoId.value}`;
});
</script>

<template>
    <div
        class="relative aspect-video w-full overflow-hidden rounded-2xl border border-slate-200/90 bg-black shadow-md dark:border-gray-800"
    >
        <media-player
            v-if="mediaSrc"
            :src="mediaSrc"
            aspect-ratio="16/9"
            playsinline
            class="h-full w-full"
        >
            <media-provider></media-provider>
            <media-video-layout no-title></media-video-layout>
        </media-player>

        <!-- Fallback if URL is invalid -->
        <div
            v-else
            class="flex h-full w-full flex-col items-center justify-center gap-3 p-6 text-center text-slate-400"
        >
            <AlertCircle class="h-8 w-8 text-amber-500/80" />
            <div class="space-y-1">
                <p class="text-sm font-semibold text-slate-200">
                    Unable to load video
                </p>
                <p class="text-xs text-slate-400">
                    The video link is invalid or unavailable.
                </p>
            </div>
            <a
                v-if="url"
                :href="url"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-2 inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-1.5 text-xs font-medium text-slate-200 transition hover:bg-slate-700 hover:text-white"
            >
                <span>Open in YouTube</span>
                <ExternalLink class="h-3.5 w-3.5" />
            </a>
        </div>
    </div>
</template>

<style scoped>
media-player {
    --media-brand: rgb(79 70 229);
    --media-focus-ring: rgb(99 102 241 / 0.5);
    --media-border-radius: 1rem;
    height: 100%;
    width: 100%;
}

/* Completely hide any Vidstack title elements */
media-player :deep(media-title),
media-player :deep(.vds-title),
media-player :deep(.vds-chapter-title),
media-player :deep(.vds-layout-title) {
    display: none !important;
}

/* Ensure volume slider remains visible on mobile/touch screens */
media-player :deep(media-volume-slider),
media-player :deep(.vds-volume-slider) {
    display: inline-flex !important;
    width: 54px !important;
    max-width: 60px !important;
}

/* Scale YouTube iframe slightly to push YouTube's internal top bar outside the clipped boundary */
media-player :deep(iframe),
media-player :deep(.vds-youtube) {
    transform: scale(1.04) !important;
    transform-origin: center center !important;
}
</style>
