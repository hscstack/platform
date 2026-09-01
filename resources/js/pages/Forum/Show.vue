<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowBigUp,
    CheckCircle2,
    MessageSquare,
    Trash2,
    Reply,
    X,
    ImageIcon,
    Send,
    HelpCircle,
    CornerDownRight,
    Lock,
    Unlock,
    Flag,
    Loader2,
    Check,
    Clock,
    AlertTriangle,
    ShieldAlert,
    Ban,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AuthModal from '@/components/AuthModal.vue';
import ChatBanModal from '@/components/ChatBanModal.vue';
import type { ChatBanUser } from '@/components/ChatBanModal.vue';
import ForumVoteButtons from '@/components/forum/ForumVoteButtons.vue';
import ImageViewerModal from '@/components/ImageViewerModal.vue';
import UserListItem from '@/components/UserListItem.vue';
import { usePermissions } from '@/lib/usePermissions';

interface User {
    id: number;
    name: string;
    username: string;
    image_path?: string | null;
    image_url?: string | null;
    institution?: string | null;
}

interface Subject {
    id: number;
    name: string;
    course: 'hsc' | 'ssc';
    slug: string;
}

interface ForumAnswer {
    id: number;
    forum_post_id: number;
    user_id: number;
    parent_id?: number | null;
    body: string;
    image_path?: string | null;
    image_url?: string | null;
    vote_score: number;
    upvotes_count: number;
    downvotes_count: number;
    created_at: string;
    user?: User;
    parent?: {
        id: number;
        user_id: number;
        user?: {
            id: number;
            name: string;
            username: string;
        };
    } | null;
    replies?: ForumAnswer[];
    user_vote?: number | null;
}

interface ForumPost {
    id: number;
    user_id: number;
    subject_id?: number | null;
    node_id?: number | null;
    curriculum: 'hsc' | 'ssc';
    title: string;
    slug: string;
    body: string;
    image_path?: string | null;
    image_url?: string | null;
    is_answered: boolean;
    is_locked?: boolean;
    moderation_status?: 'approved' | 'pending' | 'flagged' | 'rejected';
    vote_score: number;
    upvotes_count: number;
    downvotes_count: number;
    answers_count: number;
    created_at: string;
    user?: User;
    subject?: Subject | null;
    node?: { id: number; name: string; slug: string } | null;
    user_vote?: number | null;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedAnswers {
    data: ForumAnswer[];
    links: PaginationLinks[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    post: ForumPost;
    answers: PaginatedAnswers;
    upvoters?: User[];
    commentsEnabled?: boolean;
    disabledReason?: string;
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);
const isUserBanned = computed(() => Boolean(user.value?.is_banned));

const showAuthModal = ref(false);
const authModalMessage = ref('Please sign in to continue.');

// Moderator Suspension Modal State
const isBanModalOpen = ref(false);
const selectedBanUser = ref<ChatBanUser | null>(null);

const openBanModalForUser = (targetUser?: User | null) => {
    if (!targetUser) {
        return;
    }

    selectedBanUser.value = {
        id: targetUser.id,
        name: targetUser.name,
        username: targetUser.username,
        banned_until: (targetUser as any).banned_until || null,
        is_banned: (targetUser as any).is_banned ?? false,
    };
    isBanModalOpen.value = true;
};

// Upvoters Modal State
const showUpvotersModal = ref(false);
const localUpvoters = ref<User[]>([...(props.upvoters || [])]);

watch(
    () => props.upvoters,
    (val) => {
        localUpvoters.value = [...(val || [])];
    },
);

const handlePostVoted = ({
    userVote,
}: {
    value: 1 | -1;
    userVote: number | null;
    upvotesDelta: number;
}) => {
    if (!user.value) {
        return;
    }

    if (userVote === 1) {
        if (!localUpvoters.value.some((u) => u.id === user.value.id)) {
            localUpvoters.value.unshift({
                id: user.value.id,
                name: user.value.name,
                username: user.value.username,
                image_path: user.value.image_path,
                institution: user.value.institution,
            });
        }
    } else {
        localUpvoters.value = localUpvoters.value.filter(
            (u) => u.id !== user.value.id,
        );
    }
};

// Image Lightbox Modal
const showImageModal = ref(false);
const modalImageSrc = ref('');
const modalImageAlt = ref('');

const openImageModal = (src: string, alt = 'Attached image') => {
    modalImageSrc.value = src;
    modalImageAlt.value = alt;
    showImageModal.value = true;
};

// Toggle Answered
const isTogglingAnswered = ref(false);
const toggleAnswered = () => {
    if (!user.value || user.value.id !== props.post.user_id) {
        return;
    }

    isTogglingAnswered.value = true;
    router.post(
        `/forum/posts/${props.post.id}/toggle-answered`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isTogglingAnswered.value = false;
            },
        },
    );
};

// Delete Post
const deletePost = () => {
    if (
        confirm(
            'Are you sure you want to delete this question? All answers will also be deleted.',
        )
    ) {
        router.delete(`/forum/posts/${props.post.id}`);
    }
};

const { can } = usePermissions();

// Moderator Actions
const toggleLock = () => {
    router.patch(
        `/admin/forums/${props.post.id}/lock`,
        {},
        { preserveScroll: true },
    );
};

const updateModerationStatus = (
    status: 'approved' | 'pending' | 'flagged' | 'rejected',
) => {
    router.patch(
        `/admin/forums/${props.post.id}/status`,
        { moderation_status: status },
        { preserveScroll: true },
    );
};

// Delete Answer
const deleteAnswer = (answerId: number) => {
    if (confirm('Are you sure you want to delete this answer?')) {
        router.delete(`/forum/answers/${answerId}`, {
            preserveScroll: true,
        });
    }
};

// Report Content State & Methods
interface ReportTarget {
    type: 'post' | 'answer';
    id: number;
    title?: string;
    authorName?: string;
    contentPreview: string;
}

