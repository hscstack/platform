<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, ShieldCheck, Users, X } from 'lucide-vue-next';
import { ref } from 'vue';
import UserRow from '@/components/admin/UserRow.vue';
import EmptyState from '@/components/EmptyState.vue';
import { usePermissions } from '@/lib/usePermissions';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: any[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

const props = defineProps<{
    users: PaginatedUsers;
    filters?: {
        q?: string;
        role?: string;
    };
    counts?: {
        all?: number;
        staff?: number;
    };
}>();

const { can } = usePermissions();

const selectedRole = ref(props.filters?.role || 'all');
const searchQuery = ref(
    props.filters?.q ||
        (typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search).get('q') || ''
            : ''),
);

const applyFilters = (role?: string) => {
    if (role !== undefined) {
        selectedRole.value = role;
    }

    const params: Record<string, string> = {};

    if (searchQuery.value.trim()) {
        params.q = searchQuery.value.trim();
    }

    if (selectedRole.value && selectedRole.value !== 'all') {
        params.role = selectedRole.value;
    }

    router.get('/admin/users', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const handleSearch = () => {
    applyFilters();
};

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters();
};
</script>

<template>
    <Head title="Manage Users" />

    <div class="flex w-full flex-1 flex-col">
        <!-- Page Header & Action Controls -->
        <div
            class="mb-4 flex shrink-0 flex-col gap-3 border-b border-slate-100 pb-3.5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-2.5 sm:justify-start"
            >
                <div class="flex items-center gap-2.5">
                    <h3
                        class="truncate text-base font-bold tracking-tight text-slate-900 dark:text-gray-100"
                    >
                        Manage Users
                    </h3>

                    <span
                        class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400"
                    >
                        {{ users.total ?? users.data?.length ?? 0 }}
                    </span>
                </div>

                <!-- Role Filter Tabs (All / Staff) -->
                <div
                    class="inline-flex items-center rounded-xl border border-slate-200/80 bg-slate-100/80 p-1 dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        type="button"
                        @click="applyFilters('all')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold transition-all duration-150 active:scale-95"
                        :class="
                            selectedRole === 'all'
                                ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-gray-100'
                                : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                    >
                        <Users class="h-3.5 w-3.5" />
                        <span>All</span>
                        <span
                            v-if="counts?.all !== undefined"
                            class="py-0.2 rounded-md px-1.5 text-[10px] font-bold"
                            :class="
                                selectedRole === 'all'
                                    ? 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-gray-300'
                                    : 'bg-slate-200/70 text-slate-600 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            {{ counts.all }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="applyFilters('staff')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold transition-all duration-150 active:scale-95"
                        :class="
                            selectedRole === 'staff'
                                ? 'bg-white text-indigo-600 shadow-2xs dark:bg-gray-800 dark:text-indigo-400'
                                : 'text-slate-600 hover:text-slate-900 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                    >
                        <ShieldCheck class="h-3.5 w-3.5" />
                        <span>Staff Only</span>
                        <span
                            v-if="counts?.staff !== undefined"
                            class="py-0.2 rounded-md px-1.5 text-[10px] font-bold"
                            :class="
                                selectedRole === 'staff'
                                    ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300'
                                    : 'bg-slate-200/70 text-slate-600 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            {{ counts.staff }}
                        </span>
                    </button>
                </div>

                <div v-if="can('create users')" class="sm:hidden">
                    <Link
                        href="/admin/users/create"
                        class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-2xs transition-colors duration-150 hover:bg-indigo-700 active:scale-95"
                    >
                        <Plus class="h-3.5 w-3.5" :stroke-width="2.2" />
                        <span>Create</span>
                    </Link>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <!-- Search Input Bar -->
                <div class="relative w-full sm:w-64">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search name, email, username..."
                        @keyup.enter="handleSearch"
                        class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 py-1.5 pr-8 pl-9 text-xs font-semibold text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:bg-gray-900"
                    />
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                    />
                    <button
                        v-if="searchQuery"
                        @click="clearSearch"
                        type="button"
                        class="absolute top-1/2 right-2 -translate-y-1/2 p-0.5 text-slate-400 transition hover:text-slate-600 dark:hover:text-gray-200"
                        title="Clear search"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div v-if="can('create users')" class="hidden sm:block">
                    <Link
                        href="/admin/users/create"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition-colors duration-150 hover:bg-indigo-700 active:scale-95"
                    >
                        <Plus class="h-3.5 w-3.5" :stroke-width="2.2" />
                        <span>Create User</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="flex flex-1 flex-col">
            <div
                v-if="users.data && users.data.length > 0"
                class="flex flex-col gap-2.5 sm:gap-3"
            >
                <UserRow
                    v-for="user in users.data"
                    :key="user.id"
                    :user="user"
                />
            </div>

            <!-- Search Empty State -->
            <div
                v-else-if="searchQuery"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 px-4 py-12 text-center dark:border-gray-800 dark:bg-gray-900/40"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-2xs dark:bg-gray-800"
                >
                    <Search class="h-5 w-5 text-slate-400" />
                </div>
                <h3
                    class="mt-3 text-sm font-bold text-slate-800 dark:text-gray-200"
                >
                    No users found
                </h3>
                <p
                    class="mt-1 max-w-xs text-xs text-slate-500 dark:text-gray-400"
                >
                    "{{ searchQuery }}" দিয়ে কোনো ইউজার পাওয়া যায়নি। অন্য কিছু
                    লিখে সার্চ করুন।
                </p>
                <button
                    @click="clearSearch"
                    type="button"
                    class="mt-3.5 inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 active:scale-95"
                >
                    <span>Clear Search</span>
                </button>
            </div>

            <!-- Staff Filter Empty State -->
            <div
                v-else-if="selectedRole === 'staff'"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 px-4 py-12 text-center dark:border-gray-800 dark:bg-gray-900/40"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-2xs dark:bg-gray-800"
                >
                    <ShieldCheck class="h-5 w-5 text-indigo-500" />
                </div>
                <h3
                    class="mt-3 text-sm font-bold text-slate-800 dark:text-gray-200"
                >
                    No staff members found
                </h3>
                <p
                    class="mt-1 max-w-xs text-xs text-slate-500 dark:text-gray-400"
                >
                    Currently there are no users assigned to staff roles.
                </p>
                <button
                    @click="applyFilters('all')"
                    type="button"
                    class="mt-3.5 inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-indigo-700 active:scale-95"
                >
                    <span>Show All Users</span>
                </button>
            </div>

            <!-- Global Empty State -->
            <EmptyState
                v-else
                title="No users found"
                description="No registered users found in the system."
                :show-cta="false"
            />

            <!-- Pagination Bar -->
            <div
                v-if="users.links && users.links.length > 3"
                class="mt-6 border-t border-slate-100 pt-4 dark:border-gray-800"
            >
                <!-- Desktop Pagination (Tablet/PC) -->
                <div class="hidden items-center justify-between gap-3 sm:flex">
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        Showing
                        <span
                            class="font-semibold text-slate-700 dark:text-gray-200"
                            >{{ users.from ?? 0 }}</span
                        >
                        to
                        <span
                            class="font-semibold text-slate-700 dark:text-gray-200"
                            >{{ users.to ?? 0 }}</span
                        >
                        of
                        <span
                            class="font-semibold text-slate-700 dark:text-gray-200"
                            >{{ users.total ?? 0 }}</span
                        >
                        users
                    </p>

                    <div
                        class="flex flex-wrap items-center justify-center gap-1"
                    >
                        <component
                            :is="link.url ? Link : 'span'"
                            v-for="(link, index) in users.links"
                            :key="index"
                            :href="link.url || '#'"
                            preserve-scroll
                            class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-lg px-2.5 py-1 text-xs font-semibold transition-all"
                            :class="{
                                'bg-indigo-600 text-white shadow-2xs':
                                    link.active,
                                'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800':
                                    !link.active && link.url,
                                'cursor-not-allowed text-slate-300 dark:text-gray-600':
                                    !link.url,
                            }"
                        >
                            <span v-html="link.label"></span>
                        </component>
                    </div>
                </div>

                <!-- Mobile Pagination (Phone) -->
                <div class="flex flex-col gap-2.5 sm:hidden">
                    <div class="flex items-center justify-between gap-2">
                        <component
                            :is="users.links[0].url ? Link : 'span'"
                            :href="users.links[0].url || '#'"
                            preserve-scroll
                            class="flex flex-1 items-center justify-center rounded-xl border px-3 py-2 text-center text-xs font-semibold shadow-2xs transition active:scale-95"
                            :class="
                                users.links[0].url
                                    ? 'border-slate-200 bg-white text-slate-700 active:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:active:bg-gray-800'
                                    : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-600'
                            "
                        >
                            &larr; Prev
                        </component>

                        <div
                            class="shrink-0 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300"
                        >
                            Page {{ users.current_page }} of
                            {{ users.last_page }}
                        </div>

                        <component
                            :is="
                                users.links[users.links.length - 1].url
                                    ? Link
                                    : 'span'
                            "
                            :href="
                                users.links[users.links.length - 1].url || '#'
                            "
                            preserve-scroll
                            class="flex flex-1 items-center justify-center rounded-xl border px-3 py-2 text-center text-xs font-semibold shadow-2xs transition active:scale-95"
                            :class="
                                users.links[users.links.length - 1].url
                                    ? 'border-slate-200 bg-white text-slate-700 active:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:active:bg-gray-800'
                                    : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-600'
                            "
                        >
                            Next &rarr;
                        </component>
                    </div>

                    <p
                        class="text-center text-[11px] text-slate-400 dark:text-gray-500"
                    >
                        Showing {{ users.from ?? 0 }} to {{ users.to ?? 0 }} of
                        {{ users.total ?? 0 }} users
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
