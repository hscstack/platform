<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import {
    MessageSquare,
    Send,
    Trash2,
    CornerDownRight,
    Loader2,
    LogIn,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';
import { usePermissions } from '@/lib/usePermissions';

export interface CommentUser {
    id: number;
    name: string;
    username: string;
    image_path?: string | null;
    image_url?: string | null;
    institution?: string | null;
    is_verified?: boolean;
}

export interface BlogCommentItem {
    id: number;
    blog_id: number;
    user_id: number;
    parent_id?: number | null;
    content: string;
    created_at: string;
    user: CommentUser;
    replies?: BlogCommentItem[];
}

const props = defineProps<{
    blogId: number;
    blogSlug: string;
    blogAuthorId?: number | null;
    comments: BlogCommentItem[];
}>();

const { can } = usePermissions();
const page = usePage();
const currentUser = computed(() => (page.props.auth as any)?.user);
const canModerate = computed(
    () => can('moderate resources') || can('view admin'),
);

const textareaRef = ref<HTMLTextAreaElement | null>(null);
const replyingTo = ref<{ id: number; username: string } | null>(null);

const commentForm = useForm({
    content: '',
    parent_id: null as number | null,
});

const totalCommentsCount = computed(() => {
    let count = props.comments.length;

    for (const c of props.comments) {
        if (c.replies) {
            count += c.replies.length;
        }
    }

    return count;
});

const submitComment = () => {
    if (!commentForm.content.trim()) {
        return;
    }

    commentForm.parent_id = replyingTo.value ? replyingTo.value.id : null;

    commentForm.post(`/blogs/${props.blogSlug}/comments`, {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
            replyingTo.value = null;
        },
    });
};

const cancelReply = () => {
    replyingTo.value = null;
    commentForm.reset();
};

const deleteComment = (commentId: number) => {
    if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    }

    commentForm.delete(`/blogs/comments/${commentId}`, {
        preserveScroll: true,
    });
};

const replyTo = (rootCommentId: number, username: string) => {
    if (!currentUser.value) {
        window.location.href = '/login';

        return;
    }

    replyingTo.value = { id: rootCommentId, username };
    const mention = `@${username} `;

    if (!commentForm.content.includes(mention)) {
        commentForm.content = `${mention}${commentForm.content.trimStart()}`;
    }

    nextTick(() => {
        textareaRef.value?.focus();
    });
};

const formatTimeAgo = (dateStr: string) => {
    if (!dateStr) {
        return '';
    }

    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diff < 60) {
        return 'just now';
    }

    if (diff < 3600) {
        return `${Math.floor(diff / 60)}m ago`;
    }

    if (diff < 86400) {
        return `${Math.floor(diff / 3600)}h ago`;
    }

    if (diff < 2592000) {
        return `${Math.floor(diff / 86400)}d ago`;
    }

    return date.toLocaleDateString();
};

const renderCommentContent = (content: string) => {
    const escaped = content
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    return escaped.replace(
        /@([a-zA-Z0-9_]+)/g,
        '<a href="/u/$1" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">@$1</a>',
    );
};
</script>

