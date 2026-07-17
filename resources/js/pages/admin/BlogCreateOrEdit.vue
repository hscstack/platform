<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';

const props = defineProps({
    blog: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    title: props.blog?.title || '',
    content: props.blog?.content || '',
    featured_image: null,
    is_published: props.blog?.is_published ?? true,
    is_featured: props.blog?.is_featured ?? false,
    seo_tags: props.blog?.seo_tags || '',
});

const handleFileSelect = (event: any) => {
    form.featured_image = event.target.files[0];
};

const submitForm = () => {
    if (props.blog) {
        form.post(`/admin/blogs/edit/${props.blog.slug}/patch`, {
            preserveScroll: true,
            forceFormData: true,
        });
    } else {
        form.post('/admin/blogs', {
            preserveScroll: true,
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
                        {{ props.blog ? 'Edit' : 'Create' }} Blog Post
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Compose and manage article content for your web
                        application audience.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <div>
                    <label
                        for="title"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >Blog Title</label
                    >
                    <input
                        v-model="form.title"
                        type="text"
                        id="title"
                        placeholder="e.g. 10 Tips for Cracking BUET"
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

                <div>
                    <label
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >Featured Image</label
                    >
                    <div
                        class="rounded-xl border-2 border-dashed bg-slate-50/50 p-6 text-center transition"
                        :class="
                            form.errors.featured_image
                                ? 'border-rose-300 bg-rose-50/20'
                                : 'border-slate-200 hover:bg-slate-50'
                        "
                    >
                        <input
                            type="file"
                            id="featured_image_upload"
                            class="hidden"
                            accept="image/*"
                            @change="handleFileSelect"
                        />
                        <label
                            for="featured_image_upload"
                            class="flex cursor-pointer flex-col items-center justify-center"
                        >
                            <div
                                class="mb-2 rounded-full border border-slate-100 bg-white p-3 text-slate-400 shadow-sm"
                            >
                                <Upload class="h-5 w-5" />
                            </div>
                            <span
                                class="text-center text-sm font-medium break-all text-blue-700"
                            >
                                {{
                                    form.featured_image?.name ||
                                    'Click to upload or drag & drop image'
                                }}
                            </span>
                            <span class="mt-1 text-xs text-slate-400">
                                Supports PNG, JPG or WEBP
                            </span>
                        </label>
                    </div>
                    <p
                        v-if="form.errors.featured_image"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.featured_image }}
                    </p>
                </div>

                <div>
                    <label
                        for="seo_tags"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >SEO Tags (Comma separated)</label
                    >
                    <input
                        v-model="form.seo_tags"
                        type="text"
                        id="seo_tags"
                        placeholder="e.g. admission, news, tips"
                        class="w-full rounded-lg border px-4 py-2.5 transition outline-none"
                        :class="
                            form.errors.seo_tags
                                ? 'border-rose-500 focus:ring-rose-500/20'
                                : 'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20'
                        "
                    />
                    <p
                        v-if="form.errors.seo_tags"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.seo_tags }}
                    </p>
                </div>

                <div
                    class="flex flex-wrap gap-6 rounded-xl border border-slate-100 bg-slate-50/50 p-4"
                >
                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input
                            v-model="form.is_published"
                            type="checkbox"
                            class="h-4.5 w-4.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500/20"
                        />
                        <div>
                            <span
                                class="block text-xs font-bold text-slate-700 uppercase"
                                >Publish immediately</span
                            >
                            <span class="text-[11px] text-slate-500"
                                >Make this blog post visible right away.</span
                            >
                        </div>
                    </label>

                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input
                            v-model="form.is_featured"
                            type="checkbox"
                            class="h-4.5 w-4.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500/20"
                        />
                        <div>
                            <span
                                class="block text-xs font-bold text-slate-700 uppercase"
                                >Feature Post</span
                            >
                            <span class="text-[11px] text-slate-500"
                                >Pin to the top of your blog home screen.</span
                            >
                        </div>
                    </label>
                </div>

                <div>
                    <label
                        for="content"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >Blog Content (Supports HTML)</label
                    >
                    <textarea
                        v-model="form.content"
                        id="content"
                        rows="12"
                        placeholder="Write your article content here..."
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

                <div
                    class="flex justify-end space-x-3 border-t border-slate-100 pt-6"
                >
                    <Link
                        href="/admin/blogs"
                        class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Blog' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
