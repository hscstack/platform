<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import UserRow from '@/components/admin/UserRow.vue';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

const { can } = usePermissions();

defineProps({
    users: Array,
});
</script>

<template>
    <Head title="Manage Users" />

    <div class="flex w-full flex-1 flex-col">
        <!-- Compact Page Title Bar -->
        <div
            class="mb-3.5 flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 pb-3 dark:border-gray-800"
        >
            <div class="flex min-w-0 items-center gap-2.5">
                <h3
                    class="truncate text-base font-bold tracking-tight text-slate-900 dark:text-gray-100"
                >
                    Manage Users
                </h3>

                <span
                    class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400"
                >
                    {{ users.length }}
                </span>
            </div>

            <div v-if="can('create users')" class="flex items-center gap-2">
                <Link
                    href="/admin/users/create"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition-colors duration-150 hover:bg-indigo-700"
                >
                    <Plus class="h-3.5 w-3.5" :stroke-width="2.2" />
                    <span>Create User</span>
                </Link>
            </div>
        </div>

        <div class="flex flex-1 flex-col">
            <div v-if="users.length > 0" class="flex flex-col gap-2.5 sm:gap-3">
                <UserRow v-for="user in users" :key="user.id" :user="user" />
            </div>

            <EmptyState v-else />
        </div>
    </div>
</template>
