<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Send,
    Trash2,
    Lock,
    Loader2,
    Clock,
    LogIn,
    ShieldAlert,
    Info,
    Sparkles,
} from 'lucide-vue-next';
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

// Cooldown timer logic
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

const scrollToBottom = (smooth = true) => {
    nextTick(() => {
        if (messagesContainerRef.value) {
            messagesContainerRef.value.scrollTo({
                top: messagesContainerRef.value.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto',
            });
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
    if (!confirm('Delete this message?')) {
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
        <title>Student Lounge - Live Global Chat</title>
        <meta
            name="description"
            content="Talk, discuss studies, and connect with fellow HSC and SSC students in real-time."
        />
    </Head>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <!-- Minimal Platform Header -->
        <div
            class="mb-6 flex flex-col gap-3 border-b border-slate-200/80 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div>
                <div class="flex items-center gap-2.5">
                    <h1
                        class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                    >
                        Student Lounge
                    </h1>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                    >
                        <span
                            class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                        ></span>
                        Live
                    </span>
                </div>
                <p
                    class="mt-1 text-xs text-slate-500 sm:text-sm dark:text-gray-400"
                >
                    এইচএসসি ও এসএসসি শিক্ষার্থীদের জন্য রিয়েল-টাইম আলোচনার
                    উন্মুক্ত প্ল্যাটফর্ম।
                </p>
            </div>

            <!-- Meta badge -->
            <div class="flex items-center gap-2">
                <span
                    v-if="activeCooldownSeconds > 0"
                    class="inline-flex items-center gap-1 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:bg-gray-800 dark:text-gray-400"
                >
                    <Clock class="h-3.5 w-3.5 text-slate-400" />
                    {{ activeCooldownSeconds }}s interval
                </span>
            </div>
        </div>

        <!-- Chat Frame Grid: Main Stream + Sidebar Info -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Left 8 cols: Chat Stream Window -->
            <div
                class="flex h-[680px] flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xs lg:col-span-8 dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Message Feed -->
                <div
                    ref="messagesContainerRef"
                    class="flex-1 space-y-4 overflow-y-auto p-4 sm:p-6"
                >
                    <!-- Empty State -->
                    <div
                        v-if="messages.length === 0"
                        class="flex h-full flex-col items-center justify-center p-8 text-center text-slate-400"
                    >
                        <Sparkles
                            class="mb-2 h-8 w-8 text-indigo-400 opacity-60"
                        />
                        <p
                            class="text-sm font-bold text-slate-700 dark:text-gray-300"
                        >
                            No messages yet
                        </p>
                        <p
                            class="mt-0.5 text-xs text-slate-400 dark:text-gray-500"
                        >
                            Say hello to everyone and start the conversation!
                        </p>
                    </div>

                    <!-- Stream Messages -->
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="group flex items-start gap-3 text-left transition-all"
                        :class="{
                            'flex-row-reverse':
                                currentUser && currentUser.id === msg.user.id,
                        }"
                    >
                        <!-- Author Avatar -->
                        <Link
                            :href="`/u/${msg.user.username}`"
                            class="flex h-8.5 w-8.5 shrink-0 items-center justify-center overflow-hidden rounded-full font-bold transition hover:ring-2 hover:ring-indigo-400"
                            :class="
                                currentUser && currentUser.id === msg.user.id
                                    ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                    : 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-300'
                            "
                        >
                            <img
                                v-if="msg.user.image_url"
                                :src="msg.user.image_url"
                                :alt="msg.user.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="text-xs uppercase">
                                {{ msg.user.name.charAt(0) }}
                            </span>
                        </Link>

                        <!-- Content Block -->
                        <div
                            class="max-w-[85%] min-w-0 sm:max-w-[75%]"
                            :class="{
                                'items-end text-right':
                                    currentUser &&
                                    currentUser.id === msg.user.id,
                            }"
                        >
                            <!-- Metadata Header -->
                            <div
                                class="mb-1 flex items-center gap-1.5 text-xs"
                                :class="{
                                    'justify-end':
                                        currentUser &&
                                        currentUser.id === msg.user.id,
                                }"
                            >
                                <Link
                                    :href="`/u/${msg.user.username}`"
                                    class="font-bold text-slate-900 transition hover:text-indigo-600 dark:text-gray-200 dark:hover:text-indigo-400"
                                >
                                    {{ msg.user.name }}
                                </Link>
                                <VerifiedBadge v-if="msg.user.is_verified" />
                                <span
                                    class="text-[10px] text-slate-400 dark:text-gray-500"
                                >
                                    {{ formatTime(msg.created_at) }}
                                </span>
                            </div>

                            <!-- Bubble -->
                            <div
                                class="relative rounded-2xl px-4 py-2.5 text-xs leading-relaxed break-words sm:text-sm"
                                :class="
                                    currentUser &&
                                    currentUser.id === msg.user.id
                                        ? 'rounded-tr-xs bg-indigo-600 text-white dark:bg-indigo-500'
                                        : 'rounded-tl-xs border border-slate-100 bg-slate-50 text-slate-800 dark:border-gray-800/80 dark:bg-gray-800/80 dark:text-gray-100'
                                "
                            >
                                <p class="whitespace-pre-wrap">
                                    {{ msg.content }}
                                </p>

                                <!-- Delete button for admin or author -->
                                <button
                                    v-if="
                                        canDelete ||
                                        (currentUser &&
                                            currentUser.id === msg.user.id)
                                    "
                                    @click="deleteMessage(msg.id)"
                                    class="absolute -right-2 -bottom-2 hidden h-6 w-6 cursor-pointer items-center justify-center rounded-full bg-rose-50 text-rose-600 shadow-xs ring-1 ring-rose-200 transition group-hover:flex hover:bg-rose-100 dark:bg-rose-950 dark:text-rose-400 dark:ring-rose-800"
                                    title="Delete message"
                                >
                                    <Trash2 class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Input Section / Lock Notice -->
                <div
                    class="border-t border-slate-100 bg-white p-3.5 sm:p-4 dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- Allowed To Post -->
                    <form
                        v-if="canPost"
                        @submit.prevent="sendMessage"
                        class="flex items-center gap-2.5"
                    >
                        <div class="relative flex-1">
                            <input
                                v-model="inputContent"
                                type="text"
                                :disabled="isSending || cooldownSeconds > 0"
                                placeholder="Type a message (max 280 chars)..."
                                maxlength="280"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none disabled:opacity-60 sm:text-sm dark:border-gray-800 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400"
                            />
                            <span
                                v-if="inputContent.length > 200"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-[10px] font-bold text-slate-400"
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
                            class="flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm dark:bg-indigo-500 dark:hover:bg-indigo-600"
                        >
                            <Loader2
                                v-if="isSending"
                                class="h-4 w-4 animate-spin"
                            />
                            <template v-else-if="cooldownSeconds > 0">
                                <Clock class="h-3.5 w-3.5" />
                                <span>{{ cooldownSeconds }}s</span>
                            </template>
                            <template v-else>
                                <Send class="h-3.5 w-3.5" />
                                <span class="hidden sm:inline">Send</span>
                            </template>
                        </button>
                    </form>

                    <!-- Restricted Access Notice -->
                    <div
                        v-else
                        class="rounded-xl border border-slate-200/80 bg-slate-50 p-3.5 text-center dark:border-gray-800 dark:bg-gray-800/60"
                    >
                        <div
                            class="flex items-center justify-center gap-2 text-xs font-bold text-slate-700 sm:text-sm dark:text-gray-300"
                        >
                            <Lock class="h-4 w-4 shrink-0 text-amber-500" />
                            <span>{{
                                restrictionReason || 'Chat access is restricted'
                            }}</span>
                        </div>
                        <div class="mt-2.5" v-if="!currentUser">
                            <Link
                                href="/login"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-700"
                            >
                                <LogIn class="h-3.5 w-3.5" />
                                <span>Sign in to chat</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 4 cols: Community Guidelines & Lounge Info Sidebar -->
            <div class="space-y-4 lg:col-span-4">
                <!-- Guidelines Card -->
                <div
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <div
                        class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        <Info class="h-4 w-4 text-indigo-500" />
                        <span>Community Guidelines</span>
                    </div>
                    <ul
                        class="list-inside list-disc space-y-2 text-xs leading-relaxed text-slate-600 dark:text-gray-400"
                    >
                        <li>
                            Be respectful and supportive to fellow students.
                        </li>
                        <li>
                            No spamming, promotional links, or commercial ads.
                        </li>
                        <li>
                            Keep discussions focused on academic & student life
                            topics.
                        </li>
                        <li>
                            Respect the {{ activeCooldownSeconds }}s anti-spam
                            interval.
                        </li>
                    </ul>
                </div>

                <!-- Storage Policy Card -->
                <div
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-900"
                >
                    <div
                        class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        <ShieldAlert class="h-4 w-4 text-emerald-500" />
                        <span>Ephemeral Lounge</span>
                    </div>
                    <p
                        class="text-xs leading-relaxed text-slate-500 dark:text-gray-400"
                    >
                        To maintain high performance and user privacy, the
                        student lounge maintains a rolling buffer of the latest
                        200 messages. Older messages are automatically pruned.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
