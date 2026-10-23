<script setup lang="ts">
import { ArrowBigUp, ArrowBigDown } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import AuthModal from '@/components/AuthModal.vue';
import { useAuth } from '@/lib/useAuth';
import { getCsrfToken } from '@/lib/useCsrf';

const props = withDefaults(
    defineProps<{
        resourceId: number;
        initialUpvotes?: number;
        initialDownvotes?: number;
        initialScore?: number;
        initialUserVote?: number | null;
        direction?: 'horizontal' | 'vertical';
        size?: 'sm' | 'md';
    }>(),
    {
        initialUpvotes: 0,
        initialDownvotes: 0,
        initialScore: 0,
        initialUserVote: null,
        direction: 'horizontal',
        size: 'md',
    },
);

const emit = defineEmits<{
    (
        e: 'voted',
        payload: {
            resourceId: number;
            userVote: number | null;
            voteScore: number;
            upvotes: number;
            downvotes: number;
        },
    ): void;
}>();

const { user, requireAuth, showAuthModal, authModalMessage } = useAuth();
const isUserBanned = computed(() => Boolean(user.value?.is_banned));

const upvotes = ref(props.initialUpvotes ?? 0);
const downvotes = ref(props.initialDownvotes ?? 0);
const userVote = ref<number | null>(props.initialUserVote ?? null);
const isSubmitting = ref(false);

watch(
    () => props.initialUpvotes,
    (val) => {
        upvotes.value = val ?? 0;
    },
);

watch(
    () => props.initialDownvotes,
    (val) => {
        downvotes.value = val ?? 0;
    },
);

watch(
    () => props.initialUserVote,
    (val) => {
        userVote.value = val ?? null;
    },
);

const vote = async (value: 1 | -1) => {
    if (!requireAuth('ভোট দিতে অনুগ্রহ করে সাইন ইন করুন')) {
        return;
    }

    if (isUserBanned.value || isSubmitting.value) {
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

    // Apply optimistic updates
    userVote.value = newVote;
    upvotes.value = newUpvotes;
    downvotes.value = newDownvotes;
    isSubmitting.value = true;

    emit('voted', {
        resourceId: props.resourceId,
        userVote: newVote,
        voteScore: newUpvotes - newDownvotes,
        upvotes: newUpvotes,
        downvotes: newDownvotes,
    });

    try {
        const response = await fetch(`/resources/${props.resourceId}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ value }),
        });

        if (!response.ok) {
            throw new Error(`Vote failed with status ${response.status}`);
        }

        const data = await response.json();

        if (data && typeof data.vote_score === 'number') {
            upvotes.value = data.upvotes_count ?? newUpvotes;
            downvotes.value = data.downvotes_count ?? newDownvotes;
            userVote.value = data.user_vote ?? null;
            emit('voted', {
                resourceId: props.resourceId,
                userVote: data.user_vote ?? null,
                voteScore: data.vote_score,
                upvotes: data.upvotes_count ?? newUpvotes,
                downvotes: data.downvotes_count ?? newDownvotes,
            });
        }
    } catch (err) {
        // Rollback on failure
        console.error('Failed to submit vote:', err);
        userVote.value = prevVote;
        upvotes.value = prevUpvotes;
        downvotes.value = prevDownvotes;
        emit('voted', {
            resourceId: props.resourceId,
            userVote: prevVote,
            voteScore: prevUpvotes - prevDownvotes,
            upvotes: prevUpvotes,
            downvotes: prevDownvotes,
        });
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div
        class="inline-flex items-center select-none"
        :class="[
            direction === 'vertical' ? 'flex-col gap-1' : 'flex-row gap-1.5',
        ]"
        @click.stop.prevent
    >
        <!-- Upvote Button with Count (Forum Style) -->
        <button
            type="button"
            @click.stop.prevent="vote(1)"
            :disabled="isUserBanned || isSubmitting"
            :title="isUserBanned ? 'অ্যাকাউন্ট স্থগিত রয়েছে' : 'আপভোট করুন'"
            aria-label="Upvote resource"
            class="inline-flex cursor-pointer items-center justify-center rounded-xl transition-all active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
            :class="[
                direction === 'vertical'
                    ? 'min-w-[34px] flex-col px-2 py-1'
                    : 'flex-row gap-1.5 px-3 py-1.5',
                userVote === 1
                    ? 'bg-indigo-50 text-indigo-600 ring-1 ring-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-400 dark:ring-indigo-500/30'
                    : 'border border-slate-200/80 bg-white text-slate-600 hover:border-indigo-200 hover:bg-indigo-50/40 hover:text-indigo-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-indigo-400',
                size === 'sm' ? 'text-xs' : 'text-xs font-semibold sm:text-sm',
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
                        : 'text-slate-600 dark:text-gray-300',
                ]"
            >
                {{ upvotes }}
            </span>
        </button>

        <!-- Downvote Button with Count (Forum Style) -->
        <button
            type="button"
            @click.stop.prevent="vote(-1)"
            :disabled="isUserBanned || isSubmitting"
            :title="isUserBanned ? 'অ্যাকাউন্ট স্থগিত রয়েছে' : 'ডাউনভোট করুন'"
            aria-label="Downvote resource"
            class="inline-flex cursor-pointer items-center justify-center rounded-xl transition-all active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
            :class="[
                direction === 'vertical'
                    ? 'min-w-[34px] flex-col px-2 py-1'
                    : 'flex-row gap-1.5 px-3 py-1.5',
                userVote === -1
                    ? 'bg-rose-50 text-rose-600 ring-1 ring-rose-200 dark:bg-rose-500/20 dark:text-rose-400 dark:ring-rose-500/30'
                    : 'border border-slate-200/80 bg-white text-slate-600 hover:border-rose-200 hover:bg-rose-50/40 hover:text-rose-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-rose-400',
                size === 'sm' ? 'text-xs' : 'text-xs font-semibold sm:text-sm',
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
                        : 'text-slate-600 dark:text-gray-300',
                ]"
            >
                {{ downvotes }}
            </span>
        </button>

        <AuthModal v-model="showAuthModal" :message="authModalMessage" />
    </div>
</template>
