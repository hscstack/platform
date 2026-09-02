<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Download,
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Maximize2,
    User,
    Image as ImageIcon,
    ExternalLink,
    CheckCircle2,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AuthModal from '@/components/AuthModal.vue';
import ImageViewerModal from '@/components/ImageViewerModal.vue';
import UserListItem from '@/components/UserListItem.vue';
import YouTubePlayer from '@/components/YouTubePlayer.vue';
import { useAuth } from '@/lib/useAuth';

const props = defineProps({
    resource: {
        type: Object,
        required: true,
    },
    subject: {
        type: Object,
        default: null,
    },
    previousResourceId: {
        type: Number,
        default: null,
    },
    nextResourceId: {
        type: Number,
        default: null,
    },
    isCompleted: {
        type: Boolean,
        default: false,
    },
    completionsCount: {
        type: Number,
        default: 0,
    },
    completers: {
        type: Array as () => any[],
        default: () => [],
    },
});

const { user, requireAuth, showAuthModal, authModalMessage } = useAuth();
const showCompletersModal = ref(false);

// Optimistic Completion state
const localIsCompleted = ref(props.isCompleted);
const localCompletionsCount = ref(props.completionsCount);
const localCompleters = ref<any[]>([...(props.completers || [])]);
const isTogglingCompletion = ref(false);

watch(
    () => props.isCompleted,
    (val) => {
        localIsCompleted.value = val;
    },
);

watch(
    () => props.completionsCount,
    (val) => {
        localCompletionsCount.value = val;
    },
);

watch(
    () => props.completers,
    (val) => {
        localCompleters.value = [...(val || [])];
    },
);

const handleToggleComplete = () => {
    if (
        !requireAuth(
            'Please sign in to mark study materials as completed and track your syllabus progress.',
        )
    ) {
        return;
    }

    if (isTogglingCompletion.value) {
        return;
    }

    // Optimistic update
    if (localIsCompleted.value) {
        localIsCompleted.value = false;
        localCompletionsCount.value = Math.max(
            0,
            localCompletionsCount.value - 1,
        );
        localCompleters.value = localCompleters.value.filter(
            (c: any) => c.id !== user.value?.id,
        );
    } else {
        localIsCompleted.value = true;
        localCompletionsCount.value += 1;

        if (user.value) {
            localCompleters.value = [
                {
                    id: user.value.id,
                    name: user.value.name,
                    image_url: user.value.image_url,
                    image_path: user.value.image_path,
                    institution: user.value.institution,
                },
                ...localCompleters.value.filter(
                    (c: any) => c.id !== user.value.id,
                ),
            ];
        }
    }

    isTogglingCompletion.value = true;
    router.post(
        `/resources/${props.resource.id}/complete`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isTogglingCompletion.value = false;
            },
        },
    );
};

const isImageLoaded = ref(false);

watch(
    () => props.resource?.file_url,
    () => {
        isImageLoaded.value = false;
    },
);

