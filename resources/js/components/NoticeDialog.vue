<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Volume2, VolumeX, X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    notice: { type: Object, default: null },
});

const isVisible = ref(false);
const isMuted = ref(false);
const iframeRef = ref<HTMLIFrameElement | null>(null);

const postToIframe = (func: string, args: any[] = []) => {
    if (iframeRef.value?.contentWindow) {
        iframeRef.value.contentWindow.postMessage(
            JSON.stringify({ event: 'command', func, args }),
            '*',
        );
    }
};

const handleIframeLoad = () => {
    // Set volume to 65% and unmute by default
    postToIframe('setVolume', [65]);
    postToIframe('unMute');
};

const toggleSound = () => {
    isMuted.value = !isMuted.value;

    if (isMuted.value) {
        postToIframe('mute');
    } else {
        postToIframe('setVolume', [65]);
        postToIframe('unMute');
    }
};

const isInternalLink = (url?: string | null) => {
    if (!url) {
        return false;
    }

    const trimmed = url.trim();

    return trimmed.startsWith('/') && !trimmed.startsWith('//');
};

const youtubeVideoId = computed(() => {
    const url = props.notice?.video_url;

    if (!url) {
        return null;
    }

    try {
        const parsed = new URL(url);

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
        const match = url.match(
            /(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/,
        );

        return match ? match[1] : null;
    }
});

const isShortsOrReel = computed(() => {
    const url = props.notice?.video_url;

    if (!url) {
        return false;
    }

    return url.includes('/shorts/') || url.includes('reel');
});

const getStorageKey = () => {
    return props.notice?.updated_at
        ? `session_notice_${props.notice.updated_at}`
        : 'session_notice_dismissed';
};

const checkVisibility = () => {
    if (!props.notice) {
        return false;
    }

    return sessionStorage.getItem(getStorageKey()) !== 'true';
};

onMounted(() => {
    isVisible.value = checkVisibility();
});

watch(
    () => props.notice,
    () => {
        isVisible.value = checkVisibility();
    },
);

const close = () => {
    if (props.notice) {
        sessionStorage.setItem(getStorageKey(), 'true');
    }

    isVisible.value = false;
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isVisible && notice"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-slate-950/20 backdrop-blur-sm dark:bg-black/50"
                    @click="close"
                ></div>

                <div
                    class="relative w-full overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/5 transition-all dark:bg-gray-900 dark:ring-gray-700/50"
                    :class="
                        isShortsOrReel && youtubeVideoId
                            ? 'max-w-[340px] sm:max-w-[360px]'
                            : 'max-w-md'
                    "
                    role="alertdialog"
                    aria-modal="true"
                    :aria-labelledby="
                        notice.title ? 'notice-dialog-title' : undefined
                    "
                    :aria-label="notice.title ? undefined : 'Notice'"
                >
                    <!-- Top Floating Action Controls: Sound Toggle & Close -->
                    <div
                        class="absolute top-3 right-3 z-20 flex items-center gap-2"
                    >
                        <!-- Mute / Unmute Button (Shown when video is active) -->
                        <button
                            v-if="youtubeVideoId"
                            type="button"
                            @click="toggleSound"
                            class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-black/60 text-white shadow-md backdrop-blur-md transition hover:scale-105 hover:bg-black/80 active:scale-95"
                            :title="
                                isMuted
                                    ? 'শব্দ শুনুন (Unmute)'
                                    : 'শব্দ বন্ধ করুন (Mute)'
                            "
                            :aria-label="
                                isMuted ? 'Unmute video' : 'Mute video'
                            "
                        >
                            <VolumeX
                                v-if="isMuted"
                                class="h-4.5 w-4.5 text-rose-300"
                            />
                            <Volume2
                                v-else
                                class="h-4.5 w-4.5 animate-pulse text-emerald-300"
                            />
                        </button>

                        <!-- Close Button -->
                        <button
                            @click="close"
                            class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-black/60 text-white shadow-md backdrop-blur-md transition hover:scale-105 hover:bg-black/80 active:scale-95"
                            aria-label="Close notice"
                        >
                            <X class="h-4.5 w-4.5 stroke-[2.5]" />
                        </button>
                    </div>

                    <!-- Media Section: YouTube Reel/Video or Cover Image -->
                    <div
                        v-if="youtubeVideoId"
                        class="relative flex w-full items-center justify-center overflow-hidden bg-black"
                        :class="
                            isShortsOrReel
                                ? 'aspect-[9/16] max-h-[78vh] w-full'
                                : 'aspect-video w-full'
                        "
                    >
                        <iframe
                            ref="iframeRef"
                            :src="`https://www.youtube-nocookie.com/embed/${youtubeVideoId}?enablejsapi=1&autoplay=1&mute=0&loop=1&playlist=${youtubeVideoId}&controls=0&modestbranding=1&rel=0&playsinline=1&iv_load_policy=3&fs=0&disablekb=1`"
                            title="Notice Video Tutorial"
                            class="h-[108%] w-[108%] -translate-x-[4%] -translate-y-[4%] border-0"
                            allow="
                                accelerometer;
                                autoplay;
                                clipboard-write;
                                encrypted-media;
                                gyroscope;
                                picture-in-picture;
                            "
                            allowfullscreen
                            @load="handleIframeLoad"
                        ></iframe>
                    </div>

                    <img
                        v-else-if="notice.image"
                        :src="notice.image"
                        class="h-40 w-full object-cover"
                        alt=""
                    />

                    <!-- Details Section (Shown only if title, message, or button exists) -->
                    <div
                        v-if="
                            notice.title ||
                            notice.message ||
                            (notice.show_button && notice.button_link)
                        "
                        class="p-5 sm:p-6"
                    >
                        <h2
                            v-if="notice.title"
                            id="notice-dialog-title"
                            class="text-base font-bold text-slate-900 sm:text-lg dark:text-gray-100"
                        >
                            {{ notice.title }}
                        </h2>
                        <p
                            v-if="notice.message"
                            class="mt-1.5 text-xs leading-relaxed whitespace-pre-line text-slate-600 sm:text-sm dark:text-gray-400"
                        >
                            {{ notice.message }}
                        </p>

                        <div
                            v-if="notice.show_button && notice.button_link"
                            class="mt-4 flex gap-3"
                        >
                            <Link
                                v-if="isInternalLink(notice.button_link)"
                                :href="notice.button_link"
                                @click="close"
                                class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-center text-xs font-semibold text-white transition hover:bg-slate-800 sm:text-sm dark:bg-indigo-600 dark:hover:bg-indigo-500"
                            >
                                {{ notice.button_title || 'Learn more' }}
                            </Link>

                            <a
                                v-else
                                :href="notice.button_link"
                                target="_blank"
                                rel="noopener noreferrer"
                                @click="close"
                                class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-center text-xs font-semibold text-white transition hover:bg-slate-800 sm:text-sm dark:bg-indigo-600 dark:hover:bg-indigo-500"
                            >
                                {{ notice.button_title || 'Learn more' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
