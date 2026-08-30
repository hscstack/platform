<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import {
    Send,
    Trash2,
    Lock,
    Loader2,
    LogIn,
    ArrowDown,
    Ban,
    Flag,
    Check,
    X,
    Reply,
    Smile,
    Info,
    ShieldCheck,
    AlertCircle,
    MoreHorizontal,
    Radio,
    AtSign,
    LifeBuoy,
    Users,
} from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import ChatBanModal from '@/components/ChatBanModal.vue';
import type { ChatBanUser } from '@/components/ChatBanModal.vue';
import PwaInstallPrompt from '@/components/PwaInstallPrompt.vue';
import UserListItem from '@/components/UserListItem.vue';
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

export interface ChatReactorUser {
    id: number;
    name: string;
    username: string;
    image_url?: string | null;
    image_path?: string | null;
    institution?: string | null;
    is_verified?: boolean;
    roles?: Array<{ id?: number; name: string }> | string[];
}

export interface ChatMessageReactionItem {
    emoji: string;
    count: number;
    reacted: boolean;
    users?: string[];
    reactors?: ChatReactorUser[];
}

interface ChatMessageItem {
    id: number;
    content: string;
    is_deleted?: boolean;
    deleted_at?: string | null;
    reply_to_id?: number | null;
    reply_to_content?: string | null;
    reactions?: ChatMessageReactionItem[];
    created_at: string;
    user: ChatUser;
}

interface MessageSegment {
    type: 'text' | 'mention';
    text: string;
    username?: string;
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
        reaction_emojis?: string[];
        messages: ChatMessageItem[];
        channel_name?: string;
        pusher_key?: string;
        pusher_cluster?: string;
    };
}>();

const page = usePage();
const { can } = usePermissions();
const currentUser = computed(() => page.props.auth?.user);

const chatChannelName = computed(
    () => props.chatState.channel_name || 'global-chat',
);

const presenceChannelName = computed(() => chatChannelName.value);
const activeUsersCount = ref(0);

const maxMessagesLimit = ref(props.chatState.max_messages ?? 200);
const maxLengthLimit = ref(props.chatState.max_length ?? 280);
const messages = ref<ChatMessageItem[]>(props.chatState.messages || []);
const inputContent = ref('');
const isSending = ref(false);
const canPost = ref(props.chatState.can_post);
const restrictionReason = ref(props.chatState.reason);
const canDelete = ref(props.chatState.can_delete);
const activeCooldownSeconds = ref(props.chatState.cooldown_seconds ?? 30);

watch(
    () => props.chatState,
    (newState) => {
        if (newState) {
            if (newState.messages && Array.isArray(newState.messages)) {
                messages.value = [...newState.messages];
            }

            if (typeof newState.can_post === 'boolean') {
                canPost.value = newState.can_post;
            }

            if (typeof newState.can_delete === 'boolean') {
                canDelete.value = newState.can_delete;
            }

            if (newState.reason !== undefined) {
                restrictionReason.value = newState.reason;
            }

            if (newState.cooldown_seconds !== undefined) {
                activeCooldownSeconds.value = newState.cooldown_seconds;
            }

            if (newState.max_messages !== undefined) {
                maxMessagesLimit.value = newState.max_messages;
            }

            if (newState.max_length !== undefined) {
                maxLengthLimit.value = newState.max_length;
            }

            if (
                newState.reaction_emojis &&
                newState.reaction_emojis.length > 0
            ) {
                reactionEmojis.value = [...newState.reaction_emojis];
            }
        }
    },
    { deep: true },
);

const messagesContainerRef = ref<HTMLElement | null>(null);
const messageInputRef = ref<HTMLInputElement | null>(null);
const showScrollButton = ref(false);
const highlightedMessageId = ref<number | null>(null);
const showRulesModal = ref(false);

// Mobile Action Sheet State
const mobileActionMessage = ref<ChatMessageItem | null>(null);

const openMobileActions = (msg: ChatMessageItem) => {
    const isDeleted = Boolean(msg.is_deleted || msg.deleted_at);
    const isStaff = Boolean(can('manage chat') || canDelete.value);

    if (isDeleted && !isStaff) {
        return;
    }

    mobileActionMessage.value = msg;
};

const closeMobileActions = () => {
    mobileActionMessage.value = null;
};

// Reply State
const activeReplyTo = ref<ChatMessageItem | null>(null);

const startReply = (msg: ChatMessageItem) => {
    closeMobileActions();
    activeReplyTo.value = msg;
    nextTick(() => {
        messageInputRef.value?.focus();
    });
};

const cancelReply = () => {
    activeReplyTo.value = null;
};

// User Mention Autocomplete State & Logic
const showMentionSuggestions = ref(false);
const mentionQuery = ref('');
const selectedMentionIndex = ref(0);
const mentionStartPos = ref(-1);

const availableMentionUsers = computed<ChatUser[]>(() => {
    const userMap = new Map<number, ChatUser>();
    const currentId = currentUser.value ? Number(currentUser.value.id) : null;

    // Scan loaded messages from latest to oldest so active participants appear first
    for (let i = messages.value.length - 1; i >= 0; i--) {
        const u = messages.value[i]?.user;

        if (u && u.id && u.username) {
            if (currentId && Number(u.id) === currentId) {
                continue;
            }

            if (!userMap.has(Number(u.id))) {
                userMap.set(Number(u.id), u);
            }
        }
    }

    return Array.from(userMap.values());
});

const filteredMentionUsers = computed(() => {
    const q = mentionQuery.value.toLowerCase().trim();

    if (!q) {
        return [];
    }

    return availableMentionUsers.value
        .filter(
            (u) =>
                u.username.toLowerCase().includes(q) ||
                u.name.toLowerCase().includes(q),
        )
        .slice(0, 5);
});

const checkMentionTrigger = () => {
    if (!messageInputRef.value) {
        showMentionSuggestions.value = false;

        return;
    }

    const input = messageInputRef.value;
    const cursorPos = input.selectionStart ?? inputContent.value.length;
    const textBeforeCursor = inputContent.value.slice(0, cursorPos);

    const match = /(?:^|\s)@([a-zA-Z0-9_.-]*)$/.exec(textBeforeCursor);

    if (match !== null) {
        mentionQuery.value = match[1];
        mentionStartPos.value = cursorPos - match[1].length - 1;
        selectedMentionIndex.value = 0;
        showMentionSuggestions.value = true;
    } else {
        showMentionSuggestions.value = false;
    }
};

const handleInputKeydown = (e: KeyboardEvent) => {
    if (!showMentionSuggestions.value) {
        return;
    }

    if (e.key === 'Escape') {
        e.preventDefault();
        showMentionSuggestions.value = false;

        return;
    }

    if (filteredMentionUsers.value.length === 0) {
        return;
    }

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedMentionIndex.value =
            (selectedMentionIndex.value + 1) %
            filteredMentionUsers.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedMentionIndex.value =
            (selectedMentionIndex.value -
                1 +
                filteredMentionUsers.value.length) %
            filteredMentionUsers.value.length;
    } else if (e.key === 'Enter' || e.key === 'Tab') {
        if (filteredMentionUsers.value[selectedMentionIndex.value]) {
            e.preventDefault();
            insertMention(
                filteredMentionUsers.value[selectedMentionIndex.value],
            );
        }
    }
};

