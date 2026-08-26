<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Download,
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Maximize2,
    Minimize2,
    RotateCcw,
    User,
    Image as ImageIcon,
    LogIn,
    X,
    ExternalLink,
    Loader2,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import YouTubePlayer from '../components/YouTubePlayer.vue';

const props = defineProps({
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

const page = usePage();
const user = computed(() => page.props.auth?.user);
const showAuthModal = ref(false);

const isImageLoaded = ref(false);

watch(
    () => props.resource?.file_url,
    () => {
        isImageLoaded.value = false;
    },
);

const isDownloading = ref(false);

const handleDownload = async () => {
    if (!user.value) {
        showAuthModal.value = true;

        return;
    }

    if (!props.resource?.file_url || isDownloading.value) {
        return;
    }

    isDownloading.value = true;

    try {
        const response = await fetch(props.resource.file_url);

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const blob = await response.blob();
        const blobUrl = window.URL.createObjectURL(blob);

        // Derive file extension from url or mime type
        const ext =
            props.resource.file_url.split('.').pop()?.split('?')[0] ||
            (blob.type.includes('png')
                ? 'png'
                : blob.type.includes('jpeg') || blob.type.includes('jpg')
                  ? 'jpg'
                  : blob.type.includes('pdf')
                    ? 'pdf'
                    : 'file');

        const sanitizedTitle = (props.resource.title || 'study-material')
            .replace(/[/\\?%*:|"<>]/g, '_')
            .trim();

        const filename = sanitizedTitle.endsWith(`.${ext}`)
            ? sanitizedTitle
            : `${sanitizedTitle}.${ext}`;

        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(blobUrl);
    } catch {
        // Fallback for strict CORS configurations
        const link = document.createElement('a');
        link.href = props.resource.file_url;
        link.target = '_blank';
        link.download = props.resource.title || 'download';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } finally {
        isDownloading.value = false;
    }
};

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
        class="mx-auto flex min-h-[75vh] max-w-5xl flex-col justify-start px-3 pt-3 pb-24 sm:px-6"
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
                <!-- Watch on YouTube Action (For Video Resources) -->
                <a
                    v-if="
                        resource.resource_type === 'video' && resource.file_url
                    "
                    :href="resource.file_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-xs transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-red-900/50 dark:hover:bg-red-950/30 dark:hover:text-red-400"
                    title="Watch on YouTube"
                >
                    <ExternalLink class="h-3.5 w-3.5 stroke-[2.2]" />
                    <span class="hidden sm:inline">Watch on YouTube</span>
                </a>

                <!-- Download Button (Auth-guarded, only for downloadable files, NOT for video) -->
                <button
                    v-if="
                        resource.file_url && resource.resource_type !== 'video'
                    "
                    @click="handleDownload"
                    :disabled="isDownloading"
                    type="button"
                    class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-indigo-600 active:scale-95 disabled:cursor-not-allowed disabled:opacity-70 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                    title="Download Resource"
                >
                    <Loader2
                        v-if="isDownloading"
                        class="h-3.5 w-3.5 animate-spin"
                    />
                    <Download v-else class="h-3.5 w-3.5 stroke-[2.2]" />
                    <span class="hidden sm:inline">{{
                        isDownloading ? 'Downloading...' : 'Download'
                    }}</span>
                </button>

                <!-- Fullscreen Action (for images) -->
                <button
                    v-if="resource.resource_type === 'image'"
                    @click="toggleFullscreen"
                    type="button"
                    class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95"
                    title="View Fullscreen"
                >
                    <Maximize2 class="h-3.5 w-3.5 stroke-[2.2]" />
                    <span class="hidden sm:inline">Full Screen</span>
                </button>
            </div>
        </div>

        <!-- Pure Media Canvas -->
        <div
            v-if="resource.resource_type === 'image'"
            class="relative flex min-h-[55vh] w-full items-center justify-center sm:min-h-[70vh]"
        >
            <!-- Skeleton Loader Placeholder -->
            <div
                v-if="!isImageLoaded"
                class="absolute inset-0 flex flex-col items-center justify-center rounded-2xl border border-slate-200/80 bg-slate-100/70 dark:border-gray-800 dark:bg-gray-900/60"
            >
                <div class="flex animate-pulse flex-col items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-200/90 text-slate-400 dark:bg-gray-800 dark:text-gray-600"
                    >
                        <ImageIcon class="h-6 w-6 stroke-[1.8]" />
                    </div>
                    <div
                        class="h-2.5 w-28 rounded-full bg-slate-200/80 dark:bg-gray-800"
                    ></div>
                </div>
            </div>

            <img
                :src="resource.file_url"
                :alt="resource.title"
                @load="isImageLoaded = true"
                class="max-h-[85vh] w-auto max-w-full rounded-2xl border border-slate-200/90 bg-white object-contain shadow-sm transition-opacity duration-300 select-none dark:border-gray-800 dark:bg-gray-900"
                :class="{
                    'opacity-0': !isImageLoaded,
                    'opacity-100': isImageLoaded,
                }"
            />
        </div>

        <div v-else-if="resource.resource_type === 'video'">
            <YouTubePlayer :url="resource.file_url" :title="resource.title" />
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
                <button
                    v-if="resource.file_url"
                    @click="handleDownload"
                    type="button"
                    class="inline-flex cursor-pointer touch-manipulation items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs transition-all duration-200 hover:bg-indigo-700 active:scale-95"
                >
                    <Download class="h-4 w-4 stroke-[2.5]" />
                    Download
                </button>
            </div>
        </div>

        <!-- Author Credit & Notes (Below Media) -->
        <div
            v-if="
                resource.user?.name ||
                resource.resource_type === 'video' ||
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

            <!-- YouTube Educational Disclaimer (For Videos) with Official Legal Reference -->
            <div
                v-if="resource.resource_type === 'video'"
                class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3 text-xs leading-relaxed text-slate-600 dark:border-gray-800 dark:bg-gray-900/60 dark:text-gray-400"
            >
                <span class="font-bold text-slate-900 dark:text-gray-200"
                    >Note:</span
                >
                <p class="mt-0.5">
                    This content is hosted on YouTube by the original creator
                    and embedded for educational reference in compliance with
                    <a
                        href="https://www.youtube.com/static?template=terms"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-semibold text-indigo-600 underline underline-offset-2 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        YouTube's Terms of Service </a
                    >.
                </p>
            </div>

            <!-- Author Note (For Images & other resources if provided) -->
            <div
                v-else-if="
                    resource.content && resource.resource_type !== 'note'
                "
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
                    @click="handleDownload"
                    class="cursor-pointer rounded-full bg-white/10 p-3 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
                    title="Download Image"
                >
                    <Download class="h-5 w-5" />
                </button>

                <button
                    v-if="scale > 1"
                    @click="resetZoom"
                    class="cursor-pointer rounded-full bg-white/10 p-3 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
                    title="Reset Zoom"
                >
                    <RotateCcw class="h-5 w-5" />
                </button>

                <button
                    @click="toggleFullscreen"
                    class="cursor-pointer rounded-full bg-white/10 p-3 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95 dark:bg-gray-900/10 dark:hover:bg-gray-900/20"
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

    <!-- Minimal Sign-in Dialog for Guests (Download Auth Guard) -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showAuthModal"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs dark:bg-black/50"
            >
                <div
                    class="relative w-full max-w-xs rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        @click="showAuthModal = false"
                        class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <h3
                        class="text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        Sign in required
                    </h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                        Please sign in to download full-resolution study
                        materials.
                    </p>

                    <div class="mt-4 flex items-center gap-2">
                        <Link
                            :href="`/login?redirect=${encodeURIComponent($page.url)}`"
                            @click="showAuthModal = false"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-slate-900 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                        >
                            <LogIn class="h-3.5 w-3.5" />
                            <span>Sign in</span>
                        </Link>
                        <button
                            @click="showAuthModal = false"
                            class="cursor-pointer rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped></style>
