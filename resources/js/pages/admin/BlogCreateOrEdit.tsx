import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2, Save, Trash2, Upload } from 'lucide-vue-next';
import { defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

import HTMLEditor from '@/components/HTMLEditor.vue';

interface Blog {
    id?: number;
    slug: string;
    title?: string | null;
    content?: string | null;
    featured_image?: string | null;
    is_published?: boolean;
    is_featured?: boolean;
    seo_tags?: string | null;
}

export default defineComponent({
    name: 'AdminBlogCreateOrEdit',
    props: {
        blog: {
            type: Object as PropType<Blog | null>,
            default: null,
        },
    },
    setup(props) {
        const form = useForm({
            title: props.blog?.title || '',
            content: props.blog?.content || '',
            featured_image: null as File | null,
            is_published: props.blog?.is_published ?? true,
            is_featured: props.blog?.is_featured ?? false,
            seo_tags: props.blog?.seo_tags || '',
        });

        const coverPreview = ref<string | null>(
            props.blog?.featured_image ?? null,
        );

        const fileInput = ref<HTMLInputElement | null>(null);

        const handleFileSelect = (event: Event) => {
            const target = event.target as HTMLInputElement;

            if (target.files && target.files[0]) {
                const file = target.files[0];
                form.featured_image = file;
                coverPreview.value = URL.createObjectURL(file);
            }
        };

        const handleRemoveCover = (): void => {
            form.featured_image = null;
            coverPreview.value = null;

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        };

        const submitForm = (e: Event) => {
            e.preventDefault();

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

        const onTitleInput = (e: Event) => {
            form.title = (e.target as HTMLInputElement).value;
        };

        const onSeoTagsInput = (e: Event) => {
            form.seo_tags = (e.target as HTMLInputElement).value;
        };

        const onIsPublishedChange = (e: Event) => {
            form.is_published = (e.target as HTMLInputElement).checked;
        };

        const onIsFeaturedChange = (e: Event) => {
            form.is_featured = (e.target as HTMLInputElement).checked;
        };

        const onContentUpdate = (val: string) => {
            form.content = val;
        };

        const saveButtonContent = () =>
            form.processing ? (
                <>
                    <Loader2 class="h-3.5 w-3.5 animate-spin" />
                    <span>Saving...</span>
                </>
            ) : (
                <>
                    <Save class="h-3.5 w-3.5" />
                    <span>Save Blog</span>
                </>
            );

        return () => (
            <>
                <Head
                    title={
                        props.blog
                            ? `Edit ${props.blog.title}`
                            : 'Write Blog Post'
                    }
                />

                <div class="space-y-6">
                    {/* Page header */}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div class="min-w-0">
                            <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                {props.blog ? 'Edit' : 'Create'} Blog Post
                            </h1>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                Write and publish articles for your readers.
                            </p>
                        </div>

                        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                            <Link
                                href="/admin/blogs"
                                class="inline-flex h-9 w-full cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 sm:w-auto dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </Link>
                            <button
                                type="button"
                                onClick={submitForm}
                                disabled={form.processing}
                                class="inline-flex h-9 w-full cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                {saveButtonContent()}
                            </button>
                        </div>
                    </div>

                    <form onSubmit={submitForm} class="max-w-3xl space-y-5">
                        {/* Title */}
                        <div>
                            <label
                                for="title"
                                class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                Title
                            </label>
                            <input
                                value={form.title}
                                onInput={onTitleInput}
                                type="text"
                                id="title"
                                placeholder="e.g. 10 Tips for Cracking BUET"
                                class={[
                                    'h-9 w-full rounded-xl border bg-white px-3 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400',
                                    form.errors.title
                                        ? 'border-rose-300 focus:border-rose-500 dark:border-rose-500/40'
                                        : 'border-slate-200 dark:border-gray-700',
                                ]}
                            />
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-gray-500">
                                {props.blog?.slug
                                    ? `/blogs/${props.blog.slug}`
                                    : 'URL slug is generated automatically from the title.'}
                            </p>
                            {form.errors.title && (
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {form.errors.title}
                                </p>
                            )}
                        </div>

                        {/* Cover */}
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300">
                                Cover image
                            </label>
                            <input
                                ref={fileInput}
                                type="file"
                                id="featured_image_upload"
                                class="hidden"
                                accept="image/*"
                                onChange={handleFileSelect}
                            />
                            {coverPreview.value ? (
                                <div class="flex items-center gap-3">
                                    <img
                                        src={coverPreview.value}
                                        alt="Cover preview"
                                        class="h-16 w-16 shrink-0 rounded-xl border border-slate-200 object-cover dark:border-gray-700"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-semibold text-slate-700 dark:text-gray-200">
                                            {form.featured_image?.name ??
                                                'Current cover image'}
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500">
                                            PNG, JPG or WEBP
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <label
                                            for="featured_image_upload"
                                            class="inline-flex h-8 shrink-0 cursor-pointer items-center rounded-xl border border-slate-200 bg-white px-2.5 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50 active:scale-95 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                                        >
                                            Change
                                        </label>
                                        <button
                                            type="button"
                                            onClick={handleRemoveCover}
                                            class="inline-flex h-8 cursor-pointer items-center gap-1 rounded-xl px-2.5 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-rose-400 dark:hover:bg-rose-500/10"
                                        >
                                            <Trash2 class="h-3 w-3" />
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            ) : (
                                <div
                                    class={[
                                        'rounded-xl border border-dashed p-5 text-center transition dark:bg-gray-900',
                                        form.errors.featured_image
                                            ? 'border-rose-300 dark:border-rose-500/40'
                                            : 'border-slate-200 dark:border-gray-700',
                                    ]}
                                >
                                    <label
                                        for="featured_image_upload"
                                        class="flex cursor-pointer flex-col items-center justify-center"
                                    >
                                        <Upload class="mb-1.5 h-4 w-4 text-slate-400 dark:text-gray-500" />
                                        <span class="text-xs font-semibold text-slate-700 dark:text-gray-200">
                                            Click to upload cover image
                                        </span>
                                        <span class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500">
                                            PNG, JPG or WEBP
                                        </span>
                                    </label>
                                </div>
                            )}
                            {form.errors.featured_image && (
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {form.errors.featured_image}
                                </p>
                            )}
                        </div>

                        {/* Content */}
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300">
                                Content
                            </label>
                            <HTMLEditor
                                modelValue={form.content}
                                onUpdate:modelValue={onContentUpdate}
                                error={form.errors.content}
                                placeholder="Write your article content here..."
                            />
                            {form.errors.content && (
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {form.errors.content}
                                </p>
                            )}
                        </div>

                        {/* SEO tags */}
                        <div>
                            <label
                                for="seo_tags"
                                class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                SEO tags
                            </label>
                            <input
                                value={form.seo_tags}
                                onInput={onSeoTagsInput}
                                type="text"
                                id="seo_tags"
                                placeholder="e.g. admission, news, tips"
                                class={[
                                    'h-9 w-full rounded-xl border bg-white px-3 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400',
                                    form.errors.seo_tags
                                        ? 'border-rose-300 focus:border-rose-500 dark:border-rose-500/40'
                                        : 'border-slate-200 dark:border-gray-700',
                                ]}
                            />
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-gray-500">
                                Comma-separated keywords to help readers find
                                this post.
                            </p>
                            {form.errors.seo_tags && (
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {form.errors.seo_tags}
                                </p>
                            )}
                        </div>

                        {/* Publish */}
                        <section class="space-y-3 border-t border-slate-100 pt-5 dark:border-gray-800">
                            <div>
                                <h2 class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                    Publish
                                </h2>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                    Control visibility and featuring for this
                                    post.
                                </p>
                            </div>

                            <div class="divide-y divide-slate-100 dark:divide-gray-800">
                                <div class="flex items-center justify-between gap-4 py-3">
                                    <div>
                                        <p class="text-xs font-semibold text-slate-800 dark:text-gray-200">
                                            Publish immediately
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500">
                                            Make this post visible right away.
                                        </p>
                                    </div>
                                    <input
                                        checked={form.is_published}
                                        onChange={onIsPublishedChange}
                                        type="checkbox"
                                        class="h-4 w-4 shrink-0 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 dark:border-gray-600"
                                    />
                                </div>
                                <div class="flex items-center justify-between gap-4 py-3">
                                    <div>
                                        <p class="text-xs font-semibold text-slate-800 dark:text-gray-200">
                                            Feature post
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500">
                                            Pin to the top of the blog home
                                            screen.
                                        </p>
                                    </div>
                                    <input
                                        checked={form.is_featured}
                                        onChange={onIsFeaturedChange}
                                        type="checkbox"
                                        class="h-4 w-4 shrink-0 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 dark:border-gray-600"
                                    />
                                </div>
                            </div>
                        </section>

                        <div class="flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end dark:border-gray-800">
                            <Link
                                href="/admin/blogs"
                                class="inline-flex h-9 w-full cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 sm:w-auto dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={form.processing}
                                class="inline-flex h-9 w-full cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                {saveButtonContent()}
                            </button>
                        </div>
                    </form>
                </div>
            </>
        );
    },
});
