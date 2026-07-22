<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
defineProps({
    user: Object,
});

const getRoleBadgeStyles = (role: string) => {
    switch (role) {
        case 'admin':
            return 'bg-red-50 text-red-700 border-red-100';
        case 'manager':
            return 'bg-amber-50 text-amber-700 border-amber-100';
        case 'editor':
            return 'bg-purple-50 text-purple-700 border-purple-100';
        default:
            return 'bg-gray-50 text-gray-700 border-gray-100';
    }
};

const deleteUser = (id: number) => {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(`/admin/users/${id}`);
    }
};
</script>
<template>
    <tr
        class="block border-b border-gray-100 bg-white p-5 transition-colors duration-200 last:border-b-0 md:table-row md:border-b md:border-gray-200/60 md:p-0 md:hover:bg-gray-50/50"
    >
        <td
            class="block py-1.5 font-medium text-gray-900 md:table-cell md:px-6 md:py-4.5"
        >
            <div class="flex items-center gap-3.5">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold tracking-wider text-slate-700 uppercase shadow-sm ring-1 ring-black/5"
                >
                    {{ user.name.charAt(0) }}
                </div>
                <div class="flex min-w-0 flex-col">
                    <span
                        class="truncate text-sm font-semibold text-gray-900"
                        >{{ user.name }}</span
                    >
                    <span
                        class="mt-0.5 truncate text-xs text-gray-400 md:hidden"
                    >
                        {{ user.email }}
                    </span>
                </div>
            </div>
        </td>

        <td
            class="hidden text-sm font-normal text-gray-600 md:table-cell md:px-6 md:py-4.5"
        >
            {{ user.email }}
        </td>

        <td class="mt-1 block py-1.5 md:mt-0 md:table-cell md:px-6 md:py-4.5">
            <div class="flex items-center gap-2 md:block">
                <span class="w-12 text-xs font-medium text-gray-400 md:hidden">
                    Role:
                </span>
                <span
                    class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-medium tracking-wide capitalize shadow-2xs"
                    :class="getRoleBadgeStyles(user.roles?.[0]?.name)"
                >
                    {{ user.roles?.[0]?.name ?? 'no role' }}
                </span>
            </div>
        </td>

        <td
            class="mt-3 block border-t border-gray-100 py-2 pt-3 md:mt-0 md:table-cell md:border-t-0 md:px-6 md:py-4.5 md:pt-0 md:text-right"
        >
            <div class="flex items-center justify-start gap-3.5 md:justify-end">
                <Link
                    :href="`/admin/users/edit/${user.id}`"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-2xs transition-all hover:bg-gray-50 hover:text-blue-600 md:border-0 md:bg-transparent md:p-0 md:text-blue-600 md:shadow-none md:hover:text-blue-700 md:hover:underline"
                >
                    Edit
                </Link>

                <button
                    @click="deleteUser(user.id)"
                    class="inline-flex items-center justify-center gap-1 rounded-lg border border-red-100 bg-red-50/40 px-3 py-1.5 text-xs font-medium text-red-600 shadow-2xs transition-all hover:bg-red-50 hover:text-red-700 md:border-0 md:bg-transparent md:p-0 md:text-red-500 md:shadow-none md:hover:text-red-700"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    <span class="md:hidden">Delete</span>
                </button>
            </div>
        </td>
    </tr>
</template>
