import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Eye, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { defineComponent } from 'vue';
import type { PropType } from 'vue';

import {
    AdminPageHeader,
    adminDangerIconBtnClass,
    adminHoverListClass,
    adminIconBtnClass,
    adminPageClass,
    adminPrimaryBtnClass,
    adminRowClass,
} from '@/components/admin/ui';
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

                <div class={adminPageClass}>
                    {/* Page header */}
                    <AdminPageHeader
                        title="Manage Blogs"
                        count={props.blogs.length}
                        description="Publish articles, feature top stories, and keep content up to date."
                    >
                        {{
                            actions: () =>
                                can('create blogs') && (
                                    <Link
                                        href="/admin/blogs/create"
                                        class={adminPrimaryBtnClass}
                                    >
                                        <Plus
                                            class="h-3.5 w-3.5"
                                            strokeWidth={2.2}
                                        />
                                        <span>New Post</span>
                                    </Link>
                                ),
                        }}
                    </AdminPageHeader>

                    <div>
                        {props.blogs.length > 0 ? (
                            <div class={adminHoverListClass}>
                                {props.blogs.map((blog) => (
                                    <div
                                        key={blog.id || blog.slug}
                                        class={adminRowClass}
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
                                                    class={[
                                                        adminIconBtnClass,
                                                        'hover:text-indigo-600 dark:hover:text-indigo-400',
                                                    ]}
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
                                                    class={
                                                        adminDangerIconBtnClass
                                                    }
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
                                                class={adminPrimaryBtnClass}
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
