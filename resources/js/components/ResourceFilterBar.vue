<script setup lang="ts">
import {
    Search,
    X,
    RotateCcw,
    BookOpen,
    Layers,
    FileText,
    HelpCircle,
    CheckSquare,
    GraduationCap,
    Video,
    FlaskConical,
    SearchX,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';

export interface SubjectItem {
    id?: string | number;
    name: string;
    english_name?: string | null;
    slug?: string;
}

export interface MaterialTypeItem {
    id: string;
    label: string;
    bnLabel?: string;
    icon?: any;
}

interface Props {
    resources: Array<any>;
    currentSubject?: string | null;
    subjects?: Array<SubjectItem>;
    materialTypes?: Array<MaterialTypeItem>;
    batches?: Array<string>;
    placeholder?: string;
    showSubjectFilter?: boolean;
    showMaterialFilter?: boolean;
    showBatchFilter?: boolean;
    showCounter?: boolean;
    sticky?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    currentSubject: null,
    subjects: () => [],
    materialTypes: () => [],
    batches: () => ['All', 'HSC 25', 'HSC 26', 'HSC 27', 'SSC 25', 'SSC 26'],
    placeholder: 'Search resources, chapters, or topics...',
    showSubjectFilter: true,
    showMaterialFilter: true,
    showBatchFilter: false,
    showCounter: true,
    sticky: false,
});

const emit = defineEmits<{
    (e: 'update:filtered', items: any[]): void;
    (e: 'update:modelValue', items: any[]): void;
    (
        e: 'filter-change',
        payload: {
            search: string;
            subject: string;
            type: string;
            batch: string;
            count: number;
        },
    ): void;
    (e: 'reset'): void;
}>();

// Default Bangladeshi HSC/SSC Subjects
const defaultSubjects: SubjectItem[] = [
    { id: 'all', name: 'All Subjects', english_name: 'All' },
    {
        id: 'physics-1',
        name: 'পদার্থবিজ্ঞান ১ম পত্র',
        english_name: 'Physics 1st',
    },
    {
        id: 'physics-2',
        name: 'পদার্থবিজ্ঞান ২য় পত্র',
        english_name: 'Physics 2nd',
    },
    {
        id: 'chemistry-1',
        name: 'রসায়ন ১ম পত্র',
        english_name: 'Chemistry 1st',
    },
    {
        id: 'chemistry-2',
        name: 'রসায়ন ২য় পত্র',
        english_name: 'Chemistry 2nd',
    },
    {
        id: 'higher-math',
        name: 'উচ্চতর গণিত',
        english_name: 'Higher Math',
    },
    { id: 'biology', name: 'জীববিজ্ঞান', english_name: 'Biology' },
    {
        id: 'ict',
        name: 'তথ্য ও যোগাযোগ প্রযুক্তি',
        english_name: 'ICT',
    },
    { id: 'bangla', name: 'বাংলা', english_name: 'Bangla' },
    { id: 'english', name: 'ইংরেজি', english_name: 'English' },
];

// Default Material Types
const defaultMaterialTypes: MaterialTypeItem[] = [
    { id: 'all', label: 'All', bnLabel: 'সবগুলো', icon: Layers },
    { id: 'note', label: 'Hand-notes', bnLabel: 'হ্যান্ডনোট', icon: FileText },
    {
        id: 'cq',
        label: 'CQ Suggestions',
        bnLabel: 'CQ সাজেশন',
        icon: HelpCircle,
    },
    { id: 'mcq', label: 'MCQ Sheets', bnLabel: 'MCQ শিট', icon: CheckSquare },
    {
        id: 'board',
        label: 'Board Questions',
        bnLabel: 'বোর্ড প্রশ্ন',
        icon: GraduationCap,
    },
    {
        id: 'video',
        label: 'Video Lectures',
        bnLabel: 'ভিডিও ক্লাস',
        icon: Video,
    },
    {
        id: 'practical',
        label: 'Practicals',
        bnLabel: 'ব্যবহারিক',
        icon: FlaskConical,
    },
];

// Reactive filter states
const searchQuery = ref('');
const selectedSubject = ref('all');
const selectedType = ref('all');
const selectedBatch = ref('all');

