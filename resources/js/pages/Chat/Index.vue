<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Send, Trash2, Lock, Loader2, LogIn, ArrowDown, ShieldAlert, Pencil, Flag, Check, X, Reply } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';
import { getEcho } from '@/lib/echo';
import { usePermissions } from '@/lib/usePermissions';

interface ChatUser {
    id: number;
    name: string;
    username: string;
    image_url: string | null;
    institution: string | null;
    is_verified: boolean;
    roles: string[];
}

interface ChatMessageItem {
    id: number;
    content: string;
    reply_to_id?: number | null;
    reply_to_content?: string | null;
    created_at: string;
    user: ChatUser;
}

const props = defineProps<{
    chatState: {
        enabled: boolean;
        audience: string;
        cooldown_seconds: number;
        max_messages?: number;
        max_length?: number;
        can_post: boolean;
        reason: string | null;
        can_delete: boolean;
        messages: ChatMessageItem[];
        pusher_key?: string;
        pusher_cluster?: string;
    };
}>();

const page = usePage();
const { can } = usePermissions();
const currentUser = computed(() => page.props.auth?.user);

const maxMessagesLimit = computed(() => props.chatState.max_messages ?? 200);
const maxLengthLimit = computed(() => props.chatState.max_length ?? 280);
const messages = ref<ChatMessageItem[]>(props.chatState.messages || []);
const inputContent = ref('');
const isSending = ref(false);
const canPost = ref(props.chatState.can_post);
const restrictionReason = ref(props.chatState.reason);
const canDelete = ref(props.chatState.can_delete);
const activeCooldownSeconds = ref(props.chatState.cooldown_seconds ?? 30);

const messagesContainerRef = ref<HTMLElement | null>(null);
const messageInputRef = ref<HTMLInputElement | null>(null);
const showScrollButton = ref(false);
const highlightedMessageId = ref<number | null>(null);

// Reply State
const activeReplyTo = ref<ChatMessageItem | null>(null);

const startReply = (msg: ChatMessageItem) => {
    activeReplyTo.value = msg;
    nextTick(() => {
        messageInputRef.value?.focus();
    });
};

const cancelReply = () => {
    activeReplyTo.value = null;
};

