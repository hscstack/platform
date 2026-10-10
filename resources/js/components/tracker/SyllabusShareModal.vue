<script setup lang="ts">
import { toPng } from 'html-to-image';
import { Download, Loader2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import BaseModal from '@/components/BaseModal.vue';

export interface ShareSubject {
    id: number;
    name: string;
    icon?: string;
    tailwind_format?: string;
    completed: number;
    total: number;
    percent: number;
}

export interface ShareData {
    user: {
        name: string;
        username: string;
        image_url?: string | null;
        institution?: string | null;
    };
    course: string;
    group?: string;
    overallPercent: number;
    completedChapters: number;
    totalChapters: number;
    subjects: ShareSubject[];
}

const props = defineProps<{
    modelValue: boolean;
    data: ShareData;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();

const isGenerating = ref(false);
const shareCardRef = ref<HTMLElement | null>(null);

const siteTrackerUrl = computed(() => {
    if (typeof window !== 'undefined' && window.location.host) {
        return `${window.location.host}/tracker`;
    }

    return 'hscstack.site/tracker';
});

const allSubjects = computed(() => {
    return props.data.subjects || [];
});

const catMood = computed(() => {
    const p = props.data.overallPercent;

    if (p === 0) {
        return {
            emoji: '🙀',
            title: 'Sleeping Cat',
            subtitle: 'তুমি তো শেষ মামা',
            badgeClass: 'bg-amber-500/10 border-amber-400/30 text-amber-300',
        };
    } else if (p < 25) {
        return {
            emoji: '😿',
            title: 'Baby Steps Kitty',
            subtitle: 'আরও পড়া লাগবে মামা!',
            badgeClass: 'bg-orange-500/10 border-orange-400/30 text-orange-300',
        };
    } else if (p < 50) {
        return {
            emoji: '😼',
            title: 'Locking In Cat',
            subtitle: 'ভালোই শুরু করেছ মামা, কিন্তু আরও পড়া লাগবে!',
            badgeClass: 'bg-yellow-500/10 border-yellow-400/30 text-yellow-300',
        };
    } else if (p < 75) {
        return {
            emoji: '😸',
            title: 'Speedrun Cat',
            subtitle: 'এইতো যতটুকু শেষ করছ আর ততটুকু...',
            badgeClass: 'bg-sky-500/10 border-sky-400/30 text-sky-300',
        };
    } else if (p < 100) {
        return {
            emoji: '😻',
            title: 'Gigachad Cat',
            subtitle: 'আর একটু মামা...',
            badgeClass: 'bg-indigo-500/10 border-indigo-400/30 text-indigo-300',
        };
    } else {
        return {
            emoji: '👑🐱',
            title: 'Cat God (100%)',
            subtitle: 'সিলেবাস যে শেষ, মামি জানে?',
            badgeClass:
                'bg-emerald-500/10 border-emerald-400/30 text-emerald-300',
        };
    }
});

async function downloadCard() {
    if (!shareCardRef.value || isGenerating.value) {
        return;
    }

    try {
        isGenerating.value = true;
        await new Promise((resolve) => setTimeout(resolve, 100));

        const dataUrl = await toPng(shareCardRef.value, {
            pixelRatio: 2.5,
            cacheBust: true,
            quality: 0.98,
        });

        const link = document.createElement('a');
        link.download = `${props.data.user.username}-syllabus-progress.png`;
        link.href = dataUrl;
        link.click();
    } catch (err) {
        console.error('Failed to generate sharing image', err);
    } finally {
        isGenerating.value = false;
    }
}
</script>

<template>
    <BaseModal
        :is-open="modelValue"
        title="Share Syllabus Progress"
        description="Download your progress card to share on social media"
        max-width="md"
        @close="emit('update:modelValue', false)"
    >
        <div class="p-4 sm:p-6">
            <!-- Share Card Canvas Preview -->
            <div
                ref="shareCardRef"
                class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-950 p-6 text-white shadow-2xl"
                style="font-family: inherit"
            >
                <!-- Glowing Ambient Blobs -->
                <div
                    class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-indigo-500/20 blur-3xl"
                />
                <div
                    class="absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-emerald-500/20 blur-3xl"
                />

                <!-- Header: HSCStack Official Brand Logo & Course Pill -->
                <div
                    class="relative z-10 flex items-center justify-between border-b border-white/10 pb-4"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-xl bg-slate-900 shadow-md ring-1 ring-white/20"
                        >
                            <!-- Embedded SVG Logo -->
                            <svg
                                viewBox="15 15 136 136"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6.5 w-6.5 object-contain"
                            >
                                <path
                                    d="M83 34L38 56.5L83 79L128 56.5L83 34Z"
                                    fill="#818CF8"
                                />
                                <path
                                    d="M38 83L83 105.5L128 83"
                                    stroke="#E0E7FF"
                                    stroke-width="13"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M38 108L83 130.5L128 108"
                                    stroke="#38BDF8"
                                    stroke-width="13"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M83 34L38 56.5L83 79L128 56.5L83 34Z"
                                    stroke="#FFFFFF"
                                    stroke-width="8"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-base leading-none font-extrabold tracking-tight text-white"
                            >
                                HSC<span class="text-indigo-400">Stack</span>
                            </h3>
                            <p
                                class="mt-0.5 text-[9px] font-semibold tracking-wider text-slate-400 uppercase"
                            >
                                Academic Tracker
                            </p>
                        </div>
                    </div>

                    <div
                        class="inline-flex items-center rounded-full border border-indigo-400/30 bg-indigo-500/10 px-3 py-1 text-xs font-extrabold tracking-wider text-indigo-300 uppercase"
                    >
                        {{ data.course.toUpperCase()
                        }}<template v-if="data.group">
                            · {{ data.group }}</template
                        >
                    </div>
                </div>

                <!-- User Profile Header -->
                <div class="relative z-10 mt-5 flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-indigo-400/40 bg-indigo-900/60 font-bold text-white shadow-inner"
                    >
                        <img
                            v-if="data.user.image_url"
                            :src="data.user.image_url"
                            :alt="data.user.name"
                            class="h-full w-full object-cover"
                            crossorigin="anonymous"
                        />
                        <span v-else class="text-base font-extrabold">
                            {{ data.user.name.charAt(0).toUpperCase() }}
                        </span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h4
                            class="truncate text-base font-extrabold text-white"
                        >
                            {{ data.user.name }}
                        </h4>
                        <p class="text-xs font-medium text-slate-400">
                            @{{ data.user.username }}
                            <span
                                v-if="data.user.institution"
                                class="text-slate-500"
                            >
                                · {{ data.user.institution }}
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Central Score Banner with Meme Cat Progress Status -->
                <div
                    class="relative z-10 mt-5 rounded-2xl border border-white/10 bg-white/5 p-4.5 backdrop-blur-md"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <span
                                class="text-[10px] font-bold tracking-wider text-indigo-300 uppercase"
                            >
                                Syllabus Completed
                            </span>
                            <div class="mt-1">
                                <span
                                    class="bg-gradient-to-r from-white via-indigo-100 to-emerald-300 bg-clip-text text-4xl font-black tracking-tight text-transparent"
                                >
                                    {{ data.overallPercent }}%
                                </span>
                            </div>
                            <p
                                class="mt-0.5 text-[11px] font-medium text-slate-300"
                            >
                                {{ catMood.subtitle }}
                            </p>
                        </div>

                        <!-- Meme Cat Mood Avatar / Icon Box -->
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border shadow-inner transition-transform hover:scale-105"
                            :class="catMood.badgeClass"
                        >
                            <span class="text-3xl leading-none select-none">{{
                                catMood.emoji
                            }}</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div
                        class="mt-3.5 h-2.5 w-full overflow-hidden rounded-full bg-slate-800/80 ring-1 ring-white/10"
                    >
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-indigo-400 to-emerald-400 shadow-sm transition-all duration-500"
                            :style="{ width: `${data.overallPercent}%` }"
                        />
                    </div>
                </div>

                <!-- Subject Grid Breakdown (All Subjects, 2 Columns) -->
                <div
                    v-if="allSubjects.length > 0"
                    class="relative z-10 mt-4 grid grid-cols-2 gap-2"
                >
                    <div
                        v-for="subj in allSubjects"
                        :key="subj.id"
                        class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.03] px-3 py-2"
                    >
                        <span
                            class="truncate text-[11px] font-bold text-slate-200"
                        >
                            {{ subj.name }}
                        </span>
                        <span
                            class="shrink-0 text-[11px] font-black text-emerald-400"
                        >
                            {{ subj.percent }}%
                        </span>
                    </div>
                </div>

                <!-- Card Footer: Watermark / Link -->
                <div
                    class="relative z-10 mt-5 flex items-center justify-between border-t border-white/10 pt-3 text-[10px] text-slate-400"
                >
                    <span>Track your syllabus now</span>
                    <span class="font-bold text-indigo-300">
                        {{ siteTrackerUrl }}
                    </span>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between gap-3">
                <button
                    type="button"
                    @click="emit('update:modelValue', false)"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Close
                </button>

                <button
                    type="button"
                    @click="downloadCard"
                    :disabled="isGenerating"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 disabled:opacity-50 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                >
                    <Loader2
                        v-if="isGenerating"
                        class="h-3.5 w-3.5 animate-spin"
                    />
                    <Download v-else class="h-3.5 w-3.5" />
                    <span>{{
                        isGenerating ? 'Generating...' : 'Download Image'
                    }}</span>
                </button>
            </div>
        </template>
    </BaseModal>
</template>
