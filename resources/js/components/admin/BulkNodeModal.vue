<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Loader2,
    X,
    FolderPlus,
    AlertCircle,
    ChevronRight,
    ArrowLeft,
    Trash2,
    Plus,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    subject: {
        id: number;
        name: string;
        slug: string;
    };
    parent?: {
        id: number;
        name: string;
    } | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const namesInput = ref('');
const slugsInput = ref('');
const subfoldersInput = ref('Classes\nHandnotes\nPracticals');
const subfolderSlugsInput = ref('');

const isSaving = ref(false);
const errorMessage = ref('');
const isPreviewMode = ref(false);

interface ChildFolder {
    name: string;
    slug: string;
}

interface ParsedNode {
    name: string;
    slug: string;
    children: ChildFolder[];
}

const parsedNodes = ref<ParsedNode[]>([]);

const parseInputs = () => {
    errorMessage.value = '';

    const names = namesInput.value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line.length > 0);

    if (names.length === 0) {
        errorMessage.value = 'Please enter at least one folder name.';

        return false;
    }

    const slugs = slugsInput.value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line.length > 0);

    const subfolders = subfoldersInput.value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line.length > 0);

    const subfolderSlugs = subfolderSlugsInput.value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line.length > 0);

    parsedNodes.value = names.map((name, idx) => ({
        name,
        slug: slugs[idx] || '',
        children: subfolders.map((subName, subIdx) => ({
            name: subName,
            slug: subfolderSlugs[subIdx] || '',
        })),
    }));

    return true;
};

const handlePreview = () => {
    if (parseInputs()) {
        isPreviewMode.value = true;
    }
};

const handleClose = () => {
    if (isSaving.value) {
        return;
    }

    emit('close');
};

const removeNode = (index: number) => {
    parsedNodes.value.splice(index, 1);
};

const removeChildFromNode = (nodeIndex: number, childIndex: number) => {
    parsedNodes.value[nodeIndex].children.splice(childIndex, 1);
};

const addEmptyNode = () => {
    const subfolders = subfoldersInput.value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line.length > 0);

    const subfolderSlugs = subfolderSlugsInput.value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line.length > 0);

    parsedNodes.value.push({
        name: `Folder ${parsedNodes.value.length + 1}`,
        slug: '',
        children: subfolders.map((subName, subIdx) => ({
            name: subName,
            slug: subfolderSlugs[subIdx] || '',
        })),
    });
};

const saveBatchNodes = () => {
    if (parsedNodes.value.length === 0 || isSaving.value) {
        return;
    }

    isSaving.value = true;
    errorMessage.value = '';

    router.post(
        `/admin/subjects/${props.subject.id}/nodes/batch`,
        {
            parent_id: props.parent?.id || null,
            nodes: parsedNodes.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSaving.value = false;
                isPreviewMode.value = false;
                parsedNodes.value = [];
                namesInput.value = '';
                slugsInput.value = '';
                subfolderSlugsInput.value = '';
                emit('close');
            },
            onError: (errors) => {
                isSaving.value = false;
                errorMessage.value =
                    Object.values(errors).flat().join(', ') ||
                    'Failed to create folders.';
            },
        },
    );
};