const scrollToMessage = (messageId: number) => {
    const el = document.getElementById(`chat-msg-${messageId}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        highlightedMessageId.value = messageId;
        setTimeout(() => {
            if (highlightedMessageId.value === messageId) {
                highlightedMessageId.value = null;
            }
        }, 2000);
    }
};

// Cooldown logic
const cooldownSeconds = ref(0);
let cooldownInterval: ReturnType<typeof setInterval> | null = null;
const COOLDOWN_KEY = 'hscstack_chat_cooldown_until';

const initCooldown = () => {
    try {
        const storedUntil = localStorage.getItem(COOLDOWN_KEY);

        if (storedUntil) {
            const until = parseInt(storedUntil, 10);
            const remaining = Math.ceil((until - Date.now()) / 1000);

            if (remaining > 0) {
                startCooldown(remaining);
            } else {
                localStorage.removeItem(COOLDOWN_KEY);
            }
        }
    } catch {
        // ignore
    }
};

const startCooldown = (seconds: number) => {
    if (seconds <= 0) {
        return;
    }

    cooldownSeconds.value = seconds;
    const until = Date.now() + seconds * 1000;

    try {
        localStorage.setItem(COOLDOWN_KEY, until.toString());
    } catch {
        // ignore
    }

    if (cooldownInterval) {
        clearInterval(cooldownInterval);
    }

    cooldownInterval = setInterval(() => {
        if (cooldownSeconds.value > 1) {
            cooldownSeconds.value--;
        } else {
            cooldownSeconds.value = 0;

            if (cooldownInterval) {
                clearInterval(cooldownInterval);
            }

            localStorage.removeItem(COOLDOWN_KEY);
        }
    }, 1000);
};

const handleScroll = () => {
    if (!messagesContainerRef.value) {
        return;
    }

    const { scrollTop, scrollHeight, clientHeight } =
        messagesContainerRef.value;
    const distanceToBottom = scrollHeight - scrollTop - clientHeight;
    showScrollButton.value = distanceToBottom > 150;
};

const scrollToBottom = (smooth = true) => {
    nextTick(() => {
        if (messagesContainerRef.value) {
            messagesContainerRef.value.scrollTo({
                top: messagesContainerRef.value.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto',
            });
            showScrollButton.value = false;
        }
    });
};

const setupRealtime = () => {
    const echo = getEcho(
        props.chatState.pusher_key,
        props.chatState.pusher_cluster,
    );

    if (!echo) {
        return;
    }

    echo.channel('global-chat')
        .stopListening('.message.sent')
        .stopListening('.message.deleted')
        .listen('.message.sent', (e: { message: ChatMessageItem }) => {
            if (e && e.message) {
                if (!messages.value.some((m) => m.id === e.message.id)) {
                    messages.value.push(e.message);

                    if (messages.value.length > maxMessagesLimit.value) {
                        messages.value.shift();
                    }

                    scrollToBottom(true);
                }
            }
        })
        .listen('.message.deleted', (e: { messageId: number }) => {
            if (e && e.messageId) {
                messages.value = messages.value.filter(
                    (m) => m.id !== e.messageId,
                );
            }
        });
};

const sendMessage = async () => {
    if (
        !inputContent.value.trim() ||
        isSending.value ||
        cooldownSeconds.value > 0
    ) {
        return;
    }

    const content = inputContent.value.trim();

    if (content.length > maxLengthLimit.value) {
        return;
    }

    isSending.value = true;

    try {
        const token = (
            document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
        )?.content;
        const replyToId = activeReplyTo.value ? activeReplyTo.value.id : null;
        const res = await fetch('/api/chat/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify({
                content,
                reply_to_id: replyToId,
            }),
        });

        if (res.ok) {
            const newMsg = await res.json();

            if (!messages.value.some((m) => m.id === newMsg.id)) {
                messages.value.push(newMsg);

                if (messages.value.length > maxMessagesLimit.value) {
                    messages.value.shift();
                }
            }

            inputContent.value = '';
            activeReplyTo.value = null;

            if (activeCooldownSeconds.value > 0) {
                startCooldown(activeCooldownSeconds.value);
            }

            scrollToBottom(true);
        } else if (res.status === 429) {
            const data = await res.json();
            startCooldown(data.retry_after || activeCooldownSeconds.value);
        } else {
            const err = await res.json();
            alert(err.message || 'Failed to send message.');
        }
    } catch {
        alert('Network error. Please try again.');
    } finally {
        isSending.value = false;
    }
};

const deleteMessage = async (messageId: number) => {
    if (!confirm('Are you sure you want to delete this message?')) {
        return;
    }

    try {
        const token = (
            document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
        )?.content;
        const res = await fetch(`/api/chat/messages/${messageId}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token || '',
            },
        });

        if (res.ok) {
            messages.value = messages.value.filter((m) => m.id !== messageId);
        }
    } catch (e) {
        console.error('Failed to delete message', e);
    }
};

// Report message state & methods
const reportingMessage = ref<ChatMessageItem | null>(null);
const reportReason = ref('Inappropriate message or conduct');
const isSubmittingReport = ref(false);
const reportSuccessMessage = ref<string | null>(null);
const reportErrorMessage = ref<string | null>(null);

const reportReasons = [
    'Inappropriate message or conduct',
    'Spam or advertisement',
    'Harassment or hate speech',
    'False information / Exam leak attempt',
    'Other',
];

const openReportModal = (message: ChatMessageItem) => {
    if (!currentUser.value) {
        alert('Please sign in to report a message.');
        return;
    }
    reportingMessage.value = message;
    reportReason.value = 'Inappropriate message or conduct';
    reportSuccessMessage.value = null;
    reportErrorMessage.value = null;
};

