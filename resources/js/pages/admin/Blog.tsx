import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Eye, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { defineComponent } from 'vue';
import type { PropType } from 'vue';

import EmptyState from '@/components/EmptyState.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatDate } from '@/lib/useDate';
import { usePermissions } from '@/lib/usePermissions';

interface Blog {
    id: number;
    slug: string;
    title: string;
    excerpt?: string | null;
    content?: string | null;
    featured_image?: string | null;
    featured_image_path?: string | null;
    is_published?: boolean;
    is_featured?: boolean;
    seo_tags?: string | null;
    views?: number | null;
    created_at?: string | null;
}

export default defineComponent({
    name: 'AdminBlog',
    props: {
        blogs: { type: Array as PropType<Blog[]>, required: true },
    },
    setup(props) {
        const { can } = usePermissions();

        const deleteBlog = (blog: Blog): void => {
            if (confirm('Are you sure you want to delete this blog?')) {
                router.delete(`/admin/blogs/${blog.slug}`, {
                    preserveScroll: true,
                });
            }
        };

        const canEdit = (blog: Blog): boolean => {
            void blog;

            return can('edit blogs');
        };

        return () => (
            <>
                <Head title="Manage Blogs" />

                <div class="space-y-6">
                    {/* Page header */}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                    Manage Blogs
                                </h1>
                                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400">
                                    {props.blogs.length}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                Publish articles, feature top stories, and keep
                                content up to date.
                            </p>
                        </div>

                        {can('create blogs') && (
                            <div class="flex shrink-0 items-center gap-2">
                                <Link
                                    href="/admin/blogs/create"
                                    class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                >
                                    <Plus
                                        class="h-3.5 w-3.5"
                                        strokeWidth={2.2}
                                    />
                                    <span>New Post</span>
                                </Link>
                            </div>
                        )}
                    </div>

                    <div>
                        {props.blogs.length > 0 ? (
                            <div class="divide-y divide-slate-100 dark:divide-gray-800">
                                {props.blogs.map((blog) => (
                                    <div
                                        key={blog.id || blog.slug}
                                        class="flex items-center gap-3 py-3 first:pt-0 last:pb-0"
                                    >
                                        {/* Cover thumb */}
                                        <Link
                                            href={`/blogs/${blog.slug}`}
                                            class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100 dark:bg-gray-800"
                                        >
                                            {blog.featured_image ? (
                                                <img
                                                    src={blog.featured_image}
                                                    alt={blog.title}
                                                    class="h-full w-full object-cover"
                                                />
                                            ) : (
                                                <BookOpen class="h-4 w-4 text-slate-400 dark:text-gray-500" />
                                            )}
                                        </Link>

                                        {/* Title + excerpt + meta */}
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                <Link
                                                    href={`/blogs/${blog.slug}`}
                                                    class="max-w-full min-w-0 truncate text-sm font-semibold text-slate-900 hover:text-indigo-600 hover:underline dark:text-gray-100 dark:hover:text-indigo-400"
                                                >
                                                    {blog.title}
                                                </Link>
                                                <StatusBadge
                                                    status={
                                                        blog.is_published
                                                            ? 'published'
                                                            : 'draft'
                                                    }
                                                    size="xs"
                                                    showIcon={false}
                                                />
                                                {blog.is_featured && (
                                                    <StatusBadge
                                                        status="featured"
                                                        size="xs"
                                                        showIcon={false}
                                                    />
                                                )}
                                            </div>
                                            {blog.excerpt && (
                                                <p class="mt-0.5 line-clamp-1 text-xs text-slate-500 dark:text-gray-400">
                                                    {blog.excerpt}
                                                </p>
                                            )}
                                            <p class="mt-0.5 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                                <span class="inline-flex items-center gap-1">
                                                    <Eye class="h-3 w-3" />
                                                    {blog.views || 0} views
                                                </span>
                                                {blog.created_at && (
                                                    <>
                                                        <span aria-hidden="true">
                                                            ·
                                                        </span>
                                                        <span>
                                                            {formatDate(
                                                                blog.created_at,
                                                            )}
                                                        </span>
                                                    </>
                                                )}
                                                <span
                                                    aria-hidden="true"
                                                    class="hidden sm:inline"
                                                >
                                                    ·
                                                </span>
                                                <span class="hidden truncate sm:inline">
                                                    /blogs/{blog.slug}
                                                </span>
                                            </p>
                                        </div>

                                        {/* Status/date + ghost actions */}
                                        <div class="flex shrink-0 items-center gap-0.5">
                                            {canEdit(blog) && (
                                                <Link
                                                    href={`/admin/blogs/edit/${blog.slug}`}
                                                    class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                                                    title="Edit blog"
                                                    aria-label="Edit blog"
                                                >
                                                    <Pencil
                                                        class="h-4 w-4"
                                                        strokeWidth={1.8}
                                                    />
                                                </Link>
                                            )}
                                            {can('delete blogs') && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        deleteBlog(blog)
                                                    }
                                                    class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                                    title="Delete blog"
                                                    aria-label="Delete blog"
                                                >
                                                    <Trash2
                                                        class="h-4 w-4"
                                                        strokeWidth={1.8}
                                                    />
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyState
                                icon={BookOpen}
                                title="No blogs found"
                                description="No blog posts have been published yet."
                                showCta={false}
                            >
                                {{
                                    default: () =>
                                        can('create blogs') ? (
                                            <Link
                                                href="/admin/blogs/create"
                                                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                            >
                                                <Plus class="h-3.5 w-3.5" />
                                                <span>New Post</span>
                                            </Link>
                                        ) : null,
                                }}
                            </EmptyState>
                        )}
                    </div>
                </div>
            </>
        );
    },
});
