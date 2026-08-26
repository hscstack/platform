<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    FileText,
    File,
    Image as ImageIcon,
    Video,
    Upload,
    Link as LinkIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    redirect: String,
    node: Object,
    resource: Object,
});

const form = useForm({
    redirect: props?.redirect || '/',
    resource_type: props.resource?.resource_type || 'image',
    title: props.resource?.title || '',
    content: props.resource?.content || '',
    external_url: props.resource?.external_url || '',
    file: null,
    node_id: props.node.id,
});

const resourceTypes = [
    {
        id: 'image',
        name: 'Image',
        icon: ImageIcon,
        color: 'text-emerald-600 bg-emerald-50 border-emerald-200 dark:text-emerald-400 dark:bg-emerald-500/10 dark:border-emerald-500/30',
    },
    {
        id: 'pdf',
        name: 'PDF Document',
        icon: File,
        color: 'text-rose-600 bg-rose-50 border-rose-200 dark:text-rose-400 dark:bg-rose-500/10 dark:border-rose-500/30',
    },
    {
        id: 'note',
        name: 'Text Note',
        icon: FileText,
        color: 'text-amber-600 bg-amber-50 border-amber-200 dark:text-amber-400 dark:bg-amber-500/10 dark:border-amber-500/30',
    },
    {
        id: 'video',
        name: 'Video Link',
        icon: Video,
        color: 'text-blue-600 bg-blue-50 border-blue-200 dark:text-blue-400 dark:bg-blue-500/10 dark:border-blue-500/30',
    },
];

// Structural visibility logic
const requiresFile = computed(() => form.resource_type === 'image');
const requiresLink = computed(() =>
    ['video', 'pdf'].includes(form.resource_type),
);

const handleFileSelect = (event) => {
    form.file = event.target.files[0];
};

const submitForm = () => {
    if (!requiresFile.value) {
        form.file = null;
    }

    if (!requiresLink.value) {
        form.external_url = '';
    }

    if (props.resource) {
        form.post(`/admin/resources/${props.resource.id}/patch`, {
            forceFormData: true,
        });
    } else {
        form.post('/admin/resources', {
            forceFormData: true,
        });
    }
};
</script>

