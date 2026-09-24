<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronRight,
    FolderPlus,
    Lock,
    Unlock,
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import UploadResourceDropdown from '@/components/resources/UploadResourceDropdown.vue';
import { usePermissions } from '@/lib/usePermissions';

defineProps<{
    subject: any;
    parent?: any | null;
    breadcrumbs: Array<{ name: string; link: string }>;
    isFrozen: boolean;
    isDirectlyFrozen: boolean;
    isInheritedFrozen: boolean;
    isTogglingFreeze: boolean;
}>();

const emit = defineEmits<{
    (e: 'toggle-freeze'): void;
    (e: 'create-single-folder'): void;
    (e: 'create-bulk-folder'): void;
}>();

const { can } = usePermissions();

const isFolderDropdownOpen = ref(false);
const folderDropdownRef = ref<HTMLElement | null>(null);

const closeDropdowns = (e: MouseEvent) => {
    const target = e.target as Node | null;

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
            class="mb-3.5 flex items-center rounded-xl bg-white/70 px-3 py-2 text-xs font-medium text-slate-500 ring-1 ring-slate-200/60 backdrop-blur-xl sm:text-sm dark:bg-slate-900/60 dark:text-gray-400 dark:ring-slate-800/60"
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
            class="mb-3.5 flex items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-xs font-medium ring-1"
            :class="
                isDirectlyFrozen
                    ? 'bg-sky-50/80 text-sky-800 ring-sky-200/70 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/25'
                    : 'bg-amber-50/80 text-amber-800 ring-amber-200/70 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/25'
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
            class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/60 pb-4 sm:gap-3 dark:border-slate-800/60"
        >
            <div class="flex min-w-0 flex-1 items-center sm:gap-2.5">
                <h1
                    class="truncate text-base font-bold tracking-tight text-slate-900 sm:text-lg dark:text-gray-100"
                >
                    {{ parent?.name ? parent.name : subject.name }}
                </h1>
            </div>

            <div class="ms-auto flex items-center gap-1.5 sm:gap-2">
                <!-- Toggle Freeze Button -->
                <button
                    v-if="parent?.id && can('freeze nodes')"
                    type="button"
                    :disabled="isTogglingFreeze"
                    @click="emit('toggle-freeze')"
                    class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-[10px] px-2.5 text-xs font-semibold ring-1 transition-colors duration-150 disabled:opacity-50 sm:px-3"
                    :class="
                        isDirectlyFrozen
                            ? 'bg-emerald-50/80 text-emerald-700 ring-emerald-300/60 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20 dark:hover:bg-emerald-500/15'
                            : isInheritedFrozen
                              ? 'bg-amber-50/80 text-amber-700 ring-amber-300/60 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20 dark:hover:bg-amber-500/15'
                              : 'bg-sky-50/80 text-sky-700 ring-sky-300/60 hover:bg-sky-100 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20 dark:hover:bg-sky-500/15'
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
                        class="inline-flex h-8 cursor-pointer items-center gap-1 rounded-[10px] bg-white/70 px-2.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/60 backdrop-blur transition-colors hover:bg-white/90 hover:text-slate-900 sm:gap-1.5 sm:px-3 dark:bg-slate-900/60 dark:text-gray-300 dark:ring-slate-800/60 dark:hover:bg-slate-900/85 dark:hover:text-gray-100"
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
                        class="absolute right-0 z-10 mt-1.5 w-44 rounded-xl bg-white/90 p-1 shadow-lg ring-1 ring-slate-200/70 backdrop-blur-xl dark:bg-slate-900/90 dark:ring-slate-800/70"
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
                <UploadResourceDropdown
                    v-if="parent?.id && !isFrozen"
                    :node="parent"
                    :is-frozen="isFrozen"
                />
            </div>
        </div>
    </div>
</template>
