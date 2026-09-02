<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PencilLine, Loader2, AlertCircle } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import BaseModal from '@/components/BaseModal.vue';

const props = defineProps<{
    isOpen: boolean;
    node: {
        id: number;
        name: string;
    };
    resourcesCount: number;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const prefix = ref('Class');
const startNumber = ref(1);
const isSaving = ref(false);
const errorMessage = ref('');

const cleanPrefix = computed(() => {
    return prefix.value.replace(/\s*-\s*$/, '').trim();
});

const previews = computed(() => {
    const start = Number(startNumber.value) || 1;
    const count = Math.min(props.resourcesCount || 3, 3);
    const items = [];

    for (let i = 0; i < count; i++) {
        const num = String(start + i).padStart(2, '0');
        const title =
            cleanPrefix.value !== '' ? `${cleanPrefix.value} - ${num}` : num;
        items.push(title);
    }

    return items;
});

const handleClose = () => {
    if (isSaving.value) {
        return;
    }

    errorMessage.value = '';
    emit('close');
};

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            errorMessage.value = '';
            isSaving.value = false;
        }
    },
);

const handleSubmit = () => {
    if (isSaving.value || props.resourcesCount === 0) {
        return;
    }

    isSaving.value = true;
    errorMessage.value = '';

    router.post(
        `/admin/nodes/${props.node.id}/resources/bulk-rename`,
        {
            prefix: prefix.value,
            start_number: startNumber.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                handleClose();
            },
            onError: (errors) => {
                errorMessage.value =
                    errors.prefix ||
                    errors.start_number ||
                    'Failed to rename resources.';
            },
            onFinish: () => {
                isSaving.value = false;
            },
        },
    );
};
</script>

<template>
    <BaseModal
        :is-open="isOpen"
        title="Bulk Rename Resources"
        description="Quickly rename all resources in this folder with a sequential prefix."
        max-width="md"
        @close="handleClose"
    >
        <template #icon>
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
            >
                <PencilLine class="h-5 w-5" />
            </div>
        </template>

        <form @submit.prevent="handleSubmit">
            <div class="space-y-4 p-4 sm:p-6">
                <!-- Error Alert -->
                <div
                    v-if="errorMessage"
                    class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-600 dark:border-red-900/50 dark:bg-red-950/50 dark:text-red-400"
                >
                    <AlertCircle class="h-4 w-4 shrink-0" />
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- Prefix Input -->
                <div class="space-y-1.5">
                    <label
                        for="rename_prefix"
                        class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Title Prefix
                    </label>
                    <input
                        id="rename_prefix"
                        v-model="prefix"
                        type="text"
                        placeholder="e.g. Class, Lecture, Chapter"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        required
                    />
                    <p class="text-[11px] text-slate-500 dark:text-gray-400">
                        Will be formatted as:
                        <span class="font-semibold">{{
                            cleanPrefix ? `${cleanPrefix} - 01` : '01'
                        }}</span>
                    </p>
                </div>

                <!-- Start Number Input -->
                <div class="space-y-1.5">
                    <label
                        for="start_number"
                        class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Starting Number
                    </label>
                    <input
                        id="start_number"
                        v-model.number="startNumber"
                        type="number"
                        min="0"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        required
                    />
                </div>

                <!-- Live Preview Card -->
                <div
                    class="rounded-xl border border-slate-100 bg-slate-50/70 p-3 dark:border-gray-800 dark:bg-gray-800/40"
                >
                    <span
                        class="mb-1.5 block text-[11px] font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >
                        Preview (First {{ previews.length }} items)
                    </span>
                    <div
                        class="space-y-1 font-mono text-xs text-slate-700 dark:text-gray-300"
                    >
                        <div
                            v-for="(item, idx) in previews"
                            :key="idx"
                            class="flex items-center gap-2"
                        >
                            <span class="text-slate-400 dark:text-gray-500"
                                >{{ idx + 1 }}.</span
                            >
                            <span
                                class="font-semibold text-indigo-600 dark:text-indigo-400"
                                >{{ item }}</span
                            >
                        </div>
                        <div
                            v-if="resourcesCount > 3"
                            class="text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            ... and {{ resourcesCount - 3 }} more
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-6 dark:border-gray-800 dark:bg-gray-900/60"
            >
                <button
                    type="button"
                    @click="handleClose"
                    class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    :disabled="isSaving || resourcesCount === 0 || !cleanPrefix"
                    class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Loader2 v-if="isSaving" class="h-3.5 w-3.5 animate-spin" />
                    <span>{{
                        isSaving
                            ? 'Renaming...'
                            : `Rename All (${resourcesCount})`
                    }}</span>
                </button>
            </div>
        </form>
    </BaseModal>
</template>
