<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { FolderOpen, ChevronDown, ChevronRight } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    subject: Object,
    parent: Object,
    redirect: String,
    node: Object,
});

const showSlug = ref(false);

const form = useForm({
    name: props.node?.name || '',
    slug: props.node?.slug || '',
    parent_id: props.node?.parent_id || props.parent?.id || null,
    sort_order: props.node?.sort_order ?? 0,
    redirect: props.redirect,
});

function getInputClass(hasError) {
    return hasError
        ? 'border-rose-500 focus:ring-rose-500/20 bg-white dark:bg-gray-900 text-slate-900 dark:text-gray-100 placeholder:text-slate-400 dark:placeholder:text-gray-500'
        : 'border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-slate-900 dark:text-gray-100 placeholder:text-slate-400 dark:placeholder:text-gray-500 focus:ring-blue-500/20 focus:border-blue-500';
}

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
    <Head :title="node ? `Edit ${node.name}` : 'Create Node'" />

    <div class="flex w-full flex-1 flex-col">
        <div
            class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-center dark:border-gray-800"
        >
            <div>
                <h1
                    class="text-2xl font-bold text-slate-900 dark:text-gray-100"
                >
                    {{ props.node ? 'Edit Folder' : 'Create Folder' }}
                </h1>
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
                        <label
                            for="name"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >Folder Name</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            id="name"
                            placeholder="e.g., Chapter 1: Introduction"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none"
                            :class="getInputClass(form.errors.name)"
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
                        <label
                            for="sort_order"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >Order Priority</label
                        >
                        <input
                            v-model.number="form.sort_order"
                            type="number"
                            id="sort_order"
                            placeholder="0"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none"
                            :class="getInputClass(form.errors.sort_order)"
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

                    <div class="pt-1 md:col-span-3">
                        <button
                            type="button"
                            @click="showSlug = !showSlug"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 transition hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                        >
                            <component
                                :is="
                                    showSlug || form.errors.slug
                                        ? ChevronDown
                                        : ChevronRight
                                "
                                class="h-3.5 w-3.5"
                            />
                            <span
                                >Change slug?
                                <span
                                    class="font-normal text-slate-400 dark:text-gray-500"
                                    >(advanced)</span
                                ></span
                            >
                        </button>

                        <div
                            v-if="showSlug || form.errors.slug"
                            class="mt-3 space-y-1.5 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-gray-700 dark:bg-gray-800/50"
                        >
                            <label
                                for="slug"
                                class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                Custom URL Slug
                                <span
                                    class="text-[11px] font-normal text-slate-400 dark:text-gray-500"
                                    >(Optional)</span
                                >
                            </label>
                            <input
                                v-model="form.slug"
                                type="text"
                                id="slug"
                                placeholder="e.g., chapter-1-intro (leave blank to auto-generate from name)"
                                class="w-full rounded-lg border px-3.5 py-2 text-sm transition outline-none"
                                :class="getInputClass(form.errors.slug)"
                            />
                            <p
                                v-if="form.errors.slug"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ form.errors.slug }}
                            </p>
                            <p
                                class="text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                Leave empty to automatically generate from the
                                folder name.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end space-x-3 border-t border-slate-100 pt-4 dark:border-gray-800"
                >
                    <button
                        @click="goBack"
                        class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'Saving...'
                                : props.node
                                  ? 'Save Changes'
                                  : 'Create Folder'
                        }}
                    </button>
                </div>
            </form>
        </div>
</template>
