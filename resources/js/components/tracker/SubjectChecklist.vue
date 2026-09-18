<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BookOpen,
    Check,
    ChevronDown,
    ChevronUp,
    ExternalLink,
    Loader2,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import BaseModal from '@/components/BaseModal.vue';

export interface TopLevelNode {
    id: number;
    subject_id: number;
    name: string;
    slug: string;
    sort_order?: number;
}

export interface SubjectItem {
    id: number;
    name: string;
    english_name?: string | null;
    slug: string;
    course: 'hsc' | 'ssc';
    sort_order?: number;
    nodes: TopLevelNode[];
}

interface Props {
    course: 'hsc' | 'ssc';
    userCurriculum?: 'hsc' | 'ssc';
    subjects: SubjectItem[];
    completedNodeIds: number[];
    isAuthenticated: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'require-auth'): void;
}>();

// Active completed IDs state
const completedIds = ref<Set<number>>(new Set(props.completedNodeIds));

watch(
    () => props.completedNodeIds,
    (newIds) => {
        completedIds.value = new Set(newIds);
    },
    { deep: true },
);

// Expanded accordion cards state
const expandedSubjectIds = ref<Set<number>>(
    new Set(props.subjects.slice(0, 3).map((s) => s.id)),
);

function toggleExpand(subjectId: number) {
    if (expandedSubjectIds.value.has(subjectId)) {
        expandedSubjectIds.value.delete(subjectId);
    } else {
        expandedSubjectIds.value.add(subjectId);
    }
}

const showSwitchConfirmModal = ref(false);
const pendingCurriculum = ref<'hsc' | 'ssc'>('hsc');
const isSwitching = ref(false);

function switchCourse(newCourse: 'hsc' | 'ssc') {
    if (
        props.isAuthenticated &&
        props.userCurriculum &&
        props.userCurriculum !== newCourse
    ) {
        pendingCurriculum.value = newCourse;
        showSwitchConfirmModal.value = true;

        return;
    }

    router.get(
        '/tracker',
        { course: newCourse },
        { preserveState: true, preserveScroll: true },
    );
}

function confirmSwitchCurriculum() {
    isSwitching.value = true;
    router.post(
        '/tracker/curriculum',
        { curriculum: pendingCurriculum.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showSwitchConfirmModal.value = false;
            },
            onFinish: () => {
                isSwitching.value = false;
            },
        },
    );
}

// Optimistic node toggle
function toggleNodeCompletion(node: TopLevelNode) {
    if (!props.isAuthenticated) {
        emit('require-auth');

        return;
    }

    const wasCompleted = completedIds.value.has(node.id);

    if (wasCompleted) {
        completedIds.value.delete(node.id);
    } else {
        completedIds.value.add(node.id);
    }

    router.post(
        `/tracker/nodes/${node.id}/toggle`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                // Revert on error
                if (wasCompleted) {
                    completedIds.value.add(node.id);
                } else {
                    completedIds.value.delete(node.id);
                }
            },
        },
    );
}

// Overall stats
const totalChapters = computed(() => {
    return props.subjects.reduce((sum, s) => sum + s.nodes.length, 0);
});

const totalCompletedChapters = computed(() => {
    let count = 0;

    for (const s of props.subjects) {
        for (const n of s.nodes) {
            if (completedIds.value.has(n.id)) {
                count++;
            }
        }
    }

    return count;
});

const overallPercentage = computed(() => {
    if (totalChapters.value === 0) {
        return 0;
    }

    return Math.round(
        (totalCompletedChapters.value / totalChapters.value) * 100,
    );
});

function getSubjectProgress(subject: SubjectItem) {
    const total = subject.nodes.length;

    if (total === 0) {
        return { completed: 0, total: 0, percent: 0 };
    }

    const completed = subject.nodes.filter((n) =>
        completedIds.value.has(n.id),
    ).length;
    const percent = Math.round((completed / total) * 100);

    return { completed, total, percent };
}
</script>

