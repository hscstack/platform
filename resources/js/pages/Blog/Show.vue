<script setup lang="ts">
import { Link, Head, usePage, useForm, router } from '@inertiajs/vue3';
import {
    Calendar,
    User,
    Eye,
    ArrowLeft,
    Heart,
    MessageSquare,
    Trash2,
    Send,
    LogIn,
    X,
    Loader2,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    blog: {
        type: Object,
        required: true,
    },
    reactionsCount: {
        type: Number,
        default: 0,
    },
    isReacted: {
        type: Boolean,
        default: false,
    },
    reactors: {
        type: Array as () => Array<any>,
        default: () => [],
    },
    comments: {
        type: Array as () => Array<any>,
        default: () => [],
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const canAccessAdmin = computed(() => page.props.auth?.can_access_admin);

// Auth & Reactors modal states
const showAuthModal = ref(false);
const showReactorsModal = ref(false);
const authModalMessage = ref('Please sign in to react to this article.');

// Optimistic Reaction state
const localIsReacted = ref(props.isReacted);
const localReactionsCount = ref(props.reactionsCount);
const isReacting = ref(false);

watch(
    () => props.isReacted,
    (val) => {
        localIsReacted.value = val;
    },
);

watch(
    () => props.reactionsCount,
    (val) => {
        localReactionsCount.value = val;
    },
);

const handleReactionClick = () => {
    if (!currentUser.value) {
        authModalMessage.value = 'Please sign in to react to this article.';
        showAuthModal.value = true;

        return;
    }

    if (isReacting.value) {
        return;
    }

    // Optimistic update
    if (localIsReacted.value) {
        localIsReacted.value = false;
        localReactionsCount.value = Math.max(0, localReactionsCount.value - 1);
    } else {
        localIsReacted.value = true;
        localReactionsCount.value += 1;
    }

    isReacting.value = true;
    router.post(
        `/blogs/${props.blog.slug}/react`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isReacting.value = false;
            },
        },
    );
};

// Comment Form
const commentForm = useForm({
    content: '',
});

const hasUserCommented = computed(() =>
    currentUser.value
        ? props.comments.some((c: any) => c.user_id === currentUser.value.id)
        : false,
);

const handleCommentInputClick = () => {
    if (!currentUser.value) {
        authModalMessage.value =
            'Please sign in to join the conversation and leave a comment.';
        showAuthModal.value = true;
    }
};

const submitComment = () => {
    if (!currentUser.value) {
        authModalMessage.value =
            'Please sign in to join the conversation and leave a comment.';
        showAuthModal.value = true;

        return;
    }

    if (!commentForm.content.trim() || commentForm.processing) {
        return;
    }

    commentForm.post(`/blogs/${props.blog.slug}/comments`, {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
        },
    });
};

const deleteComment = (commentId: number) => {
    if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    }

    router.delete(`/blogs/comments/${commentId}`, {
        preserveScroll: true,
    });
};

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