// Resolved subject options
const availableSubjects = computed(() => {
    if (props.subjects && props.subjects.length > 0) {
        const hasAll = props.subjects.some((s) => s.id === 'all');

        if (!hasAll) {
            return [
                { id: 'all', name: 'All Subjects', english_name: 'All' },
                ...props.subjects,
            ];
        }

        return props.subjects;
    }

    // Dynamic extraction from resources if available
    const extracted = new Map<string, SubjectItem>();
    props.resources.forEach((r) => {
        if (r.subject && typeof r.subject === 'object' && r.subject.name) {
            extracted.set(r.subject.slug || r.subject.name, {
                id: r.subject.slug || r.subject.id || r.subject.name,
                name: r.subject.name,
                english_name: r.subject.english_name || r.subject.name,
                slug: r.subject.slug,
            });
        } else if (r.subject_name) {
            extracted.set(r.subject_name, {
                id: r.subject_name,
                name: r.subject_name,
                english_name: r.subject_name,
            });
        } else if (typeof r.subject === 'string' && r.subject.trim()) {
            const subjectStr = r.subject.trim();
            extracted.set(subjectStr, {
                id: subjectStr,
                name: subjectStr,
                english_name: subjectStr,
            });
        }
    });

    if (extracted.size > 0) {
        return [
            { id: 'all', name: 'All Subjects', english_name: 'All' },
            ...Array.from(extracted.values()),
        ];
    }

    return defaultSubjects;
});

// Resolved material type options
const availableTypes = computed(() => {
    if (props.materialTypes && props.materialTypes.length > 0) {
        return props.materialTypes;
    }

    return defaultMaterialTypes;
});

// Helper: Match subject aliases (Bangla & English)
const matchesSubject = (targetKey: string, resource: any): boolean => {
    if (targetKey === 'all') {
        return true;
    }

    const target = targetKey.toLowerCase();
    const subName = (
        resource.subject?.name ||
        resource.subject_name ||
        (typeof resource.subject === 'string' ? resource.subject : '') ||
        ''
    ).toLowerCase();
    const subEng = (resource.subject?.english_name || '').toLowerCase();
    const subSlug = (resource.subject?.slug || '').toLowerCase();
    const nodeName = (
        resource.node?.subject?.name ||
        resource.node?.name ||
        ''
    ).toLowerCase();
    const title = (resource.title || '').toLowerCase();

    // Direct match with subject properties
    if (
        subName.includes(target) ||
        subEng.includes(target) ||
        subSlug.includes(target) ||
        nodeName.includes(target)
    ) {
        return true;
    }

    // Keyword mapping
    const keywords: Record<string, string[]> = {
        'physics-1': [
            'পদার্থবিজ্ঞান ১ম',
            'পদার্থবিজ্ঞান ১',
            'physics 1st',
            'physics 1',
            'physics-1',
        ],
        'physics-2': [
            'পদার্থবিজ্ঞান ২য়',
            'পদার্থবিজ্ঞান ২',
            'physics 2nd',
            'physics 2',
            'physics-2',
        ],
        'chemistry-1': [
            'রসায়ন ১ম',
            'রসায়ন ১',
            'chemistry 1st',
            'chemistry 1',
            'chem 1',
            'chemistry-1',
        ],
        'chemistry-2': [
            'রসায়ন ২য়',
            'রসায়ন ২',
            'chemistry 2nd',
            'chemistry 2',
            'chem 2',
            'chemistry-2',
        ],
        'higher-math': [
            'উচ্চতর গণিত',
            'higher math',
            'math',
            'গণিত',
            'higher-math',
        ],
        biology: ['জীববিজ্ঞান', 'biology', 'bio'],
        ict: ['তথ্য ও যোগাযোগ', 'তথ্য', 'ict'],
        bangla: ['বাংলা', 'bangla', 'bengali'],
        english: ['ইংরেজি', 'english', 'eng'],
    };

    const targetList = keywords[target] || [target];

    return targetList.some(
        (kw) =>
            subName.includes(kw) ||
            subEng.includes(kw) ||
            subSlug.includes(kw) ||
            nodeName.includes(kw) ||
            title.includes(kw),
    );
};

