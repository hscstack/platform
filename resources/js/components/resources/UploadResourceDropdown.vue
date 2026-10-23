<script setup lang="ts">
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

const { user, requireAuth } = useAuth();
const isUserBanned = computed(() => Boolean(user.value?.is_banned));

const isFolderLockedModalOpen = ref(false);
const isUploadModalOpen = ref(false);
const bannedErrorMessage = ref<string | null>(null);

const handleTriggerClick = () => {
    if (props.isFrozen) {
        isFolderLockedModalOpen.value = true;

        return;
    }

    if (!requireAuth('Please sign in to upload resources.')) {
        return;
    }

    if (isUserBanned.value) {
        bannedErrorMessage.value =
            'আপনার অ্যাকাউন্ট সাময়িকভাবে স্থগিত রয়েছে। এই সময়ে কোনো রিসোর্স আপলোড করা যাবে না।';

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
            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition select-none hover:bg-emerald-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
            :title="
                isUserBanned ? 'অ্যাকাউন্ট স্থগিত রয়েছে' : 'রিসোর্স আপলোড করুন'
            "
        >
            <Plus class="h-4 w-4 stroke-[2.5]" />
            <span>আপলোড করুন</span>
        </button>

        <!-- Ban Warning Dialog / Message -->
        <div
            v-if="bannedErrorMessage"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs"
            @click="bannedErrorMessage = null"
        >
            <div
                class="w-full max-w-sm rounded-2xl border border-red-200 bg-white p-5 text-center shadow-xl dark:border-red-900/50 dark:bg-gray-900"
                @click.stop
            >
                <div
                    class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400"
                >
                    <Plus class="h-5 w-5 rotate-45 stroke-[2.5]" />
                </div>
                <h3
                    class="mb-1 text-base font-bold text-gray-900 dark:text-white"
                >
                    অ্যাকাউন্ট স্থগিত
                </h3>
                <p class="mb-4 text-xs text-gray-600 dark:text-gray-300">
                    {{ bannedErrorMessage }}
                </p>
                <button
                    type="button"
                    @click="bannedErrorMessage = null"
                    class="w-full cursor-pointer rounded-xl bg-red-600 py-2 text-xs font-bold text-white transition hover:bg-red-700"
                >
                    ঠিক আছে
                </button>
            </div>
        </div>

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