const reportingTarget = ref<ReportTarget | null>(null);
const reportReason = ref('Inappropriate content or conduct');
const isSubmittingReport = ref(false);
const reportSuccessMessage = ref<string | null>(null);
const reportErrorMessage = ref<string | null>(null);

const reportReasons = [
    'Inappropriate content or conduct',
    'Spam or advertisement',
    'Harassment or hate speech',
    'False or misleading academic information',
    'Off-topic or irrelevant',
    'Other',
];

const openReportModal = (
    type: 'post' | 'answer',
    id: number,
    title: string | undefined,
    authorName: string | undefined,
    contentPreview: string,
) => {
    if (!user.value) {
        authModalMessage.value = 'Please sign in to report this content.';
        showAuthModal.value = true;

        return;
    }

    reportingTarget.value = {
        type,
        id,
        title,
        authorName: authorName || 'Anonymous',
        contentPreview:
            contentPreview.length > 200
                ? contentPreview.slice(0, 200) + '...'
                : contentPreview,
    };
    reportReason.value = 'Inappropriate content or conduct';
    reportSuccessMessage.value = null;
    reportErrorMessage.value = null;
};

const closeReportModal = () => {
    reportingTarget.value = null;
    reportSuccessMessage.value = null;
    reportErrorMessage.value = null;
    isSubmittingReport.value = false;
};

const submitReport = async () => {
    if (!reportingTarget.value || isSubmittingReport.value) {
        return;
    }

    isSubmittingReport.value = true;
    reportSuccessMessage.value = null;
    reportErrorMessage.value = null;

    try {
        const token = (
            document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
        )?.content;

        const endpoint =
            reportingTarget.value.type === 'post'
                ? `/forum/posts/${reportingTarget.value.id}/report`
                : `/forum/answers/${reportingTarget.value.id}/report`;

        const res = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify({
                reason: reportReason.value,
            }),
        });

        const data = await res.json();

        if (res.ok) {
            reportSuccessMessage.value =
                data.message ||
                'Report submitted successfully. Our moderation team will review it.';
            setTimeout(() => {
                closeReportModal();
            }, 1800);
        } else {
            reportErrorMessage.value =
                data.message || 'Failed to submit report.';
        }
    } catch {
        reportErrorMessage.value = 'Network error. Please try again.';
    } finally {
        isSubmittingReport.value = false;
    }
};

// Main Answer Form
const answerForm = useForm({
    body: '',
    image: null as File | null,
});

const answerImagePreview = ref<string | null>(null);
const answerFileInputRef = ref<HTMLInputElement | null>(null);

const handleAnswerFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        const file = target.files[0];
        answerForm.image = file;
        answerImagePreview.value = URL.createObjectURL(file);
    }
};

const removeAnswerImage = () => {
    answerForm.image = null;

    if (answerImagePreview.value) {
        URL.revokeObjectURL(answerImagePreview.value);
        answerImagePreview.value = null;
    }

    if (answerFileInputRef.value) {
        answerFileInputRef.value.value = '';
    }
};

const submitAnswer = () => {
    if (!user.value) {
        authModalMessage.value = 'Please sign in to answer this question.';
        showAuthModal.value = true;

        return;
    }

    answerForm.post(`/forum/posts/${props.post.id}/answers`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            answerForm.reset();
            removeAnswerImage();
        },
    });
};

// Reply Form State
const activeReplyParentId = ref<number | null>(null);
const replyForm = useForm({
    parent_id: null as number | null,
    reply_to_user_id: null as number | null,
    body: '',
    image: null as File | null,
});
const replyImagePreview = ref<string | null>(null);
const replyFileInputRef = ref<HTMLInputElement | null>(null);

const openReplyForm = (
    rootAnswerId: number,
    targetUsername?: string,
    targetUserId?: number,
) => {
    if (props.post.is_locked || props.commentsEnabled === false) {
        return;
    }

    if (!user.value) {
        authModalMessage.value = 'Please sign in to reply.';
        showAuthModal.value = true;

        return;
    }

    activeReplyParentId.value = rootAnswerId;
    replyForm.parent_id = rootAnswerId;
    replyForm.reply_to_user_id = targetUserId || null;
    replyForm.body = targetUsername ? `@${targetUsername} ` : '';
    replyForm.image = null;

    if (replyImagePreview.value) {
        URL.revokeObjectURL(replyImagePreview.value);
        replyImagePreview.value = null;
    }
};

const cancelReply = () => {
    activeReplyParentId.value = null;
    replyForm.reset();

    if (replyImagePreview.value) {
        URL.revokeObjectURL(replyImagePreview.value);
        replyImagePreview.value = null;
    }
};

const handleReplyFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        const file = target.files[0];
        replyForm.image = file;
        replyImagePreview.value = URL.createObjectURL(file);
    }
};

const removeReplyImage = () => {
    replyForm.image = null;

    if (replyImagePreview.value) {
        URL.revokeObjectURL(replyImagePreview.value);
        replyImagePreview.value = null;
    }

    if (replyFileInputRef.value) {
        if (Array.isArray(replyFileInputRef.value)) {
            (replyFileInputRef.value as (HTMLInputElement | null)[]).forEach(
                (el) => {
                    if (el) {
                        el.value = '';
                    }
                },
            );
        } else {
            (replyFileInputRef.value as HTMLInputElement).value = '';
        }
    }
};

const submitReply = () => {
    if (!user.value) {
        authModalMessage.value = 'Please sign in to reply.';
        showAuthModal.value = true;

        return;
    }

    replyForm.post(`/forum/posts/${props.post.id}/answers`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            cancelReply();
        },
    });
};

function timeAgo(dateString?: string): string {
    if (!dateString) {
        return '';
    }

    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

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

    const months = Math.floor(days / 30);

    if (months < 12) {
        return `${months}mo ago`;
    }

    const years = Math.floor(days / 365);

    return `${years}y ago`;
}

