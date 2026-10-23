<script setup lang="ts">
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import FolderLockedModal from '@/components/resources/FolderLockedModal.vue';
import ResourceFormModal from '@/components/resources/ResourceFormModal.vue';
import { useAuth } from '@/lib/useAuth';

const props = defineProps<{
    node: {
        id: number;
        name: string;
    };
    isFrozen: boolean;
}>();

const { requireAuth } = useAuth();

const isFolderLockedModalOpen = ref(false);
const isUploadModalOpen = ref(false);

const handleTriggerClick = () => {
    if (props.isFrozen) {
        isFolderLockedModalOpen.value = true;

        return;
    }

    if (!requireAuth('Please sign in to upload resources.')) {
        return;
    }

    isUploadModalOpen.value = true;
};
</script>

<template>
    <div class="inline-block">
        <button
            type="button"
            @click="handleTriggerClick"
            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition select-none hover:bg-emerald-700 active:scale-95"
        >
            <Plus class="h-4 w-4 stroke-[2.5]" />
            <span>আপলোড করুন</span>
        </button>

        <!-- Lock Modal -->
        <FolderLockedModal
            :is-open="isFolderLockedModalOpen"
            @close="isFolderLockedModalOpen = false"
        />

        <!-- Unified Upload Modal -->
        <ResourceFormModal
            :is-open="isUploadModalOpen"
            :node="node"
            default-type="note"
            @close="isUploadModalOpen = false"
        />
    </div>
</template>
