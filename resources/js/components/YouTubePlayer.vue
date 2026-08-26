<script setup lang="ts">
import {
    Play,
    Pause,
    Volume2,
    VolumeX,
    Maximize,
    Minimize,
    RotateCcw,
    RotateCw,
    Settings,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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
const isPlaying = ref(false);
const isMuted = ref(false);
const isFullscreen = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const playbackRate = ref(1);
const showControls = ref(true);
const showSpeedMenu = ref(false);
const isBuffering = ref(false);

const containerRef = ref<HTMLElement | null>(null);
const progressBarRef = ref<HTMLElement | null>(null);

let player: any = null;
let timeUpdateInterval: any = null;
let controlsTimeout: any = null;
const playerId = `yt-player-${Math.random().toString(36).substring(2, 9)}`;

const speedOptions = [0.75, 1.0, 1.25, 1.5, 1.75, 2.0];

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

const formatTime = (seconds: number): string => {
    if (!seconds || isNaN(seconds)) {
return '0:00';
}

    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);

    return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
};

const progressPercent = computed(() => {
    if (!duration.value) {
return 0;
}

    return Math.min(
        100,
        Math.max(0, (currentTime.value / duration.value) * 100),
    );
});

const loadYouTubeApi = (): Promise<void> => {
    return new Promise((resolve) => {
        if ((window as any).YT && (window as any).YT.Player) {
            resolve();

            return;
        }

        const existingScript = document.getElementById('youtube-iframe-api');

        if (!existingScript) {
            const tag = document.createElement('script');
            tag.id = 'youtube-iframe-api';
            tag.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(tag);
        }

        const prevCallback = (window as any).onYouTubeIframeAPIReady;
        (window as any).onYouTubeIframeAPIReady = () => {
            if (prevCallback) {
prevCallback();
}

            resolve();
        };

        // If API is already loaded in background
        const checkInterval = setInterval(() => {
            if ((window as any).YT && (window as any).YT.Player) {
                clearInterval(checkInterval);
                resolve();
            }
        }, 100);
    });
};

const initPlayer = async () => {
    if (!videoId.value) {
return;
}

    await loadYouTubeApi();

    if (player) {
        try {
            player.destroy();
        } catch {
            // Ignore
        }
    }

    player = new (window as any).YT.Player(playerId, {
        videoId: videoId.value,
        playerVars: {
            autoplay: 1,
            controls: 0,
            modestbranding: 1,
            rel: 0,
            iv_load_policy: 3,
            playsinline: 1,
            disablekb: 1,
            fs: 0,
            origin: window.location.origin,
        },
        events: {
            onReady: (event: any) => {
                duration.value = event.target.getDuration() || 0;
                event.target.playVideo();
                startTrackingTime();
            },
            onStateChange: (event: any) => {
                const state = event.data;

                // YT.PlayerState: -1 unstarted, 0 ended, 1 playing, 2 paused, 3 buffering, 5 cued
                if (state === 1) {
                    isPlaying.value = true;
                    isBuffering.value = false;
                    startTrackingTime();
                } else if (state === 2) {
                    isPlaying.value = false;
                    isBuffering.value = false;
                } else if (state === 3) {
                    isBuffering.value = true;
                } else if (state === 0) {
                    isPlaying.value = false;
                    isBuffering.value = false;
                    showControls.value = true;
                }
            },
        },
    });
};

const startTrackingTime = () => {
    if (timeUpdateInterval) {
clearInterval(timeUpdateInterval);
}

    timeUpdateInterval = setInterval(() => {
        if (player && typeof player.getCurrentTime === 'function') {
            currentTime.value = player.getCurrentTime() || 0;

            if (!duration.value && typeof player.getDuration === 'function') {
                duration.value = player.getDuration() || 0;
            }
        }
    }, 250);
};

const handleStart = () => {
    isStarted.value = true;
    initPlayer();
};

const togglePlay = () => {
    if (!player) {
return;
}

    if (isPlaying.value) {
        player.pauseVideo();
    } else {
        player.playVideo();
    }
};

const toggleMute = () => {
    if (!player) {
return;
}

    if (isMuted.value) {
        player.unMute();
        isMuted.value = false;
    } else {
        player.mute();
        isMuted.value = true;
    }
};