// Helper: Match material type category
const matchesMaterialType = (targetType: string, resource: any): boolean => {
    if (targetType === 'all') {
        return true;
    }

    const resType = (
        resource.resource_type ||
        resource.material_type ||
        resource.category ||
        ''
    ).toLowerCase();
    const title = (resource.title || '').toLowerCase();
    const content = (
        resource.content ||
        resource.description ||
        ''
    ).toLowerCase();

    if (resType === targetType.toLowerCase()) {
        return true;
    }

    switch (targetType) {
        case 'note':
            return (
                resType === 'note' ||
                resType === 'handnote' ||
                resType === 'hand-notes' ||
                title.includes('নোট') ||
                title.includes('হ্যান্ডনোট') ||
                title.includes('note') ||
                title.includes('handnote') ||
                title.includes('লেকচার')
            );
        case 'cq':
            return (
                resType === 'cq' ||
                resType === 'question' ||
                title.includes('cq') ||
                title.includes('সৃজনশীল') ||
                title.includes('creative') ||
                title.includes('suggestion') ||
                title.includes('সাজেশন')
            );
        case 'mcq':
            return (
                resType === 'mcq' ||
                title.includes('mcq') ||
                title.includes('বহুনির্বাচনি') ||
                title.includes('নৈর্ব্যক্তিক') ||
                title.includes('quiz')
            );
        case 'board':
            return (
                resType === 'board' ||
                title.includes('board') ||
                title.includes('বোর্ড') ||
                title.includes('প্রশ্নব্যাংক') ||
                title.includes('question bank')
            );
        case 'video':
            return (
                resType === 'video' ||
                title.includes('video') ||
                title.includes('ভিডিও') ||
                title.includes('ক্লাস')
            );
        case 'practical':
            return (
                resType === 'practical' ||
                title.includes('practical') ||
                title.includes('ব্যবহারিক') ||
                title.includes('ল্যাব')
            );
        default:
            return (
                resType.includes(targetType) ||
                title.includes(targetType) ||
                content.includes(targetType)
            );
    }
};

// Filtered Resources calculation
const filteredResources = computed(() => {
    if (!props.resources || !Array.isArray(props.resources)) {
        return [];
    }

    const query = searchQuery.value.trim().toLowerCase();
    const queryTokens = query ? query.split(/\s+/).filter(Boolean) : [];
    const subject = selectedSubject.value;
    const type = selectedType.value;
    const batch = selectedBatch.value;

    return props.resources.filter((item: any) => {
        // 1. Instant Text Search (fuzzy matching title, chapter, or description)
        if (queryTokens.length > 0) {
            const title = (item.title || '').toLowerCase();
            const content = (
                item.content ||
                item.description ||
                ''
            ).toLowerCase();
            const chapter = (
                item.chapter ||
                item.chapter_name ||
                item.node?.name ||
                item.node_name ||
                ''
            ).toLowerCase();
            const subjectText = (
                item.subject?.name ||
                item.subject?.english_name ||
                item.subject_name ||
                (typeof item.subject === 'string' ? item.subject : '') ||
                ''
            ).toLowerCase();
            const itemType = (
                item.resource_type ||
                item.material_type ||
                item.category ||
                ''
            ).toLowerCase();

            const combined = `${title} ${chapter} ${content} ${subjectText} ${itemType}`;

            const matchesAll = queryTokens.every((token) =>
                combined.includes(token),
            );

            if (!matchesAll) {
                return false;
            }
        }

        // 2. Subject Filter
        if (props.showSubjectFilter && subject && subject !== 'all') {
            if (!matchesSubject(subject, item)) {
                return false;
            }
        }

        // 3. Material Type Filter
        if (props.showMaterialFilter && type && type !== 'all') {
            if (!matchesMaterialType(type, item)) {
                return false;
            }
        }

        // 4. Batch Filter
        if (props.showBatchFilter && batch && batch !== 'all') {
            const itemBatch = (
                item.batch ||
                item.batch_name ||
                ''
            ).toLowerCase();
            const title = (item.title || '').toLowerCase();
            const targetBatch = batch.toLowerCase();

            if (
                !itemBatch.includes(targetBatch) &&
                !title.includes(targetBatch)
            ) {
                return false;
            }
        }

        return true;
    });
});

// Active filter detector
const hasActiveFilters = computed(() => {
    return (
        searchQuery.value.trim().length > 0 ||
        selectedSubject.value !== 'all' ||
        selectedType.value !== 'all' ||
        (props.showBatchFilter && selectedBatch.value !== 'all')
    );
});

// Count calculation
const totalCount = computed(() => props.resources?.length ?? 0);
const filteredCount = computed(() => filteredResources.value.length);

