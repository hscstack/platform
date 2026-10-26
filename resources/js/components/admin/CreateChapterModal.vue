<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Loader2, AlertCircle } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import BaseModal from '@/components/BaseModal.vue';

const props = defineProps<{
    isOpen: boolean;
    subject: {
        id: number;
        name: string;
    };
    chapter?: {
        id: number;
        name: string;
        sort_order?: number;
        weight?: number;
    } | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const name = ref('');
const sortOrder = ref(0);
const weight = ref(2);
const isSaving = ref(false);
const errorMessage = ref('');

const initForm = () => {
    if (props.chapter) {
        name.value = props.chapter.name || '';
        sortOrder.value = props.chapter.sort_order ?? 0;
        weight.value = props.chapter.weight ?? 2;
    } else {
        name.value = '';
        sortOrder.value = 0;
        weight.value = 2;
    }

    errorMessage.value = '';
};

watch([() => props.isOpen, () => props.chapter], ([open]) => {
    if (open) {
        initForm();
    }
});

const handleClose = () => {
    if (isSaving.value) {
        return;
    }

    emit('close');
};

const submitForm = () => {
    if (!name.value.trim() || isSaving.value) {
        return;
    }

    isSaving.value = true;
    errorMessage.value = '';

    const payload = {
        name: name.value,
        sort_order: sortOrder.value,
        weight: weight.value,
    };

    if (props.chapter) {
        router.patch(
            `/admin/subjects/${props.subject.id}/chapters/${props.chapter.id}`,
            payload,
            {
                preserveScroll: true,
                onSuccess: () => {
                    emit('close');
                },
                onError: (errors) => {
                    errorMessage.value =
                        Object.values(errors).flat().join(', ') ||
                        'Failed to update chapter.';
                },
                onFinish: () => {
                    isSaving.value = false;
                },
            },
        );
    } else {
        router.post(`/admin/subjects/${props.subject.id}/chapters`, payload, {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            },
            onError: (errors) => {
                errorMessage.value =
                    Object.values(errors).flat().join(', ') ||
                    'Failed to create chapter.';
            },
            onFinish: () => {
                isSaving.value = false;
            },
        });
    }
};
</script>

<template>
    <BaseModal
        :is-open="isOpen"
        :title="chapter ? 'Edit Chapter' : 'Create Chapter'"
        :description="`In: ${subject.name}`"
        max-width="md"
        @close="handleClose"
    >
        <!-- Body / Form -->
        <form
            @submit.prevent="submitForm"
            class="flex flex-1 flex-col overflow-hidden"
        >
            <!-- Error Banner -->
            <div
                v-if="errorMessage"
                class="m-4 mb-0 flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
            >
                <AlertCircle
                    class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                />
                <div class="leading-relaxed">
                    <span class="font-bold">Error: </span>
                    <span>{{ errorMessage }}</span>
                </div>
            </div>

            <div class="space-y-4 p-4">
                <!-- Chapter Name -->
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Chapter Name <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="name"
                        type="text"
                        placeholder="e.g. ভেক্টর"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                    />
                </div>

                <!-- Sort Order & Weight -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Sort Order
                        </label>
                        <input
                            v-model.number="sortOrder"
                            type="number"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Chapter Weight
                        </label>
                        <select
                            v-model.number="weight"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option :value="1">Small Chapter (1)</option>
                            <option :value="2">Normal Chapter (2)</option>
                            <option :value="3">Large Chapter (3)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/50"
            >
                <button
                    type="button"
                    @click="handleClose"
                    class="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="isSaving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50"
                >
                    <Loader2 v-if="isSaving" class="h-3.5 w-3.5 animate-spin" />
                    <span>{{
                        chapter ? 'Save Changes' : 'Create Chapter'
                    }}</span>
                </button>
            </div>
        </form>
    </BaseModal>
</template>