const formatTimeAgo = (dateStr: string) => {
    if (!dateStr) {
        return '';
    }

    const date = new Date(dateStr);

    if (isNaN(date.getTime())) {
        return '';
    }

    const seconds = Math.floor((Date.now() - date.getTime()) / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);

    if (days < 30) {
        return `${days}d ago`;
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head>
        <title>{{ blog.meta_title || blog.title }}</title>
        <meta
            name="description"
            :content="blog.meta_description || blog.excerpt || blog.title"
        />
        <meta property="og:title" :content="blog.meta_title || blog.title" />
        <meta
            property="og:description"
            :content="blog.meta_description || blog.excerpt || blog.title"
        />
        <meta property="og:type" content="article" />
        <meta
            v-if="blog.featured_image"
            property="og:image"
            :content="blog.featured_image"
        />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="blog.meta_title || blog.title" />
        <meta
            name="twitter:description"
            :content="blog.meta_description || blog.excerpt || blog.title"
        />
        <meta
            v-if="blog.featured_image"
            name="twitter:image"
            :content="blog.featured_image"
        />
    </Head>

    <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8">
            <button
                @click="goBack"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
            >
                <ArrowLeft class="h-4 w-4" />
                <span>Back to journal</span>
            </button>
        </div>

        <article>
            <div
                class="mb-6 flex flex-wrap items-center gap-4 text-xs sm:text-sm"
            >
                <div
                    class="flex items-center gap-1.5 text-slate-500 dark:text-gray-400"
                >
                    <User class="h-4 w-4 text-slate-400 dark:text-gray-500" />
                    <span>By</span>
                    <Link
                        :href="`/about-us#${blog.user?.id}`"
                        class="font-medium text-indigo-600 transition-colors hover:text-indigo-800 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        {{ blog.user?.name }}
                    </Link>
                </div>

                <div
                    v-if="formattedDate"
                    class="flex items-center gap-1.5 text-slate-500 dark:text-gray-400"
                >
                    <Calendar
                        class="h-4 w-4 text-slate-400 dark:text-gray-500"
                    />
                    <time
                        :datetime="blog.created_at"
                        class="text-slate-500 dark:text-gray-400"
                        >{{ formattedDate }}</time
                    >
                </div>

                <div
                    class="flex items-center gap-1.5 text-slate-500 dark:text-gray-400"
                >
                    <Eye class="h-4 w-4 text-slate-400 dark:text-gray-500" />
                    <span>{{ blog.views }} views</span>
                </div>

                <button
                    type="button"
                    @click="showReactorsModal = true"
                    class="flex cursor-pointer items-center gap-1.5 text-slate-500 transition hover:text-rose-600 dark:text-gray-400 dark:hover:text-rose-400"
                >
                    <Heart class="h-4 w-4 text-rose-500" />
                    <span
                        >{{ localReactionsCount }}
                        {{ localReactionsCount === 1 ? 'love' : 'loves' }}</span
                    >
                </button>

                <a
                    href="#comments"
                    class="flex items-center gap-1.5 text-slate-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                >
                    <MessageSquare
                        class="h-4 w-4 text-slate-400 dark:text-gray-500"
                    />
                    <span>{{ comments.length }} comments</span>
                </a>
            </div>

            <h1
                class="text-3xl leading-tight font-extrabold tracking-tight text-slate-900 sm:text-4xl md:text-5xl dark:text-gray-100"
            >
                {{ blog.title }}
            </h1>

            <div
                class="my-8 overflow-hidden rounded-2xl border border-slate-100 bg-slate-50 shadow-sm dark:border-gray-800 dark:bg-gray-800"
            >
                <img
                    :src="
                        blog.featured_image || 'https://placehold.co/1200x630'
                    "
                    :alt="blog.title"
                    class="aspect-[2/1] w-full object-cover"
                />
            </div>

            <div class="blog-content max-w-none" v-html="blog.content"></div>

            <!-- Reaction Interaction Bar -->
            <div
                class="mt-10 flex flex-wrap items-center justify-between gap-4 border-y border-slate-200/80 py-4 dark:border-gray-800"
            >
                <div class="flex items-center gap-3">
                    <button
                        @click="handleReactionClick"
                        type="button"
                        class="group inline-flex cursor-pointer items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition-all duration-200 select-none active:scale-95"
                        :class="[
                            localIsReacted
                                ? 'border-rose-200 bg-rose-50 text-rose-600 shadow-xs dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-400'
                                : 'border-slate-200 bg-white text-slate-700 hover:border-rose-200 hover:bg-rose-50/50 hover:text-rose-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-rose-900/50 dark:hover:bg-rose-950/20 dark:hover:text-rose-400',
                        ]"
                    >
                        <Heart
                            class="h-5 w-5 transition-transform duration-200 group-hover:scale-110"
                            :class="[
                                localIsReacted
                                    ? 'scale-110 fill-rose-500 text-rose-500'
                                    : 'text-slate-400 group-hover:text-rose-500 dark:text-gray-400',
                            ]"
                        />
                        <span>{{ localIsReacted ? 'Loved' : 'Love' }}</span>
                        <span
                            class="ml-1 rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="[
                                localIsReacted
                                    ? 'bg-rose-200/60 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300'
                                    : 'bg-slate-100 text-slate-600 dark:bg-gray-700 dark:text-gray-300',
                            ]"
                        >
                            {{ localReactionsCount }}
                        </span>
                    </button>

                    <a
                        href="#comments"
                        class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60"
                    >
                        <MessageSquare
                            class="h-4 w-4 text-slate-400 dark:text-gray-400"
                        />
                        <span>Comment</span>
                    </a>
                </div>

                <!-- Reactors List Preview & Button -->
                <div>
                    <button
                        v-if="localReactionsCount > 0"
                        type="button"
                        @click="showReactorsModal = true"
                        class="group flex cursor-pointer items-center gap-2 text-xs text-slate-600 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                    >
                        <!-- Overlapping Avatar Stack -->
                        <div
                            v-if="reactors.length > 0"
                            class="flex -space-x-2 overflow-hidden py-1"
                        >
                            <div
                                v-for="reactor in reactors.slice(0, 3)"
                                :key="reactor.id"
                                class="inline-block h-6 w-6 overflow-hidden rounded-full bg-rose-100 ring-2 ring-white dark:bg-rose-950 dark:ring-gray-900"
                            >
                                <img
                                    v-if="
                                        reactor.image_url || reactor.image_path
                                    "
                                    :src="
                                        reactor.image_url ||
                                        '/storage/' + reactor.image_path
                                    "
                                    :alt="reactor.name"
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-[10px] font-bold text-rose-600 uppercase dark:text-rose-400"
                                >
                                    {{ reactor.name?.charAt(0) || 'U' }}
                                </span>
                            </div>
                        </div>

                        <span
                            class="font-medium underline-offset-2 group-hover:underline"
                        >
                            {{ localReactionsCount }}
                            {{
                                localReactionsCount === 1 ? 'person' : 'people'
                            }}
                            loved this
                        </span>
                    </button>

                    <span
                        v-else
                        class="text-xs text-slate-500 dark:text-gray-400"
                    >
                        Be the first to show some love!
                    </span>
                </div>
            </div>
        </article>

        <!-- "Write for us" Minimal Callout -->
        <div
            class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-100/80 bg-indigo-50/40 px-4 py-3 text-xs text-slate-600 dark:border-indigo-950/60 dark:bg-indigo-950/20 dark:text-gray-400"
        >
            <div class="flex items-center gap-2">
                <span class="font-semibold text-slate-900 dark:text-gray-200"
                    >Want to write a blog here?</span
                >
                <span class="hidden text-slate-400 sm:inline dark:text-gray-500"
                    >•</span
                >
                <span class="hidden sm:inline"
                    >Share your thoughts with the community.</span
                >
            </div>
            <Link
                href="/join"
                class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
            >
                <span>Join us</span>
                <span aria-hidden="true">&rarr;</span>
            </Link>
        </div>

        <!-- Comments Section -->
        <section
            id="comments"
            class="mt-12 border-t border-slate-200 pt-10 dark:border-gray-800"
        >
            <div class="mb-6 flex items-center justify-between">
                <h2
                    class="flex items-center gap-2 text-xl font-bold text-slate-900 dark:text-gray-100"
                >
                    <MessageSquare
                        class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
                    />
                    <span>Comments</span>
                    <span
                        class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:bg-gray-800 dark:text-gray-300"
                    >
                        {{ comments.length }}
                    </span>
                </h2>
            </div>

            <!-- Comment Input Box or Already Commented Notice -->
            <div
                v-if="hasUserCommented"
                class="mb-8 rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4.5 text-sm text-slate-700 dark:border-indigo-900/50 dark:bg-indigo-950/20 dark:text-gray-300"
            >
                <div
                    class="flex items-center gap-2 font-semibold text-indigo-700 dark:text-indigo-300"
                >
                    <MessageSquare class="h-4 w-4" />
                    <span>You've already commented on this post</span>
                </div>
                <p class="mt-1 text-xs text-slate-600 dark:text-gray-400">
                    Each user is limited to 1 comment per article. You can
                    delete your existing comment below if you wish to post a new
                    one.
                </p>
            </div>

            <div
                v-else
                class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900"
            >
                <form @submit.prevent="submitComment">
                    <div class="relative">
                        <textarea
                            v-model="commentForm.content"
                            @click="handleCommentInputClick"
                            rows="3"
                            placeholder="Share your thoughts or ask a question..."
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
                            <button
                                v-if="!currentUser"
                                type="button"
                                @click="handleCommentInputClick"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700"
                            >
                                <LogIn class="h-3.5 w-3.5" />
                                <span>Sign in to comment</span>
                            </button>

                            <button
                                v-else
                                type="submit"
                                :disabled="
                                    commentForm.processing ||
                                    !commentForm.content.trim()
                                "
                                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Loader2
                                    v-if="commentForm.processing"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <Send v-else class="h-3.5 w-3.5" />
                                <span>Post Comment</span>
                            </button>
                        </div>
                    </div>
                </form>
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

                <div
                    v-for="comment in comments"
                    :key="comment.id"
                    class="group rounded-2xl border border-slate-100 bg-white p-4.5 shadow-2xs transition hover:border-slate-200 dark:border-gray-800/80 dark:bg-gray-900/90 dark:hover:border-gray-700"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-100 font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
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
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="text-sm font-bold text-slate-900 dark:text-gray-100"
                                    >
                                        {{ comment.user?.name || 'Anonymous' }}
                                    </span>
                                    <span
                                        v-if="comment.user_id === blog.user_id"
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

                        <!-- Top-Right Actions & Timestamp -->
                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                class="text-xs text-slate-400 dark:text-gray-500"
                            >
                                {{ formatTimeAgo(comment.created_at) }}
                            </span>

                            <!-- Delete Button (Only for comment author or admin) -->
                            <button
                                v-if="
                                    currentUser &&
                                    (currentUser.id === comment.user_id ||
                                        canAccessAdmin)
                                "
                                @click="deleteComment(comment.id)"
                                title="Delete comment"
                                class="cursor-pointer rounded-lg p-1.5 text-slate-400 opacity-100 transition-opacity hover:bg-rose-50 hover:text-rose-600 sm:opacity-0 sm:group-hover:opacity-100 dark:text-gray-500 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <p
                        class="mt-3 text-sm leading-relaxed break-words whitespace-pre-line text-slate-700 dark:text-gray-300"
                    >
                        {{ comment.content }}
                    </p>
                </div>
            </div>
        </section>

        <footer
            class="mt-12 border-t border-slate-200 pt-8 dark:border-gray-700"
        >
            <div
                class="flex flex-col gap-6 rounded-2xl bg-slate-50 p-6 sm:flex-row sm:items-center sm:justify-between dark:bg-gray-800"
            >
                <div>
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        Written by
                    </p>

                    <Link
                        :href="`/about-us#author-${blog.user?.id}`"
                        class="mt-1 block text-lg font-semibold text-slate-900 underline transition dark:text-gray-100"
                    >
                        {{ blog.user?.name }}
                    </Link>

                    <Link
                        :href="`/blogs?q=${encodeURIComponent(blog.user?.name || '')}`"
                        class="mt-2 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        View more articles by {{ blog.user?.name }} →
                    </Link>
                </div>
            </div>
        </footer>
    </main>

    <!-- People who loved this (Reactors) Modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showReactorsModal"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs dark:bg-black/50"
            >
                <div
                    class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        @click="showReactorsModal = false"
                        class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <div class="mb-4 flex items-center gap-2">
                        <Heart
                            class="h-4.5 w-4.5 fill-rose-500 text-rose-500"
                        />
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            Loved by {{ localReactionsCount }}
                            {{
                                localReactionsCount === 1 ? 'person' : 'people'
                            }}
                        </h3>
                    </div>

                    <div
                        class="-mx-1 max-h-72 divide-y divide-slate-100 overflow-y-auto px-1 dark:divide-gray-800/80"
                    >
                        <div
                            v-if="reactors.length === 0"
                            class="py-6 text-center text-xs text-slate-500 dark:text-gray-400"
                        >
                            No reactions yet.
                        </div>

                        <div
                            v-for="reactor in reactors"
                            :key="reactor.id"
                            class="flex items-center gap-3 py-2.5"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-rose-50 font-semibold text-rose-600 dark:bg-rose-950/60 dark:text-rose-300"
                            >
                                <img
                                    v-if="
                                        reactor.image_url || reactor.image_path
                                    "
                                    :src="
                                        reactor.image_url ||
                                        '/storage/' + reactor.image_path
                                    "
                                    :alt="reactor.name"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else class="text-xs uppercase">
                                    {{ reactor.name?.charAt(0) || 'U' }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-xs font-semibold text-slate-900 dark:text-gray-100"
                                >
                                    {{ reactor.name }}
                                </p>
                                <p
                                    v-if="reactor.institution"
                                    class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    {{ reactor.institution }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="localReactionsCount > reactors.length"
                            class="py-3 text-center text-xs font-medium text-slate-500 dark:text-gray-400"
                        >
                            and
                            {{ localReactionsCount - reactors.length }} more...
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Sign In Required Modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showAuthModal"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs dark:bg-black/50"
            >
                <div
                    class="relative w-full max-w-xs rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        @click="showAuthModal = false"
                        class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <h3
                        class="text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        Sign in required
                    </h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                        {{ authModalMessage }}
                    </p>

                    <div class="mt-4 flex items-center gap-2">
                        <Link
                            :href="`/login?redirect=${encodeURIComponent($page.url)}`"
                            @click="showAuthModal = false"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-slate-900 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                        >
                            <LogIn class="h-3.5 w-3.5" />
                            <span>Sign in</span>
                        </Link>
                        <button
                            @click="showAuthModal = false"
                            class="cursor-pointer rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
:deep(.blog-content h1),
:deep(.blog-content h2),
:deep(.blog-content h3),
:deep(.blog-content h4),
:deep(.blog-content h5),
:deep(.blog-content h6),
:deep(.blog-content p),
:deep(.blog-content ul),
:deep(.blog-content ol),
:deep(.blog-content blockquote),
:deep(.blog-content a) {
    all: revert;
}
</style>
