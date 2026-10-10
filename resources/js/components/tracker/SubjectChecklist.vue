<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Check,
    ChevronDown,
    ChevronUp,
    ExternalLink,
    Loader2,
    Share2,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import SyllabusShareModal from '@/components/tracker/SyllabusShareModal.vue';

export interface TopLevelNode {
    id: number;
    subject_id: number;
    name: string;
    slug: string;
    sort_order?: number;
    weight?: number;
}

export interface SubjectItem {
    id: number;
    name: string;
    slug: string;
    course: 'hsc' | 'ssc';
    sort_order?: number;
    nodes: TopLevelNode[];
}

interface Props {
    course: 'hsc' | 'ssc';
    group?: 'science' | 'humanities' | 'commerce';
    subjects: SubjectItem[];
    completedNodeIds: number[];
    isAuthenticated: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    group: 'science',
});

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
const expandedSubjectIds = ref<Set<number>>(new Set());

function toggleExpand(subjectId: number) {
    if (expandedSubjectIds.value.has(subjectId)) {
        expandedSubjectIds.value.delete(subjectId);
    } else {
        expandedSubjectIds.value.add(subjectId);
    }
}

const showSwitchConfirmModal = ref(false);
const pendingCurriculum = ref<'hsc' | 'ssc'>(props.course);
const pendingGroup = ref<'science' | 'humanities' | 'commerce'>(props.group);
const isSwitching = ref(false);

watch(
    () => [props.course, props.group] as const,
    ([newCourse, newGroup]) => {
        pendingCurriculum.value = newCourse;
        pendingGroup.value = newGroup;
    },
);

function openSwitchModal() {
    pendingCurriculum.value = props.course;
    pendingGroup.value = props.group;
    showSwitchConfirmModal.value = true;
}

function formatGroup(groupKey: string) {
    switch (groupKey) {
        case 'humanities':
            return 'Humanities';
        case 'commerce':
            return 'Commerce';
        default:
            return 'Science';
    }
}

