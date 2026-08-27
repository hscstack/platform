<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import {
    MessageCircle,
    Send,
    Trash2,
    Lock,
    Loader2,
    Clock,
    ChevronDown,
    Sparkles,
} from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
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

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const isOpen = ref(false);
const isLoading = ref(false);
const isSending = ref(false);
const messages = ref<ChatMessageItem[]>([]);
const inputContent = ref('');
const canPost = ref(false);
const restrictionReason = ref<string | null>(null);
const canDelete = ref(false);
const isEnabled = ref(true);

// 30s Cooldown timer logic
const cooldownSeconds = ref(0);
let cooldownInterval: ReturnType<typeof setInterval> | null = null;
const messagesContainerRef = ref<HTMLElement | null>(null);

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

const fetchMessages = async () => {
    isLoading.value = true;

    try {
        const res = await fetch('/api/chat/messages', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.ok) {
            const data = await res.json();
            messages.value = data.messages || [];
            canPost.value = Boolean(data.can_post);
            restrictionReason.value = data.reason || null;
            canDelete.value = Boolean(data.can_delete);
            isEnabled.value = Boolean(data.enabled);

            // Connect Pusher
            setupRealtime(data.pusher_key, data.pusher_cluster);
            scrollToBottom(false);
        }
    } catch (e) {
        console.error('Failed to load chat messages', e);
    } finally {
        isLoading.value = false;
    }
};

