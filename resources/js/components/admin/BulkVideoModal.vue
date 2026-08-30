<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Youtube,
    FileSpreadsheet,
    Hash,
    Tag,
    Info,
    X,
    Loader2,
    AlertCircle,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    node: {
        id: number;
        name: string;
    };
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const playlistUrl = ref('');
const namingStrategy = ref<'youtube' | 'serial' | 'prefix'>('youtube');
const namingPrefix = ref('');
const startNumber = ref(1);
const isSaving = ref(false);
const errorMessage = ref('');

const resetForm = () => {
    playlistUrl.value = '';
    namingStrategy.value = 'youtube';
    namingPrefix.value = '';
    startNumber.value = 1;
    errorMessage.value = '';
    isSaving.value = false;
};

const handleClose = () => {
    if (isSaving.value) {
        return;
    }

    resetForm();
    emit('close');
};

watch(
    () => props.isOpen,
    (open) => {
        if (!open) {
            resetForm();
        }
    },
);

const submitForm = () => {
    if (!playlistUrl.value.trim() || isSaving.value) {
        return;
    }

    isSaving.value = true;
    errorMessage.value = '';

    const payload = {
        node_id: props.node.id,
        playlist_url: playlistUrl.value.trim(),
        naming_strategy: namingStrategy.value,
        naming_prefix: namingPrefix.value.trim(),
        start_number: startNumber.value,
    };

    router.post('/admin/resources/bulk/videos', payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
            resetForm();
            emit('close');
        },
        onError: (errors) => {
            isSaving.value = false;
            errorMessage.value =
                Object.values(errors).flat().join(', ') ||
                'Failed to import playlist.';
        },
    });
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
            >
                <div
                    class="fixed inset-0 bg-slate-950/40 backdrop-blur-xs dark:bg-black/60"
                    @click="handleClose"
                />

                <div
                    class="relative flex max-h-[92vh] w-full max-w-lg flex-col rounded-t-2xl border border-slate-200 bg-white shadow-xl sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-6 dark:border-gray-800"
                    >
                        <div class="min-w-0 flex-1 pr-3">
                            <h3
                                class="truncate text-base font-bold text-slate-900 dark:text-gray-100"
                            >
                                Import YouTube Playlist
                            </h3>
                            <p
                                class="truncate text-xs text-slate-500 dark:text-gray-400"
                            >
                                In:
                                <span
                                    class="font-medium text-slate-700 dark:text-gray-300"
                                >
                                    {{ node.name }}
                                </span>
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="handleClose"
                            class="cursor-pointer rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Form Body -->
                    <form
                        @submit.prevent="submitForm"
                        class="flex flex-1 flex-col overflow-hidden"
                    >
                        <!-- Error Banner -->
                        <div
                            v-if="errorMessage"
                            class="m-4 mb-0 flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
                        >
                            <AlertCircle
                                class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                            />
                            <div class="leading-relaxed">
                                <span class="font-bold">Error: </span>
                                <span>{{ errorMessage }}</span>
                            </div>
                        </div>

                        <div
                            class="flex-1 space-y-4 overflow-y-auto p-4 text-slate-800 sm:p-6 dark:text-gray-200"
                        >
                            <!-- Playlist URL Input -->
                            <div class="space-y-1.5">
                                <label
                                    for="modal_playlist_url"
                                    class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                                >
                                    YouTube Playlist URL
                                    <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative flex items-center">
                                    <Youtube
                                        class="pointer-events-none absolute left-3 h-4 w-4 text-rose-600"
                                    />
                                    <input
                                        id="modal_playlist_url"
                                        v-model="playlistUrl"
                                        type="url"
                                        placeholder="https://www.youtube.com/playlist?list=PL..."
                                        required
                                        autofocus
                                        class="w-full rounded-lg border border-slate-300 bg-white py-2 pr-3 pl-9 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                    />
                                </div>
                            </div>

                            <!-- Naming Strategy Selection -->
                            <div class="space-y-2">
                                <label
                                    class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                                >
                                    Resource Naming Strategy
                                </label>

                                <div
                                    class="grid grid-cols-1 gap-2 sm:grid-cols-3"
                                >
                                    <!-- Strategy 1: YouTube Title -->
                                    <label
                                        class="flex cursor-pointer items-center gap-2 rounded-lg border bg-white p-2.5 text-xs transition dark:bg-gray-900"
                                        :class="
                                            namingStrategy === 'youtube'
                                                ? 'border-indigo-600 font-semibold text-indigo-700 ring-1 ring-indigo-600 dark:border-indigo-500 dark:text-indigo-300'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            value="youtube"
                                            v-model="namingStrategy"
                                            class="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <FileSpreadsheet class="h-3.5 w-3.5" />
                                        <span>YouTube Title</span>
                                    </label>

                                    <!-- Strategy 2: Serial -->
                                    <label
                                        class="flex cursor-pointer items-center gap-2 rounded-lg border bg-white p-2.5 text-xs transition dark:bg-gray-900"
                                        :class="
                                            namingStrategy === 'serial'
                                                ? 'border-indigo-600 font-semibold text-indigo-700 ring-1 ring-indigo-600 dark:border-indigo-500 dark:text-indigo-300'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            value="serial"
                                            v-model="namingStrategy"
                                            class="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <Hash class="h-3.5 w-3.5" />
                                        <span>Sequential (01)</span>
                                    </label>

                                    <!-- Strategy 3: Prefix -->
                                    <label
                                        class="flex cursor-pointer items-center gap-2 rounded-lg border bg-white p-2.5 text-xs transition dark:bg-gray-900"
                                        :class="
                                            namingStrategy === 'prefix'
                                                ? 'border-indigo-600 font-semibold text-indigo-700 ring-1 ring-indigo-600 dark:border-indigo-500 dark:text-indigo-300'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            value="prefix"
                                            v-model="namingStrategy"
                                            class="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <Tag class="h-3.5 w-3.5" />
                                        <span>Prefix + Serial</span>
                                    </label>
                                </div>

                                <!-- Dynamic Options for Prefix / Serial -->
                                <div
                                    v-if="namingStrategy !== 'youtube'"
                                    class="grid grid-cols-1 gap-3 pt-2 sm:grid-cols-2"
                                >
                                    <div
                                        v-if="namingStrategy === 'prefix'"
                                        class="space-y-1"
                                    >
                                        <label
                                            for="modal_video_naming_prefix"
                                            class="block text-[11px] font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Prefix String
                                        </label>
                                        <input
                                            id="modal_video_naming_prefix"
                                            v-model="namingPrefix"
                                            type="text"
                                            placeholder="e.g. Lecture"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                        />
                                    </div>

                                    <div class="space-y-1">
                                        <label
                                            for="modal_video_start_number"
                                            class="block text-[11px] font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Starting Number
                                        </label>
                                        <input
                                            id="modal_video_start_number"
                                            v-model.number="startNumber"
                                            type="number"
                                            min="1"
                                            placeholder="1"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Info Box -->
                            <div
                                class="flex items-start gap-2.5 rounded-xl border border-indigo-100 bg-indigo-50/60 p-3 dark:border-indigo-500/30 dark:bg-indigo-500/10"
                            >
                                <Info
                                    class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400"
                                />
                                <p
                                    class="text-[11px] leading-relaxed text-indigo-900 dark:text-indigo-300"
                                >
                                    Playlists with multiple pages will be
                                    automatically fetched and created as
                                    sequential video resources.
                                </p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-6 dark:border-gray-800 dark:bg-gray-900/60"
                        >
                            <button
                                type="button"
                                @click="handleClose"
                                class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                :disabled="isSaving || !playlistUrl.trim()"
                                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Loader2
                                    v-if="isSaving"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <span>{{
                                    isSaving
                                        ? 'Importing Playlist...'
                                        : 'Import Playlist'
                                }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