const closeReportModal = () => {
    reportingMessage.value = null;
    reportSuccessMessage.value = null;
    reportErrorMessage.value = null;
    isSubmittingReport.value = false;
};

const submitReport = async () => {
    if (!reportingMessage.value || isSubmittingReport.value) return;

    isSubmittingReport.value = true;
    reportSuccessMessage.value = null;
    reportErrorMessage.value = null;

    try {
        const token = (
            document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
        )?.content;

        const res = await fetch('/api/chat/reports', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify({
                reported_user_id: reportingMessage.value.user.id,
                reported_user_name: reportingMessage.value.user.name,
                reported_user_username: reportingMessage.value.user.username,
                message_content: reportingMessage.value.content,
                message_sent_at: reportingMessage.value.created_at,
                reason: reportReason.value,
            }),
        });

        if (res.ok) {
            reportSuccessMessage.value = 'Thank you. The report has been sent to our moderators.';
            setTimeout(() => {
                closeReportModal();
            }, 1800);
        } else {
            const err = await res.json();
            reportErrorMessage.value = err.message || 'Failed to submit report.';
        }
    } catch {
        reportErrorMessage.value = 'Network error. Please try again.';
    } finally {
        isSubmittingReport.value = false;
    }
};

const formatTime = (isoString: string) => {
    try {
        const date = new Date(isoString);

        return date.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return '';
    }
};

const formatDateDivider = (isoString: string) => {
    try {
        const date = new Date(isoString);

        return date.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return '';
    }
};

const shouldShowDateDivider = (index: number) => {
    if (index === 0) {
        return true;
    }

    const prev = new Date(messages.value[index - 1].created_at).toDateString();
    const curr = new Date(messages.value[index].created_at).toDateString();

    return prev !== curr;
};

onMounted(() => {
    initCooldown();
    setupRealtime();
    scrollToBottom(false);
});

onUnmounted(() => {
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
    }
});
</script>

