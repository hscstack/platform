<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Check, Loader2, MessageSquare, Share2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

import ForumVoteButtons from '@/components/forum/ForumVoteButtons.vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';
import { useAppreciation } from '@/lib/useAppreciation';
import { useAuth } from '@/lib/useAuth';
import { getCsrfToken } from '@/lib/useCsrf';
import { formatTimeAgo } from '@/lib/useDate';

interface User {
    id: number;
    name: string;
    username: string;
    image_path?: string | null;
    image_url?: string | null;
    institution?: string | null;
    is_verified?: boolean;
    is_appreciated?: boolean;
}

interface NodeItem {
    id: number;
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
    slug: string;
    body: string;
    image_path?: string | null;
    image_url?: string | null;
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

const props = defineProps<{
    post: ForumPost;
}>();

const { user: currentUser, requireAuth } = useAuth();
const { isAppreciated, setAppreciation, initAppreciation } = useAppreciation();

// Initialize shared appreciation cache
if (props.post.user) {
    initAppreciation(props.post.user.id, props.post.user.is_appreciated);
}

const isTogglingAppreciation = ref(false);

watch(
    () => props.post.user?.is_appreciated,
    (val) => {
        if (props.post.user && val !== undefined) {
            setAppreciation(props.post.user.id, Boolean(val));
        }
    },
);

const toggleAppreciation = () => {
    if (!props.post.user || props.post.user.id === currentUser.value?.id) {
        return;
    }

    requireAuth(
        'সদস্যকে প্রশংসা (Appreciate) করতে অনুগ্রহ করে লগইন করুন।',
        async () => {
            if (isTogglingAppreciation.value) {
                return;
            }

            isTogglingAppreciation.value = true;

            const targetUser = props.post.user!;
            const previousState = isAppreciated(
                targetUser.id,
                targetUser.is_appreciated,
            );
            setAppreciation(targetUser.id, !previousState);

            try {
                await router.post(
                    `/u/${targetUser.id}/appreciate`,
                    {},
                    {
                        preserveScroll: true,
                        preserveState: true,
                        only: ['auth', 'flash'],
                        onError: () => {
                            setAppreciation(targetUser.id, previousState);
                        },
                        onFinish: () => {
                            isTogglingAppreciation.value = false;
                        },
                    },
                );
            } catch {
                setAppreciation(targetUser.id, previousState);
                isTogglingAppreciation.value = false;
            }
        },
    );
};

const copied = ref(false);
const isSharing = ref(false);

const sharePost = async () => {
    if (isSharing.value) {
        return;
    }

    isSharing.value = true;

    const originalUrl = `${window.location.origin}/forum/questions/${props.post.slug}`;
    let finalUrl = originalUrl;

    try {
        const res = await fetch('/api/short-urls', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                original_url: originalUrl,
            }),
        });

        if (res.ok) {
            const data = await res.json();

            if (data.short_url) {
                finalUrl = data.short_url;
            }
        }
    } catch {
        // Fallback to original URL
    } finally {
        isSharing.value = false;
    }

    if (navigator.share) {
        try {
            await navigator.share({
                title: props.post.body.slice(0, 60),
                url: finalUrl,
            });
            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 2500);

            return;
        } catch {
            // fallback
        }
    }

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(finalUrl);
        } else {
            throw new Error('Clipboard API unavailable');
        }

        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2500);
    } catch {
        try {
            const textarea = document.createElement('textarea');
            textarea.value = finalUrl;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 2500);
        } catch {
            // silent fail
        }
    }
};
</script>

