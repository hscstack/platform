<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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
    <div
        class="group border-slate-150 flex flex-col overflow-hidden rounded-2xl border bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md"
    >
        <Link
            :href="'/blogs/' + blog.slug"
            class="relative block aspect-video overflow-hidden bg-slate-100"
        >
            <img
                :src="blog.featured_image || 'https://placehold.co/600x400'"
                :alt="blog.title"
                class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                loading="lazy"
            />
            <div
                v-if="blog.category"
                class="absolute top-3 left-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-extrabold tracking-wider text-slate-900 uppercase shadow-sm backdrop-blur-md"
            >
                {{ blog.category }}
            </div>
        </Link>

        <div class="flex flex-1 flex-col p-6">
            <div class="mb-3 flex items-center gap-2 text-sm text-slate-500">
                <span>Author : </span>

                <Link
                    :href="`/about-us#${blog.user?.id}`"
                    class="font-medium text-indigo-600 transition-colors hover:text-indigo-800 hover:underline"
                >
                    {{ blog.user?.name }}
                </Link>

                <span
                    v-if="formattedDate"
                    class="h-1 w-1 rounded-full bg-slate-300"
                ></span>

                <time
                    v-if="formattedDate"
                    :datetime="blog.created_at"
                    class="text-slate-400"
                >
                    {{ formattedDate }}
                </time>
            </div>

            <Link :href="'/blogs/' + blog.slug" class="group/title block">
                <h3
                    class="line-clamp-2 text-xl leading-snug font-bold text-slate-900 transition duration-150 group-hover/title:text-indigo-600"
                >
                    {{ blog.title }}
                </h3>
            </Link>

            <p
                class="mt-3 mb-6 line-clamp-3 text-sm leading-relaxed text-slate-500"
            >
                {{ blog.excerpt }}
            </p>

            <div class="mt-auto border-t border-slate-50 pt-4">
                <Link
                    :href="'/blogs/' + blog.slug"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 transition hover:text-indigo-700"
                >
                    <span>বিস্তারিত পড়ুন </span>
                    <ArrowRight
                        class="h-4 w-4 transform transition-transform duration-200 group-hover:translate-x-1"
                    />
                </Link>
            </div>
        </div>
    </div>
</template>
