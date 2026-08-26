<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2, Eye, BookOpen } from 'lucide-vue-next';

const props = defineProps({
    blog: {
        type: Object,
        required: true,
    },
});

const deleteBlog = () => {
    if (confirm('Are you sure you want to delete this blog?')) {
        router.delete(`/admin/blogs/${props.blog.slug}`);
    }
};
</script>

<template>
    <div
        @click="router.visit(`/blogs/${blog.slug}`)"
        class="group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white p-3 transition-colors duration-150 hover:border-indigo-200 hover:bg-slate-50/50 sm:p-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/30 dark:hover:bg-gray-800/40"
    >
        <div class="flex items-center gap-3 min-w-0 flex-1">
            <!-- Thumbnail / Icon -->
            <div
                class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-black/5 bg-slate-100 dark:border-white/10 dark:bg-gray-800"
            >
                <img
                    v-if="blog.featured_image"
                    :src="blog.featured_image"
                    :alt="blog.title"
                    class="h-full w-full object-cover"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center text-slate-400 dark:text-gray-500"
                >
                    <BookOpen class="h-4.5 w-4.5 stroke-[2]" />
                </div>
            </div>

            <!-- Title & Metadata -->
            <div class="flex flex-col min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h4
                        class="text-sm font-semibold text-slate-900 break-words transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                    >
                        {{ blog.title }}
                    </h4>

                    <span
                        :class="[
                            blog.is_published
                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/30'
                                : 'bg-slate-100 text-slate-600 ring-slate-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-500/20',
                            'inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset',
                        ]"
                    >
                        {{ blog.is_published ? 'Published' : 'Draft' }}
                    </span>

                    <span
                        v-if="blog.is_featured"
                        class="inline-flex items-center rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 uppercase ring-1 ring-amber-600/20 ring-inset dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30"
                    >
                        Featured
                    </span>
                </div>

                <div class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-400 dark:text-gray-500">
                    <Eye class="h-3.5 w-3.5" />
                    <span>{{ blog.views || 0 }} views</span>
                </div>
            </div>
        </div>

        <!-- Right: Actions -->
        <div class="flex shrink-0 items-center gap-1" @click.stop>
            <Link
                :href="`/admin/blogs/edit/${blog.slug}`"
                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                title="Edit blog"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.8" />
            </Link>

            <button
                @click="deleteBlog"
                type="button"
                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                title="Delete blog"
            >
                <Trash2 class="h-4 w-4" :stroke-width="1.8" />
            </button>
        </div>
    </div>
</template>
