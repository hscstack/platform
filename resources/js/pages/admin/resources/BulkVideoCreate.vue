<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Youtube, FileSpreadsheet, Hash, Tag, Info } from 'lucide-vue-next';

const props = defineProps({
    redirect: {
        type: String,
        default: '/',
    },
    node: {
        type: Object,
        required: true,
        default: () => ({ id: null }),
    },
});

const form = useForm({
    node_id: props.node?.id ?? null,
    playlist_url: '',
    naming_strategy: 'youtube', // 'youtube' | 'serial' | 'prefix'
    naming_prefix: '',
    start_number: 1,
    redirect: props.redirect,
});

const submitForm = () => {
    form.post('/admin/resources/bulk/videos', {
        onSuccess: () => {
            form.reset('playlist_url');
        },
    });
};
</script>

<template>
    <Head title="Import YouTube Playlist" />

    <div
        class="flex min-h-full w-full flex-col justify-start bg-slate-50 p-6 lg:p-10 dark:bg-gray-950"
    >
        <div
            class="mx-auto w-full max-w-4xl rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm md:p-10 dark:border-gray-700 dark:bg-gray-900"
        >
            <!-- Header -->
            <div
                class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center dark:border-gray-800"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-slate-900 dark:text-gray-100"
                    >
                        Import YouTube Playlist
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                        Automatically fetch videos from a YouTube playlist and
                        create resources preserving playlist order.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Playlist URL Input -->
                <div>
                    <label
                        for="playlist_url"
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        YouTube Playlist URL
                    </label>
                    <div class="relative flex items-center">
                        <Youtube
                            class="pointer-events-none absolute left-4 h-5 w-5 text-rose-600"
                        />
                        <input
                            v-model="form.playlist_url"
                            type="url"
                            id="playlist_url"
                            placeholder="https://www.youtube.com/playlist?list=PL..."
                            required
                            class="w-full rounded-lg border py-2.5 pr-4 pl-11 text-sm transition outline-none"
                            :class="
                                form.errors.playlist_url
                                    ? 'border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                                    : 'border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600'
                            "
                        />
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                        Paste the full URL of the public or unlisted YouTube
                        playlist.
                    </p>
                    <p
                        v-if="form.errors.playlist_url"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.playlist_url }}
                    </p>
                </div>

                <!-- Naming Strategy Selection -->
                <div
                    class="space-y-4 rounded-xl border border-slate-200 bg-slate-50/50 p-5 dark:border-gray-700 dark:bg-gray-800/50"
                >
                    <div>
                        <h3
                            class="text-sm font-semibold text-slate-800 dark:text-gray-200"
                        >
                            Resource Naming Strategy
                        </h3>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            Choose how each resource title will be generated
                            during import.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <!-- Strategy 1: YouTube Title -->
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border bg-white p-3 transition dark:bg-gray-900"
                            :class="
                                form.naming_strategy === 'youtube'
                                    ? 'border-blue-600 ring-2 ring-blue-600/10'
                                    : 'border-slate-200 dark:border-gray-700 dark:hover:border-gray-600'
                            "
                        >
                            <input
                                type="radio"
                                value="youtube"
                                v-model="form.naming_strategy"
                                class="text-blue-600 focus:ring-blue-500 dark:text-blue-400"
                            />
                            <div
                                class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-gray-300"
                            >
                                <FileSpreadsheet
                                    class="h-4 w-4 text-slate-400 dark:text-gray-500"
                                />
                                Use YouTube Titles
                            </div>
                        </label>

                        <!-- Strategy 2: Sequential Numbering -->
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border bg-white p-3 transition dark:bg-gray-900"
                            :class="
                                form.naming_strategy === 'serial'
                                    ? 'border-blue-600 ring-2 ring-blue-600/10'
                                    : 'border-slate-200 dark:border-gray-700 dark:hover:border-gray-600'
                            "
                        >
                            <input
                                type="radio"
                                value="serial"
                                v-model="form.naming_strategy"
                                class="text-blue-600 focus:ring-blue-500 dark:text-blue-400"
                            />
                            <div
                                class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-gray-300"
                            >
                                <Hash
                                    class="h-4 w-4 text-slate-400 dark:text-gray-500"
                                />
                                Sequential Numbering
                            </div>
                        </label>

                        <!-- Strategy 3: Prefix + Sequential -->
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg border bg-white p-3 transition dark:bg-gray-900"
                            :class="
                                form.naming_strategy === 'prefix'
                                    ? 'border-blue-600 ring-2 ring-blue-600/10'
                                    : 'border-slate-200 dark:border-gray-700 dark:hover:border-gray-600'
                            "
                        >
                            <input
                                type="radio"
                                value="prefix"
                                v-model="form.naming_strategy"
                                class="text-blue-600 focus:ring-blue-500 dark:text-blue-400"
                            />
                            <div
                                class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-gray-300"
                            >
                                <Tag
                                    class="h-4 w-4 text-slate-400 dark:text-gray-500"
                                />
                                Custom Prefix + Serial
                            </div>
                        </label>
                    </div>

                    <!-- Micro Helper Text for Strategy Choice -->
                    <p
                        class="rounded-lg border border-slate-200/80 bg-white p-2.5 text-xs text-slate-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400"
                    >
                        <span v-if="form.naming_strategy === 'youtube'">
                            💡 <strong>Example:</strong> Uses original YouTube
                            video names (e.g., <em>HTML Introduction</em>,
                            <em>HTML Image Tag</em>).
                        </span>
                        <span v-else-if="form.naming_strategy === 'serial'">
                            💡 <strong>Example:</strong> Formats titles
                            sequentially (e.g., <em>01</em>, <em>02</em>,
                            <em>03</em>).
                        </span>
                        <span v-else-if="form.naming_strategy === 'prefix'">
                            💡 <strong>Example:</strong> Combines your prefix
                            and sequence (e.g., <em>Lecture 01 - 01</em>,
                            <em>Lecture 01 - 02</em>).
                        </span>
                    </p>

                    <!-- Dynamic Options for Serial / Prefix -->
                    <div
                        v-if="form.naming_strategy !== 'youtube'"
                        class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-2"
                    >
                        <div v-if="form.naming_strategy === 'prefix'">
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400"
                            >
                                Prefix String
                            </label>
                            <input
                                v-model="form.naming_prefix"
                                type="text"
                                placeholder="e.g. Lecture 01"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-900"
                            />
                            <p
                                class="mt-1 text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                Applied before the sequential number.
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-600 dark:text-gray-400"
                            >
                                Starting Number
                            </label>
                            <input
                                v-model.number="form.start_number"
                                type="number"
                                min="1"
                                placeholder="1"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-900"
                            />
                            <p
                                class="mt-1 text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                Number to begin incrementing from (default is
                                1).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Information Box -->
                <div
                    class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/60 p-4 dark:border-blue-500/30 dark:bg-blue-500/10"
                >
                    <Info
                        class="mt-0.5 h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400"
                    />
                    <p
                        class="text-xs leading-relaxed text-blue-900 dark:text-blue-300"
                    >
                        Playlists containing more than 50 videos will
                        automatically page through and import all available
                        lessons sequentially.
                    </p>
                </div>

                <!-- Footer Actions -->
                <div
                    class="flex justify-end space-x-3 border-t border-slate-100 pt-6 dark:border-gray-800"
                >
                    <Link
                        :href="redirect"
                        type="button"
                        class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || !form.playlist_url"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'Importing Playlist...'
                                : 'Import Playlist'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
