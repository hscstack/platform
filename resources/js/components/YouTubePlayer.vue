<script setup lang="ts">
import Plyr from 'plyr';
import 'plyr/dist/plyr.css';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

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

const playerContainerRef = ref<HTMLElement | null>(null);
let playerInstance: Plyr | null = null;

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

const origin = typeof window !== 'undefined' ? window.location.origin : '';

const initPlyr = async () => {
    if (playerInstance) {
        playerInstance.destroy();
        playerInstance = null;
    }

    await nextTick();

    if (!playerContainerRef.value || !videoId.value) {
return;
}

    playerInstance = new Plyr(playerContainerRef.value, {
        controls: [
            'play-large',
            'play',
            'progress',
            'current-time',
            'duration',
            'mute',
            'volume',
            'settings',
            'fullscreen',
        ],
        settings: ['speed'],
        speed: { selected: 1, options: [0.75, 1, 1.25, 1.5, 2] },
        youtube: {
            noCookie: true,
            rel: 0,
            showinfo: 0,
            iv_load_policy: 3,
            modestbranding: 1,
        },
    });
};

onMounted(() => {
    initPlyr();
});

onBeforeUnmount(() => {
    if (playerInstance) {
        playerInstance.destroy();
        playerInstance = null;
    }
});

watch(
    () => props.url,
    () => {
        initPlyr();
    },
);
</script>

<template>
    <div
        class="custom-plyr-wrapper aspect-video w-full overflow-hidden rounded-2xl border border-slate-200/90 bg-black shadow-md dark:border-gray-800"
    >
        <div ref="playerContainerRef" class="plyr__video-embed h-full w-full">
            <iframe
                v-if="videoId"
                :src="`https://www.youtube-nocookie.com/embed/${videoId}?origin=${origin}&iv_load_policy=3&modestbranding=1&playsinline=1&showinfo=0&rel=0&enablejsapi=1`"
                :title="title"
                allowfullscreen
                allowtransparency
                allow="autoplay"
                class="h-full w-full"
            ></iframe>
        </div>
    </div>
</template>

<style>
/* HSCStack Custom Theme for Plyr */
.custom-plyr-wrapper {
    --plyr-color-main: #6366f1; /* Indigo-500 */
    --plyr-video-background: #020617;
    --plyr-control-radius: 10px;
    --plyr-font-family: inherit;
    --plyr-font-size-small: 12px;
    --plyr-font-size-base: 13px;
}

.custom-plyr-wrapper .plyr {
    height: 100%;
    width: 100%;
    border-radius: 1rem;
}

.custom-plyr-wrapper .plyr--video {
    background: #020617;
}

.custom-plyr-wrapper .plyr__control--overlaid {
    background: rgba(99, 102, 241, 0.9);
    padding: 18px;
}

.custom-plyr-wrapper .plyr__control--overlaid:hover {
    background: #4f46e5;
    transform: scale(1.08);
}

.custom-plyr-wrapper .plyr__menu__container {
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
}
</style>
