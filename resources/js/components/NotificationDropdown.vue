<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Check,
    CheckCheck,
    Loader2,
    MessageSquare,
    AtSign,
    Trash2,
    ShieldAlert,
    CheckCircle2,
    HeartHandshake,
    Heart,
    ThumbsUp,
    Clock,
    LifeBuoy,
} from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface NotificationItem {
    id: string;
    type: string;
    data: {
        type?: string;
        title?: string;
        message?: string;
        url?: string;
        commenter_name?: string;
        mentioner_name?: string;
        post_title?: string;
        [key: string]: any;
    };
    read_at: string | null;
    created_at: string;
    created_at_human: string;
}

const page = usePage();
const isOpen = ref(false);
const isLoading = ref(false);
const isLoadingMore = ref(false);
const isMarkingAll = ref(false);
const isClearingAll = ref(false);
const notifications = ref<NotificationItem[]>([]);
const hasMore = ref(false);
const currentPage = ref(1);
const dropdownRef = ref<HTMLElement | null>(null);

// Get initial unread count from Inertia shared prop
const unreadCount = ref<number>(
    (page.props.auth as any)?.unread_notifications_count ?? 0,
);

// Keep unreadCount in sync if page props change (e.g. after Inertia navigation)
watch(
    () => (page.props.auth as any)?.unread_notifications_count,
    (newCount) => {
        if (typeof newCount === 'number') {
            unreadCount.value = newCount;
        }
    },
);

const getCsrfToken = (): string => {
    return (
        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
            ?.content || ''
    );
};

const fetchNotifications = async () => {
    isLoading.value = true;
    currentPage.value = 1;

    try {
        const res = await fetch('/notifications?per_page=10&page=1', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.ok) {
            const data = await res.json();
            notifications.value = data.notifications || [];
            hasMore.value = Boolean(data.has_more);
            currentPage.value = data.current_page || 1;

            if (typeof data.unread_count === 'number') {
                unreadCount.value = data.unread_count;
            }
        }
    } catch (e) {
        console.error('Failed to fetch notifications:', e);
    } finally {
        isLoading.value = false;
    }
};