const setupRealtime = (key?: string, cluster?: string) => {
    const echo = getEcho(key, cluster);

    if (!echo) {
        return;
    }

    echo.channel('global-chat')
        .stopListening('.message.sent')
        .stopListening('.message.deleted')
        .listen('.message.sent', (e: { message: ChatMessageItem }) => {
            if (e && e.message) {
                // Avoid duplicate if we already added it optimistically
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
            startCooldown(30);
            scrollToBottom(true);
        } else if (res.status === 429) {
            const data = await res.json();
            startCooldown(data.retry_after || 30);
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

const toggleChat = () => {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        if (messages.value.length === 0) {
            fetchMessages();
        } else {
            scrollToBottom(false);
        }
    }
};

onMounted(() => {
    initCooldown();
});

onUnmounted(() => {
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
    }
});

watch(isOpen, (opened) => {
    if (opened) {
        nextTick(() => scrollToBottom(false));
    }
});
</script>

<template>
    <!-- Floating Trigger Bubble / Bar -->
    <div class="fixed right-4 bottom-5 z-40 sm:right-6 sm:bottom-6">
        <!-- Collapsed Floating Button -->
        <button
            v-if="!isOpen"
            type="button"
            @click="toggleChat"
            class="group flex h-12 items-center gap-2.5 rounded-full bg-slate-900 px-4 py-2.5 text-white shadow-xl ring-4 ring-indigo-500/20 transition-all hover:scale-105 hover:bg-slate-800 hover:shadow-2xl active:scale-95 sm:h-13 sm:px-5 dark:bg-gray-100 dark:text-gray-900 dark:ring-indigo-400/20 dark:hover:bg-white"
            aria-label="Open Global Student Chat"
        >
            <div class="relative flex items-center justify-center">
                <MessageCircle
                    class="h-5 w-5 text-indigo-400 transition-transform group-hover:scale-110 dark:text-indigo-600"
                />
                <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                    ></span>
                    <span
                        class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"
                    ></span>
                </span>
            </div>
            <span class="text-xs font-bold sm:text-sm">Student Chat</span>
        </button>

        <!-- Expanded Chat Box -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="scale-95 opacity-0 translate-y-4"
            enter-to-class="scale-100 opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="scale-100 opacity-100 translate-y-0"
            leave-to-class="scale-95 opacity-0 translate-y-4"
        >
            <div
                v-if="isOpen"
                class="flex h-[520px] w-[calc(100vw-2rem)] max-w-sm flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 shadow-2xl backdrop-blur-xl sm:w-96 dark:border-gray-800 dark:bg-gray-950/95"
            >
                <!-- Chat Header -->
                <div
                    class="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-4 py-3 dark:border-gray-800/80 dark:bg-gray-900/60"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <MessageCircle class="h-4 w-4" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h3
                                    class="text-xs font-bold text-slate-900 sm:text-sm dark:text-gray-100"
                                >
                                    Global Student Chat
                                </h3>
                                <span
                                    class="py-0.2 rounded-full bg-emerald-500/10 px-1.5 text-[9px] font-extrabold text-emerald-600 dark:text-emerald-400"
                                >
                                    Live
                                </span>
                            </div>
                            <p
                                class="text-[10px] font-medium text-slate-400 dark:text-gray-500"
                            >
                                Realtime peer discussion
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="isOpen = false"
                        class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-700 active:scale-95 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        title="Minimize"
                    >
                        <ChevronDown class="h-4 w-4" />
                    </button>
                </div>

                <!-- Messages Stream -->
                <div
                    ref="messagesContainerRef"
                    class="flex-1 space-y-3.5 overflow-y-auto p-4"
                >
                    <!-- Loading state -->
                    <div
                        v-if="isLoading"
                        class="flex h-full flex-col items-center justify-center gap-2 text-slate-400"
                    >
                        <Loader2 class="h-5 w-5 animate-spin text-indigo-500" />
                        <span class="text-xs">Loading conversations...</span>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-else-if="messages.length === 0"
                        class="flex h-full flex-col items-center justify-center px-4 text-center text-slate-400"
                    >
                        <Sparkles
                            class="mb-2 h-8 w-8 text-indigo-400 opacity-60"
                        />
                        <p
                            class="text-xs font-bold text-slate-700 dark:text-gray-300"
                        >
                            No messages yet
                        </p>
                        <p
                            class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            Say hi and start the conversation with fellow
                            students!
                        </p>
                    </div>

                    <!-- Message items -->
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="group flex items-start gap-2.5 text-left"
                        :class="{
                            'flex-row-reverse':
                                currentUser && currentUser.id === msg.user.id,
                        }"
                    >
                        <!-- User Avatar -->
                        <Link
                            :href="`/u/${msg.user.username}`"
                            class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full font-semibold transition hover:ring-2 hover:ring-indigo-400"
                            :class="
                                currentUser && currentUser.id === msg.user.id
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-300'
                            "
                        >
                            <img
                                v-if="msg.user.image_url"
                                :src="msg.user.image_url"
                                :alt="msg.user.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="text-[10px] uppercase">
                                {{ msg.user.name.charAt(0) }}
                            </span>
                        </Link>

                        <!-- Bubble -->
                        <div
                            class="max-w-[78%] min-w-0"
                            :class="{
                                'items-end text-right':
                                    currentUser &&
                                    currentUser.id === msg.user.id,
                            }"
                        >
                            <!-- Sender Name & Institution -->
                            <div
                                class="mb-1 flex items-center gap-1.5 text-[10px]"
                                :class="{
                                    'justify-end':
                                        currentUser &&
                                        currentUser.id === msg.user.id,
                                }"
                            >
                                <Link
                                    :href="`/u/${msg.user.username}`"
                                    class="font-bold text-slate-800 hover:text-indigo-600 dark:text-gray-200 dark:hover:text-indigo-400"
                                >
                                    {{ msg.user.name }}
                                </Link>
                                <VerifiedBadge v-if="msg.user.is_verified" />
                                <span class="text-slate-400 dark:text-gray-500">
                                    {{ formatTime(msg.created_at) }}
                                </span>
                            </div>

                            <!-- Text Content Bubble -->
                            <div
                                class="relative rounded-2xl px-3.5 py-2 text-xs leading-relaxed break-words shadow-2xs"
                                :class="
                                    currentUser &&
                                    currentUser.id === msg.user.id
                                        ? 'rounded-tr-xs bg-indigo-600 text-white dark:bg-indigo-500'
                                        : 'rounded-tl-xs border border-slate-100 bg-slate-50 text-slate-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100'
                                "
                            >
                                <p class="whitespace-pre-wrap">
                                    {{ msg.content }}
                                </p>

                                <!-- Delete button (for admin or author) -->
                                <button
                                    v-if="
                                        canDelete ||
                                        (currentUser &&
                                            currentUser.id === msg.user.id)
                                    "
                                    @click="deleteMessage(msg.id)"
                                    class="absolute -right-2 -bottom-2 hidden h-5 w-5 items-center justify-center rounded-full bg-rose-50 text-rose-600 shadow-xs ring-1 ring-rose-200 transition group-hover:flex hover:bg-rose-100 dark:bg-rose-950 dark:text-rose-400 dark:ring-rose-800"
                                    title="Delete message"
                                >
                                    <Trash2 class="h-2.5 w-2.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Footer / Permission Gate -->
                <div
                    class="border-t border-slate-100 bg-white p-3 dark:border-gray-800 dark:bg-gray-950"
                >
                    <!-- Case 1: Can Post (Has Access) -->
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
                                placeholder="Type a message (max 280 chars)..."
                                maxlength="280"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none disabled:opacity-60 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400"
                            />
                            <span
                                v-if="inputContent.length > 200"
                                class="absolute top-1/2 right-2.5 -translate-y-1/2 text-[10px] font-bold text-slate-400"
                            >
                                {{ 280 - inputContent.length }}
                            </span>
                        </div>

                        <!-- Send Button with Cooldown -->
                        <button
                            type="submit"
                            :disabled="
                                !inputContent.trim() ||
                                isSending ||
                                cooldownSeconds > 0
                            "
                            class="flex h-8.5 shrink-0 items-center gap-1.5 rounded-xl bg-indigo-600 px-3 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                        >
                            <Loader2
                                v-if="isSending"
                                class="h-3.5 w-3.5 animate-spin"
                            />
                            <template v-else-if="cooldownSeconds > 0">
                                <Clock class="h-3.5 w-3.5" />
                                <span>{{ cooldownSeconds }}s</span>
                            </template>
                            <template v-else>
                                <Send class="h-3.5 w-3.5" />
                            </template>
                        </button>
                    </form>

                    <!-- Case 2: Restricted / Not Logged In Notice -->
                    <div
                        v-else
                        class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-2.5 text-center text-xs dark:border-gray-800 dark:bg-gray-900/60"
                    >
                        <div
                            class="flex items-center justify-center gap-1.5 font-bold text-slate-700 dark:text-gray-300"
                        >
                            <Lock class="h-3.5 w-3.5 text-amber-500" />
                            <span>{{
                                restrictionReason || 'Chat access is restricted'
                            }}</span>
                        </div>
                        <div class="mt-2" v-if="!currentUser">
                            <Link
                                href="/login"
                                class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1 text-[11px] font-bold text-white shadow-xs hover:bg-indigo-700"
                            >
                                Sign in to chat
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
