<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogIn, Pencil, Trash2, Ban } from 'lucide-vue-next';
import { ref } from 'vue';
import ChatBanModal from '@/components/ChatBanModal.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

defineProps({
    user: Object,
});

const page = usePage();
const userId = page.props.auth.user.id;
const isBanModalOpen = ref(false);

const isChatBanned = (user: any) => {
    if (!user?.chat_banned_until) {
        return false;
    }

    return new Date(user.chat_banned_until).getTime() > Date.now();
};

const openBanModal = () => {
    isBanModalOpen.value = true;
};

const getRoleBadgeStyles = (role: string) => {
    switch (role) {
        case 'admin':
            return 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/30';
        case 'manager':
            return 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30';
        case 'editor':
            return 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-500/10 dark:text-purple-400 dark:ring-purple-500/30';
        default:
            return 'bg-slate-100 text-slate-600 ring-slate-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-500/20';
    }
};

const loginAsUser = (targetUser: any) => {
    if (
        confirm(
            `Login as ${targetUser.name}? You will switch to this user's session.`,
        )
    ) {
        router.post(`/admin/users/${targetUser.id}/login`);
    }
};

const deleteUser = (id: number) => {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(`/admin/users/${id}`);
    }
};
</script>

<template>
    <div
        class="group relative flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white p-3 transition-colors duration-150 hover:border-indigo-200 hover:bg-slate-50/50 sm:p-3.5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-500/30 dark:hover:bg-gray-800/40"
        :class="[
            user.id === userId
                ? 'ring-1 ring-indigo-500/20 dark:ring-indigo-500/30'
                : '',
        ]"
    >
        <!-- Left: User Avatar + Name + Email + Role (Clickable to profile) -->
        <Link
            :href="user.username ? `/u/${user.username}` : '#'"
            class="flex min-w-0 flex-1 cursor-pointer items-center gap-3"
        >
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-black/5 bg-slate-100 text-xs font-bold text-slate-700 uppercase sm:h-10 sm:w-10 dark:border-white/10 dark:bg-gray-800 dark:text-gray-300"
            >
                <img
                    v-if="user.image_url || user.image_path"
                    :src="
                        user.image_url ||
                        (user.image_path?.startsWith('http')
                            ? user.image_path
                            : '/storage/' + user.image_path)
                    "
                    :alt="user.name"
                    class="h-full w-full object-cover"
                />
                <span v-else>{{ user.name.charAt(0) }}</span>
            </div>

            <div class="flex min-w-0 flex-col">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="text-sm font-semibold break-words text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                    >
                        {{ user.name }}
                    </span>

                    <span
                        v-if="user.id === userId"
                        class="inline-flex items-center rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700 uppercase dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        You
                    </span>

                    <span
                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase ring-1 ring-inset"
                        :class="getRoleBadgeStyles(user.roles?.[0]?.name)"
                    >
                        {{ user.roles?.[0]?.name ?? 'student' }}
                    </span>

                    <span
                        v-if="isChatBanned(user)"
                        class="inline-flex items-center gap-1 rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-bold text-rose-700 uppercase ring-1 ring-rose-600/20 ring-inset dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/30"
                        title="Chat banned"
                    >
                        <Ban class="h-2.5 w-2.5" />
                        Chat Banned
                    </span>
                </div>

                <p
                    class="mt-0.5 truncate text-xs text-slate-400 dark:text-gray-500"
                >
                    {{ user.email }}
                </p>
            </div>
        </Link>

        <!-- Right: Actions -->
        <div
            v-if="
                (user.id !== userId && can('impersonate users')) ||
                can('edit users') ||
                can('manage chat') ||
                (user.id !== userId && can('delete users'))
            "
            class="flex shrink-0 items-center gap-1"
            @click.stop
        >
            <button
                v-if="user.id !== userId && can('impersonate users')"
                type="button"
                @click="loginAsUser(user)"
                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600 dark:text-gray-500 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400"
                title="Login as user"
            >
                <LogIn class="h-4 w-4" :stroke-width="1.8" />
            </button>

            <button
                v-if="can('manage chat') || can('edit users')"
                type="button"
                @click="openBanModal"
                class="rounded-lg p-1.5 transition-colors"
                :class="
                    isChatBanned(user)
                        ? 'text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40'
                        : 'text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-950/40 dark:hover:text-rose-400'
                "
                :title="
                    isChatBanned(user)
                        ? 'Edit chat ban timer / Unban'
                        : 'Ban from chat'
                "
            >
                <Ban class="h-4 w-4" :stroke-width="1.8" />
            </button>

            <Link
                v-if="can('edit users')"
                :href="`/admin/users/edit/${user.id}`"
                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                title="Edit user"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.8" />
            </Link>

            <button
                v-if="user.id !== userId && can('delete users')"
                @click="deleteUser(user.id)"
                type="button"
                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                title="Delete user"
            >
                <Trash2 class="h-4 w-4" :stroke-width="1.8" />
            </button>
        </div>

        <!-- Chat Ban Modal -->
        <ChatBanModal
            :is-open="isBanModalOpen"
            :user="user"
            @close="isBanModalOpen = false"
        />
    </div>
</template>