<template>
    <Head
        :title="
            props.resource ? `Edit ${props.resource.title}` : 'Add Resource'
        "
    />

    <div class="flex w-full flex-1 flex-col">
        <div
            class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-center dark:border-gray-800"
        >
            <div>
                <h1
                    class="text-2xl font-bold text-slate-900 dark:text-gray-100"
                >
                    {{ props.resource ? 'Edit' : 'Add New' }} Resource
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                    Upload assets or link content for the curriculum
                    structure.
                </p>
            </div>
        </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- 1. Resource Type Selector -->
                <div>
                    <label
                        class="mb-3 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Resource Type
                    </label>
                    <div
                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"
                    >
                        <button
                            type="button"
                            v-for="type in resourceTypes"
                            :key="type.id"
                            @click="form.resource_type = type.id"
                            class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 p-4 text-center transition-all focus:outline-none"
                            :class="
                                form.resource_type === type.id
                                    ? 'border-blue-600 bg-blue-50/40 font-semibold text-blue-700 ring-2 ring-blue-600/10 dark:border-blue-500 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20'
                                    : 'border-slate-100 text-slate-600 hover:border-slate-200 dark:border-gray-800 dark:text-gray-400 dark:hover:border-gray-600'
                            "
                        >
                            <div class="rounded-lg p-2" :class="type.color">
                                <component
                                    :is="type.icon"
                                    class="h-5 w-5 shrink-0"
                                />
                            </div>
                            <span class="text-xs tracking-tight">{{
                                type.name
                            }}</span>
                        </button>
                    </div>
                    <p
                        v-if="form.errors.resource_type"
                        class="mt-1 text-sm text-rose-600 dark:text-rose-400"
                    >
                        {{ form.errors.resource_type }}
                    </p>
                </div>

                <!-- 2. Resource Title (Required for ALL) -->
                <div>
                    <label
                        for="title"
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Resource Title
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        id="title"
                        placeholder="e.g. Lecture 01 Introduction Notes"
                        maxlength="100"
                        class="w-full rounded-lg border px-4 py-2.5 transition outline-none"
                        :class="
                            form.errors.title
                                ? 'border-rose-500 focus:ring-rose-500/20'
                                : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'
                        "
                    />
                    <p
                        v-if="form.errors.title"
                        class="mt-1 text-sm text-rose-600 dark:text-rose-400"
                    >
                        {{ form.errors.title }}
                    </p>
                </div>

                <!-- 3. Content Body (Present for ALL types) -->
                <div>
                    <label
                        for="content"
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Content Body
                    </label>
                    <textarea
                        v-model="form.content"
                        id="content"
                        rows="3"
                        placeholder="Type notes, descriptions, or body text..."
                        class="w-full rounded-lg border px-4 py-2.5 font-sans transition outline-none"
                        :class="
                            form.errors.content
                                ? 'border-rose-500 focus:ring-rose-500/20'
                                : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'
                        "
                    ></textarea>
                    <p
                        v-if="form.errors.content"
                        class="mt-1 text-sm text-rose-600 dark:text-rose-400"
                    >
                        {{ form.errors.content }}
                    </p>
                </div>

                <!-- 4. File URL (Only for Video and PDF) -->
                <div v-if="requiresLink">
                    <label
                        for="external_url"
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        {{
                            form.resource_type === 'pdf'
                                ? 'PDF Resource URL'
                                : 'Video Link / URL'
                        }}
                    </label>
                    <div class="relative flex items-center">
                        <LinkIcon
                            class="pointer-events-none absolute left-4 h-4 w-4 text-slate-400 dark:text-gray-500"
                        />
                        <input
                            v-model="form.external_url"
                            type="url"
                            id="external_url"
                            :placeholder="
                                form.resource_type === 'pdf'
                                    ? 'e.g. https://example.com/document.pdf'
                                    : 'e.g. https://www.youtube.com/watch?v=... or Vimeo URL'
                            "
                            class="w-full rounded-lg border py-2.5 pr-4 pl-11 transition outline-none"
                            :class="
                                form.errors.external_url
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'
                            "
                        />
                    </div>
                    <p
                        v-if="form.errors.external_url"
                        class="mt-1 text-sm text-rose-600 dark:text-rose-400"
                    >
                        {{ form.errors.external_url }}
                    </p>
                </div>

                <!-- 5. File Upload (Only for Image) -->
                <div v-if="requiresFile">
                    <label
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Attach Image File
                    </label>
                    <div
                        class="rounded-xl border-2 border-dashed bg-slate-50/50 p-6 text-center transition dark:bg-gray-950/50"
                        :class="
                            form.errors.file
                                ? 'border-rose-300 bg-rose-50/20 dark:border-rose-400/60 dark:bg-rose-500/10'
                                : 'border-slate-200 hover:bg-slate-50 dark:border-gray-700 dark:hover:bg-gray-800'
                        "
                    >
                        <input
                            type="file"
                            id="file-upload"
                            class="hidden"
                            @change="handleFileSelect"
                            accept="image/jpeg,image/png,image/jpg"
                        />
                        <label
                            for="file-upload"
                            class="flex cursor-pointer flex-col items-center justify-center"
                        >
                            <div
                                class="mb-2 rounded-full border border-slate-100 bg-white p-3 text-slate-400 shadow-sm dark:border-gray-800 dark:bg-gray-800 dark:text-gray-500"
                            >
                                <Upload class="h-5 w-5" />
                            </div>
                            <span
                                class="text-sm font-medium text-red-700 dark:text-red-400"
                            >
                                {{
                                    form.file
                                        ? form.file.name
                                        : 'Click to upload or drag & drop'
                                }}
                            </span>
                            <span
                                class="mt-1 text-xs text-slate-400 dark:text-gray-500"
                            >
                                Max size: 10MB (JPG, JPEG, PNG)
                            </span>
                        </label>
                    </div>
                    <p
                        v-if="form.errors.file"
                        class="mt-1 text-sm text-rose-600 dark:text-rose-400"
                    >
                        {{ form.errors.file }}
                    </p>
                </div>

                <!-- Form Actions -->
                <div
                    class="flex justify-end space-x-3 border-t border-slate-100 pt-6 dark:border-gray-800"
                >
                    <Link
                        :href="redirect"
                        type="button"
                        class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Resource' }}
                    </button>
                </div>
            </form>
        </div>
</template>
