<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    FileText,
    Image as ImageIcon,
    Download,
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Maximize2,
    Minimize2,
    RotateCcw,
    User,
    FilePlay,
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
        class="mx-auto flex min-h-[75vh] max-w-4xl flex-col justify-start px-3 pt-2 pb-24 sm:px-6 sm:pt-3"
    >
        <div
            class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Sleek Minimal Content-First Toolbar -->
            <div
                class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/80 px-3 py-2 sm:px-4 sm:py-2.5 dark:border-gray-800 dark:bg-gray-900/90"
            >
                <!-- Left: Back Button + Type Badge + Compact Title -->
                <div class="flex min-w-0 items-center gap-2 sm:gap-2.5">
                    <button
                        @click="handleBack"
                        type="button"
                        aria-label="Go back"
                        class="group flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-xs transition hover:border-slate-300 hover:text-indigo-600 active:scale-95 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-indigo-400"
                        title="Back"
                    >
                        <ArrowLeft
                            class="h-4 w-4 transition-transform group-hover:-translate-x-0.5"
                        />
                    </button>

                    <span
                        class="inline-flex shrink-0 items-center gap-1 rounded-md bg-slate-200/70 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-slate-700 uppercase dark:bg-gray-800 dark:text-gray-300"
                    >
                        <FileText
                            v-if="resource.resource_type === 'note'"
                            class="h-3 w-3"
                        />
                        <ImageIcon
                            v-else-if="resource.resource_type === 'image'"
                            class="h-3 w-3"
                        />
                        <FilePlay
                            v-else-if="resource.resource_type === 'video'"
                            class="h-3 w-3"
                        />
                        <Download v-else class="h-3 w-3" />
                        <span>{{ resource.resource_type }}</span>
                    </span>

                    <h1
                        class="truncate text-xs font-bold text-slate-900 sm:text-sm dark:text-gray-100"
                        :title="resource.title"
                    >
                        {{ resource.title }}
                    </h1>
                </div>

                <!-- Right: Author Credit (desktop) + Action Buttons -->
                <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                    <Link
                        v-if="resource.user?.name"
                        :href="`/about-us#${resource.user.id}`"
                        class="hidden items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-slate-500 transition hover:bg-slate-200/60 hover:text-indigo-600 md:inline-flex dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                        :title="`Shared by ${resource.user.name}`"
                    >
                        <User class="h-3.5 w-3.5 text-slate-400" />
                        <span class="max-w-[120px] truncate">{{
                            resource.user.name
                        }}</span>
                    </Link>

                    <a
                        v-if="resource.file_url"
                        :href="resource.file_url"
                        download
                        target="_blank"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-indigo-600 active:scale-95 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-indigo-400"
                        title="Download Resource"
                    >
                        <Download class="h-3.5 w-3.5 stroke-[2.2]" />
                        <span class="hidden sm:inline">Download</span>
                    </a>

                    <button
                        v-if="resource.resource_type === 'image'"
                        @click="toggleFullscreen"
                        class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95"
                        title="View Fullscreen"
                    >
                        <Maximize2 class="h-3.5 w-3.5 stroke-[2.2]" />
                        <span class="hidden sm:inline">Full Screen</span>
                    </button>
                </div>
            </div>

            <div v-if="resource.resource_type === 'note'" class="p-4 sm:p-6">
                <div
                    class="prose max-w-none text-sm leading-relaxed font-medium text-slate-700 sm:text-base dark:text-gray-300"
                >
                    <h3
                        class="mb-2 text-xs font-black tracking-wider text-slate-400 uppercase dark:text-gray-500"
                    >
                        Note:
                    </h3>
                    <p
                        class="whitespace-pre-line selection:bg-indigo-100 selection:text-indigo-900 dark:selection:bg-indigo-500/30 dark:selection:text-indigo-300"
                    >
                        {{ resource.content }}
                    </p>
                </div>
            </div>

            <div v-else-if="resource.resource_type === 'image'">
                <div
                    v-if="resource.content"
                    class="border-b border-slate-100 bg-white p-3.5 sm:p-5 dark:border-gray-800 dark:bg-gray-900"
                >
                    <div
                        class="prose max-w-none text-xs leading-relaxed font-medium text-slate-700 sm:text-sm dark:text-gray-300"
                    >
                        <h3
                            class="mb-1 text-[11px] font-black tracking-wider text-slate-400 uppercase dark:text-gray-500"
                        >
                            Note:
                        </h3>
                        <p
                            class="whitespace-pre-line selection:bg-indigo-100 selection:text-indigo-900 dark:selection:bg-indigo-500/30 dark:selection:text-indigo-300"
                        >
                            {{ resource.content }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex justify-center bg-slate-950/5 p-2 sm:p-4 dark:bg-white/10"
                >
                    <div
                        class="relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs transition-shadow duration-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-900"
                    >
                        <img
                            :src="resource.file_url"
                            :alt="resource.title"
                            class="max-h-[78vh] w-auto object-contain select-none sm:max-h-[82vh]"
                        />
                    </div>
                </div>
            </div>
            <div v-else-if="resource.resource_type === 'video'">
                <div
                    v-if="resource.content"
                    class="border-b border-slate-100 bg-white p-3.5 sm:p-5 dark:border-gray-800 dark:bg-gray-900"
                >
                    <div
                        class="prose max-w-none text-xs leading-relaxed font-medium text-slate-700 sm:text-sm dark:text-gray-300"
                    >
                        <h3
                            class="mb-1 text-[11px] font-black tracking-wider text-slate-400 uppercase dark:text-gray-500"
                        >
                            Note:
                        </h3>
                        <p
                            class="whitespace-pre-line selection:bg-indigo-100 selection:text-indigo-900 dark:selection:bg-indigo-500/30 dark:selection:text-indigo-300"
                        >
                            This content is hosted on YouTube by the original
                            creator. We have embedded it here for educational
                            reference only.
                        </p>
                    </div>
                </div>

                <div class="bg-slate-950/5 p-2 sm:p-4 dark:bg-white/10">
                    <div
                        class="relative aspect-video w-full overflow-hidden rounded-xl border border-slate-200 bg-black shadow-sm dark:border-gray-700"
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
            </div>
            <div v-else class="p-6 text-center sm:p-10">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400"
                >
                    <AlertCircle class="h-6 w-6 stroke-[2.2]" />
                </div>

                <h3
                    class="mt-4 text-base font-bold text-slate-900 dark:text-gray-100"
                >
                    Unsupported Preview:
                    <span
                        class="text-indigo-600 capitalize dark:text-indigo-400"
                        >{{ resource.resource_type }}</span
                    >
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
                        class="inline-flex touch-manipulation items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-indigo-700 active:scale-[0.98]"
                    >
                        <Download class="h-4 w-4 stroke-[2.5]" />
                        Download
                    </a>
                    <div
                        v-else
                        class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500"
                    >
                        No download target generated for this asset.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Friendly Sticky Floating Navigation Strip -->
    <div
        class="pointer-events-none fixed inset-x-0 bottom-0 z-40 bg-gradient-to-t from-slate-900/10 via-slate-900/5 to-transparent pt-10 pb-6"
    >
        <div class="pointer-events-auto mx-auto max-w-4xl px-4 sm:px-6">
            <div
                class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-white/90 p-3 shadow-xl backdrop-blur-md dark:border-gray-700/80 dark:bg-gray-900/90"
            >
                <div>
                    <Link
                        v-if="previousResourceId"
                        :href="`/resources/${previousResourceId}`"
                        replace
                        class="group inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:text-indigo-600 active:scale-[0.97] dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                    >
                        <ArrowLeft
                            class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5"
                        />
                        <span class="xs:inline hidden">Previous Page</span>
                        <span class="xs:hidden">Prev Page</span>
                    </Link>
                    <span
                        v-else
                        class="inline-flex h-10 items-center px-4 text-xs font-bold text-slate-300 select-none dark:text-gray-600"
                        >First Page</span
                    >
                </div>

                <div
                    class="hidden text-[11px] font-bold tracking-wider text-slate-400 uppercase select-none sm:block dark:text-gray-500"
                >
                    Quick Navigation
                </div>

                <div>
                    <Link
                        v-if="nextResourceId"
                        :href="`/resources/${nextResourceId}`"
                        replace
                        class="group inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:text-indigo-600 active:scale-[0.97] dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                    >
                        <span class="xs:inline hidden">Next Page</span>
                        <span class="xs:hidden">Next Page</span>
                        <ArrowRight
                            class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5"
                        />
                    </Link>
                    <span
                        v-else
                        class="inline-flex h-10 items-center px-4 text-xs font-bold text-slate-300 select-none dark:text-gray-600"
                        >Last Page</span
                    >
                </div>
            </div>
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
