<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Download,
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Maximize2,
    Minimize2,
    RotateCcw,
    User,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

defineProps({
    resource: {
        type: Object,
        required: true,
    },
    previousResourceId: {
        type: Number,
        default: null,
    },
    nextResourceId: {
        type: Number,
        default: null,
    },
});

const isFullscreen = ref(false);

const scale = ref(1);
const translateX = ref(0);
const translateY = ref(0);
const isDragging = ref(false);

let startX = 0;
let startY = 0;
let initialScale = 1;
let startTouchDistance = 0;

const handleBack = () => {
    if (typeof window !== 'undefined') {
        window.history.back();
    }
};

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;

    if (!isFullscreen.value) {
        resetZoom();
    }
};

const resetZoom = () => {
    scale.value = 1;
    translateX.value = 0;
    translateY.value = 0;
};

const getTouchDistance = (e: TouchEvent) => {
    return Math.hypot(
        e.touches[0].clientX - e.touches[1].clientX,
        e.touches[0].clientY - e.touches[1].clientY,
    );
};

const handlePointerDown = (e: MouseEvent | TouchEvent) => {
    if ('touches' in e && e.touches.length === 2) {
        isDragging.value = false;
        initialScale = scale.value;
        startTouchDistance = getTouchDistance(e);

        return;
    }

    isDragging.value = true;
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;

    startX = clientX - translateX.value;
    startY = clientY - translateY.value;
};

const handlePointerMove = (e: MouseEvent | TouchEvent) => {
    if ('touches' in e && e.touches.length === 2) {
        e.preventDefault();
        const currentDistance = getTouchDistance(e);
        const newScale = initialScale * (currentDistance / startTouchDistance);
        scale.value = Math.min(Math.max(newScale, 1), 5);

        return;
    }

    if (!isDragging.value || scale.value === 1) {
        return;
    }

    e.preventDefault();

    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;

    translateX.value = clientX - startX;
    translateY.value = clientY - startY;
};

const handlePointerUp = () => {
    isDragging.value = false;

    if (scale.value < 1) {
        resetZoom();
    }
};

const handleWheel = (e: WheelEvent) => {
    e.preventDefault();
    const zoomIntensity = 0.1;
    const delta = e.deltaY < 0 ? 1 : -1;
    const newScale = scale.value + delta * zoomIntensity;
    scale.value = Math.min(Math.max(newScale, 1), 5);

    if (scale.value === 1) {
        resetZoom();
    }
};

watch(isFullscreen, (val) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = val ? 'hidden' : '';
    }
});

const parseYoutubeUrl = (url) => {
    try {
        const parsed = new URL(url);

        let videoId = null;

        if (parsed.hostname.includes('youtube.com')) {
            if (parsed.pathname === '/watch') {
                videoId = parsed.searchParams.get('v');
            } else if (parsed.pathname.startsWith('/embed/')) {
                videoId = parsed.pathname.split('/embed/')[1];
            } else if (parsed.pathname.startsWith('/shorts/')) {
                videoId = parsed.pathname.split('/shorts/')[1];
            }
        }

        if (parsed.hostname === 'youtu.be') {
            videoId = parsed.pathname.slice(1);
        }

        if (!videoId) {
            return null;
        }

        return `https://www.youtube.com/embed/${videoId}`;
    } catch {
        return 'https://www.youtube.com/embed/NpEaa2P7qZI';
    }
};
</script>