function parseMentions(
    content: string,
): Array<{ type: 'text' | 'mention'; text: string; username?: string }> {
    if (!content) {
        return [];
    }

    const mentionRegex = /(?<=^|\s)@([a-zA-Z0-9_.-]+)/g;
    const segments: Array<{
        type: 'text' | 'mention';
        text: string;
        username?: string;
    }> = [];
    let lastIndex = 0;
    let match: RegExpExecArray | null;

    while ((match = mentionRegex.exec(content)) !== null) {
        if (match.index > lastIndex) {
            segments.push({
                type: 'text',
                text: content.substring(lastIndex, match.index),
            });
        }

        let username = match[1];
        let mentionText = match[0];
        let trailingPunctuation = '';

        const trailingMatch = username.match(/[.,!?;:]+$/);

        if (trailingMatch) {
            trailingPunctuation = trailingMatch[0];
            username = username.slice(0, -trailingPunctuation.length);
            mentionText = mentionText.slice(0, -trailingPunctuation.length);
        }

        if (username) {
            segments.push({
                type: 'mention',
                text: mentionText,
                username,
            });
        } else {
            segments.push({
                type: 'text',
                text: match[0],
            });
        }

        if (trailingPunctuation) {
            segments.push({
                type: 'text',
                text: trailingPunctuation,
            });
        }

        lastIndex = match.index + match[0].length;
    }

    if (lastIndex < content.length) {
        segments.push({
            type: 'text',
            text: content.substring(lastIndex),
        });
    }

    return segments;
}
</script>