const insertMention = (user: ChatUser) => {
    if (!messageInputRef.value || mentionStartPos.value < 0) {
        return;
    }

    const input = messageInputRef.value;
    const cursorPos = input.selectionStart ?? inputContent.value.length;
    const before = inputContent.value.slice(0, mentionStartPos.value);
    const after = inputContent.value.slice(cursorPos);
    const mentionText = `@${user.username} `;

    inputContent.value = before + mentionText + after;
    showMentionSuggestions.value = false;

    nextTick(() => {
        if (messageInputRef.value) {
            messageInputRef.value.focus();
            const newCursorPos = before.length + mentionText.length;
            messageInputRef.value.setSelectionRange(newCursorPos, newCursorPos);
        }
    });
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

let lastFetchTimestamp = 0;
const fetchLatestMessages = async (force = false) => {
    const now = Date.now();

    if (!force && now - lastFetchTimestamp < 3000) {
        return;
    }

    lastFetchTimestamp = now;

    try {
        const res = await fetch('/api/chat/messages', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.ok) {
            const data = await res.json();

            if (data.messages && Array.isArray(data.messages)) {
                messages.value = data.messages;
                scrollToBottom(false);

                if (typeof data.can_post === 'boolean') {
                    canPost.value = data.can_post;
                }

                if (typeof data.can_delete === 'boolean') {
                    canDelete.value = data.can_delete;
                }

                if (data.reason !== undefined) {
                    restrictionReason.value = data.reason;
                }

                if (data.cooldown_seconds !== undefined) {
                    activeCooldownSeconds.value = data.cooldown_seconds;
                }

                if (
                    data.reaction_emojis &&
                    Array.isArray(data.reaction_emojis) &&
                    data.reaction_emojis.length > 0
                ) {
                    reactionEmojis.value = data.reaction_emojis;
                }
            }
        }
    } catch {
        // Silently ignore background refresh errors
    }
};

let handlePusherReconnect: (() => void) | null = null;

const setupRealtime = () => {
    const echo = getEcho(
        props.chatState.pusher_key,
        props.chatState.pusher_cluster,
    );

    if (!echo) {
        return;
    }

    echo.channel(chatChannelName.value)
        .stopListening('.message.sent')
        .stopListening('.message.deleted')
        .stopListening('.message.reacted')
        .stopListening('.settings.updated')
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
        .listen(
            '.message.deleted',
            (e: { messageId: number; deleted_at?: string }) => {
                if (e && e.messageId) {
                    const target = messages.value.find(
                        (m) => m.id === e.messageId,
                    );

                    if (target) {
                        target.is_deleted = true;
                        target.deleted_at =
                            e.deleted_at || new Date().toISOString();
                        target.content =
                            'This message was deleted by a moderator.';
                        target.reply_to_id = null;
                        target.reply_to_content = null;
                    }
                }
            },
        )
        .listen(
            '.message.reacted',
            (e: {
                messageId: number;
                reactions: ChatMessageReactionItem[];
            }) => {
                if (e && e.messageId) {
                    const target = messages.value.find(
                        (m) => m.id === e.messageId,
                    );

                    if (target) {
                        const currentReactedMap = new Map(
                            (target.reactions || []).map((r) => [
                                r.emoji,
                                r.reacted,
                            ]),
                        );

                        target.reactions = (e.reactions || []).map((r) => ({
                            ...r,
                            reacted: currentReactedMap.get(r.emoji) || false,
                        }));
                    }
                }
            },
        )
        .listen(
            '.settings.updated',
            (e: {
                settings: {
                    enabled: boolean;
                    audience: string;
                    cooldown_seconds: number;
                    max_messages: number;
                    max_length: number;
                    allowed_emojis?: string[];
                };
            }) => {
                if (e && e.settings) {
                    if (e.settings.allowed_emojis) {
                        reactionEmojis.value = e.settings.allowed_emojis;
                    }

                    activeCooldownSeconds.value = e.settings.cooldown_seconds;
                    maxMessagesLimit.value = e.settings.max_messages;
                    maxLengthLimit.value = e.settings.max_length;

                    if (messages.value.length > e.settings.max_messages) {
                        messages.value = messages.value.slice(
                            -e.settings.max_messages,
                        );
                    }

                    const user = currentUser.value;

                    if (
                        !e.settings.enabled ||
                        e.settings.audience === 'disabled'
                    ) {
                        canPost.value = false;
                        restrictionReason.value =
                            e.settings.disabled_reason &&
                            e.settings.disabled_reason.trim() !== ''
                                ? e.settings.disabled_reason.trim()
                                : 'Global chat is currently disabled for maintenance.';
                    } else if (!user) {
                        canPost.value = false;
                        restrictionReason.value =
                            'Please sign in to join the conversation.';
                    } else if (e.settings.audience === 'verified_members') {
                        if (user.is_verified || can('view admin')) {
                            canPost.value = true;
                            restrictionReason.value = null;
                        } else {
                            canPost.value = false;
                            restrictionReason.value =
                                'Global chat is currently in beta for verified members and contributors.';
                        }
                    } else if (e.settings.audience === 'all') {
                        canPost.value = true;
                        restrictionReason.value = null;
                    }
                }
            },
        );

    try {
        const pusher = (
            echo.connector as {
                pusher?: {
                    connection?: {
                        bind: (event: string, callback: () => void) => void;
                        unbind: (event: string, callback: () => void) => void;
                    };
                };
            }
        )?.pusher;

        if (pusher?.connection) {
            handlePusherReconnect = () => {
                fetchLatestMessages(true);
            };
            pusher.connection.bind('connected', handlePusherReconnect);
        }
    } catch {
        // Silently ignore connector inspection
    }
};

const setupPresenceChannel = () => {
    if (!currentUser.value) {
        return; // Only authenticated users can join presence channels
    }

    const echo = getEcho(
        props.chatState.pusher_key,
        props.chatState.pusher_cluster,
    );

    if (!echo) {
        return;
    }

    echo.join(presenceChannelName.value)
        .here((users: unknown[]) => {
            activeUsersCount.value = users.length;
        })
        .joining(() => {
            activeUsersCount.value++;
        })
        .leaving(() => {
            activeUsersCount.value = Math.max(0, activeUsersCount.value - 1);
        })
        .error(() => {
            // Silently ignore presence auth errors (e.g. guest users)
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
            showMentionSuggestions.value = false;
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
    closeMobileActions();

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
            const data = await res.json();
            const target = messages.value.find((m) => m.id === messageId);

            if (target) {
                target.is_deleted = true;
                target.deleted_at = data.deleted_at || new Date().toISOString();
                target.content = 'This message was deleted by a moderator.';
                target.reply_to_id = null;
                target.reply_to_content = null;
            }
        }
    } catch (e) {
        console.error('Failed to delete message', e);
    }
};

// Message Reactions state & methods
const activeReactionPickerMessageId = ref<number | null>(null);
const reactionEmojis = ref<string[]>(
    props.chatState.reaction_emojis &&
        props.chatState.reaction_emojis.length > 0
        ? props.chatState.reaction_emojis
        : ['👍', '❤️', '🔥', '😂', '🎉', '😮', '😢', '👏'],
);

const toggleReactionPicker = (messageId: number) => {
    if (activeReactionPickerMessageId.value === messageId) {
        activeReactionPickerMessageId.value = null;
    } else {
        activeReactionPickerMessageId.value = messageId;
    }
};

const reactToMessage = async (message: ChatMessageItem, emoji: string) => {
    if (!currentUser.value) {
        alert('Please sign in to react to messages.');

        return;
    }

    activeReactionPickerMessageId.value = null;
    closeMobileActions();

    if (!message.reactions) {
        message.reactions = [];
    }

    // Optimistic UI update: Find currently reacted emoji by this user if any
    const currentlyReacted = message.reactions.find((r) => r.reacted);

    if (currentlyReacted && currentlyReacted.emoji === emoji) {
        // Toggle off same emoji
        currentlyReacted.reacted = false;
        currentlyReacted.count = Math.max(0, currentlyReacted.count - 1);

        if (currentlyReacted.count === 0) {
            message.reactions = message.reactions.filter(
                (r) => r.emoji !== emoji,
            );
        }
    } else {
        // Remove previous reaction if any
        if (currentlyReacted) {
            currentlyReacted.reacted = false;
            currentlyReacted.count = Math.max(0, currentlyReacted.count - 1);

            if (currentlyReacted.count === 0) {
                message.reactions = message.reactions.filter(
                    (r) => r.emoji !== currentlyReacted.emoji,
                );
            }
        }

        // Add new reaction
        const target = message.reactions.find((r) => r.emoji === emoji);

        if (target) {
            target.reacted = true;
            target.count += 1;
        } else {
            message.reactions.push({
                emoji,
                count: 1,
                reacted: true,
            });
        }
    }

    try {
        const token = (
            document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
        )?.content;
        const res = await fetch(`/api/chat/messages/${message.id}/reactions`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify({ emoji }),
        });

        if (res.ok) {
            const data = await res.json();

            if (data.reactions) {
                message.reactions = data.reactions;
            }
        }
    } catch (e) {
        console.error('Failed to toggle reaction', e);
    }
};

