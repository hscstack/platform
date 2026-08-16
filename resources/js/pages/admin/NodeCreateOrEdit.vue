<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FolderOpen } from 'lucide-vue-next';
import { kInput, kButton, kBlockTitle } from 'konsta/vue';

const props = defineProps({
    subject: Object,
    parent: Object,
    redirect: String,
    node: Object,
});

const form = useForm({
    name: props.node?.name || '',
    parent_id: props.node?.parent_id || props.parent?.id || null,
    sort_order: props.node?.sort_order ?? 0,
    redirect: props.redirect,
});

const submitForm = () => {
    if (props.node) {
        form.patch(
            '/admin/subjects/' + props.subject.id + '/nodes/' + props.node.id,
            {
                preserveScroll: true,
            },
        );
    } else {
        form.post('/admin/subjects/' + props.subject.id + '/nodes', {
            preserveScroll: true,
        });
    }
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <div
        class="flex min-h-full w-full flex-col justify-start bg-slate-50 p-6 lg:p-10 dark:bg-gray-950"
    >
        <div
            class="w-full rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm md:p-10 dark:border-gray-700 dark:bg-gray-900"
        >
            <div
                class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center dark:border-gray-800"
            >
                <div>
                    <kBlockTitle
                        class="!text-2xl !font-bold !text-slate-900 dark:!text-gray-100"
                    >
                        {{ props.node ? 'Edit Folder' : 'Create Folder' }}
                    </kBlockTitle>
                    <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                        Subject:
                        <span
                            class="font-semibold text-slate-700 dark:text-gray-300"
                            >{{ props.subject?.name }}</span
                        >
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-8">
                <div
                    class="flex items-start space-x-3 rounded-xl border border-slate-200/60 bg-slate-50 p-5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <FolderOpen
                        class="mt-0.5 h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400"
                    />
                    <div>
                        <span
                            class="mb-1 block text-xs font-bold tracking-wider text-blue-600 uppercase dark:text-blue-400"
                            >Location</span
                        >
                        <p
                            v-if="props.parent?.id"
                            class="text-sm text-slate-700 dark:text-gray-300"
                        >
                            You are {{ props.node ? 'editing' : 'creating' }} a
                            <span
                                class="font-semibold text-blue-700 dark:text-blue-400"
                                >sub-folder</span
                            >
                            inside:
                            <span
                                class="ml-1 rounded bg-blue-100 px-1.5 py-0.5 font-sans text-xs font-bold text-slate-900 dark:bg-blue-500/10 dark:text-gray-100"
                            >
                                {{ props.parent.name }}
                            </span>
                        </p>
                        <p
                            v-else
                            class="text-sm text-slate-700 dark:text-gray-300"
                        >
                            You are {{ props.node ? 'editing' : 'creating' }} a
                            <span
                                class="font-semibold text-blue-700 dark:text-blue-400"
                                >top-level folder</span
                            >
                            directly under this subject.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <k-input
                            label="Folder Name"
                            type="text"
                            :value="form.name"
                            @input="form.name = $event.target.value"
                            placeholder="e.g., Chapter 1: Introduction"
                            outline
                            :error="form.errors.name"
                            required
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <k-input
                            label="Order Priority"
                            type="number"
                            :value="form.sort_order"
                            @input="
                                form.sort_order = Number($event.target.value)
                            "
                            placeholder="0"
                            outline
                            :error="form.errors.sort_order"
                        />
                        <p
                            v-if="form.errors.sort_order"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.sort_order }}
                        </p>
                        <p
                            class="mt-1.5 text-xs text-slate-400 dark:text-gray-500"
                        >
                            Lower numbers will appear first in the list.
                        </p>
                    </div>
                </div>

                <div
                    class="flex justify-end space-x-3 border-t border-slate-100 pt-4 dark:border-gray-800"
                >
                    <k-button outline @click="goBack"> Cancel </k-button>
                    <k-button
                        type="submit"
                        fill
                        :disabled="form.processing"
                        class="!rounded-lg"
                    >
                        {{
                            form.processing
                                ? 'Saving...'
                                : props.node
                                  ? 'Save Changes'
                                  : 'Create Folder'
                        }}
                    </k-button>
                </div>
            </form>
        </div>
    </div>
</template>
