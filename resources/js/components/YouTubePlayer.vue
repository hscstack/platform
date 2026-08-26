<script setup lang="ts">
import { Play } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    url: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        default: 'Video Lecture',
    },
});

const isStarted = ref(false);

const videoId = computed(() => {
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
        return null;
    }
});

const thumbnailUrl = computed(() => {
    if (!videoId.value) {
return '';
}

    return `https://i.ytimg.com/vi/${videoId.value}/maxresdefault.jpg`;
});

const embedUrl = computed(() => {
    if (!videoId.value) {
return '';
}

    return `https://www.youtube-nocookie.com/embed/${videoId.value}?autoplay=1&modestbranding=1&rel=0&iv_load_policy=3&playsinline=1&color=white`;
});

const handleStart = () => {
    isStarted.value = true;
};

watch(
    () => props.url,
    () => {
        isStarted.value = false;
    },
);
</script>

<template>
    <div
        class="group relative aspect-video w-full overflow-hidden rounded-2xl border border-slate-200/90 bg-slate-950 shadow-md select-none dark:border-gray-800"
    >
        <!-- Native Poster & Play Hero (Shown before click) -->
        <div
            v-if="!isStarted"
            class="absolute inset-0 z-20 flex cursor-pointer items-center justify-center overflow-hidden bg-slate-950"
            @click="handleStart"
        >
            <!-- Thumbnail Background with Blur & Vignette -->
            <img
                :src="thumbnailUrl"
                :alt="title"
                class="absolute inset-0 h-full w-full object-cover opacity-80 transition-transform duration-700 ease-out group-hover:scale-105"
                @error="
                    ($event.target as HTMLImageElement).src =
                        `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`
                "
            />
            <div
                class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/30 to-slate-950/40"
            ></div>

            <!-- Pulsing Native Play Button -->
            <div
                class="relative flex h-16 w-16 items-center justify-center rounded-full bg-white/95 text-slate-900 shadow-2xl backdrop-blur-md transition-all duration-300 group-hover:scale-110 sm:h-20 sm:w-20"
            >
                <Play class="ml-1 h-7 w-7 fill-current sm:h-8 sm:w-8" />
            </div>

            <!-- Bottom Video Title Preview -->
            <div class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                <span
                    class="inline-block rounded-md bg-indigo-600/90 px-2 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase backdrop-blur-xs"
                >
                    Video Lecture
                </span>
                <h3
                    class="mt-1.5 line-clamp-1 text-sm font-bold text-white sm:text-base"
                >
                    {{ title }}
                </h3>
            </div>
        </div>

        <!-- Privacy-Enhanced Clean YouTube Embed -->
        <iframe
            v-if="isStarted"
            :src="embedUrl"
            :title="title"
            class="absolute inset-0 h-full w-full border-0"
            allow="
                accelerometer;
                autoplay;
                clipboard-write;
                encrypted-media;
                gyroscope;
                picture-in-picture;
                web-share;
            "
            allowfullscreen
        ></iframe>
    </div>
</template>