// Reactor Modal state & methods
const isReactorsModalOpen = ref(false);
const activeReactorsMessage = ref<ChatMessageItem | null>(null);
const selectedReactorTab = ref<string>('all');

const openReactorsModal = (message: ChatMessageItem, initialEmoji?: string) => {
    if (!message.reactions || message.reactions.length === 0) {
        return;
    }

    activeReactorsMessage.value = message;
    selectedReactorTab.value = initialEmoji || 'all';
    isReactorsModalOpen.value = true;
};

const closeReactorsModal = () => {
    isReactorsModalOpen.value = false;
    activeReactorsMessage.value = null;
    selectedReactorTab.value = 'all';
};

const allReactors = computed(() => {
    if (!activeReactorsMessage.value?.reactions) {
        return [];
    }

    const list: Array<{ user: ChatReactorUser; emoji: string }> = [];

    for (const r of activeReactorsMessage.value.reactions) {
        if (r.reactors && Array.isArray(r.reactors)) {
            for (const u of r.reactors) {
                list.push({ user: u, emoji: r.emoji });
            }
        }
    }

    return list;
});

const activeReactorsTotalCount = computed(() => {
    if (!activeReactorsMessage.value?.reactions) {
        return 0;
    }

    return activeReactorsMessage.value.reactions.reduce(
        (sum, r) => sum + r.count,
        0,
    );
});

const displayedReactors = computed(() => {
    if (!activeReactorsMessage.value?.reactions) {
        return [];
    }

    if (selectedReactorTab.value === 'all') {
        return allReactors.value;
    }

    const target = activeReactorsMessage.value.reactions.find(
        (r) => r.emoji === selectedReactorTab.value,
    );

    if (!target?.reactors) {
        return [];
    }

    return target.reactors.map((u) => ({
        user: u,
        emoji: target.emoji,
    }));
});

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
    closeMobileActions();

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
    if (!reportingMessage.value || isSubmittingReport.value) {
        return;
    }

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
            reportSuccessMessage.value =
                'Thank you. The report has been sent to our moderators.';
            setTimeout(() => {
                closeReportModal();
            }, 1800);
        } else {
            const err = await res.json();
            reportErrorMessage.value =
                err.message || 'Failed to submit report.';
        }
    } catch {
        reportErrorMessage.value = 'Network error. Please try again.';
    } finally {
        isSubmittingReport.value = false;
    }
};