const totalFoldersCount = computed(() => {
    return parsedNodes.value.reduce(
        (total, node) => total + 1 + node.children.length,
        0,
    );
});
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
                    class="relative flex max-h-[92vh] w-full max-w-2xl flex-col rounded-t-2xl border border-slate-200 bg-white shadow-xl sm:rounded-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-6 dark:border-gray-800"
                    >
                        <div class="min-w-0 flex-1 pr-3">
                            <h3
                                class="truncate text-base font-bold text-slate-900 dark:text-gray-100"
                            >
                                Bulk Add Folders
                            </h3>
                            <p
                                class="truncate text-xs text-slate-500 dark:text-gray-400"
                            >
                                In:
                                <span
                                    class="font-medium text-slate-700 dark:text-gray-300"
                                >
                                    {{ parent ? parent.name : subject.name }}
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

                    <!-- Body -->
                    <div
                        class="flex-1 space-y-4 overflow-y-auto p-4 text-slate-800 sm:p-6 dark:text-gray-200"
                    >
                        <!-- Error Banner -->
                        <div
                            v-if="errorMessage"
                            class="flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
                        >
                            <AlertCircle
                                class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                            />
                            <div class="leading-relaxed">
                                <span class="font-bold">Error: </span>
                                <span>{{ errorMessage }}</span>
                            </div>
                        </div>

                        <!-- Step 1: Input Form -->
                        <div v-if="!isPreviewMode" class="space-y-4">
                            <!-- Main Folders -->
                            <div class="space-y-1.5">
                                <label
                                    class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                                >
                                    Folder Names
                                    <span class="text-rose-500">*</span>
                                    <span
                                        class="font-normal text-slate-400 dark:text-gray-500"
                                    >
                                        (One per line)
                                    </span>
                                </label>
                                <textarea
                                    v-model="namesInput"
                                    rows="5"
                                    placeholder="Chapter 1: Cell & Its Structure&#10;Chapter 2: Cell Division&#10;Chapter 3: Cell Chemistry"
                                    class="w-full rounded-lg border border-slate-300 bg-white p-3 font-mono text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                />
                            </div>

                            <!-- Optional Slugs -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label
                                        class="block text-xs font-bold text-slate-700 dark:text-gray-300"
                                    >
                                        Folder Slugs
                                        <span
                                            class="font-normal text-slate-400 dark:text-gray-500"
                                        >
                                            (Optional, one per line)
                                        </span>
                                    </label>
                                    <span
                                        class="text-[10px] text-slate-400 dark:text-gray-500"
                                    >
                                        Auto-generated if left empty
                                    </span>
                                </div>
                                <textarea
                                    v-model="slugsInput"
                                    rows="3"
                                    placeholder="chapter-1-cell&#10;chapter-2-cell-division&#10;chapter-3-chemistry"
                                    class="w-full rounded-lg border border-slate-300 bg-white p-3 font-mono text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                />
                            </div>

                            <!-- Sub-Folders -->
                            <div
                                class="rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 sm:p-4 dark:border-gray-700/80 dark:bg-gray-800/40"
                            >
                                <div class="mb-2">
                                    <h4
                                        class="text-xs font-bold text-slate-800 dark:text-gray-200"
                                    >
                                        Nested Sub-Folders
                                    </h4>
                                    <p
                                        class="text-[11px] text-slate-500 dark:text-gray-400"
                                    >
                                        Created inside each main folder above.
                                    </p>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                                >
                                    <div class="space-y-1">
                                        <label
                                            class="block text-[11px] font-semibold text-slate-600 dark:text-gray-400"
                                        >
                                            Names (One per line)
                                        </label>
                                        <textarea
                                            v-model="subfoldersInput"
                                            rows="3"
                                            placeholder="Classes&#10;Handnotes&#10;Practicals"
                                            class="dark:bg-gray-850 w-full rounded-lg border border-slate-300 bg-white p-2.5 font-mono text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:text-gray-100"
                                        />
                                    </div>

                                    <div class="space-y-1">
                                        <label
                                            class="block text-[11px] font-semibold text-slate-600 dark:text-gray-400"
                                        >
                                            Slugs (Optional, one per line)
                                        </label>
                                        <textarea
                                            v-model="subfolderSlugsInput"
                                            rows="3"
                                            placeholder="classes&#10;handnotes&#10;practicals"
                                            class="dark:bg-gray-850 w-full rounded-lg border border-slate-300 bg-white p-2.5 font-mono text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:text-gray-100"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Review Preview -->
                        <div v-else class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4
                                        class="text-xs font-bold text-slate-800 dark:text-gray-200"
                                    >
                                        {{ parsedNodes.length }} Main Folders
                                        <span class="font-normal text-slate-500"
                                            >({{ totalFoldersCount }} total with
                                            sub-folders)</span
                                        >
                                    </h4>
                                </div>
                                <button
                                    type="button"
                                    @click="addEmptyNode"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    <Plus class="h-3.5 w-3.5" />
                                    <span>Add</span>
                                </button>
                            </div>

                            <div
                                class="max-h-80 space-y-2.5 overflow-y-auto pr-0.5"
                            >
                                <div
                                    v-for="(node, nIdx) in parsedNodes"
                                    :key="nIdx"
                                    class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 dark:border-gray-700 dark:bg-gray-800/40"
                                >
                                    <div class="mb-2 flex items-start gap-2">
                                        <div class="flex-1 space-y-1">
                                            <input
                                                v-model="node.name"
                                                type="text"
                                                placeholder="Folder name"
                                                class="dark:bg-gray-850 w-full rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-900 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:text-gray-100"
                                            />
                                            <input
                                                v-model="node.slug"
                                                type="text"
                                                placeholder="Slug (leave empty for auto)"
                                                class="w-full rounded-md border border-slate-200 bg-white/80 px-2.5 py-1 font-mono text-[11px] text-slate-600 focus:border-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeNode(nIdx)"
                                            class="cursor-pointer rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                            title="Delete"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <!-- Nested Tags -->
                                    <div
                                        v-if="node.children.length > 0"
                                        class="flex flex-wrap items-center gap-1.5 pt-1"
                                    >
                                        <span
                                            v-for="(
                                                child, cIdx
                                            ) in node.children"
                                            :key="cIdx"
                                            class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                        >
                                            <span>{{ child.name }}</span>
                                            <span
                                                v-if="child.slug"
                                                class="font-mono text-[10px] text-slate-400"
                                                >({{ child.slug }})</span
                                            >
                                            <button
                                                type="button"
                                                @click="
                                                    removeChildFromNode(
                                                        nIdx,
                                                        cIdx,
                                                    )
                                                "
                                                class="cursor-pointer text-slate-400 hover:text-rose-500"
                                            >
                                                <X class="h-2.5 w-2.5" />
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="flex items-center justify-between border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-6 dark:border-gray-800 dark:bg-gray-900/60"
                    >
                        <button
                            v-if="isPreviewMode"
                            type="button"
                            @click="isPreviewMode = false"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <ArrowLeft class="h-3.5 w-3.5" />
                            <span>Edit Input</span>
                        </button>
                        <div v-else></div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="handleClose"
                                class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>

                            <button
                                v-if="!isPreviewMode"
                                type="button"
                                @click="handlePreview"
                                :disabled="!namesInput.trim()"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span>Preview</span>
                                <ChevronRight class="h-3.5 w-3.5" />
                            </button>

                            <button
                                v-else
                                type="button"
                                @click="saveBatchNodes"
                                :disabled="isSaving || parsedNodes.length === 0"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Loader2
                                    v-if="isSaving"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <FolderPlus v-else class="h-3.5 w-3.5" />
                                <span>{{
                                    isSaving ? 'Creating...' : 'Create Folders'
                                }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