<template>
    <div class="flex flex-col gap-5">
        <!-- Checklist Header Card -->
        <div
            class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs sm:p-6 dark:border-gray-800/90 dark:bg-gray-900"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400"
                        >
                            <BookOpen class="h-4 w-4" />
                        </span>
                        <h2
                            class="text-base font-bold text-slate-900 sm:text-lg dark:text-white"
                        >
                            Syllabus Chapter Checklist
                        </h2>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                        পড়া শেষ হওয়া অধ্যায়গুলোতে টিক দিয়ে সিলেবাসের অগ্রগতি
                        ট্র্যাক করুন
                    </p>
                </div>

                <!-- Course Switcher Tabs -->
                <div
                    class="flex self-start rounded-2xl bg-slate-100 p-1 sm:self-auto dark:bg-gray-800/80"
                >
                    <button
                        type="button"
                        @click="switchCourse('hsc')"
                        :class="[
                            course === 'hsc'
                                ? 'bg-white text-indigo-600 shadow-2xs dark:bg-gray-900 dark:text-indigo-400'
                                : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-white',
                        ]"
                        class="rounded-xl px-4 py-1.5 text-xs font-bold transition"
                    >
                        HSC
                    </button>
                    <button
                        type="button"
                        @click="switchCourse('ssc')"
                        :class="[
                            course === 'ssc'
                                ? 'bg-white text-indigo-600 shadow-2xs dark:bg-gray-900 dark:text-indigo-400'
                                : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-white',
                        ]"
                        class="rounded-xl px-4 py-1.5 text-xs font-bold transition"
                    >
                        SSC
                    </button>
                </div>
            </div>

            <!-- Overall Progress Bar -->
            <div
                class="mt-5 rounded-2xl border border-slate-100 bg-slate-50/70 p-4 dark:border-gray-800/80 dark:bg-gray-800/40"
            >
                <div
                    class="flex items-center justify-between text-xs font-semibold"
                >
                    <span class="text-slate-700 dark:text-gray-300">
                        Total Course Completion
                    </span>
                    <span class="text-indigo-600 dark:text-indigo-400">
                        {{ totalCompletedChapters }} /
                        {{ totalChapters }} Chapters ({{ overallPercentage }}%)
                    </span>
                </div>
                <div
                    class="mt-2.5 h-2.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-gray-700"
                >
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-emerald-500 transition-all duration-500"
                        :style="{ width: `${overallPercentage}%` }"
                    />
                </div>
            </div>
        </div>

        <!-- Subjects & Chapters List -->
        <div class="flex flex-col gap-3">
            <div
                v-for="subject in subjects"
                :key="subject.id"
                class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-2xs transition dark:border-gray-800/90 dark:bg-gray-900"
            >
                <!-- Subject Header (Clickable to Toggle) -->
                <div
                    @click="toggleExpand(subject.id)"
                    class="flex cursor-pointer items-center justify-between p-4.5 transition hover:bg-slate-50/70 sm:p-5 dark:hover:bg-gray-800/40"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-xs font-extrabold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            {{
                                subject.english_name
                                    ? subject.english_name
                                          .charAt(0)
                                          .toUpperCase()
                                    : subject.name.charAt(0)
                            }}
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold text-slate-900 sm:text-base dark:text-white"
                            >
                                {{ subject.name }}
                            </h3>
                            <div
                                class="flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400"
                            >
                                <span v-if="subject.english_name"
                                    >{{ subject.english_name }} •
                                </span>
                                <span
                                    >{{
                                        getSubjectProgress(subject).completed
                                    }}/{{
                                        getSubjectProgress(subject).total
                                    }}
                                    Chapters</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Subject Progress Indicator & Chevron -->
                    <div class="flex items-center gap-3">
                        <div class="hidden flex-col items-end gap-1 sm:flex">
                            <span
                                class="text-xs font-bold text-slate-800 dark:text-gray-200"
                            >
                                {{ getSubjectProgress(subject).percent }}%
                            </span>
                            <div
                                class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100 dark:bg-gray-800"
                            >
                                <div
                                    class="h-full rounded-full bg-indigo-600 transition-all duration-300 dark:bg-indigo-400"
                                    :style="{
                                        width: `${getSubjectProgress(subject).percent}%`,
                                    }"
                                />
                            </div>
                        </div>

                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                        >
                            <component
                                :is="
                                    expandedSubjectIds.has(subject.id)
                                        ? ChevronUp
                                        : ChevronDown
                                "
                                class="h-4 w-4"
                            />
                        </div>
                    </div>
                </div>

                <!-- Chapters List (Accordion Content) -->
                <div
                    v-if="expandedSubjectIds.has(subject.id)"
                    class="divide-y divide-slate-100 border-t border-slate-100 px-4.5 py-3 sm:px-5 dark:divide-gray-800/60 dark:border-gray-800/80"
                >
                    <div
                        v-if="subject.nodes.length === 0"
                        class="py-4 text-center text-xs text-slate-400 dark:text-gray-500"
                    >
                        No chapters available for this subject yet.
                    </div>

                    <div
                        v-for="node in subject.nodes"
                        :key="node.id"
                        class="group flex items-center justify-between py-2.5 transition"
                    >
                        <!-- Checkbox & Chapter Name -->
                        <div
                            @click="toggleNodeCompletion(node)"
                            class="flex flex-1 cursor-pointer items-center gap-3 pr-2 select-none"
                        >
                            <!-- Custom Checkbox -->
                            <div
                                :class="[
                                    completedIds.has(node.id)
                                        ? 'border-emerald-600 bg-emerald-600 text-white'
                                        : 'border-slate-300 bg-white hover:border-slate-400 dark:border-gray-700 dark:bg-gray-800',
                                ]"
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-lg border transition duration-150"
                            >
                                <Check
                                    v-if="completedIds.has(node.id)"
                                    class="h-3.5 w-3.5 stroke-[3]"
                                />
                            </div>

                            <!-- Title -->
                            <span
                                :class="[
                                    completedIds.has(node.id)
                                        ? 'text-slate-400 line-through dark:text-gray-500'
                                        : 'text-slate-800 dark:text-gray-200',
                                ]"
                                class="text-xs font-medium transition sm:text-sm"
                            >
                                {{ node.name }}
                            </span>
                        </div>

                        <!-- Link to Subject Resources -->
                        <Link
                            :href="`/${subject.slug}`"
                            target="_blank"
                            title="Open subject folder"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 opacity-80 transition group-hover:opacity-100 hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                        >
                            <ExternalLink class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Switch Curriculum Confirmation Modal -->
        <BaseModal
            :is-open="showSwitchConfirmModal"
            title="কারিকুলাম পরিবর্তন করবেন?"
            description="সতর্কতা: আপনার সিলেবাস ট্র্যাকার রিসেট হবে"
            max-width="md"
            @close="showSwitchConfirmModal = false"
        >
            <div class="space-y-4 p-5 text-slate-800 dark:text-gray-200">
                <div
                    class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50/80 p-3.5 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200"
                >
                    <AlertTriangle
                        class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"
                    />
                    <div class="space-y-1">
                        <p class="font-bold">সতর্কতা</p>
                        <p class="leading-relaxed">
                            কারিকুলাম পরিবর্তন করলে স্টাডি ট্র্যাকারের টিক দেওয়া
                            সকল অধ্যায়ের অগ্রগতি রিসেট হয়ে যাবে।
                        </p>
                    </div>
                </div>

                <p class="text-xs text-slate-500 dark:text-gray-400">
                    আপনি কি নিশ্চিতভাবে কারিকুলাম
                    <span
                        class="font-bold text-slate-800 uppercase dark:text-gray-200"
                        >{{ pendingCurriculum }}</span
                    >
                    এ পরিবর্তন করতে চান?
                </p>
            </div>

            <div
                class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-gray-800 dark:bg-gray-900/60"
            >
                <button
                    type="button"
                    @click="showSwitchConfirmModal = false"
                    :disabled="isSwitching"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    বাতিল
                </button>
                <button
                    type="button"
                    @click="confirmSwitchCurriculum"
                    :disabled="isSwitching"
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 active:scale-95 disabled:opacity-50"
                >
                    <Loader2
                        v-if="isSwitching"
                        class="h-3.5 w-3.5 animate-spin"
                    />
                    <span>{{
                        isSwitching
                            ? 'পরিবর্তন হচ্ছে...'
                            : 'হ্যাঁ, পরিবর্তন করুন'
                    }}</span>
                </button>
            </div>
        </BaseModal>
    </div>
</template>
