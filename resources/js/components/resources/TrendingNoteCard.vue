<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowBigUp,
    FileText,
    FlaskConical,
    NotebookPen,
    BookOpen,
    Play,
    Video,
} from 'lucide-vue-next';
import { computed } from 'vue';

import VerifiedBadge from '@/components/VerifiedBadge.vue';

interface User {
    id: number;
    name: string;
    username: string;
    image_path?: string | null;
    image_url?: string | null;
    institution?: string | null;
    is_verified?: boolean;
}

interface NodeItem {
    id: number;
    subject_id?: number;
    name: string;
    slug: string;
    subject?: {
        id: number;
        name: string;
        course: string;
        slug: string;
    } | null;
}

interface ResourceNote {
    id: number;
    title: string;
    resource_type: string;
    thumbnail?: string | null;
    items_count?: number;
    vote_score: number;
    created_at?: string;
    user?: User | null;
    node?: NodeItem | null;
}

const props = defineProps<{
    note: ResourceNote;
}>();

const previewImageUrl = computed(() => props.note.thumbnail || null);

const isVideo = computed(() => props.note.resource_type === 'video');

const typeLabel = computed(() => {
    switch (props.note.resource_type) {
        case 'video':
            return {
                icon: Video,
                text: 'Class',
                color: 'text-rose-600 dark:text-rose-400',
            };
        case 'practical':
            return {
                icon: FlaskConical,
                text: 'Practical',
                color: 'text-teal-600 dark:text-teal-400',
            };
        case 'marked_book':
            return {
                icon: BookOpen,
                text: 'দাগানো বই',
                color: 'text-emerald-600 dark:text-emerald-400',
            };
        case 'other':
            return {
                icon: FileText,
                text: 'Study material',
                color: 'text-slate-600 dark:text-slate-400',
            };
        default:
            return {
                icon: NotebookPen,
                text: 'Hand note',
                color: 'text-amber-600 dark:text-amber-400',
            };
    }
});

const pagesLabel = computed(() => {
    const count = props.note.items_count || 1;

    if (isVideo.value) {
        return `${count} ${count === 1 ? 'class' : 'classes'}`;
    }

    return `${count} ${count === 1 ? 'page' : 'pages'}`;
});

const subtitle = computed(() => {
    const parts: string[] = [];
    const course = props.note.node?.subject?.course;

    if (course) {
        parts.push(course.toUpperCase());
    }

    if (props.note.node?.subject?.name) {
        parts.push(props.note.node.subject.name);
    }

    if (props.note.node?.name) {
        parts.push(props.note.node.name);
    }

    return parts.join(' · ');
});
</script>