<template>
    <section
        id="comments"
        class="mt-8 border-t border-slate-200/80 pt-6 dark:border-gray-800"
    >
        <div class="mb-5 flex items-center justify-between">
            <h2
                class="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-gray-100"
            >
                <MessageSquare
                    class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
                />
                <span>Discussion</span>
                <span
                    class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:bg-gray-800 dark:text-gray-300"
                >
                    {{ totalCommentsCount }}
                </span>
            </h2>
        </div>

        <!-- Comments List -->
        <div class="space-y-4">
            <div
                v-if="comments.length === 0"
                class="rounded-2xl border border-dashed border-slate-200 p-8 text-center dark:border-gray-800"
            >
                <MessageSquare
                    class="mx-auto h-8 w-8 text-slate-300 dark:text-gray-600"
                />
                <p
                    class="mt-2 text-sm font-medium text-slate-600 dark:text-gray-400"
                >
                    No comments yet.
                </p>
                <p class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">
                    Be the first to share your thoughts on this article!
                </p>
            </div>

            <!-- Root Comments -->
            <div
                v-for="comment in comments"
                :key="comment.id"
                class="space-y-2.5 rounded-2xl border border-slate-100 bg-white p-4 shadow-2xs transition hover:border-slate-200 dark:border-gray-800/80 dark:bg-gray-900/90 dark:hover:border-gray-700"
            >
                <!-- Root Comment Card Header & Body -->
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <Link
                            :href="
                                comment.user?.username
                                    ? `/u/${comment.user.username}`
                                    : '#'
                            "
                            class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-100 font-semibold text-indigo-700 transition hover:ring-2 hover:ring-indigo-400 dark:bg-indigo-950 dark:text-indigo-300"
                        >
                            <img
                                v-if="
                                    comment.user?.image_url ||
                                    comment.user?.image_path
                                "
                                :src="
                                    comment.user?.image_url ||
                                    '/storage/' + comment.user?.image_path
                                "
                                :alt="comment.user?.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="text-xs uppercase">
                                {{ comment.user?.name?.charAt(0) || 'U' }}
                            </span>
                        </Link>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <Link
                                    :href="
                                        comment.user?.username
                                            ? `/u/${comment.user.username}`
                                            : '#'
                                    "
                                    class="inline-flex items-center gap-1 text-sm font-bold text-slate-900 transition hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                >
                                    <span>{{
                                        comment.user?.name || 'Anonymous'
                                    }}</span>
                                    <VerifiedBadge
                                        v-if="comment.user?.is_verified"
                                    />
                                </Link>
                                <span
                                    v-if="
                                        blogAuthorId &&
                                        comment.user_id === blogAuthorId
                                    "
                                    class="rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold tracking-wide text-indigo-600 uppercase dark:bg-indigo-950/80 dark:text-indigo-400"
                                >
                                    Author
                                </span>
                            </div>
                            <p
                                v-if="comment.user?.institution"
                                class="truncate text-xs font-medium text-slate-500 dark:text-gray-400"
                            >
                                {{ comment.user.institution }}
                            </p>
                        </div>
                    </div>

                    <!-- Right Actions -->
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="text-xs text-slate-400 dark:text-gray-500">
                            {{ formatTimeAgo(comment.created_at) }}
                        </span>

                        <button
                            v-if="comment.user?.username"
                            type="button"
                            @click="replyTo(comment.id, comment.user.username)"
                            title="Reply"
                            class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                        >
                            <CornerDownRight class="h-4 w-4" />
                        </button>

                        <button
                            v-if="
                                currentUser &&
                                (currentUser.id === comment.user_id ||
                                    canModerate)
                            "
                            type="button"
                            @click="deleteComment(comment.id)"
                            title="Delete comment"
                            class="cursor-pointer rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div
                    class="mt-2.5 text-sm leading-relaxed break-words whitespace-pre-line text-slate-700 dark:text-gray-300"
                    v-html="renderCommentContent(comment.content)"
                ></div>

                <!-- 1-Level Deep Nested Replies Section -->
                <div
                    v-if="comment.replies && comment.replies.length > 0"
                    class="mt-3.5 space-y-2.5 border-l-2 border-indigo-100 pl-3 sm:pl-4 dark:border-indigo-900/40"
                >
                    <div
                        v-for="reply in comment.replies"
                        :key="reply.id"
                        class="group/reply rounded-xl bg-slate-50/70 p-3 transition hover:bg-slate-50 dark:bg-gray-800/50 dark:hover:bg-gray-800/80"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2.5">
                                <Link
                                    :href="
                                        reply.user?.username
                                            ? `/u/${reply.user.username}`
                                            : '#'
                                    "
                                    class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-100 font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                >
                                    <img
                                        v-if="
                                            reply.user?.image_url ||
                                            reply.user?.image_path
                                        "
                                        :src="
                                            reply.user?.image_url ||
                                            '/storage/' + reply.user?.image_path
                                        "
                                        :alt="reply.user?.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else class="text-[10px] uppercase">
                                        {{ reply.user?.name?.charAt(0) || 'U' }}
                                    </span>
                                </Link>

                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap items-center gap-1.5"
                                    >
                                        <Link
                                            :href="
                                                reply.user?.username
                                                    ? `/u/${reply.user.username}`
                                                    : '#'
                                            "
                                            class="inline-flex items-center gap-1 text-xs font-bold text-slate-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                        >
                                            <span>{{
                                                reply.user?.name || 'Anonymous'
                                            }}</span>
                                            <VerifiedBadge
                                                v-if="reply.user?.is_verified"
                                            />
                                        </Link>
                                        <span
                                            v-if="
                                                blogAuthorId &&
                                                reply.user_id === blogAuthorId
                                            "
                                            class="rounded-md bg-indigo-50 px-1 py-0.5 text-[9px] font-bold tracking-wide text-indigo-600 uppercase dark:bg-indigo-950/80 dark:text-indigo-400"
                                        >
                                            Author
                                        </span>
                                    </div>
                                    <p
                                        v-if="reply.user?.institution"
                                        class="truncate text-[10px] text-slate-400 dark:text-gray-500"
                                    >
                                        {{ reply.user.institution }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <span
                                    class="text-[11px] text-slate-400 dark:text-gray-500"
                                >
                                    {{ formatTimeAgo(reply.created_at) }}
                                </span>

                                <button
                                    v-if="reply.user?.username"
                                    type="button"
                                    @click="
                                        replyTo(comment.id, reply.user.username)
                                    "
                                    title="Reply"
                                    class="cursor-pointer rounded-lg p-1 text-slate-400 transition hover:bg-slate-200/60 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-700"
                                >
                                    <CornerDownRight class="h-3.5 w-3.5" />
                                </button>

                                <button
                                    v-if="
                                        currentUser &&
                                        (currentUser.id === reply.user_id ||
                                            canModerate)
                                    "
                                    type="button"
                                    @click="deleteComment(reply.id)"
                                    title="Delete reply"
                                    class="cursor-pointer rounded-lg p-1 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <div
                            class="mt-2 text-xs leading-relaxed break-words whitespace-pre-line text-slate-700 dark:text-gray-300"
                            v-html="renderCommentContent(reply.content)"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comment Input Box -->
        <div
            class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Replying Indicator Banner -->
            <div
                v-if="replyingTo"
                class="mb-3 flex items-center justify-between rounded-xl bg-indigo-50/70 px-3 py-1.5 text-xs text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300"
            >
                <div class="flex items-center gap-1.5 font-medium">
                    <CornerDownRight class="h-3.5 w-3.5" />
                    <span
                        >Replying to
                        <strong>@{{ replyingTo.username }}</strong></span
                    >
                </div>
                <button
                    type="button"
                    @click="cancelReply"
                    class="cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-gray-200"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>

            <form @submit.prevent="submitComment">
                <div class="relative">
                    <textarea
                        ref="textareaRef"
                        v-model="commentForm.content"
                        rows="3"
                        :placeholder="
                            replyingTo
                                ? `Replying to @${replyingTo.username}...`
                                : 'Share your thoughts, ask a question, or mention someone with @username...'
                        "
                        maxlength="1000"
                        class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50/50 p-3 text-sm text-slate-900 transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:bg-gray-800"
                    ></textarea>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <span class="text-xs text-slate-400 dark:text-gray-500">
                        {{ 1000 - commentForm.content.length }} characters
                        remaining
                    </span>

                    <div class="flex items-center gap-2">
                        <Link
                            v-if="!currentUser"
                            href="/login"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700"
                        >
                            <LogIn class="h-3.5 w-3.5" />
                            <span>Sign in to comment</span>
                        </Link>

                        <button
                            v-else
                            type="submit"
                            :disabled="
                                commentForm.processing ||
                                !commentForm.content.trim()
                            "
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Loader2
                                v-if="commentForm.processing"
                                class="h-3.5 w-3.5 animate-spin"
                            />
                            <Send v-else class="h-3.5 w-3.5" />
                            <span>{{
                                replyingTo ? 'Post Reply' : 'Post Comment'
                            }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</template>