const loadMore = async () => {
    if (isLoadingMore.value || !hasMore.value) {
        return;
    }

    isLoadingMore.value = true;
    const nextPage = currentPage.value + 1;

    try {
        const res = await fetch(`/notifications?per_page=10&page=${nextPage}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.ok) {
            const data = await res.json();
            const newItems = data.notifications || [];
            notifications.value = [...notifications.value, ...newItems];
            hasMore.value = Boolean(data.has_more);
            currentPage.value = data.current_page || nextPage;

            if (typeof data.unread_count === 'number') {
                unreadCount.value = data.unread_count;
            }
        }
    } catch (e) {
        console.error('Failed to load more notifications:', e);
    } finally {
        isLoadingMore.value = false;
    }
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        fetchNotifications();
    }
};

const closeDropdown = () => {
    isOpen.value = false;
};

const handleClickOutside = (e: MouseEvent | TouchEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
        closeDropdown();
    }
};

const handleScroll = (e: Event) => {
    if (!isOpen.value) {
        return;
    }

    // Ignore scroll events originating from inside the dropdown itself (e.g. scrolling the notification items)
    if (
        dropdownRef.value &&
        e.target instanceof Node &&
        dropdownRef.value.contains(e.target)
    ) {
        return;
    }

    closeDropdown();
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && isOpen.value) {
        closeDropdown();
    }
};

const markSingleAsRead = async (notification: NotificationItem, e?: Event) => {
    if (e) {
        e.stopPropagation();
    }

    if (notification.read_at) {
        return;
    }

    // Optimistically mark as read in local state
    notification.read_at = new Date().toISOString();

    if (unreadCount.value > 0) {
        unreadCount.value--;
    }

    if (page.props.auth) {
        (page.props.auth as any).unread_notifications_count = unreadCount.value;
    }

    try {
        await fetch(`/notifications/${notification.id}/read`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });
    } catch (err) {
        console.error('Failed to mark notification as read:', err);
    }
};

const markAsRead = async (notification: NotificationItem) => {
    if (!notification.read_at) {
        // Optimistically mark as read in local state
        notification.read_at = new Date().toISOString();

        if (unreadCount.value > 0) {
            unreadCount.value--;
        }

        if (page.props.auth) {
            (page.props.auth as any).unread_notifications_count =
                unreadCount.value;
        }

        try {
            await fetch(`/notifications/${notification.id}/read`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });
        } catch (e) {
            console.error('Failed to mark notification as read:', e);
        }
    }

    closeDropdown();

    if (notification.data?.url) {
        router.visit(notification.data.url);
    }
};

const markAllAsRead = async () => {
    if (isMarkingAll.value || unreadCount.value === 0) {
        return;
    }

    isMarkingAll.value = true;
    // Optimistic update
    notifications.value.forEach((n) => {
        if (!n.read_at) {
            n.read_at = new Date().toISOString();
        }
    });
    unreadCount.value = 0;

    if (page.props.auth) {
        (page.props.auth as any).unread_notifications_count = 0;
    }

    try {
        await fetch('/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });
    } catch (e) {
        console.error('Failed to mark all notifications as read:', e);
    } finally {
        isMarkingAll.value = false;
    }
};

const clearAll = async () => {
    if (isClearingAll.value || notifications.value.length === 0) {
        return;
    }

    if (!confirm('Are you sure you want to clear all notifications?')) {
        return;
    }

    isClearingAll.value = true;
    notifications.value = [];
    unreadCount.value = 0;
    hasMore.value = false;

    if (page.props.auth) {
        (page.props.auth as any).unread_notifications_count = 0;
    }

    try {
        await fetch('/notifications/clear-all', {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });
    } catch (e) {
        console.error('Failed to clear notifications:', e);
    } finally {
        isClearingAll.value = false;
    }
};

let removeNavListener: (() => void) | null = null;

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    window.addEventListener('scroll', handleScroll, {
        passive: true,
        capture: true,
    });
    window.addEventListener('resize', closeDropdown, { passive: true });
    removeNavListener = router.on('navigate', () => {
        closeDropdown();
    });
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('scroll', handleScroll, {
        capture: true,
    } as any);
    window.removeEventListener('resize', closeDropdown);

    if (removeNavListener) {
        removeNavListener();
    }
});
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <!-- Bell Trigger Button -->
        <button
            @click="toggleDropdown"
            type="button"
            class="relative flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200/80 bg-slate-50 text-slate-600 transition-all hover:bg-slate-100 hover:text-slate-900 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
            :aria-expanded="isOpen"
            aria-label="Notifications"
            title="Notifications"
        >
            <Bell class="h-4 w-4" />

            <!-- Unread Badge Indicator -->
            <span
                v-if="unreadCount > 0"
                class="animate-in zoom-in-50 absolute -top-1 -right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-black text-white shadow-xs"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Menu -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-x-3 top-[68px] z-50 mx-auto max-w-md origin-top overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:absolute sm:inset-auto sm:top-auto sm:right-0 sm:mx-0 sm:mt-2 sm:w-96 sm:max-w-none sm:origin-top-right dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-gray-800"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs font-bold text-slate-900 dark:text-gray-100"
                        >
                            Notifications
                        </span>
                        <span
                            v-if="unreadCount > 0"
                            class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            {{ unreadCount }} new
                        </span>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllAsRead"
                            :disabled="isMarkingAll"
                            type="button"
                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 transition-colors hover:text-indigo-600 disabled:opacity-50 dark:text-gray-400 dark:hover:text-indigo-400"
                            title="Mark all as read"
                        >
                            <Loader2
                                v-if="isMarkingAll"
                                class="h-3 w-3 animate-spin"
                            />
                            <CheckCheck v-else class="h-3 w-3" />
                            <span>Mark all read</span>
                        </button>

                        <button
                            v-if="notifications.length > 0"
                            @click="clearAll"
                            :disabled="isClearingAll"
                            type="button"
                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400 transition-colors hover:text-rose-500 disabled:opacity-50 dark:text-gray-500 dark:hover:text-rose-400"
                            title="Clear all notifications"
                        >
                            <Loader2
                                v-if="isClearingAll"
                                class="h-3 w-3 animate-spin"
                            />
                            <Trash2 v-else class="h-3 w-3" />
                            <span>Clear</span>
                        </button>
                    </div>
                </div>

                <!-- Notifications List -->
                <div
                    class="max-h-[calc(100vh-140px)] divide-y divide-slate-100 overflow-y-auto sm:max-h-[380px] dark:divide-gray-800/60"
                >
                    <!-- Loading Initial State -->
                    <div
                        v-if="isLoading && notifications.length === 0"
                        class="flex flex-col items-center justify-center py-10 text-slate-400 dark:text-gray-500"
                    >
                        <Loader2
                            class="mb-2 h-5 w-5 animate-spin text-indigo-500"
                        />
                        <span class="text-xs font-medium"
                            >Loading notifications...</span
                        >
                    </div>

                    <!-- Empty State -->
                    <div
                        v-else-if="notifications.length === 0"
                        class="flex flex-col items-center justify-center px-4 py-12 text-center text-slate-400 dark:text-gray-500"
                    >
                        <div
                            class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-gray-800 dark:text-gray-500"
                        >
                            <Bell class="h-5 w-5" />
                        </div>
                        <p
                            class="text-xs font-bold text-slate-700 dark:text-gray-300"
                        >
                            No notifications yet
                        </p>
                        <p
                            class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            We'll notify you when there's an update on your
                            posts or replies.
                        </p>
                    </div>

                    <!-- Items List -->
                    <template v-else>
                        <div
                            v-for="item in notifications"
                            :key="item.id"
                            @click="markAsRead(item)"
                            role="button"
                            tabindex="0"
                            @keydown.enter="markAsRead(item)"
                            @keydown.space.prevent="markAsRead(item)"
                            class="group flex w-full cursor-pointer items-start gap-3 p-3.5 text-left transition-colors hover:bg-slate-50 dark:hover:bg-gray-800/50"
                            :class="
                                !item.read_at
                                    ? 'bg-indigo-50/40 dark:bg-indigo-950/20'
                                    : ''
                            "
                        >
                            <!-- Icon Avatar based on type -->
                            <div
                                class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl transition-colors"
                                :class="
                                    !item.read_at
                                        ? 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/60 dark:text-indigo-400'
                                        : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'
                                "
                            >
                                <MessageSquare
                                    v-if="
                                        item.data?.type === 'forum_comment' ||
                                        item.data?.type === 'blog_comment'
                                    "
                                    class="h-4 w-4 text-indigo-500"
                                />
                                <Heart
                                    v-else-if="
                                        item.data?.type === 'blog_reaction'
                                    "
                                    class="h-4 w-4 text-rose-500"
                                />
                                <ThumbsUp
                                    v-else-if="
                                        item.data?.type === 'forum_vote' ||
                                        item.data?.type === 'node_vote'
                                    "
                                    class="h-4 w-4 text-emerald-500"
                                />
                                <AtSign
                                    v-else-if="
                                        item.data?.type === 'user_mention'
                                    "
                                    class="h-4 w-4 text-indigo-500"
                                />
                                <HeartHandshake
                                    v-else-if="
                                        item.data?.type === 'user_appreciation'
                                    "
                                    class="h-4 w-4 text-pink-500"
                                />
                                <LifeBuoy
                                    v-else-if="
                                        item.data?.type === 'support_ticket'
                                    "
                                    class="h-4 w-4 text-indigo-500"
                                />
                                <Clock
                                    v-else-if="
                                        item.data?.type === 'forum_pending'
                                    "
                                    class="h-4 w-4 text-amber-500"
                                />
                                <ShieldAlert
                                    v-else-if="
                                        item.data?.type === 'chat_report' ||
                                        item.data?.type === 'forum_report' ||
                                        (item.data?.type ===
                                            'user_suspension' &&
                                            item.data?.is_banned) ||
                                        (item.data?.type === 'forum_status' &&
                                            (item.data?.status === 'flagged' ||
                                                item.data?.status ===
                                                    'rejected' ||
                                                item.data?.status === 'locked'))
                                    "
                                    class="h-4 w-4 text-amber-500"
                                />
                                <CheckCircle2
                                    v-else-if="
                                        (item.data?.type === 'forum_status' &&
                                            item.data?.status === 'approved') ||
                                        (item.data?.type ===
                                            'user_suspension' &&
                                            !item.data?.is_banned)
                                    "
                                    class="h-4 w-4 text-emerald-500"
                                />
                                <Bell v-else class="h-4 w-4" />
                            </div>

                            <!-- Content -->
                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-center justify-between gap-1.5"
                                >
                                    <p
                                        class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                                        :class="
                                            !item.read_at
                                                ? 'text-indigo-950 dark:text-indigo-200'
                                                : ''
                                        "
                                    >
                                        {{ item.data?.title || 'Notification' }}
                                    </p>

                                    <!-- Unread Dot Indicator -->
                                    <span
                                        v-if="!item.read_at"
                                        class="h-2 w-2 shrink-0 rounded-full bg-indigo-600 ring-2 ring-white dark:bg-indigo-400 dark:ring-gray-900"
                                    />
                                </div>

                                <p
                                    v-if="item.data?.message"
                                    class="mt-0.5 line-clamp-2 text-[11px] leading-relaxed text-slate-600 dark:text-gray-400"
                                >
                                    {{ item.data.message }}
                                </p>

                                <div
                                    class="mt-2 flex items-center justify-between gap-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-[10px] font-medium text-slate-400 dark:text-gray-500"
                                        >
                                            {{ item.created_at_human }}
                                        </span>
                                        <span
                                            v-if="item.data?.url"
                                            class="text-[10px] font-semibold text-indigo-600 opacity-0 transition-opacity group-hover:opacity-100 dark:text-indigo-400"
                                        >
                                            View &rarr;
                                        </span>
                                    </div>

                                    <!-- Dedicated Mark As Read Icon Button in Right Bottom -->
                                    <button
                                        v-if="!item.read_at"
                                        @click.stop="
                                            markSingleAsRead(item, $event)
                                        "
                                        type="button"
                                        class="flex h-6 w-6 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-indigo-200/60 bg-indigo-50/80 text-indigo-600 shadow-2xs transition hover:border-indigo-300 hover:bg-indigo-100 hover:text-indigo-700 active:scale-90 dark:border-indigo-800/60 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/60"
                                        title="Mark as read"
                                        aria-label="Mark as read"
                                    >
                                        <Check class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Load More Button -->
                        <div
                            v-if="hasMore"
                            class="bg-slate-50/50 p-2 text-center dark:bg-gray-900/50"
                        >
                            <button
                                @click.stop="loadMore"
                                :disabled="isLoadingMore"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-indigo-600 transition-colors hover:bg-indigo-50 hover:text-indigo-700 disabled:opacity-50 dark:text-indigo-400 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-300"
                            >
                                <Loader2
                                    v-if="isLoadingMore"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <span>{{
                                    isLoadingMore
                                        ? 'Loading more...'
                                        : 'Load more'
                                }}</span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </Transition>
    </div>
</template>
