/**
 * AdminUsersIndex — TSX proof-of-concept for "TypeScript TypeScript" admin pages.
 *
 * Same UI/behavior as the former `Index.vue` (flat, decardified), rewritten as
 * a `.tsx` `defineComponent` render function. Resolved via the explicit
 * dual-extension (`*.vue` + `*.tsx`) page resolver in `resources/js/app.ts`.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, ShieldCheck, Users, X } from 'lucide-vue-next';
import { computed, defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

import {
    AdminPageHeader,
    SegmentedTabs,
    adminPageClass,
    adminPrimaryBtnClass,
    adminSearchInputClass,
} from '@/components/admin/ui';
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

        // Derived (not a snapshot): `applyFilters` preserves this component
        // while the server swaps `props.users`, so this must recompute.
        const hasUsers = computed(
            () => props.users.data && props.users.data.length > 0,
        );

        const tabOptions = computed(() => [
            {
                value: 'all',
                label: 'All',
                icon: Users,
                badge: props.counts?.all,
            },
            {
                value: 'staff',
                label: 'Staff Only',
                icon: ShieldCheck,
                badge: props.counts?.staff,
            },
        ]);

        return () => (
            <>
                <Head title="Manage Users" />

                <div class={adminPageClass}>
                    {/* Page header */}
                    <AdminPageHeader
                        title="Manage Users"
                        count={
                            props.users.total ?? props.users.data?.length ?? 0
                        }
                        description="Manage user accounts, roles, and access."
                    >
                        {{
                            actions: () => (
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    {/* Search Input Bar */}
                                    <div class="relative w-full sm:w-64">
                                        <input
                                            value={searchQuery.value}
                                            onInput={onSearchInput}
                                            type="text"
                                            placeholder="Search name, email, username..."
                                            onKeyup={onSearchKeyup}
                                            class={adminSearchInputClass}
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
                                            class={[
                                                adminPrimaryBtnClass,
                                                'shrink-0 justify-center',
                                            ]}
                                        >
                                            <Plus
                                                class="h-3.5 w-3.5"
                                                strokeWidth={2.2}
                                            />
                                            <span>Create User</span>
                                        </Link>
                                    )}
                                </div>
                            ),
                        }}
                    </AdminPageHeader>

                    {/* Role Filter Tabs (All / Staff) */}
                    <SegmentedTabs
                        tabs={tabOptions.value}
                        active={selectedRole.value}
                        onSelect={(value: string) => applyFilters(value)}
                    />

                    {/* Users List */}
                    <div class="flex flex-1 flex-col">
                        {hasUsers.value ? (
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
                                            class={adminPrimaryBtnClass}
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
                                            class={adminPrimaryBtnClass}
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
