<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
import HTMLEditor from '@/components/HTMLEditor.vue';
import { kInput, kCheckbox, kButton, kBlockTitle } from 'konsta/vue';

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
        class="flex min-h-full w-full flex-col justify-start bg-slate-50 p-6 lg:p-10 dark:bg-gray-950"
    >
        <div
            class="mx-auto w-full max-w-4xl rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm md:p-10 dark:border-gray-700 dark:bg-gray-900"
        >
            <div
                class="mb-8 flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center dark:border-gray-800"
            >
                <div>
                    <kBlockTitle>
                        {{ props.blog ? 'Edit' : 'Create' }} Blog Post
                    </kBlockTitle>
                    <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                        Compose and manage article content for your web
                        application audience.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <div>
                    <k-input
                        label="Blog Title"
                        type="text"
                        :value="form.title"
                        @input="form.title = $event.target.value"
                        placeholder="e.g. 10 Tips for Cracking BUET"
                        outline
                        :error="form.errors.title"
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
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >Featured Image</label
                    >
                    <div
                        class="rounded-xl border-2 border-dashed bg-slate-50/50 p-6 text-center transition dark:bg-gray-800/50"
                        :class="
                            form.errors.featured_image
                                ? 'border-rose-300 bg-rose-50/20 dark:border-rose-500/30 dark:bg-rose-500/10'
                                : 'border-slate-200 dark:border-gray-700 dark:hover:bg-gray-800'
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
                                class="mb-2 rounded-full border border-slate-100 bg-white p-3 text-slate-400 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-500"
                            >
                                <Upload class="h-5 w-5" />
                            </div>
                            <span
                                class="text-center text-sm font-medium break-all text-blue-700 dark:text-blue-400"
                            >
                                {{
                                    form.featured_image?.name ||
                                    'Click to upload or drag & drop image'
                                }}
                            </span>
                            <span
                                class="mt-1 text-xs text-slate-400 dark:text-gray-500"
                            >
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
                    <k-input
                        label="SEO Tags (Comma separated)"
                        type="text"
                        :value="form.seo_tags"
                        @input="form.seo_tags = $event.target.value"
                        placeholder="e.g. admission, news, tips"
                        outline
                        :error="form.errors.seo_tags"
                    />
                    <p
                        v-if="form.errors.seo_tags"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.seo_tags }}
                    </p>
                </div>

                <div
                    class="flex flex-wrap gap-6 rounded-xl border border-slate-100 bg-slate-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/50"
                >
                    <label class="flex cursor-pointer items-center gap-2.5">
                        <k-checkbox
                            :checked="form.is_published"
                            @change="form.is_published = $event.target.checked"
                        />
                        <div>
                            <span
                                class="block text-xs font-bold text-slate-700 uppercase dark:text-gray-300"
                                >Publish immediately</span
                            >
                            <span
                                class="text-[11px] text-slate-500 dark:text-gray-400"
                                >Make this blog post visible right away.</span
                            >
                        </div>
                    </label>

                    <label class="flex cursor-pointer items-center gap-2.5">
                        <k-checkbox
                            :checked="form.is_featured"
                            @change="form.is_featured = $event.target.checked"
                        />
                        <div>
                            <span
                                class="block text-xs font-bold text-slate-700 uppercase dark:text-gray-300"
                                >Feature Post</span
                            >
                            <span
                                class="text-[11px] text-slate-500 dark:text-gray-400"
                                >Pin to the top of your blog home screen.</span
                            >
                        </div>
                    </label>
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >Blog Content</label
                    >

                    <HTMLEditor
                        v-model="form.content"
                        :error="form.errors.content"
                        placeholder="Write your article content here..."
                    />

                    <p
                        v-if="form.errors.content"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.content }}
                    </p>
                </div>

                <div
                    class="flex justify-end space-x-3 border-t border-slate-100 pt-6 dark:border-gray-800"
                >
                    <Link href="/admin/blogs">
                        <k-button clear>Cancel</k-button>
                    </Link>
                    <k-button type="submit" fill :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Blog' }}
                    </k-button>
                </div>
            </form>
        </div>
    </div>
</template>
