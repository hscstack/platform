<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import UserRow from '@/components/admin/UserRow.vue';
import EmptyState from '@/components/EmptyState.vue';
import { kBlock, kBlockTitle, kBadge } from 'konsta/vue';

defineProps({
    users: Array,
});
</script>

<template>
    <kBlock>
        <div
            class="mb-6 flex shrink-0 flex-col gap-4 border-b border-gray-300 pb-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-600"
        >
            <div>
                <kBlockTitle> Manage Users </kBlockTitle>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    Control administrative system access, roles, and profile
                    directories.
                </p>
            </div>

            <div class="flex items-center justify-between gap-3 sm:justify-end">
                <kBadge>
                    <span
                        class="text-xs font-medium text-blue-700 dark:text-blue-400"
                    >
                        Total Users: {{ users.length }}
                    </span>
                </kBadge>

                <Link
                    href="/admin/users/create"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-medium text-white shadow-xs transition-colors duration-150 hover:bg-blue-700 sm:py-1.5"
                >
                    <Plus class="h-3.5 w-3.5" :stroke-width="2.5" />
                    Create User
                </Link>
            </div>
        </div>

        <div class="flex flex-1 flex-col">
            <div
                v-if="users.length > 0"
                class="overflow-hidden rounded-xl border border-gray-300 md:border-gray-300 dark:border-gray-600"
            >
                <table
                    class="block min-w-full divide-y divide-gray-300 text-left text-sm text-gray-500 md:table dark:divide-gray-700 dark:text-gray-400"
                >
                    <thead
                        class="hidden bg-gray-50 text-xs font-semibold tracking-wider text-gray-600 uppercase md:table-header-group dark:bg-gray-800 dark:text-gray-400"
                    >
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Name</th>
                            <th scope="col" class="px-6 py-3.5">
                                Email Address
                            </th>
                            <th scope="col" class="px-6 py-3.5">Access Role</th>
                            <th
                                scope="col"
                                class="relative px-6 py-3.5 text-right"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="block divide-y divide-gray-300 bg-white md:table-row-group dark:divide-gray-700 dark:bg-gray-900"
                    >
                        <UserRow
                            v-for="user in users"
                            :key="user.id"
                            :user="user"
                        />
                    </tbody>
                </table>
            </div>

            <EmptyState v-else />
        </div>
    </kBlock>
</template>
