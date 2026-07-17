<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
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
        class="group relative flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-white p-3.5 transition-colors duration-150 hover:border-blue-200"
    >
        <Link
            :href="`/admin/blogs/edit/${blog.slug}`"
            class="flex flex-1 items-center gap-3 min-w-0 active:scale-[0.99]"
        >
            <div
                class="h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-gray-100 bg-gray-50"
            >
                <img
                    v-if="blog.featured_image"
                    :src="blog.featured_image"
                    alt=""
                    class="h-full w-full object-cover"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center text-xs font-bold text-gray-400 uppercase"
                >
                    TXT
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <h4 class="truncate text-sm font-semibold text-gray-900">
                    {{ blog.title }}
                </h4>

                <div
                    class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500"
                >
                    <span>{{ blog.views }} views</span>
                    <span>•</span>
                    <span
                        :class="
                            blog.is_published ? 'text-green-600' : 'text-gray-400'
                        "
                    >
                        {{ blog.is_published ? 'Published' : 'Draft' }}
                    </span>
                    <span
                        v-if="blog.is_featured"
                        class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-amber-700 uppercase"
                    >
                        Featured
                    </span>
                </div>
            </div>
        </Link>

        <div class="flex items-center gap-2 pl-1 shrink-0">
            <button
                @click.stop.prevent="deleteBlog"
                type="button"
                class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                title="Delete blog"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
            </button>

            <span class="text-lg text-gray-300">→</span>
        </div>
    </div>
</template>
