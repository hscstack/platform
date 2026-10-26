<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Check, Heart, Loader2, MessageSquare, Share2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

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

interface Blog {
    id: number;
    user_id?: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    content?: string;
    featured_image?: string | null;
    featured_image_path?: string | null;
    views?: number;
    reactions_count?: number;
    comments_count?: number;
    is_reacted?: boolean;
    created_at?: string;
    user?: User;
}

const props = defineProps<{
    blog: Blog;
}>();

const { user: currentUser, requireAuth } = useAuth();
const { isAppreciated, setAppreciation, initAppreciation } = useAppreciation();

// Reaction state & optimistic updates
const isReacted = ref(Boolean(props.blog.is_reacted));
const reactionsCount = ref(props.blog.reactions_count || 0);
const isReacting = ref(false);

watch(
    () => props.blog.is_reacted,
    (val) => {
        isReacted.value = Boolean(val);
    },
);

watch(
    () => props.blog.reactions_count,
    (val) => {
        reactionsCount.value = val || 0;
    },
);

const toggleReaction = () => {
    requireAuth('ব্লগে লাভ রিঅ্যাক্ট দিতে অনুগ্রহ করে লগইন করুন।', async () => {
        if (isReacting.value) {
            return;
        }

        isReacting.value = true;

        const previousReacted = isReacted.value;
        const previousCount = reactionsCount.value;

        // Optimistic update
        isReacted.value = !previousReacted;
        reactionsCount.value = isReacted.value
            ? previousCount + 1
            : Math.max(0, previousCount - 1);

        try {
            await router.post(
                `/blogs/${props.blog.slug}/react`,
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    only: ['auth', 'flash'],
                    onError: () => {
                        isReacted.value = previousReacted;
                        reactionsCount.value = previousCount;
                    },
                    onFinish: () => {
                        isReacting.value = false;
                    },
                },
            );
        } catch {
            isReacted.value = previousReacted;
            reactionsCount.value = previousCount;
            isReacting.value = false;
        }
    });
};

// Initialize shared appreciation cache
if (props.blog.user) {
    initAppreciation(props.blog.user.id, props.blog.user.is_appreciated);
}

const isTogglingAppreciation = ref(false);

watch(
    () => props.blog.user?.is_appreciated,
    (val) => {
        if (props.blog.user && val !== undefined) {
            setAppreciation(props.blog.user.id, Boolean(val));
        }
    },
);

const toggleAppreciation = () => {
    if (!props.blog.user || props.blog.user.id === currentUser.value?.id) {
        return;
    }

    requireAuth(
        'সদস্যকে প্রশংসা (Appreciate) করতে অনুগ্রহ করে লগইন করুন।',
        async () => {
            if (isTogglingAppreciation.value) {
                return;
            }

            isTogglingAppreciation.value = true;

            const targetUser = props.blog.user!;
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

const shareBlog = async () => {
    if (isSharing.value) {
        return;
    }

    isSharing.value = true;

    const originalUrl = `${window.location.origin}/blogs/${props.blog.slug}`;
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
                title: props.blog.title,
                url: finalUrl,
            });
            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 2500);

            return;
        } catch {
            // fallback to clipboard
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
            // fallback failed
        }
    }
};
</script>

