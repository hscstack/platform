<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import UserRow from '@/components/admin/UserRow.vue';
import EmptyState from '@/components/EmptyState.vue';

defineProps({
    users: Array,
});
</script>

<template>
    <div
        class="flex w-full flex-1 flex-col rounded-xl border border-gray-300 bg-white p-4 shadow-xs sm:p-6"
    >
        <div
            class="mb-6 flex shrink-0 flex-col gap-4 border-b border-gray-300 pb-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h3 class="text-lg font-semibold tracking-tight text-gray-900">
                    Manage Users
                </h3>
                <p class="mt-0.5 text-sm text-gray-500">
                    Control administrative system access, roles, and profile
                    directories.
                </p>
            </div>

            <div class="flex items-center justify-between gap-3 sm:justify-end">
                <div
                    class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1"
                >
                    <span class="text-xs font-medium text-blue-700">
                        Total Users: {{ users.length }}
                    </span>
                </div>

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
                class="overflow-hidden rounded-xl border border-gray-300 md:border-gray-300"
            >
                <table
                    class="block min-w-full divide-y divide-gray-300 text-left text-sm text-gray-500 md:table"
                >
                    <thead
                        class="hidden bg-gray-50 text-xs font-semibold tracking-wider text-gray-600 uppercase md:table-header-group"
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
                        class="block divide-y divide-gray-300 bg-white md:table-row-group"
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
    </div>
</template>
