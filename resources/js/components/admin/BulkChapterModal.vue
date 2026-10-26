<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Loader2, FolderPlus, AlertCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import BaseModal from '@/components/BaseModal.vue';

const props = defineProps<{
    isOpen: boolean;
    subject: {
        id: number;
        name: string;
    };
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const namesInput = ref('');
const isSaving = ref(false);
const errorMessage = ref('');

const submitBatch = () => {
    errorMessage.value = '';

    const lines = namesInput.value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line.length > 0);

    if (lines.length === 0) {
        errorMessage.value = 'Please enter at least one chapter name.';

        return;
    }

    isSaving.value = true;

    const chapters = lines.map((name) => ({
        name,
        weight: 2,
    }));

    router.post(
        `/admin/subjects/${props.subject.id}/chapters/batch`,
        { chapters },
        {
            preserveScroll: true,
            onSuccess: () => {
                namesInput.value = '';
                emit('close');
            },
            onError: (errors) => {
                errorMessage.value =
                    Object.values(errors).flat().join(', ') ||
                    'Failed to generate chapters.';
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
        title="Batch Generate Chapters"
        :description="`In: ${subject.name}`"
        max-width="lg"
        @close="emit('close')"
    >
        <div class="space-y-4 p-4">
            <div
                v-if="errorMessage"
                class="flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
            >
                <AlertCircle
                    class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                />
                <div>{{ errorMessage }}</div>
            </div>

            <div>
                <label
                    class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                >
                    Chapter Names (One per line)
                </label>
                <textarea
                    v-model="namesInput"
                    rows="8"
                    placeholder="১ম অধ্যায়: ভৌত জগৎ ও পরিমাপ&#10;২য় অধ্যায়: ভেক্টর&#10;৩য় অধ্যায়: গতিবিদ্যা"
                    class="w-full rounded-xl border border-slate-200 bg-white p-3 font-mono text-sm text-slate-900 transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                />
                <p class="mt-1 text-[11px] text-slate-400">
                    Each non-empty line will be created as a chapter in this
                    subject.
                </p>
            </div>
        </div>

        <div
            class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/50"
        >
            <button
                type="button"
                @click="emit('close')"
                class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
            >
                Cancel
            </button>
            <button
                type="button"
                :disabled="isSaving"
                @click="submitBatch"
                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50"
            >
                <Loader2 v-if="isSaving" class="h-3.5 w-3.5 animate-spin" />
                <FolderPlus class="h-3.5 w-3.5" />
                <span>Generate Chapters</span>
            </button>
        </div>
    </BaseModal>
</template>