function confirmSwitchCurriculum() {
    if (!props.isAuthenticated) {
        showSwitchConfirmModal.value = false;
        router.get(
            '/tracker',
            {
                course: pendingCurriculum.value,
                group: pendingGroup.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );

        return;
    }

    isSwitching.value = true;
    router.post(
        '/tracker/curriculum',
        {
            curriculum: pendingCurriculum.value,
            group: pendingGroup.value,
        },
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
    const subjectsWithChapters = props.subjects.filter(
        (s) => s.nodes && s.nodes.length > 0,
    );

    if (subjectsWithChapters.length === 0) {
        return 0;
    }

    let totalPercentSum = 0;

    for (const s of subjectsWithChapters) {
        totalPercentSum += getSubjectProgress(s).percent;
    }

    return Math.round(totalPercentSum / subjectsWithChapters.length);
});

function getSubjectProgress(subject: SubjectItem) {
    const total = subject.nodes.length;

    if (total === 0) {
        return { completed: 0, total: 0, percent: 0 };
    }

    let totalWeight = 0;
    let completedWeight = 0;
    let completedCount = 0;

    for (const node of subject.nodes) {
        const weight = node.weight || 2;
        totalWeight += weight;

        if (completedIds.value.has(node.id)) {
            completedCount++;
            completedWeight += weight;
        }
    }

    const percent =
        totalWeight > 0 ? Math.round((completedWeight / totalWeight) * 100) : 0;

    return { completed: completedCount, total, percent };
}

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const showShareModal = ref(false);

const shareData = computed(() => {
    return {
        user: {
            name: String(authUser.value?.name || 'HSC Student'),
            username: String(authUser.value?.username || 'student'),
            image_url: (authUser.value?.image_url as string | null) || null,
            institution: (authUser.value?.institution as string | null) || null,
        },
        course: `${props.course.toUpperCase()} (${formatGroup(props.group)})`,
        overallPercent: overallPercentage.value,
        completedChapters: totalCompletedChapters.value,
        totalChapters: totalChapters.value,
        subjects: props.subjects.map((s) => ({
            id: s.id,
            name: s.name,
            completed: getSubjectProgress(s).completed,
            total: getSubjectProgress(s).total,
            percent: getSubjectProgress(s).percent,
        })),
    };
});
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

                <!-- Right: Share Button + Current Curriculum Indicator with Change Link -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        type="button"
                        @click="showShareModal = true"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/90 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-indigo-300 hover:bg-indigo-50/50 hover:text-indigo-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-indigo-500/40 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400"
                        title="Share your progress on social media"
                    >
                        <Share2
                            class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                        />
                        <span>Share Progress</span>
                    </button>

                    <!-- Curriculum & Group Clickable Badges -->
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="openSwitchModal"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-black uppercase shadow-2xs transition hover:shadow-xs active:scale-95"
                            :class="
                                course === 'ssc'
                                    ? 'border-emerald-300/80 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50'
                                    : 'border-indigo-300/80 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-700/60 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50'
                            "
                            title="কারিকুলাম ও বিভাগ পরিবর্তন করতে ক্লিক করুন"
                        >
                            <span>{{ course }}</span>
                            <ChevronDown class="h-3 w-3 opacity-60" />
                        </button>

                        <button
                            type="button"
                            @click="openSwitchModal"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/90 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-600 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-indigo-500/40 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400"
                            title="কারিকুলাম ও বিভাগ পরিবর্তন করতে ক্লিক করুন"
                        >
                            <span>{{ formatGroup(group) }}</span>
                            <ChevronDown class="h-3 w-3 opacity-60" />
                        </button>
                    </div>
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
                            {{ subject.name.charAt(0) }}
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

                            <!-- Title + Size Text in Bracket -->
                            <div class="flex flex-wrap items-center gap-1.5">
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

                                <span
                                    class="text-[11px] font-normal text-slate-400 dark:text-gray-500"
                                >
                                    ({{
                                        node.weight === 3
                                            ? 'Large Chapter'
                                            : node.weight === 1
                                              ? 'Small Chapter'
                                              : 'Normal Chapter'
                                    }})
                                </span>
                            </div>
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

        <!-- Switch Curriculum & Group Modal -->
        <BaseModal
            :is-open="showSwitchConfirmModal"
            title="কারিকুলাম ও বিভাগ পরিবর্তন করুন"
            description="আপনার পড়ার কারিকুলাম লেভেল ও বিভাগ নির্বাচন করুন"
            max-width="md"
            @close="showSwitchConfirmModal = false"
        >
            <div class="space-y-4 p-5 text-slate-800 dark:text-gray-200">
                <!-- Curriculum Selection -->
                <div>
                    <label
                        class="mb-2 block text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        কারিকুলাম লেভেল
                    </label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <button
                            type="button"
                            @click="pendingCurriculum = 'hsc'"
                            :class="[
                                pendingCurriculum === 'hsc'
                                    ? 'border-indigo-600 bg-indigo-50/60 text-indigo-950 ring-1 ring-indigo-600 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-200'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                            class="flex cursor-pointer items-center justify-between rounded-2xl border p-3.5 text-left transition"
                        >
                            <div>
                                <p class="text-sm font-bold">HSC</p>
                                <p
                                    class="text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    Class 11–12
                                </p>
                            </div>
                            <span
                                v-if="pendingCurriculum === 'hsc'"
                                class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[10px] font-bold text-white dark:bg-indigo-500"
                            >
                                ✓
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="pendingCurriculum = 'ssc'"
                            :class="[
                                pendingCurriculum === 'ssc'
                                    ? 'border-emerald-600 bg-emerald-50/60 text-emerald-950 ring-1 ring-emerald-600 dark:border-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-200'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                            class="flex cursor-pointer items-center justify-between rounded-2xl border p-3.5 text-left transition"
                        >
                            <div>
                                <p class="text-sm font-bold">SSC</p>
                                <p
                                    class="text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    Class 9–10
                                </p>
                            </div>
                            <span
                                v-if="pendingCurriculum === 'ssc'"
                                class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-[10px] font-bold text-white dark:bg-emerald-500"
                            >
                                ✓
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Group Selection -->
                <div>
                    <label
                        class="mb-2 block text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        বিভাগ নির্বাচন করুন
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            @click="pendingGroup = 'science'"
                            :class="[
                                pendingGroup === 'science'
                                    ? 'border-indigo-600 bg-indigo-50/60 text-indigo-950 ring-1 ring-indigo-600 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-200'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                            class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border p-3 text-center transition"
                        >
                            <span class="text-xs font-bold">বিজ্ঞান</span>
                            <span
                                class="text-[10px] text-slate-500 dark:text-gray-400"
                            >
                                Science
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="pendingGroup = 'humanities'"
                            :class="[
                                pendingGroup === 'humanities'
                                    ? 'border-indigo-600 bg-indigo-50/60 text-indigo-950 ring-1 ring-indigo-600 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-200'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                            class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border p-3 text-center transition"
                        >
                            <span class="text-xs font-bold">মানবিক</span>
                            <span
                                class="text-[10px] text-slate-500 dark:text-gray-400"
                            >
                                Humanities
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="pendingGroup = 'commerce'"
                            :class="[
                                pendingGroup === 'commerce'
                                    ? 'border-indigo-600 bg-indigo-50/60 text-indigo-950 ring-1 ring-indigo-600 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-200'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                            class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border p-3 text-center transition"
                        >
                            <span class="text-xs font-bold"
                                >ব্যবসায় শিক্ষা</span
                            >
                            <span
                                class="text-[10px] text-slate-500 dark:text-gray-400"
                            >
                                Commerce
                            </span>
                        </button>
                    </div>
                </div>
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
                        isSwitching ? 'সংরক্ষণ হচ্ছে...' : 'সংরক্ষণ করুন'
                    }}</span>
                </button>
            </div>
        </BaseModal>

        <!-- Social Syllabus Share Modal -->
        <SyllabusShareModal v-model="showShareModal" :data="shareData" />
    </div>
</template>