// Reset filters action
const resetFilters = () => {
    searchQuery.value = '';
    selectedSubject.value = 'all';
    selectedType.value = 'all';
    selectedBatch.value = 'all';
    emit('reset');
};

// Sync filtered output with parent via emits
watch(
    filteredResources,
    (newVal) => {
        emit('update:filtered', newVal);
        emit('update:modelValue', newVal);
        emit('filter-change', {
            search: searchQuery.value,
            subject: selectedSubject.value,
            type: selectedType.value,
            batch: selectedBatch.value,
            count: newVal.length,
        });
    },
    { immediate: true },
);

// If currentSubject prop provided, initialize subject filter if matching
watch(
    () => props.currentSubject,
    (newSub) => {
        if (newSub && selectedSubject.value === 'all') {
            const found = availableSubjects.value.find(
                (s) =>
                    s.name?.toLowerCase() === newSub.toLowerCase() ||
                    s.english_name?.toLowerCase() === newSub.toLowerCase() ||
                    s.slug?.toLowerCase() === newSub.toLowerCase() ||
                    s.id?.toString().toLowerCase() === newSub.toLowerCase(),
            );

            if (found && found.id) {
                selectedSubject.value = String(found.id);
            }
        }
    },
    { immediate: true },
);
</script>

<template>
    <div
        class="w-full transition-all"
        :class="{
            'sticky top-0 z-20 border-b border-slate-200/80 bg-white/95 backdrop-blur-md dark:border-gray-800 dark:bg-gray-900/95':
                sticky,
        }"
    >
        <!-- Main Filter Toolbar Container -->
        <div class="flex flex-col gap-3 py-3">
            <!-- Row 1: Search Field + Subject Selector Dropdown + Batch Selector -->
            <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                <!-- Search Input Field -->
                <div class="relative min-w-0 flex-1">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-gray-500"
                    >
                        <Search class="h-4 w-4 stroke-[2.2]" />
                    </div>

                    <input
                        type="text"
                        v-model="searchQuery"
                        :placeholder="placeholder"
                        aria-label="Search resources"
                        class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-10 pl-10 text-xs font-medium text-slate-900 shadow-xs transition-all duration-150 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/20"
                    />

                    <!-- Clear Search Button inside input -->
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        type="button"
                        aria-label="Clear search input"
                        class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Secondary Row on Mobile, Inline on Desktop: Subject + Batch Selector -->
                <div class="flex items-center gap-2">
                    <!-- Subject Selector Dropdown -->
                    <div
                        v-if="showSubjectFilter"
                        class="relative min-w-[140px] flex-1 sm:w-52 sm:flex-initial"
                    >
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-gray-500"
                        >
                            <BookOpen class="h-3.5 w-3.5 stroke-[2]" />
                        </div>
                        <select
                            v-model="selectedSubject"
                            aria-label="Filter by subject"
                            class="w-full cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pr-8 pl-8.5 text-xs font-semibold text-slate-700 shadow-xs transition-all duration-150 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-indigo-400"
                            :class="{
                                'border-indigo-300 font-bold text-indigo-600 dark:border-indigo-700 dark:text-indigo-400':
                                    selectedSubject !== 'all',
                            }"
                        >
                            <option
                                v-for="subj in availableSubjects"
                                :key="subj.id || subj.name"
                                :value="subj.id || subj.name"
                            >
                                {{ subj.english_name || subj.name }}
                            </option>
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 dark:text-gray-500"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Batch Selector (optional) -->
                    <div
                        v-if="showBatchFilter"
                        class="relative min-w-[100px] flex-1 sm:w-36 sm:flex-initial"
                    >
                        <select
                            v-model="selectedBatch"
                            aria-label="Filter by batch"
                            class="w-full cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pr-8 pl-3 text-xs font-semibold text-slate-700 shadow-xs transition-all duration-150 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-indigo-400"
                            :class="{
                                'border-indigo-300 font-bold text-indigo-600 dark:border-indigo-700 dark:text-indigo-400':
                                    selectedBatch !== 'all',
                            }"
                        >
                            <option
                                v-for="b in batches"
                                :key="b"
                                :value="b.toLowerCase()"
                            >
                                {{ b === 'All' ? 'All Batches' : b }}
                            </option>
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 dark:text-gray-500"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: Material Type Filter Pills (Horizontal Scrollable on Mobile) -->
            <div
                v-if="showMaterialFilter"
                class="flex [scrollbar-width:none] items-center gap-1.5 overflow-x-auto pb-1 [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
            >
                <span
                    class="mr-1 hidden shrink-0 text-[11px] font-bold tracking-wider text-slate-400 uppercase lg:inline-block dark:text-gray-500"
                >
                    Type:
                </span>

                <button
                    v-for="item in availableTypes"
                    :key="item.id"
                    type="button"
                    @click="selectedType = item.id"
                    class="group inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition-all duration-150 select-none active:scale-95"
                    :class="[
                        selectedType === item.id
                            ? 'border-indigo-600 bg-indigo-600 text-white shadow-xs dark:border-indigo-500 dark:bg-indigo-500 dark:text-white'
                            : 'border-slate-200/90 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-700 dark:hover:bg-gray-800',
                    ]"
                >
                    <component
                        :is="item.icon"
                        v-if="item.icon"
                        class="h-3.5 w-3.5 transition-transform group-hover:scale-110"
                        :class="
                            selectedType === item.id
                                ? 'stroke-[2.5] text-white'
                                : 'stroke-[2] text-slate-400 group-hover:text-slate-600 dark:text-gray-500 dark:group-hover:text-gray-300'
                        "
                    />
                    <span>{{ item.label }}</span>
                </button>
            </div>

            <!-- Row 3: Result Summary & Clear Filters Button -->
            <div
                class="flex items-center justify-between pt-0.5 text-xs font-medium text-slate-500 dark:text-gray-400"
            >
                <!-- Live Result Counter -->
                <div v-if="showCounter" class="flex items-center gap-1.5">
                    <span v-if="hasActiveFilters">
                        Showing
                        <strong
                            class="font-bold text-slate-900 dark:text-gray-100"
                            >{{ filteredCount }}</strong
                        >
                        of
                        <span class="font-semibold">{{ totalCount }}</span>
                        resources
                    </span>
                    <span v-else>
                        <strong
                            class="font-bold text-slate-900 dark:text-gray-100"
                            >{{ totalCount }}</strong
                        >
                        {{ totalCount === 1 ? 'resource' : 'resources' }}
                        available
                    </span>

                    <span
                        v-if="hasActiveFilters"
                        class="ml-1 inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"
                    >
                        Filtered
                    </span>
                </div>
                <div v-else></div>

                <!-- Dynamic Clear Filters Button -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <button
                        v-if="hasActiveFilters"
                        @click="resetFilters"
                        type="button"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50 hover:text-rose-700 active:scale-95 dark:text-rose-400 dark:hover:bg-rose-950/40 dark:hover:text-rose-300"
                    >
                        <RotateCcw class="h-3 w-3 stroke-[2.2]" />
                        <span>Clear Filters</span>
                    </button>
                </Transition>
            </div>
        </div>

        <!-- Optional Slot for Wrapping Resource Grid / List -->
        <div v-if="$slots.default" class="mt-2">
            <!-- Normal Render if matches exist -->
            <div v-if="filteredResources.length > 0">
                <slot
                    :filtered-resources="filteredResources"
                    :has-active-filters="hasActiveFilters"
                    :reset-filters="resetFilters"
                    :total-count="totalCount"
                    :filtered-count="filteredCount"
                />
            </div>

            <!-- Filter Empty State (when filters yield no results) -->
            <div v-else-if="hasActiveFilters" class="py-4">
                <slot
                    name="empty-filtered"
                    :reset-filters="resetFilters"
                    :search-query="searchQuery"
                >
                    <EmptyState
                        :icon="SearchX"
                        variant="dashed"
                        title="No matching resources found"
                        description="No study materials match your search query or filter combination. Try adjusting your keywords or clearing active filters."
                    >
                        <template #actions>
                            <button
                                type="button"
                                @click="resetFilters"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                            >
                                <RotateCcw class="h-3.5 w-3.5 stroke-[2.2]" />
                                <span>Reset All Filters</span>
                            </button>
                        </template>
                    </EmptyState>
                </slot>
            </div>

            <!-- Initial Empty State (when props.resources is empty from the start) -->
            <div v-else class="py-4">
                <slot name="empty-initial">
                    <EmptyState
                        title="কোনো রিসোর্স পাওয়া যায়নি"
                        description="শীঘ্রই এখানে নতুন স্টাডি ম্যাটেরিয়াল ও নোট আপলোড করা হবে।"
                        :show-cta="true"
                    />
                </slot>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
