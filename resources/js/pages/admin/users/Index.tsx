/**
 * AdminUsersIndex — TSX proof-of-concept for "TypeScript TypeScript" admin pages.
 *
 * Same UI/behavior as the former `Index.vue` (flat, decardified), rewritten as
 * a `.tsx` `defineComponent` render function. Resolved via the explicit
 * dual-extension (`*.vue` + `*.tsx`) page resolver in `resources/js/app.ts`.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, ShieldCheck, Users, X } from 'lucide-vue-next';
import { defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

import UserRow from '@/components/admin/UserRow.vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import { usePermissions } from '@/lib/usePermissions';

interface AdminUser {
    id: number;
    name: string;
    username: string;
    email: string;
    image_url?: string | null;
    is_verified?: boolean | null;
    banned_until?: string | null;
    roles?: { id?: number; name: string }[];
}

interface PaginationLinkItem {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: AdminUser[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    links: PaginationLinkItem[];
}

interface UsersFilters {
    q?: string;
    role?: string;
}

interface UsersCounts {
    all?: number;
    staff?: number;
}

export default defineComponent({
    name: 'AdminUsersIndex',
    props: {
        users: { type: Object as PropType<PaginatedUsers>, required: true },
        filters: { type: Object as PropType<UsersFilters>, default: undefined },
        counts: { type: Object as PropType<UsersCounts>, default: undefined },
    },
    setup(props) {
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

        const onSearchInput = (e: Event) => {
            searchQuery.value = (e.target as HTMLInputElement).value;
        };

        const onSearchKeyup = (e: KeyboardEvent) => {
            if (e.key === 'Enter') {
                handleSearch();
            }
        };

        const hasUsers = props.users.data && props.users.data.length > 0;

        return () => (
            <>
                <Head title="Manage Users" />

                <div class="space-y-6">
                    {/* Page header */}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h1 class="truncate text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                    Manage Users
                                </h1>
                                <span class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400">
                                    {props.users.total ??
                                        props.users.data?.length ??
                                        0}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                Manage user accounts, roles, and access.
                            </p>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            {/* Search Input Bar */}
                            <div class="relative w-full sm:w-64">
                                <input
                                    value={searchQuery.value}
                                    onInput={onSearchInput}
                                    type="text"
                                    placeholder="Search name, email, username..."
                                    onKeyup={onSearchKeyup}
                                    class="h-9 w-full rounded-xl border border-slate-200 bg-white py-1.5 pr-8 pl-9 text-xs font-semibold text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400"
                                />
                                <Search class="pointer-events-none absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
                                {searchQuery.value && (
                                    <button
                                        onClick={clearSearch}
                                        type="button"
                                        class="absolute top-1/2 right-1.5 flex h-6 w-6 -translate-y-1/2 cursor-pointer items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                                        title="Clear search"
                                        aria-label="Clear search"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                    </button>
                                )}
                            </div>

                            {can('create users') && (
                                <Link
                                    href="/admin/users/create"
                                    class="inline-flex h-9 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                >
                                    <Plus
                                        class="h-3.5 w-3.5"
                                        strokeWidth={2.2}
                                    />
                                    <span>Create User</span>
                                </Link>
                            )}
                        </div>
                    </div>

                    {/* Role Filter Tabs (All / Staff) */}
                    <div class="inline-flex items-center gap-0.5 self-start rounded-xl bg-slate-100 p-1 dark:bg-gray-800">
                        <button
                            type="button"
                            onClick={() => applyFilters('all')}
                            class={[
                                'inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold transition-all focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95',
                                selectedRole.value === 'all'
                                    ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-gray-100'
                                    : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
                            ]}
                        >
                            <Users class="h-3.5 w-3.5" />
                            <span>All</span>
                            {props.counts?.all !== undefined && (
                                <span
                                    class={[
                                        'rounded-md px-1.5 py-0.5 text-[10px] font-bold',
                                        selectedRole.value === 'all'
                                            ? 'bg-slate-100 text-slate-700 dark:bg-gray-600 dark:text-gray-200'
                                            : 'bg-slate-200/70 text-slate-600 dark:bg-gray-700 dark:text-gray-400',
                                    ]}
                                >
                                    {props.counts.all}
                                </span>
                            )}
                        </button>

                        <button
                            type="button"
                            onClick={() => applyFilters('staff')}
                            class={[
                                'inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold transition-all focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95',
                                selectedRole.value === 'staff'
                                    ? 'bg-white text-slate-900 shadow-2xs dark:bg-gray-800 dark:text-gray-100'
                                    : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
                            ]}
                        >
                            <ShieldCheck class="h-3.5 w-3.5" />
                            <span>Staff Only</span>
                            {props.counts?.staff !== undefined && (
                                <span
                                    class={[
                                        'rounded-md px-1.5 py-0.5 text-[10px] font-bold',
                                        selectedRole.value === 'staff'
                                            ? 'bg-slate-100 text-slate-700 dark:bg-gray-600 dark:text-gray-200'
                                            : 'bg-slate-200/70 text-slate-600 dark:bg-gray-700 dark:text-gray-400',
                                    ]}
                                >
                                    {props.counts.staff}
                                </span>
                            )}
                        </button>
                    </div>

                    {/* Users List */}
                    <div class="flex flex-1 flex-col">
                        {hasUsers ? (
                            <div class="flex flex-col gap-2">
                                {props.users.data.map((user) => (
                                    <UserRow key={user.id} user={user} />
                                ))}
                            </div>
                        ) : searchQuery.value ? (
                            <EmptyState
                                icon={Search}
                                variant="dashed"
                                title="No users found"
                                description={`"${searchQuery.value}" দিয়ে কোনো ইউজার পাওয়া যায়নি। অন্য কিছু লিখে সার্চ করুন।`}
                            >
                                {{
                                    default: () => (
                                        <button
                                            type="button"
                                            onClick={clearSearch}
                                            class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                        >
                                            <span>Clear Search</span>
                                        </button>
                                    ),
                                }}
                            </EmptyState>
                        ) : selectedRole.value === 'staff' ? (
                            <EmptyState
                                icon={ShieldCheck}
                                variant="dashed"
                                title="No staff members found"
                                description="Currently there are no users assigned to staff roles."
                            >
                                {{
                                    default: () => (
                                        <button
                                            type="button"
                                            onClick={() => applyFilters('all')}
                                            class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                        >
                                            <span>Show All Users</span>
                                        </button>
                                    ),
                                }}
                            </EmptyState>
                        ) : (
                            <EmptyState
                                title="No users found"
                                description="No registered users found in the system."
                            />
                        )}

                        {/* Pagination Bar */}
                        {props.users.links && props.users.links.length > 3 && (
                            <div class="mt-6 border-t border-slate-100 pt-4 dark:border-gray-800">
                                <Pagination
                                    links={props.users.links}
                                    from={props.users.from}
                                    to={props.users.to}
                                    total={props.users.total}
                                    currentPage={props.users.current_page}
                                    lastPage={props.users.last_page}
                                    showSummary
                                />
                            </div>
                        )}
                    </div>
                </div>
            </>
        );
    },
});
