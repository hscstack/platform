<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Send, Trash2, Lock, Loader2, LogIn, ArrowDown } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';
import { getEcho } from '@/lib/echo';

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
    created_at: string;
    user: ChatUser;
}

const props = defineProps<{
    chatState: {
        enabled: boolean;
        audience: string;
        cooldown_seconds: number;
        can_post: boolean;
        reason: string | null;
        can_delete: boolean;
        messages: ChatMessageItem[];
        pusher_key?: string;
        pusher_cluster?: string;
    };
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const messages = ref<ChatMessageItem[]>(props.chatState.messages || []);
const inputContent = ref('');
const isSending = ref(false);
const canPost = ref(props.chatState.can_post);
const restrictionReason = ref(props.chatState.reason);
const canDelete = ref(props.chatState.can_delete);
const activeCooldownSeconds = ref(props.chatState.cooldown_seconds ?? 30);

const messagesContainerRef = ref<HTMLElement | null>(null);
const showScrollButton = ref(false);

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

                    if (messages.value.length > 200) {
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

    if (content.length > 280) {
        return;
    }

    isSending.value = true;

    try {
        const token = (
            document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
        )?.content;
        const res = await fetch('/api/chat/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify({ content }),
        });

        if (res.ok) {
            const newMsg = await res.json();

            if (!messages.value.some((m) => m.id === newMsg.id)) {
                messages.value.push(newMsg);

                if (messages.value.length > 200) {
                    messages.value.shift();
                }
            }

            inputContent.value = '';

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

    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <!-- Page Title & Subtitle Matching Journal / Standard Page Structure -->
        <div
            class="mb-6 flex flex-col gap-2 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between dark:border-gray-800"
        >
            <div>
                <h1
                    class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-gray-100"
                >
                    Global Chat
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                    অন্যান্য শিক্ষার্থীদের সাথে সরাসরি কথা বলুন ও প্রশ্ন শেয়ার
                    করুন।
                </p>
            </div>

            <div
                v-if="activeCooldownSeconds > 0"
                class="text-xs text-slate-400 dark:text-gray-500"
            >
                <span>{{ activeCooldownSeconds }}s delay between messages</span>
            </div>
        </div>

        <!-- Chat Container Window -->
        <div
            class="flex h-[620px] flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Messages Stream -->
            <div
                ref="messagesContainerRef"
                @scroll="handleScroll"
                class="relative flex-1 divide-y divide-slate-100 overflow-y-auto p-4 sm:p-6 dark:divide-gray-800/60"
            >
                <!-- Empty State -->
                <div
                    v-if="messages.length === 0"
                    class="flex h-full flex-col items-center justify-center p-8 text-center text-slate-400"
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
                        class="sticky top-0 z-10 my-3 flex justify-center"
                    >
                        <span
                            class="rounded-full bg-slate-100 px-3 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-300"
                        >
                            {{ formatDateDivider(msg.created_at) }}
                        </span>
                    </div>

                    <!-- Individual Message Row -->
                    <div
                        class="group -mx-2 flex items-start gap-3.5 rounded-xl px-2 py-3.5 text-left transition hover:bg-slate-50/60 dark:hover:bg-gray-800/30"
                    >
                        <!-- User Avatar -->
                        <Link
                            :href="`/u/${msg.user.username}`"
                            class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 font-semibold text-indigo-700 transition hover:ring-2 hover:ring-indigo-400 dark:bg-indigo-950/60 dark:text-indigo-300"
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

                                <div class="flex shrink-0 items-center gap-2.5">
                                    <span
                                        class="text-[11px] text-slate-400 dark:text-gray-500"
                                    >
                                        {{ formatTime(msg.created_at) }}
                                    </span>

                                    <!-- Delete Button -->
                                    <button
                                        v-if="
                                            canDelete ||
                                            (currentUser &&
                                                currentUser.id === msg.user.id)
                                        "
                                        @click="deleteMessage(msg.id)"
                                        class="cursor-pointer rounded-md p-1 text-slate-400 opacity-0 transition group-hover:opacity-100 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                        title="Delete message"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                    </button>
                                </div>
                            </div>

                            <!-- Message Text -->
                            <p
                                class="mt-1 text-xs leading-relaxed break-words whitespace-pre-wrap text-slate-700 sm:text-sm dark:text-gray-300"
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
                class="absolute right-8 bottom-24 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-slate-900 text-white shadow-md transition hover:bg-slate-800 active:scale-95 dark:bg-gray-100 dark:text-gray-900"
                title="Scroll to bottom"
            >
                <ArrowDown class="h-4 w-4" />
            </button>

            <!-- Bottom Input Field -->
            <div
                class="border-t border-slate-100 bg-white p-3.5 sm:p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Can Post -->
                <form
                    v-if="canPost"
                    @submit.prevent="sendMessage"
                    class="flex items-center gap-2"
                >
                    <div class="relative flex-1">
                        <input
                            v-model="inputContent"
                            type="text"
                            :disabled="isSending || cooldownSeconds > 0"
                            placeholder="Type a message..."
                            maxlength="280"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none disabled:opacity-60 sm:text-sm dark:border-gray-800 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400"
                        />
                        <span
                            v-if="inputContent.length > 200"
                            class="absolute top-1/2 right-3.5 -translate-y-1/2 text-[10px] font-medium text-slate-400"
                        >
                            {{ 280 - inputContent.length }}
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
                        <Lock class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                        <span>{{
                            restrictionReason || 'Posting is restricted'
                        }}</span>
                    </div>

                    <Link
                        v-if="!currentUser"
                        href="/login"
                        class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                    >
                        <LogIn class="h-3.5 w-3.5" />
                        <span>Sign in</span>
                    </Link>
                </div>
            </div>
        </div>
    </main>
</template>
