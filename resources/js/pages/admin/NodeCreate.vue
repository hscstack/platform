<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { FolderOpen } from 'lucide-vue-next';

const props = defineProps({
    subject: Object,
    parent: Object,
});

const form = useForm({
    name: '',
    parent_id: props.parent?.id || null,
    sort_order: 0,
});

function getInputClass(hasError) {
    return hasError
        ? 'border-rose-500 focus:ring-rose-500/20'
        : 'border-slate-300 focus:ring-blue-500/20 focus:border-blue-500';
}

const submitForm = () => {
    form.post('/admin/subjects/' + props.subject.id + '/nodes', {
        preserveScroll: true,
        
    });
};
</script>

<template>
    <div
        class="flex min-h-full w-full flex-col justify-start bg-slate-50 p-6 lg:p-10"
    >
        <div
            class="w-full rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm md:p-10"
        >
            <div
                class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center"
            >
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Create Folder
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Subject:
                        <span class="font-semibold text-slate-700">{{
                            props.subject?.name
                        }}</span>
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-8">
                <div
                    class="flex items-start space-x-3 rounded-xl border border-slate-200/60 bg-slate-50 p-5"
                >
                    <FolderOpen class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" />
                    <div>
                        <span
                            class="mb-1 block text-xs font-bold tracking-wider text-blue-600 uppercase"
                            >Location</span
                        >
                        <p
                            v-if="props.parent?.id"
                            class="text-sm text-slate-700"
                        >
                            You are creating a
                            <span class="font-semibold text-blue-700"
                                >sub-folder</span
                            >
                            inside:
                            <span
                                class="ml-1 rounded bg-blue-100 px-1.5 py-0.5 font-sans text-xs font-bold text-slate-900"
                            >
                                {{ props.parent.name }}
                            </span>
                        </p>
                        <p v-else class="text-sm text-slate-700">
                            You are creating a
                            <span class="font-semibold text-blue-700"
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
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
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
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
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
                        <p class="mt-1.5 text-xs text-slate-400">
                            Lower numbers will appear first in the list.
                        </p>
                    </div>
                </div>

                <div
                    class="flex justify-end space-x-3 border-t border-slate-100 pt-4"
                >
                    <Link
                        :href="`/admin/subjects/${subject.slug}/nodes`"
                        class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Create Folder' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