const seekRelative = (seconds: number) => {
    if (!player || typeof player.seekTo !== 'function') {
return;
}

    const newTime = Math.max(
        0,
        Math.min(duration.value, currentTime.value + seconds),
    );
    player.seekTo(newTime, true);
    currentTime.value = newTime;
    triggerControls();
};

const handleProgressClick = (e: MouseEvent) => {
    if (!progressBarRef.value || !player || !duration.value) {
return;
}

    const rect = progressBarRef.value.getBoundingClientRect();
    const clickX = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
    const newPercent = clickX / rect.width;
    const newTime = newPercent * duration.value;
    player.seekTo(newTime, true);
    currentTime.value = newTime;
};

const setSpeed = (speed: number) => {
    if (!player || typeof player.setPlaybackRate !== 'function') {
return;
}

    player.setPlaybackRate(speed);
    playbackRate.value = speed;
    showSpeedMenu.value = false;
};

const toggleFullscreen = () => {
    if (!containerRef.value) {
return;
}

    if (!document.fullscreenElement) {
        containerRef.value.requestFullscreen().catch(() => {
            // Fallback
        });
        isFullscreen.value = true;
    } else {
        document.exitFullscreen().catch(() => {
            // Fallback
        });
        isFullscreen.value = false;
    }
};

const triggerControls = () => {
    showControls.value = true;

    if (controlsTimeout) {
clearTimeout(controlsTimeout);
}

    if (isPlaying.value) {
        controlsTimeout = setTimeout(() => {
            if (!showSpeedMenu.value) {
                showControls.value = false;
            }
        }, 3000);
    }
};

const handleMouseLeave = () => {
    if (isPlaying.value && !showSpeedMenu.value) {
        showControls.value = false;
    }
};

onMounted(() => {
    const handleFullscreenChange = () => {
        isFullscreen.value = !!document.fullscreenElement;
    };
    document.addEventListener('fullscreenchange', handleFullscreenChange);
});

onBeforeUnmount(() => {
    if (timeUpdateInterval) {
clearInterval(timeUpdateInterval);
}

    if (controlsTimeout) {
clearTimeout(controlsTimeout);
}

    if (player) {
        try {
            player.destroy();
        } catch {
            // Ignore
        }
    }
});

watch(
    () => props.url,
    () => {
        isStarted.value = false;
        isPlaying.value = false;
        currentTime.value = 0;
        duration.value = 0;

        if (player) {
            try {
                player.destroy();
            } catch {
                // Ignore
            }

            player = null;
        }
    },
);
</script>