<template>
    <Head>
        <title>{{ resource.title }}</title>
        <meta
            name="description"
            :content="`Study material: ${resource.title} (${resource.type}) on HSCStack - Open Learning Platform.`"
        />
        <meta property="og:title" :content="`${resource.title} - HSCStack`" />
        <meta
            property="og:description"
            :content="`Study material: ${resource.title} (${resource.type}) on HSCStack.`"
        />
    </Head>

    <div
        class="mx-auto flex min-h-[75vh] max-w-4xl flex-col justify-start px-3 pt-3 pb-24 sm:px-6"
    >
        <!-- Flat, Minimal Media Header -->
        <div class="mb-3 flex items-center justify-between gap-3">
            <!-- Left: Back Button + Title -->
            <div class="flex min-w-0 items-center gap-2.5">
                <button
                    @click="handleBack"
                    type="button"
                    aria-label="Go back"
                    class="group flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-xs transition hover:border-slate-300 hover:text-indigo-600 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-700 dark:hover:text-indigo-400"
                    title="Back"
                >
                    <ArrowLeft
                        class="h-4 w-4 transition-transform group-hover:-translate-x-0.5"
                    />
                </button>

                <h1
                    class="truncate text-sm font-bold text-slate-900 sm:text-base dark:text-gray-100"
                    :title="resource.title"
                >
                    {{ resource.title }}
                </h1>
            </div>

            <!-- Right: Action Buttons -->
            <div class="flex shrink-0 items-center gap-2">
                <a
                    v-if="resource.file_url"
                    :href="resource.file_url"
                    download
                    target="_blank"
                    class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-indigo-600 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                    title="Download Resource"
                >
                    <Download class="h-3.5 w-3.5 stroke-[2.2]" />
                    <span class="hidden sm:inline">Download</span>
                </a>

                <button
                    v-if="resource.resource_type === 'image'"
                    @click="toggleFullscreen"
                    class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95"
                    title="View Fullscreen"
                >
                    <Maximize2 class="h-3.5 w-3.5 stroke-[2.2]" />
                    <span class="hidden sm:inline">Full Screen</span>
                </button>
            </div>
        </div>

        <!-- Pure Media Canvas -->
        <div v-if="resource.resource_type === 'image'">
            <div
                class="flex justify-center overflow-hidden rounded-2xl border border-slate-200/90 bg-slate-100/50 p-1.5 shadow-xs sm:p-3 dark:border-gray-800 dark:bg-gray-900/50"
            >
                <img
                    :src="resource.file_url"
                    :alt="resource.title"
                    class="max-h-[80vh] w-auto rounded-xl object-contain shadow-xs select-none"
                />
            </div>
        </div>

        <div v-else-if="resource.resource_type === 'video'">
            <div
                class="relative aspect-video w-full overflow-hidden rounded-2xl border border-slate-200 bg-black shadow-sm dark:border-gray-800"
            >
                <iframe
                    :src="parseYoutubeUrl(resource.file_url)"
                    :title="resource.title"
                    class="absolute inset-0 h-full w-full"
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
        </div>

        <div v-else-if="resource.resource_type === 'note'">
            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-8 dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="prose max-w-none text-sm leading-relaxed whitespace-pre-line text-slate-800 sm:text-base dark:text-gray-200"
                >
                    {{ resource.content }}
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-xs dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400"
            >
                <AlertCircle class="h-6 w-6 stroke-[2.2]" />
            </div>

            <h3
                class="mt-4 text-base font-bold text-slate-900 dark:text-gray-100"
            >
                Unsupported Preview:
                <span class="text-indigo-600 capitalize dark:text-indigo-400">{{
                    resource.resource_type
                }}</span>
            </h3>
            <p
                class="mx-auto mt-2 max-w-sm text-xs font-medium text-slate-500 sm:text-sm dark:text-gray-400"
            >
                The file can't be shown here. Please download.
            </p>

            <div class="mt-6 flex justify-center">
                <a
                    v-if="resource.file_url"
                    :href="resource.file_url"
                    download
                    target="_blank"
                    class="inline-flex touch-manipulation items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition-all duration-200 hover:bg-indigo-700 active:scale-95"
                >
                    <Download class="h-4 w-4 stroke-[2.5]" />
                    Download
                </a>
            </div>
        </div>

        <!-- Author Credit & Notes (Below Media) -->
        <div
            v-if="
                resource.user?.name ||
                (resource.content && resource.resource_type !== 'note')
            "
            class="mt-3.5 space-y-2.5"
        >
            <div
                v-if="resource.user?.name"
                class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-gray-400"
            >
                <Link
                    :href="`/about-us#${resource.user.id}`"
                    class="group inline-flex items-center gap-1.5 transition hover:text-indigo-600 dark:hover:text-indigo-400"
                >
                    <User
                        class="h-3.5 w-3.5 text-slate-400 group-hover:text-indigo-600 dark:text-gray-500"
                    />
                    <span>
                        Shared by
                        <span
                            class="font-bold text-slate-700 group-hover:underline dark:text-gray-300"
                        >
                            {{ resource.user.name }}
                        </span>
                    </span>
                </Link>
            </div>

            <!-- Author Note (if provided) -->
            <div
                v-if="resource.content && resource.resource_type !== 'note'"
                class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3 text-xs leading-relaxed text-slate-600 dark:border-gray-800 dark:bg-gray-900/60 dark:text-gray-400"
            >
                <span class="font-bold text-slate-900 dark:text-gray-200"
                    >Note:</span
                >
                <p class="mt-1 whitespace-pre-line">{{ resource.content }}</p>
            </div>
        </div>
    </div>

    <!-- Floating Bottom Navigation (Centered pill, no overlap with bottom-right floating share bar) -->
    <div
        v-if="previousResourceId || nextResourceId"
        class="pointer-events-none fixed inset-x-0 bottom-6 z-30 flex justify-center px-4"
    >
        <div
            class="pointer-events-auto flex items-center gap-1 rounded-full border border-slate-200/90 bg-white/95 p-1.5 shadow-xl backdrop-blur-xl dark:border-gray-800 dark:bg-gray-900/95"
        >
            <Link
                v-if="previousResourceId"
                :href="`/resources/${previousResourceId}`"
                replace
                class="inline-flex h-8 items-center gap-1.5 rounded-full px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-indigo-600 active:scale-95 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
            >
                <ArrowLeft class="h-3.5 w-3.5" />
                <span>Prev</span>
            </Link>
            <span
                v-else
                class="inline-flex h-8 items-center px-3 text-xs font-medium text-slate-300 select-none dark:text-gray-600"
            >
                First
            </span>

            <span class="h-3.5 w-px bg-slate-200 dark:bg-gray-700"></span>

            <Link
                v-if="nextResourceId"
                :href="`/resources/${nextResourceId}`"
                replace
                class="inline-flex h-8 items-center gap-1.5 rounded-full px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-indigo-600 active:scale-95 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
            >
                <span>Next</span>
                <ArrowRight class="h-3.5 w-3.5" />
            </Link>
            <span
                v-else
                class="inline-flex h-8 items-center px-3 text-xs font-medium text-slate-300 select-none dark:text-gray-600"
            >
                Last
            </span>
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="isFullscreen"
            class="fixed inset-0 z-50 flex touch-none items-center justify-center bg-slate-950/95 backdrop-blur-sm select-none"
            @wheel="handleWheel"
            @mousedown="handlePointerDown"
            @mousemove="handlePointerMove"
            @mouseup="handlePointerUp"
            @mouseleave="handlePointerUp"
            @touchstart="handlePointerDown"
            @touchmove="handlePointerMove"
            @touchend="handlePointerUp"
        >
            <div class="fixed top-4 right-4 z-50 flex items-center gap-2">
                <div
                    class="hidden items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-[11px] font-medium text-slate-300 backdrop-blur-md sm:flex dark:bg-gray-900/10"
                >
                    <span>Pinch / Scroll to Zoom</span>
                    <span class="text-slate-500">•</span>
                    <span>Drag to Pan</span>
                </div>

                <button
                    v-if="scale > 1"
                    @click="resetZoom"
                    class="rounded-full bg-white/10 p-3 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
                    title="Reset Zoom"
                >
                    <RotateCcw class="h-5 w-5" />
                </button>

                <button
                    @click="toggleFullscreen"
                    class="rounded-full bg-white/10 p-3 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
                    title="Exit Fullscreen"
                >
                    <Minimize2 class="h-5 w-5" />
                </button>
            </div>

            <img
                :src="resource.file_url"
                :alt="resource.title"
                class="pointer-events-none max-h-[90vh] max-w-[90vw] rounded object-contain shadow-2xl transition-transform duration-75 ease-out"
                :style="{
                    transform: `translate(${translateX}px, ${translateY}px) scale(${scale})`,
                }"
            />
        </div>
    </Teleport>
</template>

<style scoped></style>