<template>
    <article
        class="group relative cursor-pointer rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs transition-all duration-150 hover:border-slate-300 hover:shadow-xs sm:p-5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
        @click="router.visit('/forum/questions/' + post.slug)"
    >
        <!-- 1. Header: Author Meta (Left) & Single Subject Tag (Right) -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2.5" @click.stop>
                <!-- Avatar -->
                <Link
                    v-if="post.user"
                    :href="`/u/${post.user.username}`"
                    class="relative shrink-0"
                >
                    <img
                        v-if="post.user.image_url || post.user.image_path"
                        :src="
                            post.user.image_url ||
                            '/storage/' + post.user.image_path
                        "
                        :alt="post.user.name"
                        class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200 dark:ring-gray-800"
                    />
                    <div
                        v-else
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-purple-50 text-xs font-bold text-purple-700 dark:bg-purple-950/60 dark:text-purple-300"
                    >
                        {{ post.user.name?.charAt(0) || 'U' }}
                    </div>
                </Link>
                <div
                    v-else
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                >
                    ?
                </div>

                <!-- Name & Time ago -->
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <Link
                            v-if="post.user"
                            :href="`/u/${post.user.username}`"
                            class="truncate text-sm font-bold text-slate-900 transition hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                        >
                            {{ post.user.name }}
                        </Link>
                        <span
                            v-else
                            class="text-sm font-bold text-slate-700 dark:text-gray-300"
                        >
                            Anonymous
                        </span>

                        <VerifiedBadge
                            v-if="post.user?.is_verified"
                            size="sm"
                        />

                        <span
                            v-if="
                                post.user &&
                                (!currentUser ||
                                    currentUser.id !== post.user.id)
                            "
                            class="text-slate-300 dark:text-gray-700"
                            >•</span
                        >

                        <!-- Text-only Appreciate Button (no border) -->
                        <button
                            v-if="
                                post.user &&
                                (!currentUser ||
                                    currentUser.id !== post.user.id)
                            "
                            type="button"
                            @click.stop.prevent="toggleAppreciation"
                            :disabled="isTogglingAppreciation"
                            class="cursor-pointer text-xs font-semibold transition-colors duration-150 select-none hover:underline disabled:opacity-50"
                            :class="[
                                isAppreciated(
                                    post.user.id,
                                    post.user.is_appreciated,
                                )
                                    ? 'font-bold text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300'
                                    : 'text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300',
                            ]"
                            :title="
                                isAppreciated(
                                    post.user.id,
                                    post.user.is_appreciated,
                                )
                                    ? 'প্রশংসা অপসারণ করুন'
                                    : 'সদস্যকে প্রশংসা করুন'
                            "
                        >
                            {{
                                isAppreciated(
                                    post.user.id,
                                    post.user.is_appreciated,
                                )
                                    ? 'Appreciating'
                                    : 'Appreciate'
                            }}
                        </button>
                    </div>

                    <div class="text-[11px] text-slate-400 dark:text-gray-500">
                        {{ formatTimeAgo(post.created_at) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Body: Content with preserved newlines -->
        <div class="mt-3">
            <!-- Chapter & Subject Subtitle above question body -->
            <div
                v-if="post.node || post.subject"
                class="mb-1.5 flex items-center gap-1.5 truncate text-xs font-medium text-slate-500 dark:text-gray-400"
            >
                <span
                    v-if="post.node"
                    class="truncate font-medium text-slate-600 dark:text-gray-300"
                >
                    {{ post.node.name }}
                </span>
                <template v-if="post.node && post.subject">
                    <span class="text-slate-300 dark:text-gray-600">•</span>
                </template>
                <Link
                    v-if="post.subject"
                    :href="`/forum?subject_id=${post.subject.id}`"
                    @click.stop
                    class="shrink-0 transition hover:text-indigo-600 dark:hover:text-indigo-400"
                >
                    {{ post.subject.name }}
                </Link>
            </div>

            <p
                class="line-clamp-4 text-xs leading-relaxed font-normal whitespace-pre-line text-slate-700 sm:text-sm dark:text-gray-300"
            >
                {{ post.body }}
            </p>

            <!-- Optional Attached Image -->
            <div
                v-if="post.image_url || post.image_path"
                class="mt-3 max-h-80 overflow-hidden rounded-xl border border-slate-200/80 bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50"
            >
                <img
                    :src="post.image_url || '/storage/' + post.image_path"
                    :alt="post.body.slice(0, 50)"
                    loading="lazy"
                    class="max-h-80 w-full object-cover transition duration-300 group-hover:scale-101"
                />
            </div>
        </div>

        <!-- 3. Footer Action Bar -->
        <div
            class="mt-4 flex flex-wrap items-center justify-between gap-2.5 border-t border-slate-100 pt-3 dark:border-gray-800/80"
        >
            <div class="flex flex-wrap items-center gap-2" @click.stop>
                <ForumVoteButtons
                    votableType="post"
                    :votableId="post.id"
                    :initialUpvotes="post.upvotes_count || 0"
                    :initialDownvotes="post.downvotes_count || 0"
                    :initialUserVote="post.user_vote"
                    direction="horizontal"
                    size="sm"
                    variant="boxed"
                />

                <!-- Answer Count Button -->
                <Link
                    :href="`/forum/questions/${post.slug}#answers`"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/70 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    <MessageSquare
                        class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400"
                    />
                    <span>{{ post.answers_count || 0 }} answers</span>
                </Link>
            </div>

            <!-- Right: Share Button -->
            <div class="flex items-center gap-2" @click.stop>
                <button
                    type="button"
                    @click="sharePost"
                    :disabled="isSharing"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/70 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 disabled:cursor-wait disabled:opacity-75 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800"
                    title="Share question"
                >
                    <Loader2
                        v-if="isSharing"
                        class="h-3.5 w-3.5 animate-spin text-indigo-600 dark:text-indigo-400"
                    />
                    <Check
                        v-else-if="copied"
                        class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"
                    />
                    <Share2
                        v-else
                        class="h-3.5 w-3.5 text-slate-500 dark:text-gray-400"
                    />
                    <span>{{
                        isSharing ? 'Copying...' : copied ? 'Copied' : 'Share'
                    }}</span>
                </button>
            </div>
        </div>
    </article>
</template>