<template>
    <div
        ref="containerRef"
        class="group relative aspect-video w-full overflow-hidden rounded-2xl border border-slate-200/90 bg-slate-950 shadow-md select-none dark:border-gray-800"
        @mousemove="triggerControls"
        @mouseleave="handleMouseLeave"
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

        <!-- YouTube Video IFrame Container (Controlled via API) -->
        <div
            class="absolute inset-0 h-full w-full"
            :class="{ invisible: !isStarted }"
        >
            <div :id="playerId" class="h-full w-full"></div>
        </div>

        <!-- Clickable Play/Pause Overlay over Video -->
        <div
            v-if="isStarted"
            class="absolute inset-0 z-10 cursor-pointer"
            @click="togglePlay"
        ></div>

        <!-- Sleek Custom Native Controls Skin -->
        <div
            v-if="isStarted"
            class="absolute inset-x-0 bottom-0 z-20 bg-gradient-to-t from-slate-950/95 via-slate-950/60 to-transparent p-3 pt-8 transition-opacity duration-300 sm:p-4"
            :class="{
                'pointer-events-auto opacity-100': showControls || !isPlaying,
                'pointer-events-none opacity-0': !showControls && isPlaying,
            }"
            @click.stop
        >
            <!-- Custom Interactive Scrub Bar -->
            <div
                ref="progressBarRef"
                class="group/bar relative mb-2.5 h-1.5 w-full cursor-pointer rounded-full bg-white/20 transition-all hover:h-2.5"
                @click="handleProgressClick"
            >
                <!-- Filled Track -->
                <div
                    class="h-full rounded-full bg-indigo-500 transition-[width] duration-75 ease-linear"
                    :style="{ width: `${progressPercent}%` }"
                ></div>

                <!-- Scrub Handle Pin -->
                <div
                    class="absolute top-1/2 -ml-1.5 h-3.5 w-3.5 -translate-y-1/2 rounded-full bg-white opacity-0 shadow-md transition-opacity group-hover/bar:opacity-100"
                    :style="{ left: `${progressPercent}%` }"
                ></div>
            </div>

            <!-- Bottom Controls Bar -->
            <div class="flex items-center justify-between text-white">
                <!-- Left Controls: Play/Pause, Rewind/Forward, Time -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <button
                        @click="togglePlay"
                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-white/90 transition hover:bg-white/15 hover:text-white"
                        :title="isPlaying ? 'Pause' : 'Play'"
                    >
                        <Pause v-if="isPlaying" class="h-4 w-4 fill-current" />
                        <Play v-else class="ml-0.5 h-4 w-4 fill-current" />
                    </button>

                    <button
                        @click="seekRelative(-5)"
                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-white/80 transition hover:bg-white/15 hover:text-white"
                        title="Rewind 5s"
                    >
                        <RotateCcw class="h-4 w-4" />
                    </button>

                    <button
                        @click="seekRelative(5)"
                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-white/80 transition hover:bg-white/15 hover:text-white"
                        title="Forward 5s"
                    >
                        <RotateCw class="h-4 w-4" />
                    </button>

                    <!-- Volume Toggle -->
                    <button
                        @click="toggleMute"
                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-white/80 transition hover:bg-white/15 hover:text-white"
                        :title="isMuted ? 'Unmute' : 'Mute'"
                    >
                        <VolumeX v-if="isMuted" class="h-4 w-4" />
                        <Volume2 v-else class="h-4 w-4" />
                    </button>

                    <!-- Timestamp -->
                    <span
                        class="text-[11px] font-medium tracking-tight text-white/80 select-none sm:text-xs"
                    >
                        {{ formatTime(currentTime) }} /
                        {{ formatTime(duration) }}
                    </span>
                </div>

                <!-- Right Controls: Speed Selector, Fullscreen -->
                <div class="relative flex items-center gap-1.5 sm:gap-2">
                    <!-- Playback Speed Menu -->
                    <div class="relative">
                        <button
                            @click="showSpeedMenu = !showSpeedMenu"
                            class="inline-flex h-8 cursor-pointer items-center gap-1 rounded-lg px-2 text-xs font-semibold text-white/90 transition hover:bg-white/15 hover:text-white"
                            title="Playback Speed"
                        >
                            <Settings class="h-3.5 w-3.5" />
                            <span>{{ playbackRate }}x</span>
                        </button>

                        <!-- Speed Popover -->
                        <div
                            v-if="showSpeedMenu"
                            class="absolute right-0 bottom-full mb-2 w-28 rounded-xl border border-white/10 bg-slate-900/95 p-1 shadow-2xl backdrop-blur-md"
                        >
                            <div
                                class="px-2 py-1 text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                            >
                                Speed
                            </div>
                            <button
                                v-for="speed in speedOptions"
                                :key="speed"
                                @click="setSpeed(speed)"
                                class="flex w-full cursor-pointer items-center justify-between rounded-lg px-2 py-1.5 text-xs text-slate-300 transition hover:bg-white/10 hover:text-white"
                                :class="{
                                    'font-bold text-indigo-400':
                                        playbackRate === speed,
                                }"
                            >
                                <span>{{ speed }}x</span>
                                <span
                                    v-if="speed === 1.0"
                                    class="text-[10px] text-slate-500"
                                    >Normal</span
                                >
                            </button>
                        </div>
                    </div>

                    <!-- Fullscreen Toggle -->
                    <button
                        @click="toggleFullscreen"
                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-white/90 transition hover:bg-white/15 hover:text-white"
                        :title="
                            isFullscreen
                                ? 'Exit Fullscreen'
                                : 'Enter Fullscreen'
                        "
                    >
                        <Minimize v-if="isFullscreen" class="h-4 w-4" />
                        <Maximize v-else class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