const parseMessageSegments = (content: string): MessageSegment[] => {
    if (!content) {
        return [];
    }

    const mentionRegex = /(?<=^|\s)@([a-zA-Z0-9_.-]+)/g;
    const segments: MessageSegment[] = [];
    let lastIndex = 0;
    let match: RegExpExecArray | null;

    while ((match = mentionRegex.exec(content)) !== null) {
        const matchStart = match.index;

        if (matchStart > lastIndex) {
            segments.push({
                type: 'text',
                text: content.substring(lastIndex, matchStart),
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
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);

        if (date.toDateString() === today.toDateString()) {
            return 'Today';
        }

        if (date.toDateString() === yesterday.toDateString()) {
            return 'Yesterday';
        }

        return date.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year:
                date.getFullYear() !== today.getFullYear()
                    ? 'numeric'
                    : undefined,
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

// Smart Message Grouping: consecutive messages by same author within 2 minutes merge
const isGroupedWithPrevious = (idx: number) => {
    if (idx === 0) {
        return false;
    }

    if (shouldShowDateDivider(idx)) {
        return false;
    }

    const prev = messages.value[idx - 1];
    const curr = messages.value[idx];

    if (!prev || !curr) {
        return false;
    }

    if (Number(prev.user.id) !== Number(curr.user.id)) {
        return false;
    }

    if (curr.reply_to_content || curr.is_deleted || prev.is_deleted) {
        return false;
    }

    const prevTime = new Date(prev.created_at).getTime();
    const currTime = new Date(curr.created_at).getTime();

    return currTime - prevTime < 120000;
};

const isBanModalOpen = ref(false);
const selectedUserToBan = ref<ChatBanUser | null>(null);

const openBanModal = (user: ChatUser) => {
    closeMobileActions();
    selectedUserToBan.value = {
        id: user.id,
        name: user.name,
        username: user.username,
    };
    isBanModalOpen.value = true;
};

let removeNavigateListener: (() => void) | null = null;

const handleVisibilityOrFocus = () => {
    if (document.visibilityState === 'visible') {
        fetchLatestMessages(true);
    }
};

const handlePopState = () => {
    fetchLatestMessages(true);
    setupRealtime();
};

const handleDocumentClick = () => {
    activeReactionPickerMessageId.value = null;
    showMentionSuggestions.value = false;
};

onMounted(() => {
    initCooldown();
    setupRealtime();
    setupPresenceChannel();
    fetchLatestMessages(true);
    scrollToBottom(false);

    window.addEventListener('focus', () => fetchLatestMessages(true));
    window.addEventListener('pageshow', () => fetchLatestMessages(true));
    window.addEventListener('popstate', handlePopState);
    document.addEventListener('visibilitychange', handleVisibilityOrFocus);
    window.addEventListener('click', handleDocumentClick);

    removeNavigateListener = router.on('navigate', (event) => {
        if (event.detail.page.component === 'Chat/Index') {
            setupRealtime();
            fetchLatestMessages(true);
        }
    });
});

onUnmounted(() => {
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
    }

    if (removeNavigateListener) {
        removeNavigateListener();
    }

    window.removeEventListener('focus', () => fetchLatestMessages(true));
    window.removeEventListener('pageshow', () => fetchLatestMessages(true));
    window.removeEventListener('popstate', handlePopState);
    document.removeEventListener('visibilitychange', handleVisibilityOrFocus);
    window.removeEventListener('click', handleDocumentClick);

    const echo = getEcho();

    if (echo) {
        if (handlePusherReconnect) {
            try {
                const pusher = (
                    echo.connector as {
                        pusher?: {
                            connection?: {
                                unbind: (
                                    event: string,
                                    callback: () => void,
                                ) => void;
                            };
                        };
                    }
                )?.pusher;

                if (pusher?.connection) {
                    pusher.connection.unbind(
                        'connected',
                        handlePusherReconnect,
                    );
                }
            } catch {
                // Ignore
            }
        }

        echo.leave(chatChannelName.value);
        echo.leave(presenceChannelName.value);
    }
});
</script>

<template>
    <Head>
        <title>HSCStack Global Chat — Talk. Ask. Connect.</title>
        <meta
            name="description"
            content="Connect with fellow students, ask questions, share ideas, get help, and join the conversation on HSCStack Global Chat."
        />
        <meta
            property="og:title"
            content="HSCStack Global Chat — Talk. Ask. Connect."
        />
        <meta
            property="og:description"
            content="Connect with fellow students, ask questions, share ideas, get help, and join the conversation on HSCStack Global Chat."
        />
        <meta
            property="og:image"
            content="https://cdn.hscstack.site/images/og_chat.png"
        />
        <meta name="twitter:card" content="summary_large_image" />
        <meta
            name="twitter:title"
            content="HSCStack Global Chat — Talk. Ask. Connect."
        />
        <meta
            name="twitter:description"
            content="Connect with fellow students, ask questions, share ideas, get help, and join the conversation on HSCStack Global Chat."
        />
        <meta
            name="twitter:image"
            content="https://cdn.hscstack.site/images/og_chat.png"
        />
    </Head>

    <main class="mx-auto max-w-4xl px-3 py-3 sm:px-6 sm:py-5 lg:px-8">
        <!-- Compact Page Title & Subtitle + Rules Button -->
        <div
            class="mb-3 flex items-center justify-between border-b border-slate-100 pb-3 dark:border-zinc-800"
        >
            <div>
                <div class="flex items-center gap-2.5">
                    <h1
                        class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl dark:text-zinc-100"
                    >
                        Global Chat
                    </h1>
                    <div
                        v-if="activeUsersCount > 0"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200/80 bg-emerald-50/80 px-2.5 py-1 text-xs font-semibold text-emerald-700 shadow-2xs dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                    >
                        <span class="relative flex h-2 w-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                            ></span>
                            <span
                                class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                            ></span>
                        </span>
                        {{ activeUsersCount }} active
                    </div>
                </div>
                <p
                    class="mt-0.5 text-xs text-slate-500 sm:text-sm dark:text-zinc-400"
                >
                    অন্যান্য শিক্ষার্থীদের সাথে সরাসরি কথা বলুন ও প্রশ্ন শেয়ার
                    করুন।
                </p>
            </div>

            <div class="flex items-center gap-2">
                <!-- Rules / Guidelines Trigger Button -->
                <button
                    type="button"
                    @click="showRulesModal = true"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/80 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-100 active:scale-95 dark:border-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-200 dark:hover:bg-zinc-700"
                    title="Chat Rules & Guidelines"
                >
                    <Info
                        class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                    />
                    <span class="hidden sm:inline">Chat Rules</span>
                </button>
            </div>
        </div>

        <!-- Main Messenger Shell -->
        <div
            class="flex h-[calc(100dvh-13.5rem)] max-h-[760px] min-h-[480px] flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs sm:h-[calc(100dvh-15rem)] dark:border-zinc-800 dark:bg-zinc-900"
        >
            <!-- Scrollable Message Stream -->
            <div
                ref="messagesContainerRef"
                @scroll="handleScroll"
                class="relative flex-1 space-y-1 overflow-y-auto px-3 py-4 sm:px-5"
            >
                <!-- Empty State -->
                <div
                    v-if="messages.length === 0"
                    class="flex h-full flex-col items-center justify-center p-8 text-center"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400"
                    >
                        <Radio class="h-6 w-6" />
                    </div>
                    <p
                        class="mt-3 text-sm font-semibold text-slate-800 dark:text-zinc-200"
                    >
                        No messages yet
                    </p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-zinc-500">
                        Be the first to start the conversation!
                    </p>
                </div>

                <!-- Message Items -->
                <template v-for="(msg, idx) in messages" :key="msg.id">
                    <!-- Date Divider -->
                    <div
                        v-if="shouldShowDateDivider(idx)"
                        class="my-3 flex justify-center"
                    >
                        <span
                            class="rounded-full border border-slate-200/80 bg-slate-50 px-3 py-0.5 text-[10px] font-semibold text-slate-500 shadow-2xs dark:border-zinc-800 dark:bg-zinc-800/80 dark:text-zinc-400"
                        >
                            {{ formatDateDivider(msg.created_at) }}
                        </span>
                    </div>

                    <!-- Chat Message Row -->
                    <div
                        :id="`chat-msg-${msg.id}`"
                        class="group relative flex items-start gap-2.5 rounded-xl px-2.5 py-1.5 transition-colors duration-150 sm:gap-3 sm:px-3 sm:py-2"
                        :class="[
                            currentUser &&
                            Number(currentUser.id) === Number(msg.user.id)
                                ? 'bg-indigo-50/30 dark:bg-indigo-950/15'
                                : 'hover:bg-slate-50/80 dark:hover:bg-zinc-800/40',
                            highlightedMessageId === msg.id
                                ? 'bg-indigo-100/70 ring-2 ring-indigo-500/50 dark:bg-indigo-950/60'
                                : '',
                            activeReactionPickerMessageId === msg.id
                                ? 'z-30'
                                : 'z-0',
                            isGroupedWithPrevious(idx) ? '!pt-0.5' : 'mt-1',
                        ]"
                    >
                        <!-- Left Avatar Gutter -->
                        <div class="w-8 shrink-0 sm:w-9">
                            <Link
                                v-if="!isGroupedWithPrevious(idx)"
                                :href="`/u/${msg.user.username}`"
                                class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full font-semibold transition hover:opacity-85 sm:h-9 sm:w-9"
                                :class="
                                    currentUser &&
                                    Number(currentUser.id) ===
                                        Number(msg.user.id)
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-slate-100 text-slate-700 dark:bg-zinc-800 dark:text-zinc-300'
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
                            <span
                                v-else
                                class="block text-center text-[9px] text-slate-300 opacity-0 transition-opacity group-hover:opacity-100 dark:text-zinc-600"
                            >
                                {{ formatTime(msg.created_at) }}
                            </span>
                        </div>

                        <!-- Message Content Area -->
                        <div class="min-w-0 flex-1">
                            <!-- Header Info (Shown on First in Group) -->
                            <div
                                v-if="!isGroupedWithPrevious(idx)"
                                class="flex items-baseline gap-1.5"
                            >
                                <Link
                                    :href="`/u/${msg.user.username}`"
                                    class="truncate text-xs font-bold text-slate-900 transition hover:text-indigo-600 dark:text-zinc-100 dark:hover:text-indigo-400"
                                >
                                    {{ msg.user.name }}
                                </Link>

                                <span
                                    v-if="
                                        currentUser &&
                                        Number(currentUser.id) ===
                                            Number(msg.user.id)
                                    "
                                    class="py-0.2 rounded-md bg-indigo-100 px-1 text-[9px] font-bold text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300"
                                >
                                    You
                                </span>

                                <VerifiedBadge v-if="msg.user.is_verified" />

                                <span
                                    class="truncate text-[11px] text-slate-400 dark:text-zinc-500"
                                >
                                    @{{ msg.user.username }}
                                </span>

                                <span
                                    class="ml-auto text-[10px] text-slate-400 select-none sm:text-[11px] dark:text-zinc-500"
                                >
                                    {{ formatTime(msg.created_at) }}
                                </span>
                            </div>

                            <!-- Quoted Parent Snapshot (If Reply) -->
                            <div
                                v-if="msg.reply_to_content"
                                @click="
                                    msg.reply_to_id
                                        ? scrollToMessage(msg.reply_to_id)
                                        : null
                                "
                                class="mt-1 flex items-center gap-1.5 rounded-lg border-l-2 border-indigo-500 bg-slate-100/60 px-2 py-0.5 text-[11px] text-slate-600 transition dark:border-indigo-400 dark:bg-zinc-800/60 dark:text-zinc-400"
                                :class="
                                    msg.reply_to_id
                                        ? 'cursor-pointer hover:bg-indigo-50/60 dark:hover:bg-indigo-950/30'
                                        : ''
                                "
                                :title="
                                    msg.reply_to_id
                                        ? 'Jump to original message'
                                        : ''
                                "
                            >
                                <Reply class="h-3 w-3 shrink-0 opacity-70" />
                                <span class="truncate italic">
                                    "{{ msg.reply_to_content }}"
                                </span>
                            </div>

                            <!-- Message Text Body -->
                            <p
                                v-if="msg.is_deleted || msg.deleted_at"
                                class="mt-0.5 text-xs text-slate-400 italic select-none sm:text-sm dark:text-zinc-500"
                            >
                                {{ msg.content }}
                            </p>
                            <p
                                v-else
                                class="mt-0.5 text-xs leading-relaxed break-words whitespace-pre-wrap text-slate-800 sm:text-sm dark:text-zinc-200"
                            >
                                <template
                                    v-for="(seg, sIdx) in parseMessageSegments(
                                        msg.content,
                                    )"
                                    :key="sIdx"
                                >
                                    <Link
                                        v-if="
                                            seg.type === 'mention' &&
                                            seg.username
                                        "
                                        :href="`/u/${seg.username}`"
                                        class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                        @click.stop
                                    >
                                        {{ seg.text }}
                                    </Link>
                                    <span v-else>{{ seg.text }}</span>
                                </template>
                            </p>

                            <!-- Reaction Pills -->
                            <div
                                v-if="msg.reactions && msg.reactions.length > 0"
                                class="mt-1.5 flex flex-wrap items-center gap-1"
                            >
                                <button
                                    v-for="r in msg.reactions"
                                    :key="r.emoji"
                                    type="button"
                                    :disabled="
                                        !currentUser ||
                                        msg.is_deleted ||
                                        !!msg.deleted_at
                                    "
                                    @click.stop="reactToMessage(msg, r.emoji)"
                                    @contextmenu.prevent.stop="
                                        openReactorsModal(msg, r.emoji)
                                    "
                                    class="inline-flex cursor-pointer items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium transition select-none active:scale-95 disabled:cursor-default"
                                    :class="
                                        r.reacted
                                            ? 'border-indigo-300 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300'
                                            : 'border-slate-200 bg-slate-50/90 text-slate-600 hover:bg-slate-100 dark:border-zinc-800 dark:bg-zinc-800/80 dark:text-zinc-400 dark:hover:bg-zinc-800'
                                    "
                                    :title="
                                        r.users && r.users.length
                                            ? `Reacted by: ${r.users.join(', ')} · Right-click to view reactors`
                                            : ''
                                    "
                                >
                                    <span>{{ r.emoji }}</span>
                                    <span class="font-bold">{{ r.count }}</span>
                                </button>

                                <button
                                    type="button"
                                    @click.stop="openReactorsModal(msg)"
                                    class="inline-flex cursor-pointer items-center rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                                    title="View all reactions"
                                >
                                    <Users class="h-3 w-3" />
                                </button>
                            </div>
                        </div>

                        <!-- Floating Action Toolbar (Desktop on Hover or Active Picker) -->
                        <div
                            v-if="
                                (!msg.is_deleted && !msg.deleted_at) ||
                                ((can('manage chat') || canDelete) &&
                                    Number(currentUser?.id) !==
                                        Number(msg.user.id))
                            "
                            class="absolute -top-3.5 right-3 z-30 items-center gap-0.5 rounded-xl border border-slate-200/90 bg-white p-0.5 shadow-md dark:border-zinc-700 dark:bg-zinc-800"
                            :class="[
                                activeReactionPickerMessageId === msg.id
                                    ? 'flex ring-1 ring-black/5 dark:ring-white/10'
                                    : 'hidden sm:group-hover:flex',
                            ]"
                            @click.stop
                        >
                            <!-- Reaction Trigger (active messages only) -->
                            <div
                                v-if="!msg.is_deleted && !msg.deleted_at"
                                class="relative"
                            >
                                <button
                                    v-if="currentUser"
                                    type="button"
                                    @click.stop="toggleReactionPicker(msg.id)"
                                    class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-amber-50 hover:text-amber-600 dark:text-zinc-400 dark:hover:bg-amber-950/40 dark:hover:text-amber-400"
                                    :class="
                                        activeReactionPickerMessageId === msg.id
                                            ? 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400'
                                            : ''
                                    "
                                    title="Add reaction"
                                >
                                    <Smile class="h-3.5 w-3.5" />
                                </button>

                                <!-- Emoji Quick Popover -->
                                <div
                                    v-if="
                                        activeReactionPickerMessageId === msg.id
                                    "
                                    class="absolute right-0 bottom-full z-40 mb-1.5 flex items-center gap-0.5 rounded-full border border-slate-200/90 bg-white px-2 py-1 shadow-xl ring-1 ring-black/5 dark:border-zinc-700 dark:bg-zinc-800 dark:ring-white/10"
                                    @click.stop
                                >
                                    <button
                                        v-for="emoji in reactionEmojis"
                                        :key="emoji"
                                        type="button"
                                        @click.stop="reactToMessage(msg, emoji)"
                                        class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg text-base transition-transform select-none hover:scale-125 hover:bg-slate-100 active:scale-95 dark:hover:bg-zinc-700"
                                        :title="`React with ${emoji}`"
                                    >
                                        {{ emoji }}
                                    </button>
                                </div>
                            </div>

                            <!-- Reply (active messages only) -->
                            <button
                                v-if="
                                    !msg.is_deleted &&
                                    !msg.deleted_at &&
                                    currentUser &&
                                    canPost
                                "
                                type="button"
                                @click.stop="startReply(msg)"
                                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 dark:text-zinc-400 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400"
                                title="Reply"
                            >
                                <Reply class="h-3.5 w-3.5" />
                            </button>

                            <!-- Ban (Staff) - stays visible on deleted messages -->
                            <button
                                v-if="
                                    (can('manage chat') || canDelete) &&
                                    Number(currentUser?.id) !==
                                        Number(msg.user.id)
                                "
                                type="button"
                                @click.stop="openBanModal(msg.user)"
                                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:text-zinc-400 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                :title="`Ban @${msg.user.username}`"
                            >
                                <Ban class="h-3.5 w-3.5" />
                            </button>

                            <!-- Report (active messages only) -->
                            <button
                                v-if="
                                    !msg.is_deleted &&
                                    !msg.deleted_at &&
                                    currentUser &&
                                    Number(currentUser.id) !==
                                        Number(msg.user.id)
                                "
                                type="button"
                                @click.stop="openReportModal(msg)"
                                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-amber-50 hover:text-amber-600 dark:text-zinc-400 dark:hover:bg-amber-950/40 dark:hover:text-amber-400"
                                title="Report"
                            >
                                <Flag class="h-3.5 w-3.5" />
                            </button>

                            <!-- Delete (Staff, active messages only) -->
                            <button
                                v-if="
                                    !msg.is_deleted &&
                                    !msg.deleted_at &&
                                    canDelete
                                "
                                type="button"
                                @click.stop="deleteMessage(msg.id)"
                                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:text-zinc-400 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                title="Delete"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <!-- Mobile Action Trigger (3-dots) -->
                        <button
                            v-if="
                                (!msg.is_deleted && !msg.deleted_at) ||
                                ((can('manage chat') || canDelete) &&
                                    Number(currentUser?.id) !==
                                        Number(msg.user.id))
                            "
                            type="button"
                            @click.stop="openMobileActions(msg)"
                            class="shrink-0 p-1 text-slate-300 hover:text-slate-600 sm:hidden dark:text-zinc-600 dark:hover:text-zinc-300"
                        >
                            <MoreHorizontal class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </template>
            </div>

            <!-- Floating Scroll-To-Bottom Button -->
            <button
                v-if="showScrollButton"
                type="button"
                @click="scrollToBottom(true)"
                class="absolute right-6 bottom-20 z-20 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-slate-900 text-white shadow-lg transition hover:scale-105 active:scale-95 sm:right-8 sm:bottom-22 dark:bg-zinc-100 dark:text-zinc-900"
                title="Scroll to bottom"
            >
                <ArrowDown class="h-4 w-4" />
            </button>

            <!-- Bottom Input Bar Area -->
            <footer
                class="border-t border-slate-100 bg-white p-3 sm:p-4 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <!-- Active Reply Banner -->
                <div
                    v-if="activeReplyTo"
                    class="mb-2 flex items-center justify-between rounded-xl border border-indigo-100 bg-indigo-50/70 px-3 py-1.5 text-xs text-indigo-900 dark:border-indigo-900/50 dark:bg-indigo-950/40 dark:text-indigo-200"
                >
                    <div class="flex min-w-0 items-center gap-1.5 truncate">
                        <Reply
                            class="h-3.5 w-3.5 shrink-0 text-indigo-600 dark:text-indigo-400"
                        />
                        <span class="truncate">
                            Replying to
                            <strong class="font-semibold">{{
                                activeReplyTo.user.name
                            }}</strong
                            >:
                            <span class="italic opacity-80"
                                >"{{ activeReplyTo.content }}"</span
                            >
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="cancelReply"
                        class="cursor-pointer rounded-lg p-0.5 text-indigo-500 hover:bg-indigo-100 dark:text-indigo-400 dark:hover:bg-indigo-900/60"
                        title="Cancel reply"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>

                <!-- Input Form when User can post -->
                <form
                    v-if="canPost"
                    @submit.prevent="sendMessage"
                    class="flex items-center gap-2"
                >
                    <div class="relative flex-1">
                        <!-- Mention Autocomplete Suggestions Popup -->
                        <div
                            v-if="showMentionSuggestions"
                            class="absolute bottom-full left-0 z-40 mb-2 w-full max-w-xs overflow-hidden rounded-xl border border-slate-200/90 bg-white p-1.5 shadow-xl ring-1 ring-black/5 sm:max-w-sm dark:border-zinc-700 dark:bg-zinc-800 dark:ring-white/10"
                            @click.stop
                        >
                            <div
                                class="flex items-center gap-1 border-b border-slate-100 px-2.5 py-1 pb-1.5 text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:border-zinc-700/60 dark:text-zinc-400"
                            >
                                <AtSign class="h-3 w-3 text-indigo-500" />
                                <span>Mention user</span>
                            </div>

                            <!-- Matching users list when typing -->
                            <div
                                v-if="
                                    mentionQuery.trim() &&
                                    filteredMentionUsers.length > 0
                                "
                                class="max-h-48 space-y-0.5 overflow-y-auto pt-1"
                            >
                                <button
                                    v-for="(user, idx) in filteredMentionUsers"
                                    :key="user.id"
                                    type="button"
                                    @mousedown.prevent="insertMention(user)"
                                    @mouseenter="selectedMentionIndex = idx"
                                    class="flex w-full cursor-pointer items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition select-none"
                                    :class="
                                        selectedMentionIndex === idx
                                            ? 'bg-indigo-50 text-indigo-900 dark:bg-indigo-950/60 dark:text-indigo-200'
                                            : 'text-slate-700 hover:bg-slate-50 dark:text-zinc-300 dark:hover:bg-zinc-700/50'
                                    "
                                >
                                    <div
                                        class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-[10px] font-semibold text-slate-700 dark:bg-zinc-700 dark:text-zinc-300"
                                    >
                                        <img
                                            v-if="user.image_url"
                                            :src="user.image_url"
                                            :alt="user.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <span v-else>{{
                                            user.name?.charAt(0) || 'U'
                                        }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1">
                                            <span
                                                class="truncate text-xs font-semibold"
                                                >{{ user.name }}</span
                                            >
                                            <VerifiedBadge
                                                v-if="user.is_verified"
                                            />
                                        </div>
                                        <div
                                            class="truncate text-[10px] text-slate-400 dark:text-zinc-500"
                                        >
                                            @{{ user.username }}
                                        </div>
                                    </div>
                                </button>
                            </div>

                            <!-- No results found for query -->
                            <div
                                v-else-if="mentionQuery.trim()"
                                class="px-3 py-2.5 text-center text-xs text-slate-500 dark:text-zinc-400"
                            >
                                <p
                                    class="font-medium text-slate-600 dark:text-zinc-300"
                                >
                                    No recent chatters matched
                                </p>
                                <p
                                    class="mt-0.5 text-[11px] text-slate-400 dark:text-zinc-500"
                                >
                                    Write full username if not suggested
                                </p>
                            </div>

                            <!-- Initial state when just typing '@' -->
                            <div
                                v-else
                                class="px-3 py-2.5 text-center text-xs text-slate-500 dark:text-zinc-400"
                            >
                                <p
                                    class="font-medium text-slate-600 dark:text-zinc-300"
                                >
                                    Start typing to get suggestions...
                                </p>
                                <p
                                    class="mt-0.5 text-[11px] text-slate-400 dark:text-zinc-500"
                                >
                                    Type a name or username
                                </p>
                            </div>
                        </div>

                        <input
                            ref="messageInputRef"
                            v-model="inputContent"
                            type="text"
                            :disabled="isSending || cooldownSeconds > 0"
                            :placeholder="
                                activeReplyTo
                                    ? `Reply to ${activeReplyTo.user.name}...`
                                    : 'Send a message...'
                            "
                            :maxlength="maxLengthLimit"
                            @input="checkMentionTrigger"
                            @click="checkMentionTrigger"
                            @keyup="checkMentionTrigger"
                            @keydown="handleInputKeydown"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none disabled:opacity-60 sm:text-sm dark:border-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-indigo-400 dark:focus:bg-zinc-800"
                        />
                        <span
                            v-if="inputContent.length > maxLengthLimit - 60"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-[10px] font-medium text-slate-400 dark:text-zinc-500"
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

                <!-- Restricted State Notice -->
                <div
                    v-else
                    class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-2.5 text-xs dark:border-zinc-800 dark:bg-zinc-800/40"
                >
                    <div
                        class="flex items-center gap-2 text-slate-600 dark:text-zinc-400"
                    >
                        <Lock
                            class="h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-zinc-500"
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
            </footer>
        </div>

        <!-- Mobile Action Sheet Bottom Drawer -->
        <div
            v-if="mobileActionMessage"
            class="fixed inset-0 z-50 flex items-end bg-black/50 backdrop-blur-xs sm:hidden"
            @click.self="closeMobileActions"
        >
            <div
                class="w-full rounded-t-2xl border-t border-slate-200 bg-white p-4 shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <!-- Header with message preview -->
                <div
                    class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-zinc-800"
                >
                    <span
                        class="text-xs font-bold text-slate-700 dark:text-zinc-300"
                    >
                        Message by @{{ mobileActionMessage.user.username }}
                    </span>
                    <button
                        type="button"
                        @click="closeMobileActions"
                        class="rounded-lg p-1 text-slate-400"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Quick Emoji Reactions (active messages only) -->
                <div
                    v-if="
                        currentUser &&
                        !mobileActionMessage.is_deleted &&
                        !mobileActionMessage.deleted_at
                    "
                    class="my-3 flex justify-between gap-1"
                >
                    <button
                        v-for="emoji in reactionEmojis"
                        :key="emoji"
                        type="button"
                        @click="reactToMessage(mobileActionMessage, emoji)"
                        class="cursor-pointer text-xl transition active:scale-95"
                    >
                        {{ emoji }}
                    </button>
                </div>

                <!-- Action Rows -->
                <div class="space-y-1 pt-1 text-xs">
                    <button
                        v-if="
                            mobileActionMessage.reactions &&
                            mobileActionMessage.reactions.length > 0
                        "
                        type="button"
                        @click="
                            openReactorsModal(mobileActionMessage);
                            closeMobileActions();
                        "
                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 font-semibold text-slate-700 hover:bg-slate-100 active:scale-98 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        <Smile class="h-4 w-4 text-amber-500" />
                        <span>
                            View Reactions ({{
                                mobileActionMessage.reactions.reduce(
                                    (sum, r) => sum + r.count,
                                    0,
                                )
                            }})
                        </span>
                    </button>

                    <button
                        v-if="
                            currentUser &&
                            canPost &&
                            !mobileActionMessage.is_deleted &&
                            !mobileActionMessage.deleted_at
                        "
                        type="button"
                        @click="startReply(mobileActionMessage)"
                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 font-semibold text-slate-700 hover:bg-slate-100 active:scale-98 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        <Reply class="h-4 w-4 text-indigo-500" />
                        <span>Reply</span>
                    </button>

                    <button
                        v-if="
                            currentUser &&
                            Number(currentUser.id) !==
                                Number(mobileActionMessage.user.id) &&
                            !mobileActionMessage.is_deleted &&
                            !mobileActionMessage.deleted_at
                        "
                        type="button"
                        @click="openReportModal(mobileActionMessage)"
                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 font-semibold text-amber-600 hover:bg-amber-50 active:scale-98 dark:text-amber-400 dark:hover:bg-amber-950/40"
                    >
                        <Flag class="h-4 w-4" />
                        <span>Report Message</span>
                    </button>

                    <!-- Ban User (Staff) - stays visible on deleted messages -->
                    <button
                        v-if="
                            (can('manage chat') || canDelete) &&
                            Number(currentUser?.id) !==
                                Number(mobileActionMessage.user.id)
                        "
                        type="button"
                        @click="openBanModal(mobileActionMessage.user)"
                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 font-semibold text-rose-600 hover:bg-rose-50 active:scale-98 dark:text-rose-400 dark:hover:bg-rose-950/40"
                    >
                        <Ban class="h-4 w-4" />
                        <span>Ban User</span>
                    </button>

                    <button
                        v-if="
                            canDelete &&
                            !mobileActionMessage.is_deleted &&
                            !mobileActionMessage.deleted_at
                        "
                        type="button"
                        @click="deleteMessage(mobileActionMessage.id)"
                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 font-semibold text-rose-600 hover:bg-rose-50 active:scale-98 dark:text-rose-400 dark:hover:bg-rose-950/40"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span>Delete Message</span>
                    </button>
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
                class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 pb-3.5 dark:border-zinc-800"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                        >
                            <Flag class="h-4 w-4" />
                        </div>
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-zinc-100"
                        >
                            Report Message
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="closeReportModal"
                        class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
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
                        class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-zinc-800 dark:bg-zinc-800/60"
                    >
                        <div
                            class="flex items-center justify-between text-[11px] text-slate-500 dark:text-zinc-400"
                        >
                            <span
                                class="font-semibold text-slate-700 dark:text-zinc-300"
                            >
                                {{ reportingMessage.user.name }} (@{{
                                    reportingMessage.user.username
                                }})
                            </span>
                            <span>{{
                                formatTime(reportingMessage.created_at)
                            }}</span>
                        </div>
                        <p
                            class="mt-1.5 text-xs break-words text-slate-800 dark:text-zinc-200"
                        >
                            "{{ reportingMessage.content }}"
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-zinc-300"
                        >
                            Why are you reporting this message?
                        </label>
                        <select
                            v-model="reportReason"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-2 text-xs font-medium text-slate-800 transition outline-none focus:border-indigo-500 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:focus:border-indigo-400 dark:focus:bg-zinc-800"
                        >
                            <option
                                v-for="reason in reportReasons"
                                :key="reason"
                                :value="reason"
                                class="bg-white text-slate-800 dark:bg-zinc-800 dark:text-zinc-200"
                            >
                                {{ reason }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="closeReportModal"
                            class="cursor-pointer rounded-xl border border-slate-200 bg-transparent px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
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

        <!-- Reactions Modal -->
        <div
            v-if="isReactorsModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                @click="closeReactorsModal"
            ></div>

            <div
                class="relative w-full max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl transition-all dark:border-zinc-800 dark:bg-zinc-900"
            >
                <button
                    @click="closeReactorsModal"
                    class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                >
                    <X class="h-4 w-4" />
                </button>

                <div class="mb-4 flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                    >
                        <Smile class="h-4 w-4 stroke-[2.2]" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-zinc-100"
                        >
                            Message Reactions
                        </h3>
                        <p
                            class="text-[11px] font-medium text-slate-500 dark:text-zinc-400"
                        >
                            {{ activeReactorsTotalCount }} reaction{{
                                activeReactorsTotalCount === 1 ? '' : 's'
                            }}
                        </p>
                    </div>
                </div>

                <!-- Tabs -->
                <div
                    v-if="
                        activeReactorsMessage?.reactions &&
                        activeReactorsMessage.reactions.length > 1
                    "
                    class="mb-3 flex flex-wrap items-center gap-1.5 border-b border-slate-100 pb-2.5 dark:border-zinc-800"
                >
                    <button
                        type="button"
                        @click="selectedReactorTab = 'all'"
                        class="inline-flex cursor-pointer items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium transition select-none"
                        :class="
                            selectedReactorTab === 'all'
                                ? 'border-indigo-300 bg-indigo-50 font-semibold text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300'
                                : 'border-slate-200 bg-slate-50/90 text-slate-600 hover:bg-slate-100 dark:border-zinc-800 dark:bg-zinc-800/80 dark:text-zinc-400'
                        "
                    >
                        <span>All</span>
                        <span class="font-bold">{{
                            activeReactorsTotalCount
                        }}</span>
                    </button>
                    <button
                        v-for="r in activeReactorsMessage.reactions"
                        :key="r.emoji"
                        type="button"
                        @click="selectedReactorTab = r.emoji"
                        class="inline-flex cursor-pointer items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium transition select-none"
                        :class="
                            selectedReactorTab === r.emoji
                                ? 'border-indigo-300 bg-indigo-50 font-semibold text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300'
                                : 'border-slate-200 bg-slate-50/90 text-slate-600 hover:bg-slate-100 dark:border-zinc-800 dark:bg-zinc-800/80 dark:text-zinc-400'
                        "
                    >
                        <span>{{ r.emoji }}</span>
                        <span class="font-bold">{{ r.count }}</span>
                    </button>
                </div>

                <!-- Reactors List -->
                <div
                    class="-mx-1 max-h-72 divide-y divide-slate-100 overflow-y-auto px-1 dark:divide-zinc-800/80"
                >
                    <div
                        v-if="displayedReactors.length === 0"
                        class="py-6 text-center text-xs text-slate-500 dark:text-zinc-400"
                    >
                        No reactions recorded.
                    </div>

                    <div
                        v-for="(item, idx) in displayedReactors"
                        :key="`${item.user.id}-${item.emoji}-${idx}`"
                        class="flex items-center justify-between gap-2"
                    >
                        <UserListItem
                            :user="item.user"
                            class="min-w-0 flex-1"
                        />
                        <span
                            class="shrink-0 rounded-lg bg-slate-100 px-2 py-0.5 text-sm dark:bg-zinc-800"
                        >
                            {{ item.emoji }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reusable Quick Ban Moderation Modal (Staff) -->
        <ChatBanModal
            :is-open="isBanModalOpen"
            :user="selectedUserToBan"
            @close="isBanModalOpen = false"
        />

        <!-- PWA Install Modal Prompt -->
        <PwaInstallPrompt variant="modal" />

        <!-- Chat Guidelines & Rules Alert Dialog Modal -->
        <div
            v-if="showRulesModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                @click="showRulesModal = false"
            ></div>

            <div
                class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl transition-all sm:p-6 dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-start justify-between border-b border-slate-100 pb-4 dark:border-zinc-800"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-zinc-100"
                            >
                                Global Chat Rules & Guidelines
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-zinc-400"
                            >
                                সবার জন্য চ্যাট নিরাপদ ও ফ্রেন্ডলি রাখতে নিচের
                                নিয়মগুলো মেনে চলুন।
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showRulesModal = false"
                        class="cursor-pointer rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Rules List -->
                <div
                    class="my-4 space-y-3.5 text-xs text-slate-700 dark:text-zinc-300"
                >
                    <div class="flex items-start gap-2.5">
                        <div
                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300"
                        >
                            1
                        </div>
                        <div>
                            <strong
                                class="font-semibold text-slate-900 dark:text-zinc-100"
                                >পরস্পরকে সম্মান করুন (Respectful
                                Environment):</strong
                            >
                            <p class="mt-0.5 text-slate-500 dark:text-zinc-400">
                                অন্য শিক্ষার্থী ও মডারেটরদের সাথে শালীন আচরণ
                                বজায় রাখুন। কোনো ধরনের ব্যক্তিগত আক্রমণ, বুলিং
                                বা হেট স্পিচ কঠোরভাবে নিষিদ্ধ।
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5">
                        <div
                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300"
                        >
                            2
                        </div>
                        <div>
                            <strong
                                class="font-semibold text-slate-900 dark:text-zinc-100"
                                >খারাপ ভাষা ও গালিগালাজ নিষেধ (No Abuse /
                                Slang):</strong
                            >
                            <p class="mt-0.5 text-slate-500 dark:text-zinc-400">
                                বাংলা, ইংরেজি বা বাংলিশ কোনো ভাষাতেই গালাগালি বা
                                অশালীন শব্দ ব্যবহার করা যাবে না। এমন মেসেজ
                                অটোমেটিক ব্লক হবে।
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5">
                        <div
                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300"
                        >
                            3
                        </div>
                        <div>
                            <strong
                                class="font-semibold text-slate-900 dark:text-zinc-100"
                                >স্প্যামিং ও অ্যাডভার্টাইজিং নিষেধ (No
                                Spam):</strong
                            >
                            <p class="mt-0.5 text-slate-500 dark:text-zinc-400">
                                একই মেসেজ বারবার পাঠানো, চ্যাট ফ্লাড করা বা
                                অনুমতি ছাড়া কোনো প্রোমোশন বা অপ্রাসঙ্গিক লিংক
                                শেয়ার করা যাবে না।
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5">
                        <div
                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300"
                        >
                            4
                        </div>
                        <div>
                            <strong
                                class="font-semibold text-slate-900 dark:text-zinc-100"
                                >অনুপযুক্ত মেসেজ রিপোর্ট করুন (Report
                                Violations):</strong
                            >
                            <p class="mt-0.5 text-slate-500 dark:text-zinc-400">
                                কারো মেসেজে নিয়ম লঙ্ঘন দেখতে পেলে মেসেজের
                                ডানপাশে থাকা রিপোর্ট বাটনে ক্লিক করে মডারেটরদের
                                জানান।
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Notice -->
                <div
                    class="rounded-xl border border-amber-200/80 bg-amber-50/70 p-3 text-[11px] text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300"
                >
                    <div class="flex items-center gap-1.5 font-bold">
                        <AlertCircle
                            class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400"
                        />
                        <span>অটো-ব্যান ও এনফোর্সমেন্ট পলিসি:</span>
                    </div>
                    <p class="mt-1 leading-relaxed">
                        একটি মেসেজে ৫ জন শিক্ষার্থীর রিপোর্ট (৫ Reports) পড়লে
                        সংশ্লিষ্ট ব্যবহারকারী
                        <strong
                            >স্বয়ংক্রিয়ভাবে ১ দিনের জন্য চ্যাট ব্যান</strong
                        >
                        হবেন। এছাড়া নিয়ম ভঙ্গে মডারেটররা তাৎক্ষণিক স্থায়ী ব্যান
                        দিতে পারেন।
                    </p>
                </div>

                <!-- Support Notice in Chat Modal -->
                <div
                    class="mt-3 flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/80 px-3.5 py-2.5 text-[11px] text-slate-600 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-400"
                >
                    <div class="flex items-center gap-2">
                        <LifeBuoy
                            class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                        />
                        <span>কোনো টেকনিক্যাল সমস্যা বা সহায়তার জন্য?</span>
                    </div>
                    <Link
                        href="/support"
                        class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                    >
                        সাপোর্ট সেন্টার &rarr;
                    </Link>
                </div>

                <!-- Footer Close Button -->
                <div class="mt-4 flex justify-end">
                    <button
                        type="button"
                        @click="showRulesModal = false"
                        class="cursor-pointer rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        বুঝেছি (Close)
                    </button>
                </div>
            </div>
        </div>
    </main>
</template>
