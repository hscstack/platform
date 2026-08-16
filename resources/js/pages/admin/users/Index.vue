<script setup lang="ts">
import {} from '@inertiajs/vue3';
import {
    kBlock,
    kBlockTitle,
    kBadge,
    kList,
    kListItem,
    kButton,
} from 'konsta/vue';
import { Plus } from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';

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

                <k-button fill rounded href="/admin/users/create">
                    <Plus class="h-3.5 w-3.5" :stroke-width="2.5" />
                    Create User
                </k-button>
            </div>
        </div>

        <div class="flex flex-1 flex-col">
            <k-list
                strong
                outline
                dividers
                class="ios:-mx-4"
                v-if="users.length > 0"
            >
                <k-list-item
                    v-for="user in users"
                    :key="user.id"
                    :title="user.name"
                    :subtitle="user.email"
                    :link="`/admin/users/${user.id}/edit`"
                />
            </k-list>

            <EmptyState v-else />
        </div>
    </kBlock>
</template>
