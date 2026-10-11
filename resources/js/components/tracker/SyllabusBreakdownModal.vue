<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    ChevronDown,
    ChevronRight,
    Info,
    Share2,
} from 'lucide-vue-next';
import { ref } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import SubjectIcon from '@/components/SubjectIcon.vue';

export interface SyllabusChapter {
    id: number;
    name: string;
    weight: number;
    is_completed: boolean;
}

export interface SyllabusSubject {
    id: number;
    name: string;
    slug?: string;
    course?: string;
    tailwind_format?: string;
    icon?: string;
    completed: number;
    total: number;
    percent: number;
    chapters?: SyllabusChapter[];
}

export interface SyllabusProgressData {
    course: string;
    group?: string;
    overallPercent: number;
    completedChapters: number;
    totalChapters: number;
    subjects: SyllabusSubject[];
}

const props = defineProps<{
    modelValue: boolean;
    user: {
        name: string;
        username?: string;
    };
    syllabusProgress: SyllabusProgressData;
    isOwnProfile: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'share'): void;
}>();

const showAlgorithmInfo = ref(false);
const expandedSubjects = ref<Set<number>>(new Set());

const toggleSubjectExpanded = (subjId: number) => {
    if (expandedSubjects.value.has(subjId)) {
        expandedSubjects.value.delete(subjId);
    } else {
        expandedSubjects.value.add(subjId);
    }
};

const formatGroupName = (group?: string) => {
    if (!group) {
        return '';
    }

    return group.charAt(0).toUpperCase() + group.slice(1);
};

function toggleChapterCompletion(subj: SyllabusSubject, chap: SyllabusChapter) {
    if (!props.isOwnProfile) return;

    const wasCompleted = chap.is_completed;
    chap.is_completed = !wasCompleted;

    if (wasCompleted) {
        subj.completed = Math.max(0, subj.completed - 1);
    } else {
        subj.completed = Math.min(subj.total, subj.completed + 1);
    }

    if (subj.chapters && subj.chapters.length > 0) {
        let totalWeight = 0;
        let completedWeight = 0;
        for (const c of subj.chapters) {
            const w = c.weight || 2;
            totalWeight += w;
            if (c.is_completed) {
                completedWeight += w;
            }
        }
        subj.percent =
            totalWeight > 0
                ? Math.round((completedWeight / totalWeight) * 100)
                : 0;
    }

    if (props.syllabusProgress?.subjects) {
        let validSubjectsCount = 0;
        let totalSubjectPercents = 0;
        let totalCompletedChapters = 0;
        for (const s of props.syllabusProgress.subjects) {
            totalCompletedChapters += s.completed;
            if (s.total > 0) {
                totalSubjectPercents += s.percent;
                validSubjectsCount++;
            }
        }
        if (validSubjectsCount > 0) {
            props.syllabusProgress.overallPercent = Math.round(
                totalSubjectPercents / validSubjectsCount,
            );
        }
        props.syllabusProgress.completedChapters = totalCompletedChapters;
    }

    router.post(
        `/tracker/nodes/${chap.id}/toggle`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                chap.is_completed = wasCompleted;
                router.reload({ only: ['syllabusProgress'] });
            },
        },
    );
}
</script>