<template>
    <Head>
        <title>Global Chat</title>
        <meta
            name="description"
            content="Real-time public discussion space for HSC and SSC students."
        />
    </Head>

    <main class="mx-auto max-w-4xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <!-- Compact Page Title & Subtitle -->
        <div class="mb-3 border-b border-slate-100 pb-3 dark:border-gray-800">
            <h1
                class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
            >
                Global Chat
            </h1>
            <p
                class="mt-0.5 text-xs text-slate-500 sm:text-sm dark:text-gray-400"
            >
                অন্যান্য শিক্ষার্থীদের সাথে সরাসরি কথা বলুন ও প্রশ্ন শেয়ার করুন।
            </p>
        </div>

        <!-- Chat Container Window -->
        <div
            class="flex h-[calc(100vh-14rem)] max-h-[740px] min-h-[520px] flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs dark:border-gray-800 dark:bg-gray-900/90"
        >
            <!-- Messages Stream -->
            <div
                ref="messagesContainerRef"
                @scroll="handleScroll"
                class="relative flex-1 divide-y divide-slate-100/80 overflow-y-auto p-3 sm:p-5 dark:divide-gray-800/60"
            >
                <!-- Empty State -->
                <div
                    v-if="messages.length === 0"
                    class="flex h-full flex-col items-center justify-center p-8 text-center text-slate-400 dark:text-gray-500"
                >
                    <p
                        class="text-sm font-semibold text-slate-600 dark:text-gray-400"
                    >
                        No messages yet
                    </p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">
                        Be the first to send a message to start the
                        conversation.
                    </p>
                </div>

                <!-- Messages Feed -->
                <template v-for="(msg, idx) in messages" :key="msg.id">
                    <!-- Date Divider -->
                    <div
                        v-if="shouldShowDateDivider(idx)"
                        class="sticky top-0 z-10 my-2 flex justify-center"
                    >
                        <span
                            class="rounded-full bg-slate-100/90 px-3 py-0.5 text-[10px] font-medium text-slate-600 shadow-2xs backdrop-blur-xs dark:bg-gray-800/90 dark:text-gray-300"
                        >
                            {{ formatDateDivider(msg.created_at) }}
                        </span>
                    </div>

                    <!-- Individual Message Row (Distinctly Highlighted for currentUser) -->
                    <div
                        :id="`chat-msg-${msg.id}`"
                        class="group flex items-start gap-3 rounded-2xl p-2.5 transition-all duration-300 sm:p-3"
                        :class="[
                            currentUser &&
                            Number(currentUser.id) === Number(msg.user.id)
                                ? 'bg-indigo-50/50 dark:bg-indigo-950/20'
                                : 'hover:bg-slate-50 dark:hover:bg-gray-800/40',
                            highlightedMessageId === msg.id
                                ? 'ring-2 ring-indigo-500 bg-indigo-100/70 dark:bg-indigo-950/60'
                                : '',
                        ]"
                    >
                        <!-- User Avatar -->
                        <Link
                            :href="`/u/${msg.user.username}`"
                            class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full font-semibold transition hover:ring-2 hover:ring-indigo-400 sm:h-9 sm:w-9"
                            :class="
                                currentUser &&
                                Number(currentUser.id) === Number(msg.user.id)
                                    ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                    : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300'
                            "
                        >
                            <img
                                v-if="msg.user.image_url"
                                :src="msg.user.image_url"
                                :alt="msg.user.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="text-xs uppercase">
                                {{ msg.user.name?.charAt(0) || 'U' }}
                            </span>
                        </Link>

                        <!-- Content Area -->
                        <div class="min-w-0 flex-1">
                            <!-- Header Info -->
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex min-w-0 items-center gap-1.5">
                                    <Link
                                        :href="`/u/${msg.user.username}`"
                                        class="truncate text-xs font-semibold text-slate-900 transition hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                    >
                                        {{ msg.user.name }}
                                    </Link>

                                    <!-- You Badge -->
                                    <span
                                        v-if="
                                            currentUser &&
                                            Number(currentUser.id) ===
                                                Number(msg.user.id)
                                        "
                                        class="rounded-md bg-indigo-100 px-1.5 py-0.5 text-[9px] font-bold text-indigo-700 dark:bg-indigo-900/70 dark:text-indigo-200"
                                    >
                                        You
                                    </span>

                                    <VerifiedBadge
                                        v-if="msg.user.is_verified"
                                    />
                                    <span
                                        v-if="msg.user.institution"
                                        class="hidden truncate text-[11px] text-slate-400 sm:inline dark:text-gray-500"
                                    >
                                        • {{ msg.user.institution }}
                                    </span>
                                </div>

                                <div class="flex shrink-0 items-center gap-1">
                                    <span
                                        class="text-[10px] text-slate-400 sm:text-[11px] dark:text-gray-500"
                                    >
                                        {{ formatTime(msg.created_at) }}
                                    </span>

                                    <!-- Reply Button -->
                                    <button
                                        v-if="currentUser && canPost"
                                        type="button"
                                        @click="startReply(msg)"
                                        class="cursor-pointer rounded-md p-1 text-slate-400 opacity-100 transition hover:bg-indigo-50 hover:text-indigo-600 sm:opacity-0 sm:group-hover:opacity-100 dark:text-gray-500 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400"
                                        title="Reply to message"
                                    >
                                        <Reply class="h-3 w-3" />
                                    </button>

                                    <!-- Quick Admin Edit / Ban User Button -->
                                    <Link
                                        v-if="can('edit users')"
                                        :href="`/admin/users/edit/${msg.user.id}`"
                                        class="cursor-pointer rounded-md p-1 text-slate-400 opacity-100 transition hover:bg-indigo-50 hover:text-indigo-600 sm:opacity-0 sm:group-hover:opacity-100 dark:text-gray-500 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400"
                                        :title="`Manage / Ban ${msg.user.name}`"
                                    >
                                        <Pencil class="h-3 w-3" />
                                    </Link>

                                    <!-- Report Button (for non-author users) -->
                                    <button
                                        v-if="
                                            currentUser &&
                                            Number(currentUser.id) !==
                                                Number(msg.user.id)
                                        "
                                        @click="openReportModal(msg)"
                                        class="cursor-pointer rounded-md p-1 text-slate-400 opacity-100 transition hover:bg-amber-50 hover:text-amber-600 sm:opacity-0 sm:group-hover:opacity-100 dark:text-gray-500 dark:hover:bg-amber-950/40 dark:hover:text-amber-400"
                                        title="Report message"
                                    >
                                        <Flag class="h-3 w-3" />
                                    </button>

                                    <!-- Delete Button (Staff Only) -->
                                    <button
                                        v-if="canDelete"
                                        type="button"
                                        @click="deleteMessage(msg.id)"
                                        class="cursor-pointer rounded-md p-1 text-slate-400 opacity-100 transition hover:bg-rose-50 hover:text-rose-600 sm:opacity-0 sm:group-hover:opacity-100 dark:text-gray-500 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                        title="Delete message (Staff)"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                    </button>
                                </div>
                            </div>

                            <!-- Quoted Parent Snapshot (If Reply) -->
                            <div
                                v-if="msg.reply_to_content"
                                @click="msg.reply_to_id ? scrollToMessage(msg.reply_to_id) : null"
                                class="mt-1.5 flex items-center gap-1.5 rounded-lg border-l-2 border-indigo-500 bg-slate-100/70 px-2.5 py-1 text-[11px] text-slate-600 transition dark:border-indigo-400 dark:bg-gray-800/60 dark:text-gray-300"
                                :class="msg.reply_to_id ? 'cursor-pointer hover:bg-indigo-50/60 dark:hover:bg-indigo-950/30' : ''"
                                :title="msg.reply_to_id ? 'Click to jump to original message' : ''"
                            >
                                <Reply class="h-3 w-3 shrink-0 text-indigo-500 dark:text-indigo-400" />
                                <span class="truncate italic">
                                    "{{ msg.reply_to_content }}"
                                </span>
                            </div>

                            <!-- Message Text -->
                            <p
                                class="mt-1 text-xs leading-relaxed break-words whitespace-pre-wrap sm:text-sm"
                                :class="
                                    currentUser &&
                                    Number(currentUser.id) ===
                                        Number(msg.user.id)
                                        ? 'text-slate-900 dark:text-gray-100'
                                        : 'text-slate-700 dark:text-gray-300'
                                "
                            >
                                {{ msg.content }}
                            </p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Scroll To Bottom Button -->
            <button
                v-if="showScrollButton"
                type="button"
                @click="scrollToBottom(true)"
                class="absolute right-8 bottom-24 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-slate-900 text-white shadow-md transition hover:bg-slate-800 active:scale-95 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
                title="Scroll to bottom"
            >
                <ArrowDown class="h-4 w-4" />
            </button>

            <!-- Bottom Input Field -->
            <div
                class="border-t border-slate-100 bg-white p-3.5 sm:p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Active Reply Banner -->
                <div
                    v-if="activeReplyTo"
                    class="mb-2 flex items-center justify-between rounded-xl border border-indigo-100 bg-indigo-50/70 px-3 py-1.5 text-xs text-indigo-900 dark:border-indigo-900/50 dark:bg-indigo-950/40 dark:text-indigo-200"
                >
                    <div class="flex min-w-0 items-center gap-1.5 truncate">
                        <Reply class="h-3.5 w-3.5 shrink-0 text-indigo-600 dark:text-indigo-400" />
                        <span class="truncate">
                            Replying to <strong class="font-semibold">{{ activeReplyTo.user.name }}</strong>:
                            <span class="opacity-80 italic">"{{ activeReplyTo.content }}"</span>
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="cancelReply"
                        class="cursor-pointer rounded-lg p-0.5 text-indigo-500 transition hover:bg-indigo-100 dark:text-indigo-400 dark:hover:bg-indigo-900/60"
                        title="Cancel reply"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>

                <!-- Can Post -->
                <form
                    v-if="canPost"
                    @submit.prevent="sendMessage"
                    class="flex items-center gap-2"
                >
                    <div class="relative flex-1">
                        <input
                            ref="messageInputRef"
                            v-model="inputContent"
                            type="text"
                            :disabled="isSending || cooldownSeconds > 0"
                            :placeholder="activeReplyTo ? `Reply to ${activeReplyTo.user.name}...` : 'Type a message...'"
                            :maxlength="maxLengthLimit"
                            class="dark:border-gray-750 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none disabled:opacity-60 sm:text-sm dark:border-gray-700/80 dark:bg-gray-800/80 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:bg-gray-800"
                        />
                        <span
                            v-if="inputContent.length > (maxLengthLimit - 80)"
                            class="absolute top-1/2 right-3.5 -translate-y-1/2 text-[10px] font-medium text-slate-400 dark:text-gray-500"
                        >
                            {{ maxLengthLimit - inputContent.length }}
                        </span>
                    </div>

                    <button
                        type="submit"
                        :disabled="
                            !inputContent.trim() ||
                            isSending ||
                            cooldownSeconds > 0
                        "
                        class="inline-flex h-10 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        <Loader2
                            v-if="isSending"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <template v-else-if="cooldownSeconds > 0">
                            <span>{{ cooldownSeconds }}s</span>
                        </template>
                        <template v-else>
                            <Send class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">Send</span>
                        </template>
                    </button>
                </form>

                <!-- Restricted Notice -->
                <div
                    v-else
                    class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-2.5 text-xs dark:border-gray-800 dark:bg-gray-800/40"
                >
                    <div
                        class="flex items-center gap-2 text-slate-600 dark:text-gray-400"
                    >
                        <Lock
                            class="h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-gray-500"
                        />
                        <span>{{
                            restrictionReason || 'Posting is restricted'
                        }}</span>
                    </div>

                    <Link
                        v-if="!currentUser"
                        href="/login"
                        class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        <LogIn class="h-3.5 w-3.5" />
                        <span>Sign in</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Report Modal -->
        <div
            v-if="reportingMessage"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
            @click.self="closeReportModal"
        >
            <div
                class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between border-b border-slate-100 pb-3.5 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <Flag class="h-4 w-4" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-gray-100">
                            Report Message
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="closeReportModal"
                        class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Success State -->
                <div
                    v-if="reportSuccessMessage"
                    class="my-6 flex flex-col items-center justify-center py-4 text-center"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <Check class="h-6 w-6" />
                    </div>
                    <p class="mt-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                        {{ reportSuccessMessage }}
                    </p>
                </div>

                <!-- Form Content -->
                <form v-else @submit.prevent="submitReport" class="mt-4 space-y-4">
                    <!-- Error notice if duplicate or validation fails -->
                    <div
                        v-if="reportErrorMessage"
                        class="rounded-xl border border-rose-200 bg-rose-50/80 p-3 text-xs font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/50 dark:text-rose-300"
                    >
                        {{ reportErrorMessage }}
                    </div>

                    <!-- Message Preview Snapshot -->
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-gray-800 dark:bg-gray-800/60">
                        <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-gray-400">
                            <span class="font-semibold text-slate-700 dark:text-gray-300">
                                {{ reportingMessage.user.name }} (@{{ reportingMessage.user.username }})
                            </span>
                            <span>{{ formatTime(reportingMessage.created_at) }}</span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-800 break-words dark:text-gray-200">
                            "{{ reportingMessage.content }}"
                        </p>
                    </div>

                    <!-- Reason Selection -->
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300">
                            Why are you reporting this message?
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

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2 pt-2">
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
    </main>
</template>
