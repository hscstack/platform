<script setup lang="ts">
import {
    Plus,
    ChevronDown,
    FileText,
    Image as ImageIcon,
    Youtube,
} from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';
import BulkImageModal from '@/components/admin/BulkImageModal.vue';
import BulkVideoModal from '@/components/admin/BulkVideoModal.vue';
import CreateResourceModal from '@/components/admin/CreateResourceModal.vue';
import FolderLockedModal from '@/components/resources/FolderLockedModal.vue';

const props = defineProps<{
    node: {
        id: number;
        name: string;
    };
    isFrozen: boolean;
}>();

const dropdownRef = ref<HTMLElement | null>(null);
const isDropdownOpen = ref(false);

const isFolderLockedModalOpen = ref(false);
const isCreateModalOpen = ref(false);
const isBulkImageModalOpen = ref(false);
const isBulkVideoModalOpen = ref(false);

const handleTriggerClick = () => {
    if (props.isFrozen) {
        isFolderLockedModalOpen.value = true;

        return;
    }

    isDropdownOpen.value = !isDropdownOpen.value;
};

const openModal = (type: 'single' | 'images' | 'videos') => {
    isDropdownOpen.value = false;

    if (type === 'single') {
        isCreateModalOpen.value = true;
    } else if (type === 'images') {
        isBulkImageModalOpen.value = true;
    } else if (type === 'videos') {
        isBulkVideoModalOpen.value = true;
    }
};

const handleClickOutside = (e: MouseEvent) => {
    const target = e.target as Node | null;

    if (dropdownRef.value && target && !dropdownRef.value.contains(target)) {
        isDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="dropdownRef" class="relative inline-block">
        <button
            type="button"
            @click="handleTriggerClick"
            class="inline-flex cursor-pointer items-center gap-1 rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs transition select-none hover:bg-indigo-700 active:scale-95"
        >
            <Plus class="h-4 w-4 stroke-[2.5]" />
            <span>আপলোড করুন</span>
            <ChevronDown class="h-3.5 w-3.5 opacity-80" />
        </button>

        <div
            v-if="isDropdownOpen"
            class="absolute right-0 z-20 mt-1.5 w-48 rounded-xl border border-slate-200/90 bg-white p-1 shadow-lg dark:border-gray-800 dark:bg-gray-900"
        >
            <button
                type="button"
                @click="openModal('single')"
                class="flex w-full cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
            >
                <FileText
                    class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                />
                <span>সিঙ্গেল কন্টেন্ট</span>
            </button>
            <button
                type="button"
                @click="openModal('images')"
                class="flex w-full cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
            >
                <ImageIcon
                    class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                />
                <span>একাধিক ছবি</span>
            </button>
            <button
                type="button"
                @click="openModal('videos')"
                class="flex w-full cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100"
            >
                <Youtube class="h-4 w-4 text-rose-600 dark:text-rose-400" />
                <span>ইউটিউব প্লেলিস্ট</span>
            </button>
        </div>

        <!-- Lock Modal -->
        <FolderLockedModal
            :is-open="isFolderLockedModalOpen"
            @close="isFolderLockedModalOpen = false"
        />

        <!-- Modals -->
        <CreateResourceModal
            :is-open="isCreateModalOpen"
            :node="node"
            @close="isCreateModalOpen = false"
        />
        <BulkImageModal
            :is-open="isBulkImageModalOpen"
            :node="node"
            @close="isBulkImageModalOpen = false"
        />
        <BulkVideoModal
            :is-open="isBulkVideoModalOpen"
            :node="node"
            @close="isBulkVideoModalOpen = false"
        />
    </div>
</template>