<template>
    <Head>
        <title>{{ post.title }} — HSCStack Forum</title>
        <meta name="description" :content="post.body.slice(0, 160)" />
    </Head>

    <main class="mx-auto max-w-4xl px-3.5 py-3.5 sm:px-6 sm:py-8">
        <!-- Back Link -->
        <div class="mb-2.5 sm:mb-5">
            <Link
                href="/forum"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
            >
                <ArrowLeft class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                <span>Back to Forum</span>
            </Link>
        </div>

        <!-- Question Article -->
        <article
            class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:mb-8 sm:p-7 dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Moderation Notice Banner (When post is not approved) -->
            <div
                v-if="
                    post.moderation_status &&
                    post.moderation_status !== 'approved'
                "
                class="mb-4 flex items-start gap-3 rounded-xl border p-4 text-xs"
                :class="[
                    post.moderation_status === 'pending'
                        ? 'border-amber-200 bg-amber-50/90 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300'
                        : post.moderation_status === 'flagged'
                          ? 'border-rose-200 bg-rose-50/90 text-rose-900 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300'
                          : 'border-red-200 bg-red-50/90 text-red-900 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300',
                ]"
            >
                <Clock
                    v-if="post.moderation_status === 'pending'"
                    class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                />
                <AlertTriangle
                    v-else-if="post.moderation_status === 'flagged'"
                    class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                />
                <ShieldAlert
                    v-else
                    class="mt-0.5 h-4 w-4 shrink-0 text-red-600 dark:text-red-400"
                />

                <div>
                    <div class="font-bold">
                        <span v-if="post.moderation_status === 'pending'"
                            >⏳ Pending Review</span
                        >
                        <span v-else-if="post.moderation_status === 'flagged'"
                            >⚠️ Temporarily Hidden (Under Review)</span
                        >
                        <span v-else>🛑 Question Unpublished</span>
                    </div>
                    <p class="mt-0.5 leading-relaxed opacity-90">
                        <span v-if="post.moderation_status === 'pending'">
                            This question is currently pending moderator review
                            and is only visible to you and platform moderators.
                        </span>
                        <span v-else-if="post.moderation_status === 'flagged'">
                            This question was temporarily hidden following
                            community reports and is awaiting review by our
                            moderation team.
                        </span>
                        <span v-else>
                            This question has been hidden or unpublished by
                            moderators for violating forum guidelines.
                        </span>
                    </p>
                </div>
            </div>

            <!-- Badges Row -->
            <div class="mb-3 flex flex-wrap items-center gap-2 text-xs">
                <!-- Curriculum Badge -->
                <span
                    class="rounded-md px-2 py-0.5 font-bold uppercase"
                    :class="[
                        post.curriculum === 'ssc'
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400'
                            : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400',
                    ]"
                >
                    {{ post.curriculum }}
                </span>

                <!-- Moderation Status Badge (if not approved) -->
                <span
                    v-if="
                        post.moderation_status &&
                        post.moderation_status !== 'approved'
                    "
                    class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase"
                    :class="[
                        post.moderation_status === 'pending'
                            ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                            : post.moderation_status === 'flagged'
                              ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                              : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
                    ]"
                >
                    {{
                        post.moderation_status === 'pending'
                            ? 'Pending Review'
                            : post.moderation_status === 'flagged'
                              ? 'Flagged'
                              : 'Unpublished'
                    }}
                </span>

                <!-- Subject Badge -->
                <span
                    v-if="post.subject"
                    class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    {{ post.subject.name }}
                </span>
                <span
                    v-else
                    class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                >
                    Other / General
                </span>

                <!-- Chapter Badge -->
                <span
                    v-if="post.node"
                    class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-400"
                >
                    # {{ post.node.name }}
                </span>

                <!-- Answered Badge -->
                <span
                    v-if="post.is_answered"
                    class="inline-flex items-center gap-1 rounded-md bg-emerald-100/80 px-2 py-0.5 font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                >
                    <CheckCircle2
                        class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"
                    />
                    <span>Answered</span>
                </span>
            </div>

            <!-- Question Title -->
            <h1
                class="text-xl font-extrabold text-slate-900 sm:text-2xl dark:text-gray-100"
            >
                {{ post.title }}
            </h1>

            <!-- Author & Metadata Row -->
            <div
                class="mt-3 flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4 text-xs text-slate-400 dark:border-gray-800 dark:text-gray-500"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        v-if="post.user?.username"
                        :href="`/u/${post.user.username}`"
                        class="inline-flex items-center gap-1.5 font-semibold text-slate-700 hover:text-indigo-600 hover:underline dark:text-gray-300 dark:hover:text-indigo-400"
                    >
                        <div
                            class="flex h-6 w-6 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-xs font-bold text-slate-700 dark:bg-gray-700 dark:text-gray-300"
                        >
                            <img
                                v-if="post.user.image_url"
                                :src="post.user.image_url"
                                :alt="post.user.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{ post.user.name.charAt(0) }}</span>
                        </div>
                        <span>{{ post.user.name }}</span>
                    </Link>
                    <span
                        v-else
                        class="font-semibold text-slate-700 dark:text-gray-300"
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
                    <span>Asked {{ timeAgo(post.created_at) }}</span>
                </div>

                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center gap-1 font-medium text-slate-600 dark:text-gray-400"
                    >
                        <MessageSquare class="h-3.5 w-3.5" />
                        <span
                            >{{ post.answers_count }}
                            {{
                                post.answers_count === 1 ? 'answer' : 'answers'
                            }}</span
                        >
                    </span>
                </div>
            </div>

            <!-- Question Body -->
            <div
                class="prose dark:prose-invert mt-4 max-w-none text-sm leading-relaxed whitespace-pre-wrap text-slate-800 sm:text-base dark:text-gray-200"
            >
                {{ post.body }}
            </div>

            <!-- Attached Image Preview -->
            <div v-if="post.image_url" class="mt-5">
                <div
                    class="inline-block overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-gray-800 dark:bg-gray-900"
                >
                    <img
                        :src="post.image_url"
                        :alt="post.title"
                        @click="openImageModal(post.image_url, post.title)"
                        class="max-h-96 cursor-zoom-in rounded-xl object-contain transition hover:opacity-95"
                    />
                </div>
                <p class="mt-1 text-[11px] text-slate-400 dark:text-gray-500">
                    Click image to expand
                </p>
            </div>

            <!-- Post Footer Actions (Voting, Toggle Answered, Share, Delete) -->
            <div
                class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4 dark:border-gray-800"
            >
                <!-- Left: Voting & Upvoters Stack -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <ForumVoteButtons
                        votableType="post"
                        :votableId="post.id"
                        :initialUpvotes="post.upvotes_count"
                        :initialDownvotes="post.downvotes_count"
                        :initialUserVote="post.user_vote"
                        direction="horizontal"
                        size="md"
                        @voted="handlePostVoted"
                    />

                    <!-- Upvoters Avatar Stack & Modal Trigger -->
                    <button
                        v-if="localUpvoters.length > 0"
                        type="button"
                        @click="showUpvotersModal = true"
                        class="group flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800"
                        title="View users who upvoted"
                    >
                        <div class="flex -space-x-1.5 overflow-hidden">
                            <div
                                v-for="upvoter in localUpvoters.slice(0, 3)"
                                :key="upvoter.id"
                                class="inline-block h-5 w-5 overflow-hidden rounded-full ring-2 ring-white dark:ring-gray-900"
                            >
                                <img
                                    v-if="upvoter.image_url"
                                    :src="upvoter.image_url"
                                    :alt="upvoter.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-indigo-100 text-[9px] font-bold text-indigo-700 uppercase dark:bg-indigo-950 dark:text-indigo-300"
                                >
                                    {{ upvoter.name?.charAt(0) || 'U' }}
                                </div>
                            </div>
                        </div>

                        <span
                            class="font-medium text-slate-700 underline-offset-2 group-hover:underline dark:text-gray-300"
                        >
                            {{ localUpvoters.length }}
                            {{
                                localUpvoters.length === 1
                                    ? 'upvote'
                                    : 'upvotes'
                            }}
                        </span>
                    </button>

                    <!-- Owner "Mark as Answered" Toggle -->
                    <button
                        v-if="user && user.id === post.user_id"
                        type="button"
                        @click="toggleAnswered"
                        :disabled="isTogglingAnswered"
                        class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold transition"
                        :class="[
                            post.is_answered
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300'
                                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800',
                        ]"
                    >
                        <CheckCircle2 class="h-3.5 w-3.5" />
                        <span>{{
                            post.is_answered
                                ? 'Mark as Unanswered'
                                : 'Mark as Answered'
                        }}</span>
                    </button>
                </div>

                <!-- Right: Moderator and Owner Actions -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Inline Moderator Tools -->
                    <template v-if="can('manage forums') || can('manage chat')">
                        <!-- Suspend Author Button -->
                        <button
                            v-if="post.user"
                            type="button"
                            @click="openBanModalForUser(post.user)"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-rose-200/80 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 shadow-2xs transition hover:bg-rose-50 dark:border-rose-900/60 dark:bg-gray-900 dark:text-rose-400 dark:hover:bg-rose-950/40"
                            :title="`Moderate / Suspend @${post.user.username}`"
                        >
                            <Ban class="h-3.5 w-3.5" />
                            <span>Suspend</span>
                        </button>

                        <!-- Toggle Lock Button -->
                        <button
                            v-if="can('manage forums')"
                            type="button"
                            @click="toggleLock"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold shadow-2xs transition"
                            :class="[
                                post.is_locked
                                    ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                            :title="
                                post.is_locked
                                    ? 'Unlock discussion replies'
                                    : 'Lock discussion replies'
                            "
                        >
                            <Unlock
                                v-if="post.is_locked"
                                class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"
                            />
                            <Lock v-else class="h-3.5 w-3.5 text-slate-500" />
                            <span>{{
                                post.is_locked ? 'Unlock' : 'Lock'
                            }}</span>
                        </button>

                        <!-- Moderation Status Dropdown -->
                        <select
                            v-if="can('manage forums')"
                            :value="post.moderation_status || 'approved'"
                            @change="
                                (e) =>
                                    updateModerationStatus(
                                        (e.target as HTMLSelectElement)
                                            .value as any,
                                    )
                            "
                            class="cursor-pointer rounded-xl border px-2.5 py-1.5 text-xs font-bold transition outline-none"
                            :class="[
                                post.moderation_status === 'approved' ||
                                !post.moderation_status
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300'
                                    : post.moderation_status === 'pending'
                                      ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300'
                                      : post.moderation_status === 'flagged'
                                        ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300'
                                        : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300',
                            ]"
                        >
                            <option value="approved">Approved (Live)</option>
                            <option value="pending">Pending Review</option>
                            <option value="flagged">Flagged (Reports)</option>
                            <option value="rejected">Rejected (Hidden)</option>
                        </select>
                    </template>

                    <!-- Report Question Action -->
                    <button
                        v-if="!user || user.id !== post.user_id"
                        type="button"
                        @click="
                            openReportModal(
                                'post',
                                post.id,
                                post.title,
                                post.user?.name,
                                post.body,
                            )
                        "
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-2xs transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-amber-900/60 dark:hover:bg-amber-950/40 dark:hover:text-amber-300"
                        title="Report question to moderators"
                    >
                        <Flag class="h-3.5 w-3.5 text-amber-500" />
                        <span>Report</span>
                    </button>

                    <!-- Owner or Admin Delete Action -->
                    <button
                        v-if="
                            user &&
                            (user.id === post.user_id || can('manage forums'))
                        "
                        type="button"
                        @click="deletePost"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-rose-200/80 bg-white px-3 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50 dark:border-rose-950 dark:bg-gray-900 dark:text-rose-400 dark:hover:bg-rose-950/30"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span>Delete</span>
                    </button>
                </div>
            </div>
        </article>

        <!-- Answers Section Header -->
        <div class="mb-5 flex items-center justify-between">
            <h2
                class="text-lg font-bold text-slate-900 sm:text-xl dark:text-gray-100"
            >
                {{ post.answers_count }}
                {{ post.answers_count === 1 ? 'Answer' : 'Answers' }}
            </h2>
        </div>

        <!-- Answers List -->
        <div v-if="answers.data.length > 0" class="mb-10 space-y-4">
            <div
                v-for="answer in answers.data"
                :key="answer.id"
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-6 dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Direct Answer Author Row -->
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <Link
                            v-if="answer.user?.username"
                            :href="`/u/${answer.user.username}`"
                            class="inline-flex items-center gap-2 font-semibold text-slate-900 hover:text-indigo-600 hover:underline dark:text-gray-100 dark:hover:text-indigo-400"
                        >
                            <div
                                class="flex h-7 w-7 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-xs font-bold text-slate-700 dark:bg-gray-700 dark:text-gray-300"
                            >
                                <img
                                    v-if="answer.user.image_url"
                                    :src="answer.user.image_url"
                                    :alt="answer.user.name"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{
                                    answer.user.name.charAt(0)
                                }}</span>
                            </div>
                            <span class="text-xs sm:text-sm">{{
                                answer.user.name
                            }}</span>
                        </Link>
                        <span
                            v-else
                            class="text-xs font-semibold text-slate-900 sm:text-sm dark:text-gray-100"
                        >
                            {{ answer.user?.name || 'Anonymous' }}
                        </span>

                        <span class="text-xs text-slate-400 dark:text-gray-500"
                            >•</span
                        >
                        <span
                            class="text-xs text-slate-400 dark:text-gray-500"
                            >{{ timeAgo(answer.created_at) }}</span
                        >
                    </div>

                    <!-- Delete button for direct answer -->
                    <button
                        v-if="
                            user &&
                            (user.id === answer.user_id || can('manage forums'))
                        "
                        type="button"
                        @click="deleteAnswer(answer.id)"
                        class="rounded-lg p-1 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-950/30 dark:hover:text-rose-400"
                        aria-label="Delete answer"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                    </button>
                </div>

                <!-- Answer Body -->
                <div
                    class="prose dark:prose-invert mt-3 text-xs leading-relaxed whitespace-pre-wrap text-slate-800 sm:text-sm dark:text-gray-200"
                >
                    <template
                        v-for="(seg, sIdx) in parseMentions(answer.body)"
                        :key="sIdx"
                    >
                        <Link
                            v-if="seg.type === 'mention' && seg.username"
                            :href="`/u/${seg.username}`"
                            class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            {{ seg.text }}
                        </Link>
                        <span v-else>{{ seg.text }}</span>
                    </template>
                </div>

                <!-- Answer Image (if any) -->
                <div v-if="answer.image_url" class="mt-3">
                    <img
                        :src="answer.image_url"
                        alt="Answer attachment"
                        @click="
                            openImageModal(
                                answer.image_url,
                                'Answer attachment',
                            )
                        "
                        class="max-h-64 cursor-zoom-in rounded-xl border border-slate-200 object-contain shadow-2xs transition hover:opacity-95 dark:border-gray-800"
                    />
                </div>

                <!-- Direct Answer Action Row -->
                <div
                    class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-2.5 dark:border-gray-800/80"
                >
                    <ForumVoteButtons
                        votableType="answer"
                        :votableId="answer.id"
                        :initialUpvotes="answer.upvotes_count"
                        :initialDownvotes="answer.downvotes_count"
                        :initialUserVote="answer.user_vote"
                        direction="horizontal"
                        size="sm"
                    />

                    <div class="flex flex-wrap items-center gap-1">
                        <!-- Moderator Suspend Author Button -->
                        <button
                            v-if="
                                answer.user &&
                                (can('manage chat') || can('manage forums'))
                            "
                            type="button"
                            @click="openBanModalForUser(answer.user)"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                            :title="`Moderate / Suspend @${answer.user.username}`"
                        >
                            <Ban class="h-3.5 w-3.5" />
                            <span>Suspend</span>
                        </button>

                        <button
                            v-if="!user || user.id !== answer.user_id"
                            type="button"
                            @click="
                                openReportModal(
                                    'answer',
                                    answer.id,
                                    post.title,
                                    answer.user?.name,
                                    answer.body,
                                )
                            "
                            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-500 transition hover:bg-amber-50 hover:text-amber-700 dark:text-gray-400 dark:hover:bg-amber-950/40 dark:hover:text-amber-300"
                            title="Report answer"
                        >
                            <Flag class="h-3.5 w-3.5 text-amber-500" />
                            <span>Report</span>
                        </button>

                        <button
                            v-if="!post.is_locked && commentsEnabled !== false"
                            type="button"
                            @click="
                                openReplyForm(
                                    answer.id,
                                    answer.user?.username,
                                    answer.user_id,
                                )
                            "
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800"
                        >
                            <Reply class="h-3.5 w-3.5" />
                            <span>Reply</span>
                        </button>
                    </div>
                </div>

                <!-- Nested Replies List (Depth 1) -->
                <div
                    v-if="answer.replies && answer.replies.length > 0"
                    class="space-y-3 pt-3 pl-4 sm:pl-8"
                >
                    <div
                        v-for="reply in answer.replies"
                        :key="reply.id"
                        class="rounded-xl bg-slate-50/80 p-3 text-xs dark:bg-gray-800/50"
                    >
                        <!-- Reply Author Row -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <Link
                                    v-if="reply.user?.username"
                                    :href="`/u/${reply.user.username}`"
                                    class="inline-flex items-center gap-1.5 font-semibold text-slate-800 hover:text-indigo-600 hover:underline dark:text-gray-200 dark:hover:text-indigo-400"
                                >
                                    <div
                                        class="flex h-5 w-5 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-[10px] font-bold text-slate-700 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        <img
                                            v-if="reply.user.image_url"
                                            :src="reply.user.image_url"
                                            :alt="reply.user.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <span v-else>{{
                                            reply.user.name.charAt(0)
                                        }}</span>
                                    </div>
                                    <span>{{ reply.user.name }}</span>
                                </Link>
                                <span
                                    v-else
                                    class="font-semibold text-slate-800 dark:text-gray-200"
                                >
                                    {{ reply.user?.name || 'Anonymous' }}
                                </span>

                                <span class="text-slate-400 dark:text-gray-500"
                                    >•</span
                                >
                                <span
                                    class="text-slate-400 dark:text-gray-500"
                                    >{{ timeAgo(reply.created_at) }}</span
                                >
                            </div>

                            <!-- Delete reply -->
                            <button
                                v-if="
                                    user &&
                                    (user.id === reply.user_id ||
                                        can('manage forums'))
                                "
                                type="button"
                                @click="deleteAnswer(reply.id)"
                                class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-950/30 dark:hover:text-rose-400"
                                aria-label="Delete reply"
                            >
                                <Trash2 class="h-3 w-3" />
                            </button>
                        </div>

                        <!-- Reply Body -->
                        <div
                            class="prose dark:prose-invert mt-1.5 text-xs leading-relaxed whitespace-pre-wrap text-slate-700 dark:text-gray-300"
                        >
                            <template
                                v-for="(seg, sIdx) in parseMentions(reply.body)"
                                :key="sIdx"
                            >
                                <Link
                                    v-if="
                                        seg.type === 'mention' && seg.username
                                    "
                                    :href="`/u/${seg.username}`"
                                    class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                >
                                    {{ seg.text }}
                                </Link>
                                <span v-else>{{ seg.text }}</span>
                            </template>
                        </div>

                        <!-- Reply Image (if any) -->
                        <div v-if="reply.image_url" class="mt-2">
                            <img
                                :src="reply.image_url"
                                alt="Reply attachment"
                                @click="
                                    openImageModal(
                                        reply.image_url,
                                        'Reply attachment',
                                    )
                                "
                                class="max-h-48 cursor-zoom-in rounded-lg border border-slate-200 object-contain shadow-2xs transition hover:opacity-95 dark:border-gray-700"
                            />
                        </div>

                        <!-- Reply Actions -->
                        <div
                            class="mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200/50 pt-1.5 dark:border-gray-700/50"
                        >
                            <ForumVoteButtons
                                votableType="answer"
                                :votableId="reply.id"
                                :initialUpvotes="reply.upvotes_count"
                                :initialDownvotes="reply.downvotes_count"
                                :initialUserVote="reply.user_vote"
                                direction="horizontal"
                                size="sm"
                            />

                            <div class="flex flex-wrap items-center gap-1">
                                <!-- Moderator Suspend Author Button -->
                                <button
                                    v-if="
                                        reply.user &&
                                        (can('manage chat') ||
                                            can('manage forums'))
                                    "
                                    type="button"
                                    @click="openBanModalForUser(reply.user)"
                                    class="inline-flex cursor-pointer items-center gap-1 px-1.5 py-0.5 text-[11px] font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300"
                                    :title="`Moderate / Suspend @${reply.user.username}`"
                                >
                                    <Ban class="h-3 w-3" />
                                    <span>Suspend</span>
                                </button>

                                <button
                                    v-if="!user || user.id !== reply.user_id"
                                    type="button"
                                    @click="
                                        openReportModal(
                                            'answer',
                                            reply.id,
                                            post.title,
                                            reply.user?.name,
                                            reply.body,
                                        )
                                    "
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[11px] font-semibold text-slate-400 hover:text-amber-600 dark:text-gray-500 dark:hover:text-amber-400"
                                    title="Report reply"
                                >
                                    <Flag class="h-3 w-3 text-amber-500" />
                                    <span>Report</span>
                                </button>

                                <button
                                    v-if="
                                        !post.is_locked &&
                                        commentsEnabled !== false
                                    "
                                    type="button"
                                    @click="
                                        openReplyForm(
                                            answer.id,
                                            reply.user?.username,
                                            reply.user_id,
                                        )
                                    "
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    <CornerDownRight class="h-3 w-3" />
                                    <span>Reply</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inline Reply Box (Shown under this root answer) -->
                <div
                    v-if="activeReplyParentId === answer.id"
                    class="mt-3 rounded-xl border border-indigo-100 bg-indigo-50/40 p-3 dark:border-indigo-900/40 dark:bg-indigo-950/20"
                >
                    <form @submit.prevent="submitReply">
                        <textarea
                            v-model="replyForm.body"
                            rows="3"
                            required
                            placeholder="Write your reply..."
                            class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs text-slate-900 shadow-2xs placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        ></textarea>

                        <p
                            v-if="replyForm.errors.body"
                            class="mt-1 text-xs text-rose-500"
                        >
                            {{ replyForm.errors.body }}
                        </p>

                        <!-- Attached Reply Image Preview -->
                        <div
                            v-if="replyImagePreview"
                            class="relative mt-2 inline-block"
                        >
                            <img
                                :src="replyImagePreview"
                                alt="Reply preview"
                                class="max-h-28 rounded-lg border border-slate-200 object-contain dark:border-gray-700"
                            />
                            <button
                                type="button"
                                @click="removeReplyImage"
                                class="absolute top-1 right-1 rounded-md bg-slate-900/80 p-1 text-white hover:bg-rose-600"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </div>

                        <!-- Reply Form Actions -->
                        <div class="mt-2 flex items-center justify-between">
                            <div>
                                <input
                                    ref="replyFileInputRef"
                                    type="file"
                                    :id="'reply-image-' + answer.id"
                                    accept="image/jpeg,image/png,image/jpg,image/webp"
                                    @click="
                                        (e) =>
                                            ((
                                                e.target as HTMLInputElement
                                            ).value = '')
                                    "
                                    @change="handleReplyFileChange"
                                    class="hidden"
                                />
                                <label
                                    :for="'reply-image-' + answer.id"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    <ImageIcon class="h-3 w-3" />
                                    <span>Image</span>
                                </label>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="cancelReply"
                                    class="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 dark:text-gray-400 dark:hover:bg-gray-800"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="replyForm.processing"
                                    class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    <Send class="h-3 w-3" />
                                    <span>{{
                                        replyForm.processing
                                            ? 'Replying...'
                                            : 'Reply'
                                    }}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Answers Pagination -->
        <div
            v-if="answers.links && answers.links.length > 3"
            class="mb-10 border-t border-slate-100 pt-5 dark:border-gray-800"
        >
            <div class="flex items-center justify-center gap-1.5">
                <component
                    :is="link.url ? Link : 'span'"
                    v-for="(link, index) in answers.links"
                    :key="index"
                    :href="link.url"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                    :class="{
                        'bg-indigo-600 text-white': link.active,
                        'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300':
                            !link.active && link.url,
                        'cursor-not-allowed text-slate-300 dark:text-gray-700':
                            !link.url,
                    }"
                >
                    <span v-html="link.label"></span>
                </component>
            </div>
        </div>

        <!-- Main Answer Submission Box -->
        <div
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-7 dark:border-gray-800 dark:bg-gray-900"
        >
            <h3
                class="mb-4 text-base font-bold text-slate-900 sm:text-lg dark:text-gray-100"
            >
                Your Answer
            </h3>

            <!-- Locked Discussion Notice -->
            <div
                v-if="post.is_locked"
                class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50/70 p-4 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-300"
            >
                <Lock
                    class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
                />
                <span
                    >This discussion is locked. New answers and replies are
                    disabled.</span
                >
            </div>

            <!-- Comments Disabled Notice -->
            <div
                v-else-if="commentsEnabled === false"
                class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300"
            >
                <Lock class="h-4 w-4 shrink-0 text-slate-400" />
                <span>{{
                    disabledReason ||
                    'Submitting new answers is temporarily paused for maintenance.'
                }}</span>
            </div>

            <!-- Suspended Notice -->
            <div
                v-else-if="isUserBanned"
                class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-xs text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300"
            >
                <Ban
                    class="h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                />
                <span
                    >Your account is temporarily suspended from community
                    participation. Posting answers and replies is
                    disabled.</span
                >
            </div>

            <!-- Authenticated Answer Form -->
            <form
                v-else-if="user"
                @submit.prevent="submitAnswer"
                class="space-y-4"
            >
                <div>
                    <textarea
                        v-model="answerForm.body"
                        rows="5"
                        required
                        placeholder="Write a clear, detailed answer to help this student..."
                        class="w-full rounded-xl border border-slate-200 bg-white p-3.5 text-sm text-slate-900 shadow-2xs transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500"
                    ></textarea>
                    <p
                        v-if="answerForm.errors.body"
                        class="mt-1.5 text-xs text-rose-500"
                    >
                        {{ answerForm.errors.body }}
                    </p>
                </div>

                <!-- Answer Image Upload & Preview -->
                <div>
                    <div v-if="!answerImagePreview">
                        <input
                            ref="answerFileInputRef"
                            type="file"
                            id="answer-image-file"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            @change="handleAnswerFileChange"
                            class="hidden"
                        />
                        <label
                            for="answer-image-file"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            <ImageIcon
                                class="h-4 w-4 text-slate-400 dark:text-gray-500"
                            />
                            <span>Attach Image (Optional)</span>
                        </label>
                    </div>

                    <div v-else class="relative inline-block">
                        <img
                            :src="answerImagePreview"
                            alt="Answer preview"
                            class="max-h-40 rounded-xl border border-slate-200 object-contain shadow-xs dark:border-gray-800"
                        />
                        <button
                            type="button"
                            @click="removeAnswerImage"
                            class="absolute top-1.5 right-1.5 rounded-lg bg-slate-900/80 p-1 text-white shadow backdrop-blur-xs hover:bg-rose-600"
                            aria-label="Remove image"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <p
                        v-if="answerForm.errors.image"
                        class="mt-1.5 text-xs text-rose-500"
                    >
                        {{ answerForm.errors.image }}
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-2">
                    <button
                        type="submit"
                        :disabled="answerForm.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:scale-[0.98] disabled:opacity-50"
                    >
                        <Send class="h-4 w-4" />
                        <span>{{
                            answerForm.processing
                                ? 'Posting...'
                                : 'Post Your Answer'
                        }}</span>
                    </button>
                </div>
            </form>

            <!-- Guest Sign-in Prompt -->
            <div
                v-else
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center dark:border-gray-800 dark:bg-gray-950/40"
            >
                <HelpCircle
                    class="mb-2 h-7 w-7 text-slate-400 dark:text-gray-500"
                />
                <h4 class="text-sm font-bold text-slate-900 dark:text-gray-100">
                    Sign in to answer this question
                </h4>
                <p
                    class="mt-1 max-w-sm text-xs text-slate-500 dark:text-gray-400"
                >
                    Join the conversation, share your academic solution, and
                    help fellow students.
                </p>
                <button
                    type="button"
                    @click="showAuthModal = true"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700"
                >
                    Sign In
                </button>
            </div>
        </div>

        <!-- Reusable Components -->
        <ImageViewerModal
            v-model="showImageModal"
            :src="modalImageSrc"
            :alt="modalImageAlt"
        />

        <!-- Upvoters Modal (Shows list of upvoters; downvotes list is intentionally hidden) -->
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
                    v-if="showUpvotersModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs dark:bg-black/50"
                    @click.self="showUpvotersModal = false"
                >
                    <div
                        class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                    >
                        <button
                            @click="showUpvotersModal = false"
                            class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                        >
                            <X class="h-4 w-4" />
                        </button>

                        <div class="mb-4 flex items-center gap-2.5">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                            >
                                <ArrowBigUp
                                    class="h-4.5 w-4.5 fill-indigo-600 text-indigo-600 dark:fill-indigo-400 dark:text-indigo-400"
                                />
                            </div>
                            <div>
                                <h3
                                    class="text-sm font-bold text-slate-900 dark:text-gray-100"
                                >
                                    Upvoted by
                                </h3>
                                <p
                                    class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                                >
                                    {{ localUpvoters.length }}
                                    {{
                                        localUpvoters.length === 1
                                            ? 'person'
                                            : 'people'
                                    }}
                                    upvoted this question
                                </p>
                            </div>
                        </div>

                        <div
                            class="-mx-1 max-h-72 divide-y divide-slate-100 overflow-y-auto px-1 dark:divide-gray-800/80"
                        >
                            <div
                                v-if="localUpvoters.length === 0"
                                class="py-6 text-center text-xs text-slate-500 dark:text-gray-400"
                            >
                                No upvotes yet.
                            </div>

                            <UserListItem
                                v-for="upvoter in localUpvoters"
                                :key="upvoter.id"
                                :user="upvoter"
                                theme="indigo"
                            />
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Report Modal -->
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
                    v-if="reportingTarget"
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs dark:bg-black/60"
                    @click.self="closeReportModal"
                >
                    <div
                        class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl sm:p-6 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 pb-3.5 dark:border-gray-800"
                        >
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                                >
                                    <Flag class="h-4 w-4" />
                                </div>
                                <div>
                                    <h3
                                        class="text-sm font-bold text-slate-900 dark:text-gray-100"
                                    >
                                        Report
                                        {{
                                            reportingTarget.type === 'post'
                                                ? 'Question'
                                                : 'Answer'
                                        }}
                                    </h3>
                                    <p
                                        class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                                    >
                                        Help keep the community helpful and
                                        constructive
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeReportModal"
                                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Success State -->
                        <div
                            v-if="reportSuccessMessage"
                            class="my-6 flex flex-col items-center justify-center py-4 text-center"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                            >
                                <Check class="h-6 w-6" />
                            </div>
                            <p
                                class="mt-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300"
                            >
                                {{ reportSuccessMessage }}
                            </p>
                        </div>

                        <!-- Form Content -->
                        <form
                            v-else
                            @submit.prevent="submitReport"
                            class="mt-4 space-y-4"
                        >
                            <div
                                v-if="reportErrorMessage"
                                class="rounded-xl border border-rose-200 bg-rose-50/80 p-3 text-xs font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/50 dark:text-rose-300"
                            >
                                {{ reportErrorMessage }}
                            </div>

                            <div
                                class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-gray-800 dark:bg-gray-800/60"
                            >
                                <div
                                    class="flex items-center justify-between text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    <span
                                        class="font-semibold text-slate-700 dark:text-gray-300"
                                    >
                                        {{ reportingTarget.authorName }}
                                    </span>
                                    <span class="capitalize">{{
                                        reportingTarget.type
                                    }}</span>
                                </div>
                                <p
                                    class="mt-1.5 line-clamp-3 text-xs text-slate-700 dark:text-gray-300"
                                >
                                    "{{ reportingTarget.contentPreview }}"
                                </p>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                                >
                                    Why are you reporting this content?
                                </label>
                                <select
                                    v-model="reportReason"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-2 text-xs font-medium text-slate-800 transition outline-none focus:border-indigo-500 focus:bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-400 dark:focus:bg-gray-800"
                                >
                                    <option
                                        v-for="reason in reportReasons"
                                        :key="reason"
                                        :value="reason"
                                        class="bg-white text-slate-800 dark:bg-gray-800 dark:text-gray-200"
                                    >
                                        {{ reason }}
                                    </option>
                                </select>
                            </div>

                            <div
                                class="flex items-center justify-end gap-2 pt-2"
                            >
                                <button
                                    type="button"
                                    @click="closeReportModal"
                                    class="cursor-pointer rounded-xl border border-slate-200 bg-transparent px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmittingReport"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-700 active:scale-95 disabled:opacity-50 dark:bg-rose-500 dark:hover:bg-rose-600"
                                >
                                    <Loader2
                                        v-if="isSubmittingReport"
                                        class="h-3.5 w-3.5 animate-spin"
                                    />
                                    <span>Submit Report</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <AuthModal
            v-model="showAuthModal"
            title="Sign in required"
            :message="authModalMessage"
        />

        <!-- Suspension Modal -->
        <ChatBanModal
            :is-open="isBanModalOpen"
            :user="selectedBanUser"
            @close="isBanModalOpen = false"
        />
    </main>
</template>
