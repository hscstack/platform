<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Heart, MessageSquare } from 'lucide-vue-next';

defineProps({
    blog: Object,
});
</script>

<template>
    <Link
        :href="'/blogs/' + blog.slug"
        class="group relative flex flex-row items-center overflow-hidden rounded-xl border border-slate-200 bg-white p-3 shadow-xs transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md sm:flex-col sm:p-0 dark:border-gray-700 dark:bg-gray-900 dark:hover:shadow-gray-900/50"
    >
        <!-- Featured Image Container -->
        <div
            class="relative block h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-slate-100 sm:aspect-[16/9] sm:h-auto sm:w-full sm:rounded-none dark:bg-gray-800"
        >
            <img
                :src="blog.featured_image || 'https://placehold.co/600x400'"
                :alt="blog.title"
                class="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
                loading="lazy"
            />
            <div
                v-if="blog.category"
                class="absolute top-2.5 left-2.5 hidden rounded-md bg-indigo-600 px-2 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase shadow-xs sm:block"
            >
                {{ blog.category }}
            </div>
        </div>

        <!-- Card Body -->
        <div class="flex min-w-0 flex-1 flex-col justify-between pl-3 sm:p-4">
            <div>
                <!-- Top Row: Category / Author -->
                <div
                    class="mb-1 flex items-center gap-1.5 text-[11px] sm:mb-1.5 sm:text-xs"
                >
                    <span
                        v-if="blog.category"
                        class="rounded bg-indigo-50 px-1.5 py-0.5 text-[9px] font-bold text-indigo-600 sm:hidden dark:bg-indigo-950/60 dark:text-indigo-400"
                    >
                        {{ blog.category }}
                    </span>

                    <span class="text-slate-400 dark:text-gray-500">By</span>
                    <Link
                        v-if="blog.user?.username"
                        :href="`/u/${blog.user.username}`"
                        @click.stop
                        class="relative z-10 truncate font-semibold text-slate-700 transition-colors hover:text-indigo-600 hover:underline dark:text-gray-300 dark:hover:text-indigo-400"
                    >
                        {{ blog.user?.name }}
                    </Link>
                    <span
                        v-else
                        class="truncate font-semibold text-slate-700 dark:text-gray-300"
                    >
                        {{ blog.user?.name }}
                    </span>
                </div>

                <!-- Title -->
                <h3
                    class="line-clamp-2 text-xs leading-snug font-bold text-slate-900 transition duration-150 group-hover:text-indigo-600 sm:text-base dark:text-gray-100 dark:group-hover:text-indigo-400"
                >
                    {{ blog.title }}
                </h3>

                <!-- Excerpt (hidden on mobile to save vertical space) -->
                <p
                    class="mt-1.5 mb-3 line-clamp-2 hidden text-xs leading-relaxed text-slate-600 sm:block dark:text-gray-400"
                >
                    {{ blog.excerpt }}
                </p>
            </div>

            <!-- Footer Action & Interaction Stats -->
            <div
                class="mt-1 flex items-center justify-between pt-1 sm:mt-auto sm:border-t sm:border-slate-100 sm:pt-3 dark:sm:border-gray-800"
            >
                <div
                    class="flex items-center gap-3 text-[11px] text-slate-400 sm:text-xs dark:text-gray-500"
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

                <!-- Bottom-Right Arrow Indicator -->
                <div
                    class="flex h-5 w-5 shrink-0 items-center justify-center text-slate-400 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"
                >
                    <ArrowRight class="h-3.5 w-3.5" />
                </div>
            </div>
        </div>
    </Link>
</template>
