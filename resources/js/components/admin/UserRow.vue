<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogIn, Pencil, Trash2, Ban, Mail } from 'lucide-vue-next';
import { ref } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import ChatBanModal from '@/components/ChatBanModal.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

defineProps({
    user: Object,
});

const page = usePage();
const userId = page.props.auth.user.id;
const isBanModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isSendingDeleteLink = ref(false);

const isBanned = (user: any) => {
    const banUntil = user?.banned_until;

    if (!banUntil) {
        return false;
    }

    return new Date(banUntil).getTime() > Date.now();
};

const openBanModal = () => {
    isBanModalOpen.value = true;
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

const openDeleteModal = () => {
    isDeleteModalOpen.value = true;
};

const handleHardDelete = (id: number) => {
    if (
        confirm(
            'Are you absolutely sure you want to permanently delete this user now? This action cannot be undone.',
        )
    ) {
        isDeleteModalOpen.value = false;
        router.delete(`/admin/users/${id}`);
    }
};

const handleSendDeleteLink = (id: number) => {
    if (
        !confirm(
            'Are you sure you want to send a signed account deletion link to this user? They will receive an email to confirm deletion.',
        )
    ) {
        return;
    }

    isSendingDeleteLink.value = true;
    router.post(
        `/admin/users/${id}/send-delete-link`,
        {},
        {
            onFinish: () => {
                isSendingDeleteLink.value = false;
                isDeleteModalOpen.value = false;
            },
        },
    );
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
                    v-if="user.image_url"
                    :src="user.image_url"
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

                    <VerifiedBadge v-if="user.is_verified" />

                    <span
                        v-if="user.id === userId"
                        class="inline-flex items-center rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700 uppercase dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        You
                    </span>

                    <StatusBadge
                        :status="user.roles?.[0]?.name ?? 'student'"
                        size="xs"
                    />

                    <span
                        v-if="user.priority > 0"
                        class="inline-flex items-center rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400"
                        :title="`Priority: ${user.priority}`"
                    >
                        Priority {{ user.priority }}
                    </span>

                    <StatusBadge
                        v-if="isBanned(user)"
                        variant="rose"
                        label="Suspended"
                        :icon="Ban"
                        size="xs"
                        title="Suspended from community"
                    />
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
                can('manage users') ||
                can('manage chat') ||
                can('manage forums')
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
                v-if="
                    can('manage chat') ||
                    can('manage forums') ||
                    can('manage users')
                "
                type="button"
                @click="openBanModal"
                class="rounded-lg p-1.5 transition-colors"
                :class="
                    isBanned(user)
                        ? 'text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40'
                        : 'text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-950/40 dark:hover:text-rose-400'
                "
                :title="
                    isBanned(user)
                        ? 'Edit suspension timer / Lift suspension'
                        : 'Suspend user'
                "
            >
                <Ban class="h-4 w-4" :stroke-width="1.8" />
            </button>

            <Link
                v-if="can('manage users')"
                :href="`/admin/users/edit/${user.id}`"
                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-indigo-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                title="Edit user"
            >
                <Pencil class="h-4 w-4" :stroke-width="1.8" />
            </Link>

            <button
                v-if="user.id !== userId && can('manage users')"
                @click="openDeleteModal"
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

        <!-- Account Deletion Action Modal -->
        <BaseModal
            :is-open="isDeleteModalOpen"
            title="Delete User Account"
            description="Choose how you want to handle the deletion of this account."
            maxWidth="md"
            @close="isDeleteModalOpen = false"
        >
            <div class="space-y-4 p-4 sm:p-6">
                <div
                    class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 dark:border-gray-800 dark:bg-gray-800/50"
                >
                    <p
                        class="text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >
                        Target User
                    </p>
                    <p
                        class="text-sm font-semibold text-slate-900 dark:text-white"
                    >
                        {{ user.name }}
                    </p>
                    <p
                        v-if="user.email"
                        class="font-mono text-xs text-slate-600 dark:text-gray-400"
                    >
                        {{ user.email }}
                    </p>
                </div>

                <div class="space-y-3">
                    <!-- Option 1: Send Deletion Link -->
                    <button
                        type="button"
                        :disabled="!user.email || isSendingDeleteLink"
                        @click="handleSendDeleteLink(user.id)"
                        class="group flex w-full items-start gap-3 rounded-xl border border-indigo-100 bg-indigo-50/50 p-3.5 text-left transition hover:border-indigo-300 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-indigo-950/50 dark:bg-indigo-950/20 dark:hover:border-indigo-800"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 group-hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-400"
                        >
                            <Mail class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-sm font-bold text-indigo-950 dark:text-indigo-200"
                                    >Send Deletion Link to User</span
                                >
                                <span
                                    class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 uppercase dark:bg-indigo-900/60 dark:text-indigo-300"
                                    >Recommended</span
                                >
                            </div>
                            <p
                                class="mt-0.5 text-xs leading-relaxed text-slate-600 dark:text-gray-400"
                            >
                                Sends a secure, 7-day signed authorization link
                                to their email address. The user confirms and
                                deletes the account themselves.
                            </p>
                            <p
                                v-if="!user.email"
                                class="mt-1 text-[11px] font-medium text-amber-600 dark:text-amber-400"
                            >
                                User has no email address configured.
                            </p>
                        </div>
                    </button>

                    <!-- Option 2: Delete Instantly (Hard Delete) -->
                    <button
                        type="button"
                        @click="handleHardDelete(user.id)"
                        class="group flex w-full items-start gap-3 rounded-xl border border-rose-100 bg-rose-50/50 p-3.5 text-left transition hover:border-rose-300 hover:bg-rose-50 dark:border-rose-950/50 dark:bg-rose-950/20 dark:hover:border-rose-800"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600 group-hover:bg-rose-200 dark:bg-rose-900/40 dark:text-rose-400"
                        >
                            <Trash2 class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <span
                                class="text-sm font-bold text-rose-950 dark:text-rose-200"
                                >Delete Instantly (Hard Delete)</span
                            >
                            <p
                                class="mt-0.5 text-xs leading-relaxed text-slate-600 dark:text-gray-400"
                            >
                                Permanently wipe the account and all associated
                                data immediately. Sends a farewell notification
                                email if email is present.
                            </p>
                        </div>
                    </button>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end">
                    <button
                        type="button"
                        @click="isDeleteModalOpen = false"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        Cancel
                    </button>
                </div>
            </template>
        </BaseModal>
    </div>
</template>