<template>
    <BaseModal
        :is-open="modelValue"
        :title="`${user.name}'s Syllabus Progress`"
        :description="`${syllabusProgress.overallPercent}% completed (${syllabusProgress.completedChapters}/${syllabusProgress.totalChapters} chapters in ${syllabusProgress.course.toUpperCase()}${syllabusProgress.group ? ' ' + formatGroupName(syllabusProgress.group) : ''})`"
        max-width="lg"
        @close="emit('update:modelValue', false)"
    >
        <div class="max-h-[70vh] space-y-3 overflow-y-auto p-4 sm:p-6">
            <!-- Algorithm Info Banner / Toggle -->
            <div
                class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3 transition-colors dark:border-indigo-900/40 dark:bg-indigo-950/30"
            >
                <button
                    type="button"
                    @click="showAlgorithmInfo = !showAlgorithmInfo"
                    class="flex w-full cursor-pointer items-center justify-between text-left text-xs font-semibold text-indigo-900 dark:text-indigo-200"
                >
                    <span class="flex items-center gap-1.5">
                        <Info
                            class="h-3.5 w-3.5 shrink-0 text-indigo-600 dark:text-indigo-400"
                        />
                        সিলেবাসের অগ্রগতি কীভাবে হিসাব করা হয়?
                    </span>
                    <span
                        class="text-[10px] font-medium text-indigo-600 dark:text-indigo-400"
                    >
                        {{ showAlgorithmInfo ? 'লুকান' : 'বিস্তারিত' }}
                    </span>
                </button>

                <div
                    v-if="showAlgorithmInfo"
                    class="mt-2.5 space-y-2 border-t border-indigo-100/80 pt-2.5 text-[11px] leading-relaxed text-indigo-950/80 dark:border-indigo-900/40 dark:text-indigo-200/90"
                >
                    <p>
                        <strong>১. অধ্যায়ের গুরুত্ব (Weight):</strong> অধ্যায়ের
                        পরিধি অনুযায়ী পয়েন্ট নির্ধারিত (ছোট অধ্যায় = ১ পয়েন্ট,
                        সাধারণ = ২ পয়েন্ট, বড় অধ্যায় = ৩ পয়েন্ট) থাকে।
                    </p>
                    <p>
                        <strong>২. বিষয়ের অগ্রগতি:</strong> প্রতিটি বিষয়ের
                        সম্পন্ন হওয়া অধ্যায়ের মোট পয়েন্টকে ঐ বিষয়ের সর্বমোট
                        পয়েন্ট দিয়ে ভাগ করে শতকরা হার বের করা হয়।
                    </p>
                    <p>
                        <strong>৩. সামগ্রিক অগ্রগতি (Overall):</strong> সব
                        বিষয়ের শতকরা অগ্রগতির সমান গড় (Average) করে মোট অগ্রগতি
                        বের করা হয়, যাতে কোনো বিষয়ে অধ্যায় বেশি থাকলেও ফলাফলে
                        বৈষম্য না ঘটে।
                    </p>
                </div>
            </div>

            <div
                v-for="subj in syllabusProgress.subjects"
                :key="subj.id"
                class="rounded-2xl border border-slate-100 bg-slate-50/60 p-3.5 transition sm:p-4 dark:border-gray-800/80 dark:bg-gray-800/40"
            >
                <div
                    @click="toggleSubjectExpanded(subj.id)"
                    class="flex cursor-pointer items-center justify-between gap-3 select-none"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            :class="[
                                subj.tailwind_format ||
                                    'bg-indigo-50 text-indigo-600',
                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-black/5 dark:border-white/10',
                            ]"
                        >
                            <SubjectIcon
                                :name="subj.icon"
                                class-name="h-4.5 w-4.5 stroke-[2]"
                            />
                        </div>
                        <div class="min-w-0">
                            <h4
                                class="truncate text-xs font-bold text-slate-900 sm:text-sm dark:text-gray-100"
                            >
                                {{ subj.name }}
                            </h4>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <div class="text-right">
                            <span
                                class="text-xs font-extrabold text-slate-900 dark:text-gray-100"
                            >
                                {{ subj.percent }}%
                            </span>
                            <p
                                class="text-[10px] text-slate-400 dark:text-gray-500"
                            >
                                {{ subj.completed }}/{{ subj.total }} Chapters
                            </p>
                        </div>
                        <component
                            :is="
                                expandedSubjects.has(subj.id)
                                    ? ChevronDown
                                    : ChevronRight
                            "
                            class="h-4 w-4 text-slate-400 transition dark:text-gray-500"
                        />
                    </div>
                </div>

                <!-- Progress Bar -->
                <div
                    class="mt-2.5 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-gray-700"
                >
                    <div
                        class="h-full rounded-full bg-indigo-600 transition-all duration-300 dark:bg-indigo-400"
                        :style="{ width: `${subj.percent}%` }"
                    />
                </div>

                <!-- Expanded Chapters List -->
                <div
                    v-if="
                        expandedSubjects.has(subj.id) &&
                        subj.chapters &&
                        subj.chapters.length > 0
                    "
                    class="mt-3 divide-y divide-slate-200/60 rounded-xl border border-slate-200/60 bg-white/70 pt-0.5 dark:divide-gray-700/50 dark:border-gray-700/50 dark:bg-gray-900/40"
                >
                    <div
                        v-for="chap in subj.chapters"
                        :key="chap.id"
                        @click="
                            isOwnProfile
                                ? toggleChapterCompletion(subj, chap)
                                : null
                        "
                        class="flex items-center justify-between gap-2.5 px-3 py-2 text-xs transition"
                        :class="[
                            isOwnProfile
                                ? 'cursor-pointer hover:bg-slate-100/70 active:bg-slate-200/50 select-none dark:hover:bg-gray-800/60 dark:active:bg-gray-800'
                                : '',
                        ]"
                        :role="isOwnProfile ? 'button' : undefined"
                        :tabindex="isOwnProfile ? 0 : undefined"
                        @keydown.enter="
                            isOwnProfile
                                ? toggleChapterCompletion(subj, chap)
                                : null
                        "
                        @keydown.space.prevent="
                            isOwnProfile
                                ? toggleChapterCompletion(subj, chap)
                                : null
                        "
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                :class="[
                                    chap.is_completed
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-slate-300 dark:text-gray-600',
                                    'shrink-0 transition-colors',
                                ]"
                            >
                                <CheckCircle2
                                    v-if="chap.is_completed"
                                    class="h-4 w-4 fill-emerald-100 stroke-[2.2] dark:fill-emerald-950"
                                />
                                <div
                                    v-else
                                    class="h-3.5 w-3.5 rounded-full border-2 border-slate-300 transition-colors dark:border-gray-600"
                                />
                            </span>

                            <span
                                :class="[
                                    chap.is_completed
                                        ? 'font-medium text-slate-900 dark:text-gray-100'
                                        : 'text-slate-500 dark:text-gray-400',
                                    'truncate',
                                ]"
                            >
                                {{ chap.name }}
                            </span>
                        </div>

                        <span
                            class="shrink-0 text-[10px] text-slate-400 dark:text-gray-500"
                        >
                            {{
                                chap.weight === 3
                                    ? '(Large Chapter)'
                                    : chap.weight === 1
                                      ? '(Small Chapter)'
                                      : '(Normal Chapter)'
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div
                class="flex items-center gap-3"
                :class="isOwnProfile ? 'justify-between' : 'justify-end'"
            >
                <button
                    v-if="isOwnProfile"
                    type="button"
                    @click="emit('share')"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <Share2
                        class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                    />
                    <span>Share Progress</span>
                </button>

                <Link
                    href="/tracker"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                >
                    <span>আমার প্রগ্রেস ট্র্যাক করুন</span>
                    <ArrowRight class="h-3.5 w-3.5" />
                </Link>
            </div>
        </template>
    </BaseModal>
</template>
