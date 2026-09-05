<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { CheckCircle2, ImageIcon, MessageSquare } from 'lucide-vue-next';
import ForumVoteButtons from '@/components/forum/ForumVoteButtons.vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';
import { formatTimeAgo } from '@/lib/useDate';

interface User {
    id: number;
    name: string;
    username: string;
    image_path?: string | null;
    image_url?: string | null;
    institution?: string | null;
    is_verified?: boolean;
}

interface NodeItem {
    id: number;
    subject_id?: number;
    name: string;
    slug: string;
}

interface Subject {
    id: number;
    name: string;
    course: 'hsc' | 'ssc';
    slug: string;
}

interface ForumPost {
    id: number;
    user_id?: number;
    subject_id?: number | null;
    node_id?: number | null;
    curriculum: 'hsc' | 'ssc';
    title: string;
    slug: string;
    body?: string | null;
    image_path?: string | null;
    image_url?: string | null;
    is_answered?: boolean;
    vote_score: number;
    upvotes_count?: number;
    downvotes_count?: number;
    answers_count: number;
    created_at: string;
    user?: User;
    subject?: Subject | null;
    node?: NodeItem | null;
    user_vote?: number | null;
}

defineProps<{
    post: ForumPost;
}>();
</script>

<template>
    <article
        class="group cursor-pointer rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs transition duration-150 hover:border-slate-300 hover:shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
        @click="router.visit('/forum/questions/' + post.slug)"
    >
        <div class="flex items-start gap-3 sm:gap-4">
            <!-- Left: Vote Controls (Vertical on all screens) -->
            <div class="shrink-0 pt-0.5" @click.stop>
                <ForumVoteButtons
                    votableType="post"
                    :votableId="post.id"
                    :initialUpvotes="post.upvotes_count || 0"
                    :initialDownvotes="post.downvotes_count || 0"
                    :initialUserVote="post.user_vote"
                    direction="vertical"
                    size="sm"
                />
            </div>

            <!-- Right: Content Body -->
            <div class="min-w-0 flex-1">
                <!-- Badges Row -->
                <div
                    class="mb-1.5 flex flex-wrap items-center gap-1.5 text-[11px]"
                >
                    <!-- Curriculum Badge -->
                    <span
                        class="rounded-md px-1.5 py-0.5 font-bold uppercase"
                        :class="[
                            post.curriculum === 'ssc'
                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400'
                                : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400',
                        ]"
                    >
                        {{ post.curriculum }}
                    </span>

                    <!-- Subject Badge -->
                    <span
                        v-if="post.subject"
                        class="rounded-md bg-slate-100 px-1.5 py-0.5 font-medium text-slate-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        {{ post.subject.name }}
                    </span>
                    <span
                        v-else
                        class="rounded-md bg-slate-100 px-1.5 py-0.5 font-medium text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                    >
                        Other / General
                    </span>

                    <!-- Chapter Badge -->
                    <span
                        v-if="post.node"
                        class="rounded-md bg-slate-100 px-1.5 py-0.5 font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-400"
                    >
                        # {{ post.node.name }}
                    </span>

                    <!-- Answered Badge -->
                    <span
                        v-if="post.is_answered"
                        class="inline-flex items-center gap-1 rounded-md bg-emerald-100/70 px-1.5 py-0.5 font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        <CheckCircle2
                            class="h-3 w-3 text-emerald-600 dark:text-emerald-400"
                        />
                        <span>Answered</span>
                    </span>
                </div>

                <!-- Question Title -->
                <h2
                    class="text-base font-bold text-slate-900 transition sm:text-lg dark:text-gray-100"
                >
                    <Link
                        :href="`/forum/questions/${post.slug}`"
                        class="hover:text-indigo-600 dark:hover:text-indigo-400"
                    >
                        {{ post.title }}
                    </Link>
                </h2>

                <!-- Preview snippet -->
                <p
                    v-if="post.body"
                    class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-gray-300"
                >
                    {{ post.body }}
                </p>

                <!-- Attached Image Indicator (if any) -->
                <div
                    v-if="post.image_url"
                    class="mt-2 inline-flex items-center gap-1 text-xs text-slate-400 dark:text-gray-500"
                >
                    <ImageIcon class="h-3.5 w-3.5" />
                    <span>Image attached</span>
                </div>

                <!-- Footer Author & Stats Metadata -->
                <div
                    class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-2.5 text-xs text-slate-400 dark:border-gray-800/80 dark:text-gray-500"
                >
                    <!-- Author info -->
                    <div class="flex items-center gap-1.5">
                        <Link
                            v-if="post.user?.username"
                            :href="`/u/${post.user.username}`"
                            @click.stop
                            class="inline-flex items-center gap-1.5 font-medium text-slate-700 hover:text-indigo-600 hover:underline dark:text-gray-300 dark:hover:text-indigo-400"
                        >
                            <div
                                class="flex h-5 w-5 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-[10px] font-bold text-slate-700 dark:bg-gray-700 dark:text-gray-300"
                            >
                                <img
                                    v-if="post.user.image_url"
                                    :src="post.user.image_url"
                                    :alt="post.user.name"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{
                                    post.user.name.charAt(0)
                                }}</span>
                            </div>
                            <span>{{ post.user.name }}</span>
                            <VerifiedBadge v-if="post.user.is_verified" />
                        </Link>
                        <span
                            v-else
                            class="font-medium text-slate-700 dark:text-gray-300"
                        >
                            {{ post.user?.name || 'Anonymous' }}
                        </span>

                        <span
                            v-if="post.user?.institution"
                            class="hidden text-slate-400 sm:inline dark:text-gray-500"
                        >
                            ({{ post.user.institution }})
                        </span>

                        <span>•</span>
                        <span>{{ formatTimeAgo(post.created_at) }}</span>
                    </div>

                    <!-- Answer count badge -->
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center gap-1 font-medium"
                            :class="
                                post.answers_count > 0
                                    ? 'text-indigo-600 dark:text-indigo-400'
                                    : 'text-slate-400 dark:text-gray-500'
                            "
                        >
                            <MessageSquare class="h-3.5 w-3.5" />
                            <span
                                >{{ post.answers_count }}
                                {{
                                    post.answers_count === 1
                                        ? 'answer'
                                        : 'answers'
                                }}</span
                            >
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>
