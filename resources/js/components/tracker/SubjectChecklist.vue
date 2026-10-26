<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowUp,
    BookOpen,
    Check,
    ChevronDown,
    ChevronRight,
    ChevronUp,
    ExternalLink,
    Loader2,
    Share2,
    SlidersHorizontal,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
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

export interface OptionalSubjectItem {
    id: number;
    name: string;
    course: string;
    group: string;
    is_optional: boolean;
}

interface Props {
    course: 'hsc' | 'ssc';
    group?: 'science' | 'humanities' | 'commerce';
    batch?: string | null;
    subjects: SubjectItem[];
    optionalSubjectIds?: number[];
    optionalSubjects?: OptionalSubjectItem[];
    completedNodeIds: number[];
    isAuthenticated: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    group: 'science',
    batch: null,
    optionalSubjectIds: () => [],
    optionalSubjects: () => [],
});

const emit = defineEmits<{
    (e: 'require-auth', message?: string): void;
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
const pendingBatch = ref<string>(props.batch || '');
const pendingOptionalSubjectIds = ref<Set<number>>(
    new Set(props.optionalSubjectIds),
);
const isSwitching = ref(false);

watch(
    () =>
        [
            props.course,
            props.group,
            props.batch,
            props.optionalSubjectIds,
        ] as const,
    ([newCourse, newGroup, newBatch, newOptionals]) => {
        pendingCurriculum.value = newCourse;
        pendingGroup.value = newGroup;
        pendingBatch.value = newBatch || '';
        pendingOptionalSubjectIds.value = new Set(newOptionals);
    },
);

// Optional subjects relevant to the pending curriculum and group
const relevantOptionalSubjects = computed(() => {
    return props.optionalSubjects.filter(
        (s) =>
            s.course === pendingCurriculum.value &&
            (s.group === pendingGroup.value || s.group === 'common'),
    );
});

const selectedOptionalSubjectsCount = computed(() => {
    return (props.optionalSubjectIds || []).length;
});

const selectedOptionalSubjectNames = computed(() => {
    const set = new Set(props.optionalSubjectIds || []);

    return props.optionalSubjects
        .filter(
            (s) =>
                s.course === props.course &&
                (s.group === props.group || s.group === 'common') &&
                set.has(s.id),
        )
        .map((s) => s.name);
});

const hasAvailableOptionalSubjects = computed(() => {
    return props.optionalSubjects.some(
        (s) =>
            s.course === props.course &&
            (s.group === props.group || s.group === 'common'),
    );
});

function toggleOptionalSubject(subjId: number) {
    if (pendingOptionalSubjectIds.value.has(subjId)) {
        pendingOptionalSubjectIds.value.delete(subjId);
    } else {
        pendingOptionalSubjectIds.value.add(subjId);
    }
}

function openSwitchModal() {
    if (!props.isAuthenticated) {
        emit('require-auth', 'Log in to change your curriculum.');

        return;
    }

    pendingCurriculum.value = props.course;
    pendingGroup.value = props.group;
    pendingBatch.value = props.batch || '';
    pendingOptionalSubjectIds.value = new Set(props.optionalSubjectIds);
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
    isSwitching.value = true;
    router.post(
        '/tracker/curriculum',
        {
            curriculum: pendingCurriculum.value,
            group: pendingGroup.value,
            batch: pendingBatch.value || null,
            optional_subject_ids: Array.from(pendingOptionalSubjectIds.value),
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
        emit('require-auth', 'Log in to save your chapter checklist progress.');

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

export interface NodeGroup {
    name: string | null;
    nodes: TopLevelNode[];
}

function getSubjectGroups(subject: SubjectItem): NodeGroup[] {
    const nodes = subject.nodes || [];

    return [{ name: null, nodes }];
}

// Collapsed state for sub-groups with names (default: 1st group expanded (index 0), others collapsed)
const collapsedSubgroups = ref<Set<string>>(new Set());

function isSubgroupExpanded(
    subjectId: number,
    groupIndex: number,
    groupName: string | null,
): boolean {
    if (!groupName) {
        return true;
    }

    const key = `${subjectId}_${groupName}`;

    if (collapsedSubgroups.value.has(key)) {
        return false;
    }

    // Initially expand 1st group (index 0), others collapsed
    if (groupIndex === 0) {
        return true;
    }

    const explicitExpandedKey = `exp_${key}`;

    return collapsedSubgroups.value.has(explicitExpandedKey);
}

function toggleSubgroup(
    subjectId: number,
    groupIndex: number,
    groupName: string | null,
) {
    if (!groupName) {
        return;
    }

    const key = `${subjectId}_${groupName}`;
    const explicitExpandedKey = `exp_${key}`;
    const currentlyExpanded = isSubgroupExpanded(
        subjectId,
        groupIndex,
        groupName,
    );

    if (currentlyExpanded) {
        collapsedSubgroups.value.add(key);
        collapsedSubgroups.value.delete(explicitExpandedKey);
    } else {
        collapsedSubgroups.value.delete(key);
        collapsedSubgroups.value.add(explicitExpandedKey);
    }
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
        course: props.course,
        group: formatGroup(props.group),
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

// Floating scroll-to-top button
const showScrollTop = ref(false);

function handleScroll() {
    const scrollPos =
        window.scrollY ||
        document.documentElement?.scrollTop ||
        document.body?.scrollTop ||
        0;
    showScrollTop.value = scrollPos > 250;
}

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    // Run once on mount in case already scrolled
    handleScroll();

    // Auto-prompt modal if authenticated user has not set their exam batch yet
    if (props.isAuthenticated && !props.batch) {
        openSwitchModal();
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="flex flex-col gap-5">
        <!-- Checklist Header Card -->
        <div
            class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:rounded-3xl sm:p-6 dark:border-gray-800/90 dark:bg-gray-900"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400"
                        >
                            <BookOpen class="h-4 w-4" />
                        </span>
                        <h2
                            class="truncate text-sm font-bold text-slate-900 sm:text-lg dark:text-white"
                        >
                            Syllabus Chapter Checklist
                        </h2>
                    </div>
                    <p
                        class="mt-1 hidden text-xs text-slate-500 sm:block dark:text-gray-400"
                    >
                        পড়া শেষ হওয়া অধ্যায়গুলোতে টিক দিয়ে সিলেবাসের অগ্রগতি
                        ট্র্যাক করুন
                    </p>
                </div>

                <!-- Right: Share Button + Current Curriculum Indicator with Change Link -->
                <div class="flex shrink-0 items-center gap-1.5 sm:gap-2.5">
                    <button
                        type="button"
                        @click="showShareModal = true"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/90 bg-white p-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-indigo-300 hover:bg-indigo-50/50 hover:text-indigo-600 sm:px-3 sm:py-1.5 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-indigo-500/40 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400"
                        title="Share your progress on social media"
                    >
                        <Share2
                            class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                        />
                        <span class="hidden sm:inline">Share Progress</span>
                    </button>

                    <!-- Curriculum & Group Clickable Badges -->
                    <div class="flex items-center gap-1 sm:gap-1.5">
                        <button
                            type="button"
                            @click="openSwitchModal"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-lg border px-2 py-1 text-[11px] font-black uppercase shadow-2xs transition hover:shadow-xs active:scale-95 sm:rounded-xl sm:px-3 sm:py-1.5 sm:text-xs"
                            :class="
                                course === 'ssc'
                                    ? 'border-emerald-300/80 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50'
                                    : 'border-indigo-300/80 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-700/60 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50'
                            "
                            title="কারিকুলাম ও বিভাগ পরিবর্তন করতে ক্লিক করুন"
                        >
                            <span>{{ course }}</span>
                            <ChevronDown
                                class="h-2.5 w-2.5 opacity-60 sm:h-3 sm:w-3"
                            />
                        </button>

                        <button
                            type="button"
                            @click="openSwitchModal"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200/90 bg-white px-2 py-1 text-[11px] font-bold text-slate-700 shadow-2xs transition hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-600 active:scale-95 sm:rounded-xl sm:px-3 sm:py-1.5 sm:text-xs dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-indigo-500/40 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400"
                            title="কারিকুলাম ও বিভাগ পরিবর্তন করতে ক্লিক করুন"
                        >
                            <span>{{ formatGroup(group) }}</span>
                            <ChevronDown
                                class="h-2.5 w-2.5 opacity-60 sm:h-3 sm:w-3"
                            />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Overall Progress Bar -->
            <div
                class="mt-3.5 rounded-xl border-0 bg-slate-50/70 p-3 sm:mt-5 sm:rounded-2xl sm:border sm:border-slate-100 sm:p-4 dark:bg-gray-800/40 dark:sm:border-gray-800/80"
            >
                <div
                    class="flex items-center justify-between text-xs font-semibold"
                >
                    <span class="text-slate-700 dark:text-gray-300">
                        <span class="sm:hidden">Completion</span>
                        <span class="hidden sm:inline"
                            >Total Course Completion</span
                        >
                    </span>
                    <span class="text-indigo-600 dark:text-indigo-400">
                        <span class="sm:hidden"
                            >{{ totalCompletedChapters }}/{{
                                totalChapters
                            }}
                            ({{ overallPercentage }}%)</span
                        >
                        <span class="hidden sm:inline"
                            >{{ totalCompletedChapters }} /
                            {{ totalChapters }} Chapters ({{
                                overallPercentage
                            }}%)</span
                        >
                    </span>
                </div>
                <div
                    class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 sm:mt-2.5 sm:h-2.5 dark:bg-gray-700"
                >
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-emerald-500 transition-all duration-500"
                        :style="{ width: `${overallPercentage}%` }"
                    />
                </div>
            </div>
        </div>

        <!-- Optional Subjects Callout Banner -->
        <div
            v-if="
                hasAvailableOptionalSubjects &&
                selectedOptionalSubjectsCount === 0
            "
            class="flex flex-col gap-3 rounded-2xl border border-indigo-200/90 bg-gradient-to-r from-indigo-50/90 via-white to-indigo-50/60 p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between sm:p-4.5 dark:border-indigo-900/60 dark:bg-gradient-to-r dark:from-indigo-950/40 dark:via-gray-900 dark:to-indigo-950/20"
        >
            <div class="flex items-start gap-3 sm:items-center">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-2xs dark:bg-indigo-500"
                >
                    <SlidersHorizontal class="h-4.5 w-4.5" />
                </span>
                <div>
                    <h3
                        class="text-xs font-bold text-slate-900 sm:text-sm dark:text-white"
                    >
                        অতিরিক্ত বিষয় নির্বাচন করুন
                    </h3>
                    <p
                        class="mt-0.5 text-[11px] text-slate-600 sm:text-xs dark:text-gray-400"
                    >
                        আপনার গ্রুপের ঐচ্ছিক বিষয়গুলো সিলেবাসে যুক্ত করতে
                        নির্বাচন করুন
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="openSwitchModal"
                class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-2xs transition hover:bg-indigo-700 active:scale-95 dark:bg-indigo-600 dark:hover:bg-indigo-500"
            >
                <span>বিষয় নির্বাচন করুন</span>
                <ChevronRight class="h-3.5 w-3.5" />
            </button>
        </div>

        <div
            v-else-if="
                hasAvailableOptionalSubjects &&
                selectedOptionalSubjectsCount > 0
            "
            class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-white px-3.5 py-2.5 text-xs shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <div class="flex min-w-0 items-center gap-2">
                <span
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                >
                    <Check class="h-3.5 w-3.5 stroke-[2.5]" />
                </span>
                <span class="truncate text-slate-600 dark:text-gray-300">
                    <strong class="font-semibold text-slate-900 dark:text-white"
                        >অতিরিক্ত বিষয়:</strong
                    >
                    {{ selectedOptionalSubjectNames.join(', ') }}
                </span>
            </div>
            <button
                type="button"
                @click="openSwitchModal"
                class="ml-2 inline-flex shrink-0 cursor-pointer items-center gap-1 font-semibold text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                <span>পরিবর্তন করুন</span>
                <ChevronRight class="h-3 w-3" />
            </button>
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
                    class="border-t border-slate-100 px-4.5 py-3 sm:px-5 dark:border-gray-800/80"
                >
                    <div
                        v-if="subject.nodes.length === 0"
                        class="py-4 text-center text-xs text-slate-400 dark:text-gray-500"
                    >
                        No chapters available for this subject yet.
                    </div>

                    <div
                        v-for="(group, gIdx) in getSubjectGroups(subject)"
                        :key="gIdx"
                        class="space-y-1"
                        :class="{
                            'mt-3 border-t border-slate-100/80 pt-3 dark:border-gray-800/60':
                                gIdx > 0,
                        }"
                    >
                        <!-- Sub-group Header (collapsible if grouped) -->
                        <div
                            v-if="group.name"
                            @click="
                                toggleSubgroup(subject.id, gIdx, group.name)
                            "
                            class="mb-1.5 flex cursor-pointer items-center justify-between rounded-lg px-2 py-1.5 text-xs font-bold text-slate-600 transition select-none hover:bg-slate-100/70 dark:text-gray-300 dark:hover:bg-gray-800/60"
                        >
                            <div class="flex items-center gap-2">
                                <component
                                    :is="
                                        isSubgroupExpanded(
                                            subject.id,
                                            gIdx,
                                            group.name,
                                        )
                                            ? ChevronDown
                                            : ChevronRight
                                    "
                                    class="h-3.5 w-3.5 text-slate-400 dark:text-gray-500"
                                />
                                <span>{{ group.name }}</span>
                            </div>
                            <span
                                class="text-[11px] font-normal text-slate-400 dark:text-gray-500"
                            >
                                {{
                                    group.nodes.filter((n) =>
                                        completedIds.has(n.id),
                                    ).length
                                }}/{{ group.nodes.length }}
                            </span>
                        </div>

                        <!-- Chapters under group -->
                        <div
                            v-if="
                                isSubgroupExpanded(subject.id, gIdx, group.name)
                            "
                            class="divide-y divide-slate-100 dark:divide-gray-800/60"
                            :class="{ 'pl-2 sm:pl-3': group.name }"
                        >
                            <div
                                v-for="node in group.nodes"
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
                                    <div
                                        class="flex flex-wrap items-center gap-1.5"
                                    >
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
                                    :href="`/notes?subject=${subject.id}`"
                                    target="_blank"
                                    title="Open notes"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 opacity-80 transition group-hover:opacity-100 hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                                >
                                    <ExternalLink class="h-3.5 w-3.5" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Switch Curriculum & Group Modal -->
        <BaseModal
            :is-open="showSwitchConfirmModal"
            title="কারিকুলাম ও বিভাগ পরিবর্তন করুন"
            description="আপনার পড়ার কারিকুলাম লেভেল ও বিভাগ নির্বাচন করুন"
            max-width="lg"
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

                <!-- Batch Selection -->
                <div>
                    <label
                        class="mb-2 block text-xs font-bold text-slate-700 dark:text-gray-300"
                    >
                        ব্যাচ নির্বাচন করুন (Exam Batch)
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            v-for="b in pendingCurriculum === 'hsc'
                                ? [
                                      { key: 'hsc_25', label: 'HSC 25' },
                                      { key: 'hsc_26', label: 'HSC 26' },
                                      { key: 'hsc_27', label: 'HSC 27' },
                                      { key: 'hsc_28', label: 'HSC 28' },
                                  ]
                                : [
                                      { key: 'ssc_25', label: 'SSC 25' },
                                      { key: 'ssc_26', label: 'SSC 26' },
                                      { key: 'ssc_27', label: 'SSC 27' },
                                  ]"
                            :key="b.key"
                            type="button"
                            @click="pendingBatch = b.key"
                            :class="[
                                pendingBatch === b.key
                                    ? 'border-indigo-600 bg-indigo-50/60 text-indigo-950 ring-1 ring-indigo-600 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-200'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                            class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border p-2.5 text-center transition"
                        >
                            <span class="text-xs font-bold">{{ b.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Optional Subjects Selection (only if relevant optional subjects exist) -->
                <div v-if="relevantOptionalSubjects.length > 0">
                    <div class="mb-2">
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                        >
                            অতিরিক্ত বিষয়সমূহ
                        </label>
                        <p
                            class="text-[11px] text-slate-500 dark:text-gray-400"
                        >
                            যে বিষয়গুলো আপনার গ্রুপে আছে সেগুলো নির্বাচন করুন
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <button
                            v-for="subj in relevantOptionalSubjects"
                            :key="subj.id"
                            type="button"
                            @click="toggleOptionalSubject(subj.id)"
                            :class="[
                                pendingOptionalSubjectIds.has(subj.id)
                                    ? 'border-indigo-600 bg-indigo-50/60 text-indigo-950 ring-1 ring-indigo-600 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-200'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                            class="flex cursor-pointer items-center justify-between rounded-xl border p-2.5 px-3 text-left transition"
                        >
                            <span
                                class="line-clamp-1 text-xs leading-tight font-semibold"
                            >
                                {{ subj.name }}
                            </span>
                            <span
                                class="flex h-4 w-4 shrink-0 items-center justify-center rounded text-[10px] font-bold transition"
                                :class="[
                                    pendingOptionalSubjectIds.has(subj.id)
                                        ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                        : 'border border-slate-300 bg-transparent text-transparent dark:border-gray-600',
                                ]"
                            >
                                <Check
                                    v-if="
                                        pendingOptionalSubjectIds.has(subj.id)
                                    "
                                    class="h-3 w-3 stroke-[3]"
                                />
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

        <!-- Floating Scroll to Top Button -->
        <button
            v-if="showScrollTop"
            type="button"
            @click="scrollToTop"
            title="Scroll to top"
            aria-label="Scroll to top"
            class="fixed right-4 bottom-[calc(4.5rem+env(safe-area-inset-bottom)+0.5rem)] z-50 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-slate-900/90 text-white shadow-lg ring-1 ring-black/10 backdrop-blur-xs transition duration-150 hover:scale-105 hover:bg-indigo-600 active:scale-95 sm:right-6 sm:bottom-6 sm:h-9.5 sm:w-9.5 dark:bg-gray-800/90 dark:text-gray-100 dark:ring-white/10 dark:hover:bg-indigo-600 dark:hover:text-white"
        >
            <ArrowUp class="h-4 w-4 stroke-[2.2]" />
        </button>
    </div>
</template>