<template>
    <article
        class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-xs dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
    >
        <!-- Full-card clickable Link overlay for robust, native Inertia navigation -->
        <Link
            :href="`/resources/${note.id}`"
            class="absolute inset-0 z-10"
            :aria-label="note.title"
        />

        <!-- Top Half: Preview Thumbnail (Row 1) -->
        <div
            class="relative aspect-[4/3] w-full overflow-hidden bg-slate-100 dark:bg-gray-800/80"
        >
            <!-- Image / Thumbnail preview -->
            <img
                v-if="previewImageUrl"
                :src="previewImageUrl"
                :alt="note.title"
                loading="lazy"
                class="h-full w-full object-cover object-top transition duration-300 group-hover:scale-102"
            />

            <!-- Fallback Realistic Notebook/Document Cover Preview -->
            <div
                v-else
                class="dark:bg-gray-850 relative flex h-full w-full flex-col items-center justify-center overflow-hidden bg-slate-50 [background-image:radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:12px_12px] p-4 dark:[background-image:radial-gradient(#374151_1px,transparent_1px)]"
            >
                <!-- Notebook Spiral Spine Strip on left -->
                <div
                    class="absolute top-0 bottom-0 left-0 flex w-3.5 flex-col items-center justify-around border-r border-slate-200/80 bg-slate-100/90 py-3 dark:border-gray-700/80 dark:bg-gray-800"
                >
                    <span
                        v-for="i in 5"
                        :key="i"
                        class="h-1.5 w-1.5 rounded-full bg-slate-300 shadow-2xs dark:bg-gray-600"
                    ></span>
                </div>

                <!-- Center Subject Cover Plaque -->
                <div
                    class="ml-2 flex max-w-[85%] flex-col items-center justify-center text-center"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl border shadow-2xs transition-transform duration-200 group-hover:scale-105"
                        :class="
                            note.resource_type === 'video'
                                ? 'border-rose-200/60 bg-rose-50 text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-400'
                                : note.resource_type === 'practical'
                                  ? 'border-teal-200/60 bg-teal-50 text-teal-600 dark:border-teal-900/50 dark:bg-teal-950/40 dark:text-teal-400'
                                  : note.resource_type === 'other'
                                    ? 'border-slate-200 bg-slate-100 text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                    : 'border-amber-200/60 bg-amber-50 text-amber-600 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-400'
                        "
                    >
                        <component
                            :is="typeLabel.icon"
                            class="h-5.5 w-5.5 stroke-[1.8]"
                        />
                    </div>

                    <span
                        v-if="note.node?.subject?.name"
                        class="mt-2 line-clamp-1 text-[11px] font-bold text-slate-700 dark:text-gray-300"
                    >
                        {{ note.node.subject.name }}
                    </span>
                    <span
                        class="mt-0.5 text-[9px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                    >
                        {{
                            note.resource_type === 'video'
                                ? 'Video Class'
                                : 'PDF Resource'
                        }}
                    </span>
                </div>
            </div>

            <!-- Video Play Icon Indicator if video -->
            <div
                v-if="isVideo"
                class="absolute inset-0 flex items-center justify-center bg-black/20"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-rose-600 shadow-md backdrop-blur-xs transition group-hover:scale-110"
                >
                    <Play class="ml-0.5 h-4.5 w-4.5 fill-current" />
                </div>
            </div>

            <!-- Top Right: Vote Score Pill (Read-only) -->
            <div
                class="absolute top-2 right-2 inline-flex items-center gap-1 rounded-lg bg-black/65 px-2 py-0.5 text-xs font-bold text-white shadow-xs backdrop-blur-xs"
                title="Vote score"
            >
                <ArrowBigUp
                    class="h-3.5 w-3.5"
                    :class="
                        (note.vote_score || 0) > 0
                            ? 'fill-indigo-400 text-indigo-400'
                            : 'text-slate-300'
                    "
                />
                <span class="tabular-nums">{{ note.vote_score || 0 }}</span>
            </div>

            <!-- Bottom Right: Pages / Classes Count Overlay Pill -->
            <div
                class="absolute right-2 bottom-2 inline-flex items-center rounded-lg bg-black/70 px-2 py-0.5 text-[11px] font-semibold text-white shadow-xs backdrop-blur-xs"
            >
                {{ pagesLabel }}
            </div>
        </div>

        <!-- Bottom Half: Details (Row 2) -->
        <div class="flex flex-1 flex-col justify-between p-3.5 sm:p-4">
            <div>
                <!-- Format Tag (Hand note, Practical, Class, etc.) -->
                <div
                    class="flex items-center gap-1.5 text-xs font-bold"
                    :class="typeLabel.color"
                >
                    <component
                        :is="typeLabel.icon"
                        class="h-3.5 w-3.5 stroke-[2.2]"
                    />
                    <span>{{ typeLabel.text }}</span>
                </div>

                <!-- Note Title -->
                <h3
                    class="mt-1.5 line-clamp-2 text-xs font-bold text-slate-900 transition group-hover:text-indigo-600 sm:text-sm dark:text-gray-100 dark:group-hover:text-indigo-400"
                >
                    <Link :href="`/resources/${note.id}`" @click.stop>
                        {{ note.title }}
                    </Link>
                </h3>

                <!-- Subtitle: HSC · Subject · Chapter -->
                <p
                    v-if="subtitle"
                    class="mt-1 truncate text-[11px] text-slate-400 dark:text-gray-500"
                    :title="subtitle"
                >
                    {{ subtitle }}
                </p>
            </div>

            <!-- Author & Avatar Footer -->
            <div
                class="relative z-20 mt-3 flex items-center justify-between border-t border-slate-100 pt-2.5 text-xs text-slate-500 dark:border-gray-800 dark:text-gray-400"
            >
                <div class="flex min-w-0 items-center gap-1.5">
                    <Link
                        v-if="note.user?.username"
                        :href="`/u/${note.user.username}`"
                        @click.stop
                        class="inline-flex min-w-0 items-center gap-1.5 font-medium text-slate-700 hover:text-indigo-600 hover:underline dark:text-gray-300 dark:hover:text-indigo-400"
                    >
                        <div
                            class="flex h-5 w-5 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-[10px] font-bold text-slate-700 dark:bg-gray-700 dark:text-gray-300"
                        >
                            <img
                                v-if="note.user.image_url"
                                :src="note.user.image_url"
                                :alt="note.user.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{ note.user.name.charAt(0) }}</span>
                        </div>
                        <span class="truncate">{{ note.user.name }}</span>
                        <VerifiedBadge v-if="note.user.is_verified" />
                    </Link>
                    <span
                        v-else
                        class="truncate font-medium text-slate-700 dark:text-gray-300"
                    >
                        {{ note.user?.name || 'Anonymous' }}
                    </span>
                </div>
            </div>
        </div>
    </article>
</template>
