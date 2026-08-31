<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ArrowBigUp, ArrowBigDown } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AuthModal from '@/components/AuthModal.vue';

const props = withDefaults(
    defineProps<{
        votableType: 'post' | 'answer';
        votableId: number | string;
        initialUpvotes?: number;
        initialDownvotes?: number;
        initialUserVote?: number | null;
        direction?: 'vertical' | 'horizontal';
        size?: 'sm' | 'md';
    }>(),
    {
        initialUpvotes: 0,
        initialDownvotes: 0,
        initialUserVote: null,
        direction: 'vertical',
        size: 'md',
    },
);

const emit = defineEmits<{
    (
        e: 'voted',
        payload: {
            value: 1 | -1;
            userVote: number | null;
            upvotesDelta: number;
        },
    ): void;
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);
const isUserBanned = computed(() => Boolean(user.value?.is_banned));
const showAuthModal = ref(false);

const upvotes = ref(props.initialUpvotes || 0);
const downvotes = ref(props.initialDownvotes || 0);
const userVote = ref<number | null>(props.initialUserVote);

watch(
    () => props.initialUpvotes,
    (val) => {
        upvotes.value = val || 0;
    },
);

watch(
    () => props.initialDownvotes,
    (val) => {
        downvotes.value = val || 0;
    },
);

watch(
    () => props.initialUserVote,
    (val) => {
        userVote.value = val;
    },
);

const isVoting = ref(false);

const vote = (value: 1 | -1) => {
    if (!user.value) {
        showAuthModal.value = true;

        return;
    }

    if (isUserBanned.value || isVoting.value) {
        return;
    }

    const prevVote = userVote.value;
    const prevUpvotes = upvotes.value;
    const prevDownvotes = downvotes.value;

    let newVote: number | null = null;
    let newUpvotes = prevUpvotes;
    let newDownvotes = prevDownvotes;

    if (value === 1) {
        if (prevVote === 1) {
            // Un-vote
            newVote = null;
            newUpvotes = Math.max(0, prevUpvotes - 1);
        } else if (prevVote === -1) {
            // Switch from downvote to upvote
            newVote = 1;
            newDownvotes = Math.max(0, prevDownvotes - 1);
            newUpvotes = prevUpvotes + 1;
        } else {
            // New upvote
            newVote = 1;
            newUpvotes = prevUpvotes + 1;
        }
    } else {
        if (prevVote === -1) {
            // Un-vote
            newVote = null;
            newDownvotes = Math.max(0, prevDownvotes - 1);
        } else if (prevVote === 1) {
            // Switch from upvote to downvote
            newVote = -1;
            newUpvotes = Math.max(0, prevUpvotes - 1);
            newDownvotes = prevDownvotes + 1;
        } else {
            // New downvote
            newVote = -1;
            newDownvotes = prevDownvotes + 1;
        }
    }

    userVote.value = newVote;
    upvotes.value = newUpvotes;
    downvotes.value = newDownvotes;
    isVoting.value = true;

    emit('voted', {
        value,
        userVote: newVote,
        upvotesDelta: newUpvotes - prevUpvotes,
    });

    const url =
        props.votableType === 'post'
            ? `/forum/posts/${props.votableId}/vote`
            : `/forum/answers/${props.votableId}/vote`;

    router.post(
        url,
        { value },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isVoting.value = false;
            },
            onError: () => {
                // Rollback on error
                userVote.value = prevVote;
                upvotes.value = prevUpvotes;
                downvotes.value = prevDownvotes;
                emit('voted', {
                    value,
                    userVote: prevVote,
                    upvotesDelta: prevUpvotes - newUpvotes,
                });
            },
        },
    );
};
</script>

<template>
    <div
        class="inline-flex items-center select-none"
        :class="[
            direction === 'vertical' ? 'flex-col gap-1' : 'flex-row gap-1.5',
        ]"
    >
        <!-- Upvote Button with Count -->
        <button
            type="button"
            @click.stop="vote(1)"
            :disabled="isUserBanned"
            :title="
                isUserBanned ? 'Voting is disabled while suspended' : 'Upvote'
            "
            :aria-label="'Upvote ' + votableType"
            class="inline-flex items-center justify-center rounded-xl transition-colors active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
            :class="[
                direction === 'vertical'
                    ? 'min-w-[34px] flex-col px-2 py-1'
                    : 'flex-row gap-1 px-2.5 py-1',
                userVote === 1
                    ? 'bg-indigo-50 text-indigo-600 ring-1 ring-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-400 dark:ring-indigo-500/30'
                    : 'text-slate-500 hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-indigo-400',
                size === 'sm' ? 'text-xs' : 'text-xs sm:text-sm',
            ]"
        >
            <ArrowBigUp
                :class="[
                    size === 'sm' ? 'h-4 w-4' : 'h-4 w-4 sm:h-5 sm:w-5',
                    userVote === 1 ? 'fill-current' : '',
                ]"
            />
            <span
                class="font-bold tabular-nums"
                :class="[
                    userVote === 1
                        ? 'text-indigo-600 dark:text-indigo-400'
                        : 'text-slate-600 dark:text-gray-400',
                ]"
            >
                {{ upvotes }}
            </span>
        </button>

        <!-- Downvote Button with Count -->
        <button
            type="button"
            @click.stop="vote(-1)"
            :disabled="isUserBanned"
            :title="
                isUserBanned ? 'Voting is disabled while suspended' : 'Downvote'
            "
            :aria-label="'Downvote ' + votableType"
            class="inline-flex items-center justify-center rounded-xl transition-colors active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
            :class="[
                direction === 'vertical'
                    ? 'min-w-[34px] flex-col px-2 py-1'
                    : 'flex-row gap-1 px-2.5 py-1',
                userVote === -1
                    ? 'bg-rose-50 text-rose-600 ring-1 ring-rose-200 dark:bg-rose-500/20 dark:text-rose-400 dark:ring-rose-500/30'
                    : 'text-slate-500 hover:bg-slate-100 hover:text-rose-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-rose-400',
                size === 'sm' ? 'text-xs' : 'text-xs sm:text-sm',
            ]"
        >
            <ArrowBigDown
                :class="[
                    size === 'sm' ? 'h-4 w-4' : 'h-4 w-4 sm:h-5 sm:w-5',
                    userVote === -1 ? 'fill-current' : '',
                ]"
            />
            <span
                class="font-bold tabular-nums"
                :class="[
                    userVote === -1
                        ? 'text-rose-600 dark:text-rose-400'
                        : 'text-slate-600 dark:text-gray-400',
                ]"
            >
                {{ downvotes }}
            </span>
        </button>

        <AuthModal
            v-model="showAuthModal"
            title="Sign in required"
            message="Please sign in to vote on forum posts and answers."
        />
    </div>
</template>
