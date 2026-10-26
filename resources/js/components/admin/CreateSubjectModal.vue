<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { AlertCircle, Loader2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import BaseModal from '@/components/BaseModal.vue';

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        subject?: {
            id: number;
            name: string;
            course: string;
            group?: string;
            sort_order: number;
            is_optional?: boolean;
            is_disabled?: boolean;
        } | null;
        defaultCourse?: string;
        defaultGroup?: string;
    }>(),
    {
        defaultCourse: 'hsc',
        defaultGroup: 'science',
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const isSaving = ref(false);
const errorMessage = ref('');

const name = ref('');
const course = ref('hsc');
const group = ref('science');
const sortOrder = ref(0);
const isOptional = ref(false);
const isDisabled = ref(false);

const initForm = () => {
    if (props.subject) {
        name.value = props.subject.name || '';
        course.value = props.subject.course || props.defaultCourse || 'hsc';
        group.value = props.subject.group || props.defaultGroup || 'science';
        sortOrder.value = props.subject.sort_order ?? 0;
        isOptional.value = Boolean(props.subject.is_optional);
        isDisabled.value = Boolean(props.subject.is_disabled);
    } else {
        name.value = '';
        course.value = props.defaultCourse || 'hsc';
        group.value = props.defaultGroup || 'science';
        sortOrder.value = 0;
        isOptional.value = false;
        isDisabled.value = false;
    }

    errorMessage.value = '';
};

watch([() => props.isOpen, () => props.subject], ([open]) => {
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
        course: course.value,
        group: group.value,
        sort_order: sortOrder.value,
        is_optional: isOptional.value,
        is_disabled: isDisabled.value,
    };

    if (props.subject) {
        router.patch(`/admin/subjects/edit/${props.subject.id}`, payload, {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            },
            onError: (errors) => {
                errorMessage.value =
                    Object.values(errors).flat().join(', ') ||
                    'Failed to update subject.';
            },
            onFinish: () => {
                isSaving.value = false;
            },
        });
    } else {
        router.post('/admin/subjects', payload, {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            },
            onError: (errors) => {
                errorMessage.value =
                    Object.values(errors).flat().join(', ') ||
                    'Failed to create subject.';
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
        :title="subject ? 'Edit Subject' : 'Create Subject'"
        :description="
            subject
                ? 'Update subject settings'
                : 'Add a new subject to the platform'
        "
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
                <!-- Subject Name -->
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Subject Name <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="name"
                        type="text"
                        placeholder="e.g. পদার্থবিজ্ঞান ১ম পত্র"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                    />
                </div>

                <!-- Course & Group Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Level / Course
                        </label>
                        <select
                            v-model="course"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="hsc">HSC</option>
                            <option value="ssc">SSC</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Discipline / Group
                        </label>
                        <select
                            v-model="group"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="science">Science</option>
                            <option value="humanities">Humanities</option>
                            <option value="commerce">Commerce</option>
                            <option value="common">Common (All Groups)</option>
                        </select>
                    </div>
                </div>

                <!-- Sort Order -->
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

                <!-- Toggles -->
                <div class="space-y-2 pt-2">
                    <label
                        class="flex items-center gap-2.5 text-xs text-slate-700 dark:text-gray-300"
                    >
                        <input
                            v-model="isOptional"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800"
                        />
                        <span>Optional Subject (4th Subject)</span>
                    </label>

                    <label
                        class="flex items-center gap-2.5 text-xs text-slate-700 dark:text-gray-300"
                    >
                        <input
                            v-model="isDisabled"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800"
                        />
                        <span>Disabled / Archived</span>
                    </label>
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
                        subject ? 'Save Changes' : 'Create Subject'
                    }}</span>
                </button>
            </div>
        </form>
    </BaseModal>
</template>