const handleDownload = () => {
    if (
        !requireAuth(
            'Please sign in to download full-resolution study materials.',
        )
    ) {
        return;
    }

    if (props.resource?.file_url) {
        const downloadUrl = props.resource.file_url.includes('?')
            ? `${props.resource.file_url}&download=1`
            : `${props.resource.file_url}?download=1`;

        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = props.resource.title || 'download';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

const isFullscreen = ref(false);

const handleBack = () => {
    if (typeof window !== 'undefined') {
        window.history.back();
    }
};

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
};
</script>

<template>
    <Head>
        <title>
            {{ resource.title
            }}{{
                subject
                    ? ` - ${subject.course.toUpperCase()} - ${subject.name}`
                    : ''
            }}
        </title>
        <meta
            name="description"
            :content="`Study material: ${resource.title} (${resource.resource_type})${subject ? ` for ${subject.course.toUpperCase()} - ${subject.name}` : ''} on HSCStack.`"
        />
        <meta
            property="og:title"
            :content="`${resource.title}${subject ? ` - ${subject.course.toUpperCase()} - ${subject.name}` : ''} - HSCStack`"
        />
        <meta
            property="og:description"
            :content="`Study material: ${resource.title} (${resource.resource_type})${subject ? ` for ${subject.course.toUpperCase()} - ${subject.name}` : ''} on HSCStack.`"
        />
    </Head>

    <div
        class="mx-auto flex max-w-5xl flex-col justify-start px-3 pt-3 pb-24 sm:px-6"
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

                <div class="min-w-0">
                    <span
                        v-if="subject"
                        class="block truncate text-xs font-semibold text-indigo-600 dark:text-indigo-400"
                    >
                        {{ subject.course.toUpperCase() }} - {{ subject.name }}
                    </span>
                    <h1
                        class="truncate text-sm font-bold text-slate-900 sm:text-base dark:text-gray-100"
                        :title="resource.title"
                    >
                        {{ resource.title }}
                    </h1>
                </div>
            </div>

            <!-- Right: Action Buttons -->
            <div class="flex shrink-0 items-center gap-2">
                <!-- Mark as Done / Completed Button (Auth-guarded) -->
                <div class="flex items-center gap-1.5">
                    <button
                        @click="handleToggleComplete"
                        type="button"
                        class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl border px-2.5 text-xs font-semibold shadow-xs transition active:scale-95 sm:px-3"
                        :class="
                            localIsCompleted
                                ? 'border-emerald-500/40 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-950/60'
                                : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-200 hover:bg-emerald-50/70 hover:text-emerald-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-emerald-900/50 dark:hover:bg-emerald-950/30 dark:hover:text-emerald-400'
                        "
                        :title="
                            localIsCompleted
                                ? 'Marked as completed (click to undo)'
                                : 'Mark as done'
                        "
                        :aria-label="localIsCompleted ? 'Done' : 'Mark as Done'"
                    >
                        <CheckCircle2
                            class="h-3.5 w-3.5 stroke-[2.2] transition-colors"
                            :class="
                                localIsCompleted
                                    ? 'fill-emerald-600/20 text-emerald-600 dark:text-emerald-400'
                                    : ''
                            "
                        />
                        <span class="hidden text-xs font-semibold sm:inline">{{
                            localIsCompleted ? 'Done' : 'Mark as Done'
                        }}</span>
                    </button>

                    <!-- Completers Avatar Stack & Counter Trigger -->
                    <button
                        v-if="localCompletionsCount > 0"
                        @click="showCompletersModal = true"
                        type="button"
                        class="flex h-9 cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-600 shadow-xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800"
                        title="View students who completed this"
                    >
                        <div class="flex -space-x-1.5 overflow-hidden">
                            <div
                                v-for="completer in localCompleters.slice(0, 3)"
                                :key="completer.id"
                                class="inline-block h-5 w-5 rounded-full ring-2 ring-white dark:ring-gray-900"
                            >
                                <img
                                    v-if="
                                        completer.image_url ||
                                        completer.image_path
                                    "
                                    :src="
                                        completer.image_url ||
                                        '/storage/' + completer.image_path
                                    "
                                    :alt="completer.name"
                                    class="h-full w-full rounded-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center rounded-full bg-emerald-100 text-[9px] font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200"
                                >
                                    {{ completer.name?.charAt(0) || 'U' }}
                                </div>
                            </div>
                        </div>
                        <span
                            class="text-xs font-bold text-slate-700 dark:text-gray-300"
                        >
                            {{ localCompletionsCount }}
                        </span>
                    </button>
                </div>

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
                    type="button"
                    class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-indigo-600 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                    title="Download Resource"
                >
                    <Download class="h-3.5 w-3.5 stroke-[2.2]" />
                    <span class="hidden sm:inline">Download</span>
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
                @click="isFullscreen = true"
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
                    :href="
                        resource.user?.username
                            ? `/u/${resource.user.username}`
                            : '#'
                    "
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

    <!-- Reusable Fullscreen Image Viewer Modal -->
    <ImageViewerModal
        v-if="resource.resource_type === 'image'"
        v-model="isFullscreen"
        :src="resource.file_url"
        :title="resource.title"
        :alt="resource.title"
        @download="handleDownload"
    />

    <!-- Minimal Sign-in Dialog for Guests (Download & Completion Auth Guard) -->
    <AuthModal v-model="showAuthModal" :message="authModalMessage" />

    <!-- Students who Completed Modal -->
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
                v-if="showCompletersModal"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs dark:bg-black/50"
                @click.self="showCompletersModal = false"
            >
                <div
                    class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        @click="showCompletersModal = false"
                        class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <div class="mb-4 flex items-center gap-2.5">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="h-4 w-4 stroke-[2.2]" />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold text-slate-900 dark:text-gray-100"
                            >
                                Completed By
                            </h3>
                            <p
                                class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                            >
                                {{ localCompletionsCount }} student{{
                                    localCompletionsCount === 1 ? '' : 's'
                                }}
                                marked this as done
                            </p>
                        </div>
                    </div>

                    <div
                        class="-mx-1 max-h-72 divide-y divide-slate-100 overflow-y-auto px-1 dark:divide-gray-800/80"
                    >
                        <div
                            v-if="localCompleters.length === 0"
                            class="py-6 text-center text-xs text-slate-500 dark:text-gray-400"
                        >
                            No completions recorded yet.
                        </div>

                        <UserListItem
                            v-for="completer in localCompleters"
                            :key="completer.id"
                            :user="completer"
                            theme="emerald"
                        />

                        <div
                            v-if="
                                localCompletionsCount > localCompleters.length
                            "
                            class="py-3 text-center text-xs font-medium text-slate-500 dark:text-gray-400"
                        >
                            and
                            {{ localCompletionsCount - localCompleters.length }}
                            more...
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped></style>