<template>
    <article
        class="group relative cursor-pointer rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs transition-all duration-150 hover:border-slate-300 hover:shadow-xs sm:p-5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700"
        @click="router.visit('/blogs/' + blog.slug)"
    >
        <!-- 1. Header: Author Meta (Left) & 'Blog' Pill (Right) -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2.5" @click.stop>
                <!-- Avatar -->
                <Link
                    v-if="blog.user"
                    :href="`/u/${blog.user.username}`"
                    class="relative shrink-0"
                >
                    <img
                        v-if="blog.user.image_url || blog.user.image_path"
                        :src="
                            blog.user.image_url ||
                            '/storage/' + blog.user.image_path
                        "
                        :alt="blog.user.name"
                        class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200 dark:ring-gray-800"
                    />
                    <div
                        v-else
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"
                    >
                        {{ blog.user.name?.charAt(0) || 'U' }}
                    </div>
                </Link>
                <div
                    v-else
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                >
                    ?
                </div>

                <!-- Name, Appreciate & Time ago -->
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <Link
                            v-if="blog.user"
                            :href="`/u/${blog.user.username}`"
                            class="truncate text-sm font-bold text-slate-900 transition hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                        >
                            {{ blog.user.name }}
                        </Link>
                        <span
                            v-else
                            class="text-sm font-bold text-slate-700 dark:text-gray-300"
                        >
                            Anonymous
                        </span>

                        <VerifiedBadge
                            v-if="blog.user?.is_verified"
                            size="sm"
                        />

                        <span
                            v-if="
                                blog.user &&
                                (!currentUser ||
                                    currentUser.id !== blog.user.id)
                            "
                            class="text-slate-300 dark:text-gray-700"
                            >•</span
                        >

                        <!-- Text-only Appreciate Button (no border) -->
                        <button
                            v-if="
                                blog.user &&
                                (!currentUser ||
                                    currentUser.id !== blog.user.id)
                            "
                            type="button"
                            @click.stop.prevent="toggleAppreciation"
                            :disabled="isTogglingAppreciation"
                            class="cursor-pointer text-xs font-semibold transition-colors duration-150 select-none hover:underline disabled:opacity-50"
                            :class="[
                                isAppreciated(
                                    blog.user.id,
                                    blog.user.is_appreciated,
                                )
                                    ? 'font-bold text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300'
                                    : 'text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300',
                            ]"
                            :title="
                                isAppreciated(
                                    blog.user.id,
                                    blog.user.is_appreciated,
                                )
                                    ? 'প্রশংসা অপসারণ করুন'
                                    : 'সদস্যকে প্রশংসা করুন'
                            "
                        >
                            {{
                                isAppreciated(
                                    blog.user.id,
                                    blog.user.is_appreciated,
                                )
                                    ? 'Appreciating'
                                    : 'Appreciate'
                            }}
                        </button>
                    </div>

                    <div class="text-[11px] text-slate-400 dark:text-gray-500">
                        {{ formatTimeAgo(blog.created_at) }}
                    </div>
                </div>
            </div>

            <!-- Blog Pill (Top Right, exactly like subject tag in forum card) -->
            <div class="shrink-0" @click.stop>
                <Link
                    href="/blogs"
                    class="rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/60"
                >
                    ব্লগ
                </Link>
            </div>
        </div>

        <!-- 2. Body: Title, Excerpt & Optional Image -->
        <div class="mt-3">
            <Link :href="'/blogs/' + blog.slug" class="group/title block">
                <h3
                    class="line-clamp-2 text-base font-bold text-slate-900 transition group-hover/title:text-indigo-600 sm:text-lg dark:text-white dark:group-hover/title:text-indigo-400"
                >
                    {{ blog.title }}
                </h3>
            </Link>

            <p
                v-if="blog.excerpt"
                class="mt-1.5 line-clamp-2 text-xs text-slate-600 sm:text-sm dark:text-gray-300"
            >
                {{ blog.excerpt }}
            </p>

            <!-- Featured Image (Attached image preview below excerpt) -->
            <div
                v-if="blog.featured_image || blog.featured_image_path"
                class="mt-3 max-h-80 overflow-hidden rounded-xl border border-slate-200/80 bg-slate-50 dark:border-gray-800 dark:bg-gray-800/50"
            >
                <img
                    :src="blog.featured_image || blog.featured_image_path || ''"
                    :alt="blog.title"
                    loading="lazy"
                    class="max-h-80 w-full object-cover transition duration-300 group-hover:scale-101"
                />
            </div>
        </div>

        <!-- 3. Footer Action Bar -->
        <div
            class="mt-4 flex flex-wrap items-center justify-between gap-2.5 border-t border-slate-100 pt-3 dark:border-gray-800/80"
        >
            <div
                class="flex items-center gap-3 text-xs text-slate-500 dark:text-gray-400"
                @click.stop
            >
                <!-- Clickable Love React Button -->
                <button
                    type="button"
                    @click="toggleReaction"
                    :disabled="isReacting"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border px-2.5 py-1 text-xs font-semibold transition active:scale-95 disabled:opacity-60"
                    :class="[
                        isReacted
                            ? 'border-rose-200 bg-rose-50/90 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300'
                            : 'border-slate-200/80 bg-slate-50/70 text-slate-700 hover:border-slate-300 hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800',
                    ]"
                    :title="isReacted ? 'রিঅ্যাক্ট সরান' : 'লাভ রিঅ্যাক্ট দিন'"
                >
                    <Heart
                        class="h-3.5 w-3.5 transition-transform"
                        :class="[
                            isReacted
                                ? 'scale-110 fill-rose-500 text-rose-500'
                                : 'text-slate-400 hover:text-rose-500 dark:text-gray-400',
                        ]"
                    />
                    <span>{{ reactionsCount }}</span>
                </button>

                <Link
                    :href="`/blogs/${blog.slug}#comments`"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/70 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    <MessageSquare
                        class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400"
                    />
                    <span>{{ blog.comments_count ?? 0 }} comments</span>
                </Link>
            </div>

            <!-- Right: Share Button -->
            <div class="flex items-center gap-2" @click.stop>
                <button
                    type="button"
                    @click="shareBlog"
                    :disabled="isSharing"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/70 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 disabled:cursor-wait disabled:opacity-75 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800"
                    title="Share blog"
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
