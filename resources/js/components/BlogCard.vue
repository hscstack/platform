<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Heart, MessageSquare, BadgeCheck } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    blog: Object,
});

const formattedDate = computed(() => {
    if (!props.blog?.created_at) {
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
        class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:shadow-gray-900/50"
    >
        <!-- Featured Image Container -->
        <Link
            :href="'/blogs/' + blog.slug"
            class="relative block aspect-[16/9] overflow-hidden bg-slate-100 dark:bg-gray-800"
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

        <!-- Card Body -->
        <div class="flex flex-1 flex-col p-4">
            <!-- News Meta (Author & Date) -->
            <div
                class="mb-1.5 flex items-center gap-1.5 text-xs text-slate-500 dark:text-gray-400"
            >
                <span class="text-slate-400 dark:text-gray-500">Author</span>
                <Link
                    v-if="blog.user?.username"
                    :href="`/u/${blog.user.username}`"
                    class="inline-flex items-center gap-1 font-medium text-indigo-600 underline transition-colors hover:underline dark:text-indigo-400"
                >
                    <span>{{ blog.user?.name }}</span>
                    <BadgeCheck
                        v-if="blog.user?.roles && blog.user.roles.length > 0"
                        class="h-3.5 w-3.5 fill-blue-50 stroke-[2.2] text-blue-600 dark:fill-blue-950/60 dark:text-blue-400"
                        title="Verified Contributor"
                    />
                </Link>
                <span
                    v-else
                    class="inline-flex items-center gap-1 font-medium text-slate-700 dark:text-gray-300"
                >
                    <span>{{ blog.user?.name }}</span>
                    <BadgeCheck
                        v-if="blog.user?.roles && blog.user.roles.length > 0"
                        class="h-3.5 w-3.5 fill-blue-50 stroke-[2.2] text-blue-600 dark:fill-blue-950/60 dark:text-blue-400"
                        title="Verified Contributor"
                    />
                </span>

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

            <!-- Title -->
            <Link :href="'/blogs/' + blog.slug" class="group/title block">
                <h3
                    class="line-clamp-2 text-base leading-snug font-bold text-slate-900 transition duration-150 group-hover/title:text-indigo-600 dark:text-gray-100 dark:group-hover/title:text-indigo-400"
                >
                    {{ blog.title }}
                </h3>
            </Link>

            <!-- Excerpt -->
            <p
                class="mt-1.5 mb-3 line-clamp-2 text-xs leading-relaxed text-slate-600 dark:text-gray-400"
            >
                {{ blog.excerpt }}
            </p>

            <!-- Footer Action & Interaction Stats -->
            <div
                class="mt-auto flex items-center justify-between border-t border-slate-100 pt-3 dark:border-gray-800"
            >
                <div
                    class="flex items-center gap-3 text-xs text-slate-400 dark:text-gray-500"
                >
                    <span
                        class="inline-flex items-center gap-1"
                        :class="{
                            'font-medium text-rose-500 dark:text-rose-400':
                                blog.reactions_count > 0,
                        }"
                    >
                        <Heart
                            class="h-3.5 w-3.5"
                            :class="
                                blog.reactions_count > 0
                                    ? 'fill-rose-500 text-rose-500'
                                    : 'text-slate-400 dark:text-gray-500'
                            "
                        />
                        <span>{{ blog.reactions_count || 0 }}</span>
                    </span>

                    <span class="inline-flex items-center gap-1">
                        <MessageSquare class="h-3.5 w-3.5" />
                        <span>{{ blog.comments_count || 0 }}</span>
                    </span>
                </div>

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
        </div>
    </div>
</template>
