<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    Eye,
    Heart,
    MessageSquare,
} from 'lucide-vue-next';
import { formatTimeAgo } from '@/lib/useDate';

interface User {
    id?: number;
    name?: string;
    username?: string;
    image_url?: string | null;
}

interface Blog {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    category?: string | null;
    featured_image?: string | null;
    featured_image_path?: string | null;
    is_featured?: boolean;
    views?: number;
    reactions_count?: number;
    comments_count?: number;
    created_at?: string;
    user?: User;
}

defineProps<{
    blog: Blog;
}>();
</script>

<template>
    <Link
        :href="'/blogs/' + blog.slug"
        class="group relative flex touch-manipulation flex-row items-center overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-3 shadow-xs transition-all duration-200 hover:border-indigo-300/80 hover:shadow-sm active:scale-[0.99] sm:flex-col sm:items-stretch sm:p-0 sm:active:scale-100 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/40 dark:hover:shadow-indigo-500/5"
    >
        <!-- Featured Image Container -->
        <div
            class="relative h-20 w-24 shrink-0 overflow-hidden rounded-xl bg-slate-100 sm:aspect-[16/9] sm:h-auto sm:w-full sm:rounded-none dark:bg-gray-800"
        >
            <img
                v-if="blog.featured_image"
                :src="blog.featured_image"
                :alt="blog.title"
                class="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
                loading="lazy"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center bg-slate-100 text-slate-400 dark:bg-gray-800 dark:text-gray-500"
            >
                <BookOpen class="h-6 w-6 stroke-[1.8] opacity-60" />
            </div>

            <!-- Desktop Category Badge (on image) -->
            <div
                v-if="blog.category"
                class="absolute top-2.5 left-2.5 hidden rounded-md bg-indigo-600 px-2 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase shadow-xs sm:block"
            >
                {{ blog.category }}
            </div>
        </div>

        <!-- Card Body -->
        <div class="flex min-w-0 flex-1 flex-col justify-between pl-3 sm:p-4.5">
            <div>
                <!-- Top Row: Category / Author / Date -->
                <div
                    class="mb-1 flex items-center gap-1.5 text-[11px] sm:mb-1.5 sm:text-xs"
                >
                    <span
                        v-if="blog.category"
                        class="rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold text-indigo-600 sm:hidden dark:bg-indigo-950/60 dark:text-indigo-400"
                    >
                        {{ blog.category }}
                    </span>

                    <span
                        v-if="blog.user?.name"
                        class="truncate font-semibold text-slate-700 dark:text-gray-300"
                    >
                        {{ blog.user.name }}
                    </span>

                    <template v-if="blog.created_at">
                        <span class="text-slate-300 dark:text-gray-600">•</span>
                        <span
                            class="shrink-0 text-slate-400 dark:text-gray-500"
                        >
                            {{ formatTimeAgo(blog.created_at) }}
                        </span>
                    </template>
                </div>

                <!-- Title -->
                <h3
                    class="line-clamp-2 text-sm leading-snug font-bold text-slate-900 transition-colors duration-150 group-hover:text-indigo-600 sm:text-base dark:text-gray-100 dark:group-hover:text-indigo-400"
                >
                    {{ blog.title }}
                </h3>

                <!-- Excerpt (hidden on mobile to maintain compact height) -->
                <p
                    v-if="blog.excerpt"
                    class="mt-1.5 mb-3 line-clamp-2 hidden text-xs leading-relaxed text-slate-600 sm:block dark:text-gray-400"
                >
                    {{ blog.excerpt }}
                </p>
            </div>

            <!-- Footer Action & Interaction Stats -->
            <div
                class="mt-1.5 flex items-center justify-between pt-0.5 sm:mt-auto sm:border-t sm:border-slate-100 sm:pt-3 dark:sm:border-gray-800/80"
            >
                <div
                    class="flex items-center gap-3 text-[11px] text-slate-400 sm:text-xs dark:text-gray-500"
                >
                    <span class="inline-flex items-center gap-1">
                        <Heart class="h-3.5 w-3.5 stroke-[2]" />
                        <span>{{ blog.reactions_count || 0 }}</span>
                    </span>

                    <span class="inline-flex items-center gap-1">
                        <MessageSquare class="h-3.5 w-3.5 stroke-[2]" />
                        <span>{{ blog.comments_count || 0 }}</span>
                    </span>

                    <span
                        v-if="blog.views"
                        class="inline-flex items-center gap-1"
                    >
                        <Eye class="h-3.5 w-3.5 stroke-[2]" />
                        <span>{{ blog.views }}</span>
                    </span>
                </div>

                <!-- Arrow Indicator matching platform style -->
                <div
                    class="flex h-5 w-5 shrink-0 items-center justify-center text-slate-300 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-indigo-600 dark:text-gray-600 dark:group-hover:text-indigo-400"
                >
                    <ArrowRight class="h-3.5 w-3.5 stroke-[2]" />
                </div>
            </div>
        </div>
    </Link>
</template>
