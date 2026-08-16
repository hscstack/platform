<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { kCard } from 'konsta/vue';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    blog: Object,
});

const formattedDate = computed(() => {
    if (!props.blog.created_at) {
        return '';
    }

    const date = new Date(props.blog.created_at);

    if (isNaN(date.getTime())) {
        return '';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
});
</script>

<template>
    <kCard outline raised>
        <template #header>
            <Link
                :href="'/blogs/' + blog.slug"
                class="relative block aspect-[16/9] overflow-hidden"
            >
                <img
                    :src="blog.featured_image || 'https://placehold.co/600x400'"
                    :alt="blog.title"
                    class="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
                    loading="lazy"
                />
                <div
                    v-if="blog.category"
                    class="absolute top-2.5 left-2.5 rounded-md bg-indigo-600 px-2 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase shadow-xs"
                >
                    {{ blog.category }}
                </div>
            </Link>
        </template>

        <div class="flex flex-1 flex-col p-4">
            <div
                class="mb-1.5 flex items-center gap-1.5 text-xs text-slate-500 dark:text-gray-400"
            >
                <span class="text-slate-400 dark:text-gray-500">Author</span>
                <Link
                    :href="`/about-us#${blog.user?.id}`"
                    class="font-medium text-indigo-600 underline transition-colors hover:underline dark:text-indigo-400"
                >
                    {{ blog.user?.name }}
                </Link>

                <span
                    v-if="formattedDate"
                    class="text-slate-300 dark:text-gray-600"
                    >•</span
                >

                <time
                    v-if="formattedDate"
                    :datetime="blog.created_at"
                    class="text-slate-400 dark:text-gray-500"
                >
                    {{ formattedDate }}
                </time>
            </div>

            <Link :href="'/blogs/' + blog.slug" class="group/title block">
                <h3
                    class="line-clamp-2 text-base leading-snug font-bold text-slate-900 transition duration-150 group-hover/title:text-indigo-600 dark:text-gray-100 dark:group-hover/title:text-indigo-400"
                >
                    {{ blog.title }}
                </h3>
            </Link>

            <p
                class="mt-1.5 mb-3 line-clamp-2 text-xs leading-relaxed text-slate-600 dark:text-gray-400"
            >
                {{ blog.excerpt }}
            </p>
        </div>

        <template #footer>
            <div
                class="mt-auto border-t border-slate-100 pt-3 dark:border-gray-800"
            >
                <Link
                    :href="'/blogs/' + blog.slug"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    <span>বিস্তারিত পড়ুন</span>
                    <ArrowRight
                        class="h-3.5 w-3.5 transform transition-transform duration-200 group-hover:translate-x-1"
                    />
                </Link>
            </div>
        </template>
    </kCard>
</template>
