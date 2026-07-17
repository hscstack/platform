<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    FileText,
    Image as ImageIcon,
    Download,
    AlertCircle,
    ArrowLeft,
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
        reired: true,
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
    <div
        class="mx-auto flex min-h-[75vh] max-w-4xl flex-col justify-start px-4 pt-8 pb-20 sm:px-6 sm:pt-12"
    >
        <div class="mb-6">
            <button
                @click="handleBack"
                class="group inline-flex items-center gap-2 text-xs font-bold text-slate-500 transition-colors hover:text-indigo-600"
            >
                <ArrowLeft
                    class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-0.5"
                />
                Back
            </button>
        </div>

        <div
            class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
            <div class="border-b border-slate-100 bg-slate-50/50 p-5 sm:p-6">
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2 py-1 text-xs font-bold tracking-wider text-slate-600 uppercase"
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
                            {{ resource.resource_type }}
                        </span>

                        <h1
                            class="mt-2 text-xl font-black tracking-tight text-slate-950 sm:text-2xl"
                        >
                            {{ resource.title }}
                        </h1>

                        <Link
                            v-if="resource.user?.name"
                            :href="`/about-us#${resource.user.id}`"
                            class="group mt-2 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 transition-all hover:bg-indigo-50 hover:text-indigo-700"
                        >
                            <User
                                class="h-3.5 w-3.5 stroke-[2.2] text-slate-500 group-hover:text-indigo-600"
                            />
                            <span>
                                Shared by
                                <span
                                    class="font-bold text-indigo-600 group-hover:underline"
                                >
                                    {{ resource.user.name }}
                                </span>
                            </span>
                        </Link>
                    </div>

                    <div
                        v-if="resource.resource_type === 'image'"
                        class="flex items-center gap-2 self-start sm:self-center"
                    >
                        <a
                            v-if="resource.file_url"
                            :href="resource.file_url"
                            download
                            target="_blank"
                            class="inline-flex h-9 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:text-indigo-600 active:scale-[0.98]"
                        >
                            <Download class="h-4 w-4 stroke-[2.2]" />
                            Download
                        </a>

                        <button
                            @click="toggleFullscreen"
                            class="inline-flex h-9 items-center gap-2 rounded-xl bg-indigo-600 px-4 text-xs font-bold text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-[0.98]"
                            title="View Fullscreen"
                        >
                            <Maximize2 class="h-4 w-4 stroke-[2.2]" />
                            Full Screen
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="resource.resource_type === 'note'" class="p-6 sm:p-8">
                <div
                    class="prose max-w-none text-sm leading-relaxed font-medium text-slate-700 sm:text-base"
                >
                    <h3
                        class="mb-2 text-xs font-black tracking-wider text-slate-400 uppercase"
                    >
                        Note:
                    </h3>
                    <p
                        class="whitespace-pre-line selection:bg-indigo-100 selection:text-indigo-900"
                    >
                        {{ resource.content }}
                    </p>
                </div>
            </div>

            <div v-else-if="resource.resource_type === 'image'">
                <div
                    v-if="resource.content"
                    class="border-b border-slate-100 bg-white p-6 sm:p-8"
                >
                    <div
                        class="prose max-w-none text-sm leading-relaxed font-medium text-slate-700 sm:text-base"
                    >
                        <h3
                            class="mb-2 text-xs font-black tracking-wider text-slate-400 uppercase"
                        >
                            Note:
                        </h3>
                        <p
                            class="whitespace-pre-line selection:bg-indigo-100 selection:text-indigo-900"
                        >
                            {{ resource.content }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-center bg-slate-950/5 p-4 sm:p-8">
                    <div
                        class="relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-shadow duration-300 hover:shadow-md"
                    >
                        <img
                            :src="resource.file_url"
                            :alt="resource.title"
                            class="max-h-[70vh] w-auto object-contain select-none"
                        />
                    </div>
                </div>
            </div>
            <div v-else-if="resource.resource_type === 'video'">
                <div
                    v-if="resource.content"
                    class="border-b border-slate-100 bg-white p-6 sm:p-8"
                >
                    <div
                        class="prose max-w-none text-sm leading-relaxed font-medium text-slate-700 sm:text-base"
                    >
                        <h3
                            class="mb-2 text-xs font-black tracking-wider text-slate-400 uppercase"
                        >
                            Note:
                        </h3>
                        <p
                            class="whitespace-pre-line selection:bg-indigo-100 selection:text-indigo-900"
                        >
                            This content is hosted on YouTube by the original
                            creator. We have embedded it here for educational
                            reference only.
                        </p>
                    </div>
                </div>

                <div class="bg-slate-950/5 p-4 sm:p-8">
                    <div
                        class="relative aspect-video w-full overflow-hidden rounded-xl border border-slate-200 bg-black shadow-sm"
                    >
                        <iframe
                            :src="parseYoutubeUrl(resource.content)"
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
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-600"
                >
                    <AlertCircle class="h-6 w-6 stroke-[2.2]" />
                </div>

                <h3 class="mt-4 text-base font-bold text-slate-900">
                    Unsupported Preview:
                    <span class="text-indigo-600 capitalize">{{
                        resource.resource_type
                    }}</span>
                </h3>
                <p
                    class="mx-auto mt-2 max-w-sm text-xs font-medium text-slate-500 sm:text-sm"
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
                        class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-400"
                    >
                        No download target generated for this asset.
                    </div>
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
                    class="hidden items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-[11px] font-medium text-slate-300 backdrop-blur-md sm:flex"
                >
                    <span>Pinch / Scroll to Zoom</span>
                    <span class="text-slate-500">•</span>
                    <span>Drag to Pan</span>
                </div>

                <button
                    v-if="scale > 1"
                    @click="resetZoom"
                    class="rounded-full bg-white/10 p-3 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95"
                    title="Reset Zoom"
                >
                    <RotateCcw class="h-5 w-5" />
                </button>

                <button
                    @click="toggleFullscreen"
                    class="rounded-full bg-white/10 p-3 text-white backdrop-blur-md transition-all hover:bg-white/20 active:scale-95"
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
