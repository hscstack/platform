<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
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
    file: null,
    node_id: props.node.id,
});

const resourceTypes = [
    {
        id: 'image',
        name: 'Image',
        icon: ImageIcon,
        color: 'text-emerald-600 bg-emerald-50 border-emerald-200',
    },

    {
        id: 'pdf',
        name: 'PDF Document',
        icon: File,
        color: 'text-rose-600 bg-rose-50 border-rose-200',
    },
    {
        id: 'note',
        name: 'Text Note',
        icon: FileText,
        color: 'text-amber-600 bg-amber-50 border-amber-200',
    },
    {
        id: 'video',
        name: 'Video Link',
        icon: Video,
        color: 'text-blue-600 bg-blue-50 border-blue-200',
    },
];

const requiresFile = computed(() => ['image'].includes(form.resource_type));
const requiresContent = computed(() =>
    ['note', 'question', 'image'].includes(form.resource_type),
);
const requiresLink = computed(() =>
    ['video', 'pdf'].includes(form.resource_type),
);

const handleFileSelect = (event) => {
    form.file = event.target.files[0];
};

const submitForm = () => {
    // If switching from file to link, make sure old file references are cleared out
    if (requiresLink.value) {
        form.file = null;
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
    <div
        class="flex min-h-full w-full flex-col justify-start bg-slate-50 p-6 lg:p-10"
    >
        <div
            class="mx-auto w-full max-w-4xl rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm md:p-10"
        >
            <div
                class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center"
            >
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        {{ props.resource ? 'Edit' : 'Add New' }} Resource
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Upload assets or link content for the curriculum
                        structure.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <div>
                    <label
                        class="mb-3 block text-sm font-semibold text-slate-700"
                        >Resource Type</label
                    >
                    <div
                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5"
                    >
                        <button
                            type="button"
                            v-for="type in resourceTypes"
                            :key="type.id"
                            @click="form.resource_type = type.id"
                            class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 p-4 text-center transition-all focus:outline-none"
                            :class="
                                form.resource_type === type.id
                                    ? 'border-blue-600 bg-blue-50/40 font-semibold text-blue-700 ring-2 ring-blue-600/10'
                                    : 'border-slate-100 text-slate-600 hover:border-slate-200'
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
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.resource_type }}
                    </p>
                </div>

                <div>
                    <label
                        for="title"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >Resource Title</label
                    >
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
                                : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20'
                        "
                    />
                    <p
                        v-if="form.errors.title"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.title }}
                    </p>
                </div>

                <div v-if="requiresContent">
                    <label
                        for="content"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >Content Body</label
                    >
                    <textarea
                        v-model="form.content"
                        id="content"
                        rows="2"
                        placeholder="Type your notes or questions details right here..."
                        class="w-full rounded-lg border px-4 py-2.5 font-sans transition outline-none"
                        :class="
                            form.errors.content
                                ? 'border-rose-500 focus:ring-rose-500/20'
                                : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20'
                        "
                    ></textarea>
                    <p
                        v-if="form.errors.content"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.content }}
                    </p>
                </div>

                <div v-if="requiresLink">
                    <label
                        for="content_link"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        {{
                            form.resource_type === 'pdf'
                                ? 'Resource Link / URL'
                                : 'Video URL / Link'
                        }}
                    </label>
                    <div class="relative flex items-center">
                        <LinkIcon
                            class="pointer-events-none absolute left-4 h-4 w-4 text-slate-400"
                        />
                        <input
                            v-model="form.content"
                            type="url"
                            id="content_link"
                            :placeholder="
                                form.resource_type === 'pdf'
                                    ? 'e.g. https://example.com/document.pdf'
                                    : 'e.g. https://www.youtube.com/watch?v=... or Vimeo URL'
                            "
                            class="w-full rounded-lg border py-2.5 pr-4 pl-11 transition outline-none"
                            :class="
                                form.errors.content
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20'
                            "
                        />
                    </div>
                    <p
                        v-if="form.errors.content"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.content }}
                    </p>
                </div>

                <div v-if="requiresFile">
                    <label
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >Attach File</label
                    >
                    <div
                        class="rounded-xl border-2 border-dashed bg-slate-50/50 p-6 text-center transition"
                        :class="
                            form.errors.file
                                ? 'border-rose-300 bg-rose-50/20'
                                : 'border-slate-200 hover:bg-slate-50'
                        "
                    >
                        <input
                            type="file"
                            id="file-upload"
                            class="hidden"
                            @change="handleFileSelect"
                            :accept="
                                form.resource_type === 'pdf'
                                    ? '.pdf'
                                    : 'image/*'
                            "
                        />
                        <label
                            for="file-upload"
                            class="flex cursor-pointer flex-col items-center justify-center"
                        >
                            <div
                                class="mb-2 rounded-full border border-slate-100 bg-white p-3 text-slate-400 shadow-sm"
                            >
                                <Upload class="h-5 w-5" />
                            </div>
                            <span class="text-sm font-medium text-red-700">
                                {{
                                    form.file
                                        ? form.file.name
                                        : 'Click to upload or drag & drop'
                                }}
                            </span>
                            <span class="mt-1 text-xs text-slate-400">
                                Max file size: 10MB (Supports
                                {{ form.resource_type.toUpperCase() }})
                            </span>
                        </label>
                    </div>
                    <p
                        v-if="form.errors.file"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.file }}
                    </p>
                </div>

                <div
                    class="flex justify-end space-x-3 border-t border-slate-100 pt-6"
                >
                    <Link
                        :href="redirect"
                        type="button"
                        class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
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
    </div>
</template>
