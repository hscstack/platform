<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChevronDown,
    ChevronRight,
    FolderPlus,
    Lock,
    Plus,
    Unlock,
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import { usePermissions } from '@/lib/usePermissions';

defineProps<{
    subject: any;
    parent?: any | null;
    breadcrumbs: Array<{ name: string; link: string }>;
    backUrl: string;
    isFrozen: boolean;
    isDirectlyFrozen: boolean;
    isInheritedFrozen: boolean;
    isTogglingFreeze: boolean;
}>();

const emit = defineEmits<{
    (e: 'toggle-freeze'): void;
    (e: 'create-single-folder'): void;
    (e: 'create-bulk-folder'): void;
    (e: 'create-single-resource'): void;
    (e: 'create-bulk-image'): void;
    (e: 'create-bulk-video'): void;
}>();

const { can } = usePermissions();

const isResourceDropdownOpen = ref(false);
const isFolderDropdownOpen = ref(false);
const resourceDropdownRef = ref<HTMLElement | null>(null);
const folderDropdownRef = ref<HTMLElement | null>(null);

const closeDropdowns = (e: MouseEvent) => {
    const target = e.target as Node | null;

    if (
        resourceDropdownRef.value &&
        target &&
        !resourceDropdownRef.value.contains(target)
    ) {
        isResourceDropdownOpen.value = false;
    }

    if (
        folderDropdownRef.value &&
        target &&
        !folderDropdownRef.value.contains(target)
    ) {
        isFolderDropdownOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', closeDropdowns));
onUnmounted(() => document.removeEventListener('click', closeDropdowns));
</script>

<template>
    <div>
        <!-- Dedicated Breadcrumb Navigation Bar (Separate Row) -->
        <div
            v-if="breadcrumbs.length > 0"
            class="mb-3.5 flex items-center rounded-xl bg-slate-50/80 px-3 py-2 text-xs font-medium text-slate-500 sm:text-sm dark:bg-gray-800/40 dark:text-gray-400"
        >
            <nav
                class="no-scrollbar flex min-w-0 flex-wrap items-center gap-1.5"
            >
                <template v-for="(crumb, idx) in breadcrumbs" :key="idx">
                    <ChevronRight
                        v-if="idx > 0"
                        class="h-3.5 w-3.5 shrink-0 stroke-[2.5] text-slate-300 dark:text-gray-600"
                    />
                    <span
                        v-if="idx === breadcrumbs.length - 1"
                        class="font-bold text-slate-900 dark:text-gray-100"
                        :title="crumb.name"
                    >
                        {{ crumb.name }}
                    </span>
                    <Link
                        v-else
                        :href="crumb.link"
                        class="transition-colors hover:text-indigo-600 dark:hover:text-indigo-400"
                        :title="crumb.name"
                    >
                        {{ crumb.name }}
                    </Link>
                </template>
            </nav>
        </div>

        <!-- Freeze Notice Banner -->
        <div
            v-if="isFrozen"
            class="mb-3.5 flex items-center gap-2.5 rounded-xl border px-3.5 py-2.5 text-xs font-medium"
            :class="
                isDirectlyFrozen
                    ? 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-300'
                    : 'border-amber-200 bg-amber-50/80 text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-300'
            "
        >
            <Lock
                class="h-4 w-4 shrink-0"
                :class="
                    isDirectlyFrozen
                        ? 'text-sky-600 dark:text-sky-400'
                        : 'text-amber-600 dark:text-amber-400'
                "
            />
            <span v-if="isDirectlyFrozen">
                এই ফোল্ডারটি ফ্রিজ (লক) করা রয়েছে। এখানে নতুন কিছু আপলোড, এডিট
                বা ডিলিট করা যাবে না।
            </span>
            <span v-else>
                প্যারেন্ট ফোল্ডার লক থাকায় এই ফোল্ডারটিও স্বয়ংক্রিয়ভাবে লক
                রয়েছে। এখানে নতুন কিছু আপলোড, এডিট বা ডিলিট করা যাবে না।
            </span>
        </div>

        <!-- Compact Page Title Bar -->
        <div
            class="mb-3.5 flex shrink-0 items-center justify-between gap-2 border-b border-slate-100 pb-3 sm:gap-3 dark:border-gray-800"
        >
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-2.5">
                <Link
                    :href="backUrl"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-2xs transition-colors hover:bg-slate-50 hover:text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    title="Go back"
                >
                    <ArrowLeft class="h-4 w-4" :stroke-width="2.2" />
                </Link>

                <h3
                    class="truncate text-sm font-bold tracking-tight text-slate-900 sm:text-base dark:text-gray-100"
                >
                    {{ parent?.name ? parent.name : subject.name }}
                </h3>
            </div>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <!-- Toggle Freeze Button -->
                <button
                    v-if="parent?.id && can('freeze nodes')"
                    type="button"
                    :disabled="isTogglingFreeze"
                    @click="emit('toggle-freeze')"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold shadow-2xs transition-colors disabled:opacity-50 sm:px-3"
                    :class="
                        isDirectlyFrozen
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/50'
                            : isInheritedFrozen
                              ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300 dark:hover:bg-amber-900/50'
                              : 'border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-950/50 dark:text-sky-300 dark:hover:bg-sky-900/50'
                    "
                    :title="
                        isDirectlyFrozen
                            ? 'Unfreeze this folder'
                            : isInheritedFrozen
                              ? 'Freeze this folder individually'
                              : 'Freeze this folder'
                    "
                >
                    <Unlock
                        v-if="isDirectlyFrozen"
                        class="h-3.5 w-3.5"
                        :stroke-width="2"
                    />
                    <Lock v-else class="h-3.5 w-3.5" :stroke-width="2" />
                    <span>{{
                        isDirectlyFrozen
                            ? 'Unfreeze'
                            : isInheritedFrozen
                              ? 'Freeze Individually'
                              : 'Freeze'
                    }}</span>
                </button>

                <!-- Add Folder Dropdown -->
                <div
                    v-if="!isFrozen && can('create nodes')"
                    ref="folderDropdownRef"
                    class="relative inline-block"
                >
                    <button
                        type="button"
                        @click="isFolderDropdownOpen = !isFolderDropdownOpen"
                        class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition-colors hover:bg-slate-50 sm:gap-1.5 sm:px-3 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <FolderPlus
                            class="h-3.5 w-3.5 text-slate-500 dark:text-gray-400"
                            :stroke-width="2"
                        />
                        <span
                            ><span class="hidden sm:inline">Add </span
                            >Folder</span
                        >
                        <ChevronDown class="h-3.5 w-3.5 text-slate-400" />
                    </button>

                    <div
                        v-if="isFolderDropdownOpen"
                        class="absolute right-0 z-10 mt-1.5 w-44 rounded-xl border border-slate-100 bg-white p-1 shadow-lg dark:border-gray-800 dark:bg-gray-900"
                    >
                        <button
                            type="button"
                            @click="
                                isFolderDropdownOpen = false;
                                emit('create-single-folder');
                            "
                            class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Single Folder
                        </button>
                        <button
                            type="button"
                            @click="
                                isFolderDropdownOpen = false;
                                emit('create-bulk-folder');
                            "
                            class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Add Multiple Folders
                        </button>
                    </div>
                </div>

                <!-- Add Resource Dropdown -->
                <div
                    v-if="!isFrozen && parent?.id && can('create resources')"
                    ref="resourceDropdownRef"
                    class="relative inline-block"
                >
                    <button
                        type="button"
                        @click="
                            isResourceDropdownOpen = !isResourceDropdownOpen
                        "
                        class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-2xs transition-colors duration-150 hover:bg-indigo-700 sm:gap-1.5 sm:px-3"
                    >
                        <Plus class="h-3.5 w-3.5" :stroke-width="2.2" />
                        <span
                            ><span class="hidden sm:inline">Add </span
                            >Resource</span
                        >
                        <ChevronDown class="h-3.5 w-3.5" />
                    </button>

                    <div
                        v-if="isResourceDropdownOpen"
                        class="absolute right-0 z-10 mt-1.5 w-48 rounded-xl border border-slate-100 bg-white p-1 shadow-lg dark:border-gray-800 dark:bg-gray-900"
                    >
                        <button
                            type="button"
                            @click="
                                isResourceDropdownOpen = false;
                                emit('create-single-resource');
                            "
                            class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Single Resource
                        </button>
                        <button
                            type="button"
                            @click="
                                isResourceDropdownOpen = false;
                                emit('create-bulk-image');
                            "
                            class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Multiple Images
                        </button>
                        <button
                            type="button"
                            @click="
                                isResourceDropdownOpen = false;
                                emit('create-bulk-video');
                            "
                            class="block w-full cursor-pointer rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        >
                            Upload Multiple Videos
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
