/**
 * Unified navigation — single TypeScript (TSX) source for every navigation
 * surface in the app:
 *
 * - SiteRail            desktop side rail (collapsible 72px <-> 280px)
 * - SiteBottomNav       mobile bottom bar (customizable 3-5 items, YT style)
 * - SiteDrawer          mobile top hamburger bar + slide-over drawer
 * - AdminRail           staff side rail (same rail language, admin items)
 * - AdminDrawer         staff mobile drawer (teleported, same slide motion)
 * - BottomNavCustomizer Profile settings UI for the mobile bottom bar
 *
 * Pure state/logic stays in the existing TypeScript modules under
 * resources/js/lib (navigation, useBottomNavCustomization, useDarkMode,
 * useOrientation, useBreakpoint, usePwa, usePermissions). Shared visuals
 * (MaterialIcon, AppLogo, NotificationDropdown, LogoutConfirmModal) are
 * imported — only the navigation chrome itself is unified here.
 */
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Teleport,
    Transition,
    computed,
    defineComponent,
    onBeforeUnmount,
    ref,
    watch,
} from 'vue';
import type { PropType, Ref } from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import LogoutConfirmModal from '@/components/LogoutConfirmModal.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import MaterialIcon from '@/components/ui/MaterialIcon.vue';
import { allNavItems } from '@/lib/navigation';
import { useBottomNavCustomization } from '@/lib/useBottomNavCustomization';
import { useDarkMode } from '@/lib/useDarkMode';
import { usePermissions } from '@/lib/usePermissions';
import { usePwa } from '@/lib/usePwa';

export type AdminNavItem = {
    name: string;
    to: string;
    icon: string;
    permission?: string;
};

/** Authenticated user shape used across the navigation surfaces. */
type AuthedUser = {
    name: string;
    email: string;
    username?: string | null;
    image_url?: string | null;
};

/** `title` / `aria-label` are valid fallthrough attributes at runtime;
 *  declaring them here keeps TSX prop-checking in parity with SFC
 *  templates (which allow fallthrough attrs implicitly). */
declare module 'vue' {
    interface ComponentCustomProps {
        title?: string;
        'aria-label'?: string;
    }
}

function staffAccess(auth: unknown): boolean {
    return Boolean(
        (auth as { can_access_admin?: unknown } | null | undefined)
            ?.can_access_admin,
    );
}

/* ------------------------------------------------------------------ */
/* shared helpers                                                      */
/* ------------------------------------------------------------------ */

function preferredHomeHref(currentUrl: string): string {
    if (typeof window !== 'undefined') {
        try {
            const pref = localStorage.getItem('preferred_course');

            if (pref === 'ssc') {
                return '/ssc';
            }
        } catch {
            // ignore
        }
    }

    return currentUrl.startsWith('/ssc') ? '/ssc' : '/';
}

function isAdminRoute(url: string): boolean {
    return url.startsWith('/admin');
}

function isActiveAdminRoute(to: string, currentUrl: string): boolean {
    if (to === '/admin') {
        return currentUrl === '/admin' || currentUrl.startsWith('/admin?');
    }

    return currentUrl.startsWith(to);
}

/** Spread onto LogoutConfirmModal: avoids the ambiguous JSX
 *  `v-model:open` spelling and stays type-safe. */
function bindOpen(source: Ref<boolean>): {
    open: boolean;
    'onUpdate:open': (value: boolean) => void;
} {
    return {
        open: source.value,
        'onUpdate:open': (value: boolean) => {
            source.value = value;
        },
    };
}

/* ------------------------------------------------------------------ */
/* SiteRail — desktop side rail                                        */
/* ------------------------------------------------------------------ */

export const SiteRail = defineComponent({
    name: 'SiteRail',
    props: {
        collapsed: { type: Boolean, required: true },
    },
    emits: ['toggle'],
    setup(props, { emit }) {
        const page = usePage();
        const user = computed(
            () => page.props.auth?.user as AuthedUser | undefined,
        );
        const canAccessAdmin = computed(() => staffAccess(page.props.auth));
        const currentUrl = computed(() => String(page.url));

        const { theme, toggle, setTheme } = useDarkMode();
        const showLogoutModal = ref(false);
        const { deferredPrompt, isInstalled, promptInstall } = usePwa();
        const canInstallApp = computed(
            () => !isInstalled.value && Boolean(deferredPrompt.value),
        );
        const handleInstallApp = async () => {
            await promptInstall();
        };

        const homeHref = computed(() => preferredHomeHref(currentUrl.value));

        const isActive = (href: string, match?: (url: string) => boolean) => {
            if (match) {
                return match(currentUrl.value);
            }

            return currentUrl.value.startsWith(href);
        };

        return () => (
            <aside
                class={[
                    'flex shrink-0 flex-col bg-white/70 backdrop-blur-xl transition-all duration-300 dark:bg-slate-900/60 dark:backdrop-blur-xl',
                    'sticky top-0 h-screen border-r border-slate-200/60 shadow-[1px_0_3px_rgba(0,0,0,0.02),4px_0_16px_rgba(0,0,0,0.03)] dark:border-slate-800/60 dark:shadow-none',
                    props.collapsed ? 'w-[72px]' : 'w-[280px]',
                ]}
                aria-label="Side navigation"
            >
                {/* Header: Logo + Collapse toggle */}
                <div
                    class={[
                        'flex shrink-0 items-center border-b border-slate-200/60 bg-white/70 backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-900/60 dark:backdrop-blur-xl',
                        props.collapsed
                            ? 'h-auto flex-col justify-center gap-3 px-2 py-3'
                            : 'h-16 flex-row justify-between px-3',
                    ]}
                >
                    {!props.collapsed ? (
                        <AppLogo />
                    ) : (
                        <Link
                            href={homeHref.value}
                            class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-slate-900 shadow-sm ring-1 ring-slate-900/10 dark:bg-gray-100"
                            aria-label="Home"
                        >
                            <img
                                src="/favicon.svg"
                                alt="HSCStack"
                                class="h-6 w-6 scale-120 object-cover"
                            />
                        </Link>
                    )}
                    <button
                        type="button"
                        onClick={() => emit('toggle')}
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition-all duration-150 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 hover:shadow dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                        aria-label={
                            props.collapsed
                                ? 'Expand sidebar'
                                : 'Collapse sidebar'
                        }
                    >
                        <MaterialIcon
                            name={
                                props.collapsed
                                    ? 'chevron_right'
                                    : 'chevron_left'
                            }
                            size={18}
                        />
                    </button>
                </div>

                {/* Scrollable nav */}
                <div class="flex flex-1 flex-col overflow-y-auto py-3.5">
                    <nav class="space-y-0.5 px-2.5">
                        {allNavItems.map((item) => (
                            <Link
                                key={item.href}
                                href={
                                    item.href === '/'
                                        ? homeHref.value
                                        : item.href
                                }
                                class={[
                                    'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                                    isActive(item.href, item.match)
                                        ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                        : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                    props.collapsed
                                        ? 'justify-center px-2'
                                        : '',
                                ]}
                                title={props.collapsed ? item.label : undefined}
                            >
                                <MaterialIcon
                                    name={item.icon}
                                    size={22}
                                    class={`shrink-0 transition-colors duration-150 ${
                                        isActive(item.href, item.match)
                                            ? 'text-indigo-600 dark:text-indigo-300'
                                            : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                    }`}
                                />
                                {!props.collapsed && (
                                    <span class="truncate">{item.label}</span>
                                )}
                                {!props.collapsed &&
                                    isActive(item.href, item.match) && (
                                        <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                    )}
                            </Link>
                        ))}
                    </nav>
                </div>

                {/* Footer controls */}
                <div class="border-t border-slate-200/60 bg-white/40 p-2 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/40 dark:backdrop-blur-sm">
                    <div class="space-y-1.5">
                        {/* Appearance — segmented group */}
                        {!props.collapsed ? (
                            <div>
                                <p class="mb-1.5 px-2 text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500">
                                    Appearance
                                </p>
                                <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                                    <button
                                        type="button"
                                        onClick={() => setTheme('light')}
                                        class={[
                                            'flex items-center justify-center gap-1 rounded-lg py-2 text-xs font-semibold transition-all',
                                            theme.value === 'light'
                                                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-slate-100'
                                                : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200',
                                        ]}
                                    >
                                        <MaterialIcon
                                            name="light_mode"
                                            size={16}
                                        />
                                        Light
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setTheme('dark')}
                                        class={[
                                            'flex items-center justify-center gap-1 rounded-lg py-2 text-xs font-semibold transition-all',
                                            theme.value === 'dark'
                                                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-slate-100'
                                                : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200',
                                        ]}
                                    >
                                        <MaterialIcon
                                            name="dark_mode"
                                            size={16}
                                        />
                                        Dark
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setTheme('system')}
                                        class={[
                                            'flex items-center justify-center gap-1 rounded-lg py-2 text-xs font-semibold transition-all',
                                            theme.value === 'system'
                                                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-slate-100'
                                                : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200',
                                        ]}
                                    >
                                        <MaterialIcon
                                            name="computer"
                                            size={16}
                                        />
                                        System
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <button
                                type="button"
                                onClick={toggle}
                                aria-label="Toggle theme"
                                title={`Theme: ${theme.value}`}
                                class="flex h-11 w-full items-center justify-center rounded-xl border border-transparent text-slate-600 transition-all duration-150 hover:border-slate-200 hover:bg-white hover:text-slate-900 hover:shadow-sm dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                            >
                                <MaterialIcon
                                    name={
                                        theme.value === 'system'
                                            ? 'computer'
                                            : theme.value === 'light'
                                              ? 'light_mode'
                                              : 'dark_mode'
                                    }
                                    size={20}
                                />
                            </button>
                        )}

                        {/* Notifications */}
                        {user.value && (
                            <div
                                class={[
                                    'flex h-11 w-full items-center gap-2.5 px-3',
                                    props.collapsed
                                        ? 'justify-center px-0'
                                        : 'justify-start',
                                ]}
                            >
                                <NotificationDropdown
                                    plain={!props.collapsed}
                                />
                                {!props.collapsed && (
                                    <span class="text-[13px] font-semibold text-slate-600 dark:text-slate-400">
                                        Notifications
                                    </span>
                                )}
                            </div>
                        )}

                        {/* PWA Install */}
                        {canInstallApp.value && (
                            <button
                                type="button"
                                aria-label="Install App"
                                title={
                                    props.collapsed ? 'Install App' : undefined
                                }
                                onClick={handleInstallApp}
                                class={[
                                    'flex h-11 w-full items-center justify-center gap-2.5 rounded-xl border px-3 text-[13px] font-bold shadow-sm transition-all duration-150',
                                    props.collapsed
                                        ? 'border-slate-900 bg-slate-900 text-white hover:border-slate-700 hover:bg-slate-800 dark:border-white dark:bg-white dark:text-slate-900 dark:hover:border-slate-200 dark:hover:bg-slate-200'
                                        : 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 hover:shadow-sm dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20',
                                ]}
                            >
                                <MaterialIcon name="download" size={20} />
                                {!props.collapsed && <span>Install App</span>}
                            </button>
                        )}

                        {/* Auth */}
                        <div class={props.collapsed ? 'px-0' : 'pt-1'}>
                            {!user.value ? (
                                <Link
                                    href="/login"
                                    title={
                                        props.collapsed ? 'Login' : undefined
                                    }
                                    aria-label="Login"
                                    class={[
                                        'flex h-11 w-full items-center justify-center gap-2.5 rounded-xl border px-3 text-[13px] font-bold shadow-sm transition-all duration-150',
                                        'border-transparent bg-indigo-600 text-white hover:border-indigo-700 hover:bg-indigo-700 hover:shadow-md dark:border-transparent dark:bg-indigo-500 dark:hover:border-indigo-400 dark:hover:bg-indigo-400',
                                    ]}
                                >
                                    <MaterialIcon name="login" size={20} />
                                    {!props.collapsed && <span>Login</span>}
                                </Link>
                            ) : (
                                <div
                                    class={[
                                        'flex items-center gap-3 rounded-xl border bg-white px-3 py-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800',
                                        props.collapsed
                                            ? 'flex-col justify-center gap-2 py-3'
                                            : 'justify-between',
                                    ]}
                                >
                                    <Link
                                        href={
                                            user.value.username
                                                ? `/u/${user.value.username}`
                                                : '/profile'
                                        }
                                        class={[
                                            'flex items-center gap-2.5',
                                            props.collapsed
                                                ? 'justify-center'
                                                : 'min-w-0',
                                        ]}
                                        title={
                                            props.collapsed
                                                ? 'Profile'
                                                : undefined
                                        }
                                        aria-label={
                                            props.collapsed
                                                ? 'Profile'
                                                : undefined
                                        }
                                    >
                                        {user.value.image_url ? (
                                            <img
                                                src={user.value.image_url}
                                                alt={user.value.name}
                                                class="h-8 w-8 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700"
                                            />
                                        ) : (
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-xs font-bold text-white ring-1 ring-indigo-600/20">
                                                {user.value.name
                                                    .charAt(0)
                                                    .toUpperCase()}
                                            </span>
                                        )}
                                        {!props.collapsed && (
                                            <div class="min-w-0 text-left">
                                                <p class="truncate text-xs font-semibold text-slate-900 dark:text-slate-100">
                                                    {user.value.name}
                                                </p>
                                                <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">
                                                    {user.value.email}
                                                </p>
                                            </div>
                                        )}
                                    </Link>
                                    <div
                                        class={[
                                            'flex shrink-0 items-center gap-1',
                                            props.collapsed
                                                ? 'flex-col'
                                                : 'flex-row',
                                        ]}
                                    >
                                        {canAccessAdmin.value && (
                                            <Link
                                                href={
                                                    isAdminRoute(
                                                        currentUrl.value,
                                                    )
                                                        ? '/'
                                                        : '/admin'
                                                }
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-500 transition-colors hover:bg-white hover:text-slate-900 hover:shadow-sm dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                                                title={
                                                    isAdminRoute(
                                                        currentUrl.value,
                                                    )
                                                        ? 'Home'
                                                        : 'Staff Panel'
                                                }
                                                aria-label={
                                                    isAdminRoute(
                                                        currentUrl.value,
                                                    )
                                                        ? 'Home'
                                                        : 'Staff Panel'
                                                }
                                            >
                                                <MaterialIcon
                                                    name="dashboard"
                                                    size={20}
                                                />
                                            </Link>
                                        )}
                                        <button
                                            type="button"
                                            onClick={() => {
                                                showLogoutModal.value = true;
                                            }}
                                            title="Log out"
                                            aria-label="Log out"
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-600 hover:shadow-sm dark:text-rose-400 dark:hover:bg-rose-950/40 dark:hover:text-rose-300"
                                        >
                                            <MaterialIcon
                                                name="logout"
                                                size={20}
                                            />
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <LogoutConfirmModal {...bindOpen(showLogoutModal)} />
            </aside>
        );
    },
});

/* ------------------------------------------------------------------ */
/* SiteBottomNav — mobile bottom bar (YT style)                         */
/* ------------------------------------------------------------------ */

export const SiteBottomNav = defineComponent({
    name: 'SiteBottomNav',
    setup() {
        const page = usePage();
        const currentUrl = computed(() => String(page.url));
        const { bottomNavItems } = useBottomNavCustomization();

        const homeHref = computed(() => preferredHomeHref(currentUrl.value));

        const isActive = (href: string, match?: (url: string) => boolean) => {
            if (match) {
                return match(currentUrl.value);
            }

            return currentUrl.value.startsWith(href);
        };

        const resolvedHref = (item: { href: string }) =>
            item.href === '/' ? homeHref.value : item.href;

        return () => (
            <nav
                class="fixed inset-x-0 bottom-0 z-40 w-full border-t border-slate-200/70 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/95"
                aria-label="Bottom navigation"
            >
                <div class="mx-auto flex w-full max-w-md items-center justify-around px-1 py-2">
                    {bottomNavItems.value.map((item) => (
                        <Link
                            key={item.href}
                            href={resolvedHref(item)}
                            class={[
                                'flex min-w-0 flex-1 flex-col items-center gap-1 rounded-xl px-2 py-2 transition-all duration-150 ease-out',
                                isActive(item.href, item.match)
                                    ? 'text-slate-900 dark:text-white'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200',
                            ]}
                        >
                            <MaterialIcon
                                name={item.icon}
                                size={26}
                                filled={isActive(item.href, item.match)}
                                weight={400}
                                class={`shrink-0 transition-transform duration-150 ${
                                    isActive(item.href, item.match)
                                        ? 'scale-[1.02] text-slate-900 dark:text-white'
                                        : 'text-slate-500 dark:text-slate-400'
                                }`}
                            />
                            <span
                                class={[
                                    'text-[10px] leading-none tracking-wide antialiased',
                                    isActive(item.href, item.match)
                                        ? 'font-bold'
                                        : 'font-medium',
                                ]}
                            >
                                {item.label}
                            </span>
                        </Link>
                    ))}
                </div>
            </nav>
        );
    },
});

/* ------------------------------------------------------------------ */
/* SiteDrawer — mobile top hamburger bar + slide-over drawer            */
/* ------------------------------------------------------------------ */

export const SiteDrawer = defineComponent({
    name: 'SiteDrawer',
    props: {
        open: { type: Boolean, required: true },
    },
    emits: ['update:open', 'close'],
    setup(props, { emit }) {
        const page = usePage();
        const user = computed(
            () => page.props.auth?.user as AuthedUser | undefined,
        );
        const canAccessAdmin = computed(() => staffAccess(page.props.auth));
        const currentUrl = computed(() => String(page.url));

        const { theme, setTheme } = useDarkMode();
        const { deferredPrompt, isInstalled, promptInstall } = usePwa();
        const canInstallApp = computed(
            () => !isInstalled.value && Boolean(deferredPrompt.value),
        );
        const { availableItems } = useBottomNavCustomization();

        const showLogoutModal = ref(false);

        const close = () => {
            emit('update:open', false);
            emit('close');
        };

        const askLogout = () => {
            close();
            showLogoutModal.value = true;
        };

        const handleInstallApp = async () => {
            await promptInstall();
            close();
        };

        const isActive = (href: string, match?: (url: string) => boolean) => {
            if (match) {
                return match(currentUrl.value);
            }

            return currentUrl.value.startsWith(href);
        };

        let previousOverflow: string | null = null;

        watch(
            () => props.open,
            (v) => {
                if (typeof document === 'undefined') {
                    return;
                }

                if (v) {
                    previousOverflow = document.body.style.overflow;
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = previousOverflow ?? '';
                    previousOverflow = null;
                }
            },
        );

        onBeforeUnmount(() => {
            if (typeof document === 'undefined') {
                return;
            }

            if (previousOverflow !== null) {
                document.body.style.overflow = previousOverflow;
                previousOverflow = null;
            } else if (props.open) {
                document.body.style.overflow = '';
            }
        });

        return () => (
            <div>
                {/* Top bar hamburger (mobile) */}
                <div class="sticky top-0 z-30 flex h-[56px] items-center gap-2.5 border-b border-slate-200/70 bg-white/90 px-3.5 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/90">
                    <button
                        type="button"
                        onClick={() => emit('update:open', true)}
                        class="flex h-9 w-9 items-center justify-center rounded-full text-slate-700 transition-colors hover:bg-slate-100 active:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-800"
                        aria-label="Open navigation menu"
                    >
                        <MaterialIcon name="menu" size={24} />
                    </button>
                    <div class="ml-1">
                        <AppLogo />
                    </div>
                    <div class="ml-auto flex items-center gap-1.5">
                        {user.value && <NotificationDropdown />}
                    </div>
                </div>

                {/* Drawer */}
                <Teleport to="body">
                    {props.open && (
                        <div class="fixed inset-0 z-50 flex">
                            <div
                                class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm dark:bg-black/60"
                                onClick={close}
                            />
                            <Transition
                                appear
                                enterActiveClass="transition-transform duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]"
                                enterFromClass="-translate-x-full"
                                enterToClass="translate-x-0"
                                leaveActiveClass="transition-transform duration-200 ease-in"
                                leaveFromClass="translate-x-0"
                                leaveToClass="-translate-x-full"
                            >
                                <aside class="relative flex h-full w-[84%] max-w-[320px] flex-col border-r border-slate-200/60 bg-white/85 shadow-[8px_0_32px_rgba(0,0,0,0.12)] backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-900/70 dark:backdrop-blur-xl">
                                    <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200/60 px-4 dark:border-slate-800/60">
                                        <AppLogo />
                                        <button
                                            onClick={close}
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition-all duration-150 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                                            aria-label="Close menu"
                                        >
                                            <MaterialIcon
                                                name="close"
                                                size={18}
                                            />
                                        </button>
                                    </div>

                                    <div class="flex-1 overflow-y-auto py-3.5">
                                        {/* Overflow items */}
                                        <p class="mb-2 px-4 text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500">
                                            More
                                        </p>
                                        <nav class="space-y-0.5 px-2.5">
                                            {availableItems.value.map(
                                                (item) => (
                                                    <Link
                                                        key={item.href}
                                                        href={item.href}
                                                        onClick={close}
                                                        class={[
                                                            'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                                                            isActive(
                                                                item.href,
                                                                item.match,
                                                            )
                                                                ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                                : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                                        ]}
                                                    >
                                                        <MaterialIcon
                                                            name={item.icon}
                                                            size={22}
                                                            class={`shrink-0 transition-colors duration-150 ${
                                                                isActive(
                                                                    item.href,
                                                                    item.match,
                                                                )
                                                                    ? 'text-indigo-600 dark:text-indigo-300'
                                                                    : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                            }`}
                                                        />
                                                        <span class="truncate">
                                                            {item.label}
                                                        </span>
                                                        {isActive(
                                                            item.href,
                                                            item.match,
                                                        ) && (
                                                            <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                                        )}
                                                    </Link>
                                                ),
                                            )}
                                            {availableItems.value.length ===
                                                0 && (
                                                <p class="px-3 py-2 text-xs text-slate-500 dark:text-gray-400">
                                                    All items are in your bottom
                                                    bar. Customize in Profile →
                                                    Bottom navigation.
                                                </p>
                                            )}
                                        </nav>

                                        {/* Install */}
                                        <div class="px-2.5">
                                            {canInstallApp.value && (
                                                <button
                                                    type="button"
                                                    onClick={handleInstallApp}
                                                    class="mt-4 flex h-11 w-full items-center justify-center gap-2.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3 text-[13px] font-bold text-indigo-700 shadow-sm transition-all duration-150 hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20"
                                                >
                                                    <MaterialIcon
                                                        name="download"
                                                        size={20}
                                                    />
                                                    Install App
                                                </button>
                                            )}
                                        </div>

                                        {/* Theme */}
                                        <div class="mt-4 px-2.5">
                                            <p class="mb-2 px-2 text-[11px] font-bold tracking-widest text-slate-400 uppercase dark:text-gray-500">
                                                Appearance
                                            </p>
                                            <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 dark:bg-gray-900">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setTheme('light')
                                                    }
                                                    class={[
                                                        'rounded-lg py-2 text-xs font-semibold',
                                                        theme.value === 'light'
                                                            ? 'bg-white shadow dark:bg-gray-800'
                                                            : 'text-slate-500',
                                                    ]}
                                                >
                                                    <span class="flex items-center justify-center gap-1">
                                                        <MaterialIcon
                                                            name="light_mode"
                                                            size={16}
                                                        />
                                                        Light
                                                    </span>
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setTheme('dark')
                                                    }
                                                    class={[
                                                        'rounded-lg py-2 text-xs font-semibold',
                                                        theme.value === 'dark'
                                                            ? 'bg-white shadow dark:bg-gray-800'
                                                            : 'text-slate-500',
                                                    ]}
                                                >
                                                    <span class="flex items-center justify-center gap-1">
                                                        <MaterialIcon
                                                            name="dark_mode"
                                                            size={16}
                                                        />
                                                        Dark
                                                    </span>
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setTheme('system')
                                                    }
                                                    class={[
                                                        'rounded-lg py-2 text-xs font-semibold',
                                                        theme.value === 'system'
                                                            ? 'bg-white shadow dark:bg-gray-800'
                                                            : 'text-slate-500',
                                                    ]}
                                                >
                                                    <span class="flex items-center justify-center gap-1">
                                                        <MaterialIcon
                                                            name="computer"
                                                            size={16}
                                                        />
                                                        System
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Account footer (fixed, desktop-style card) */}
                                    <div class="shrink-0 border-t border-slate-200/60 bg-white/40 p-2 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/40 dark:backdrop-blur-sm">
                                        {user.value ? (
                                            <div class="flex items-center gap-2.5 rounded-xl border bg-white px-3 py-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                                <Link
                                                    href={
                                                        user.value.username
                                                            ? `/u/${user.value.username}`
                                                            : '/profile'
                                                    }
                                                    onClick={close}
                                                    class="flex min-w-0 flex-1 items-center gap-2.5"
                                                    title="Profile"
                                                    aria-label="Profile"
                                                >
                                                    {user.value.image_url ? (
                                                        <img
                                                            src={
                                                                user.value
                                                                    .image_url
                                                            }
                                                            alt={
                                                                user.value.name
                                                            }
                                                            class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700"
                                                        />
                                                    ) : (
                                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-xs font-bold text-white ring-1 ring-indigo-600/20">
                                                            {user.value.name
                                                                .charAt(0)
                                                                .toUpperCase()}
                                                        </span>
                                                    )}
                                                    <span class="min-w-0 text-left">
                                                        <span class="block truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                            {user.value.name}
                                                        </span>
                                                        <span class="block truncate text-xs text-slate-500 dark:text-slate-400">
                                                            {user.value.email}
                                                        </span>
                                                    </span>
                                                </Link>
                                                {canAccessAdmin.value && (
                                                    <Link
                                                        href={
                                                            isAdminRoute(
                                                                currentUrl.value,
                                                            )
                                                                ? '/'
                                                                : '/admin'
                                                        }
                                                        onClick={close}
                                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-500 transition-colors hover:bg-white hover:text-slate-900 hover:shadow-sm dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                                                        title={
                                                            isAdminRoute(
                                                                currentUrl.value,
                                                            )
                                                                ? 'Home'
                                                                : 'Staff Panel'
                                                        }
                                                        aria-label={
                                                            isAdminRoute(
                                                                currentUrl.value,
                                                            )
                                                                ? 'Home'
                                                                : 'Staff Panel'
                                                        }
                                                    >
                                                        <MaterialIcon
                                                            name="dashboard"
                                                            size={20}
                                                        />
                                                    </Link>
                                                )}
                                                <button
                                                    type="button"
                                                    onClick={askLogout}
                                                    title="Log out"
                                                    aria-label="Log out"
                                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-600 hover:shadow-sm dark:text-rose-400 dark:hover:bg-rose-950/40 dark:hover:text-rose-300"
                                                >
                                                    <MaterialIcon
                                                        name="logout"
                                                        size={20}
                                                    />
                                                </button>
                                            </div>
                                        ) : (
                                            <Link
                                                href="/login"
                                                onClick={close}
                                                class="flex h-11 w-full items-center justify-center gap-2.5 rounded-xl border border-transparent bg-indigo-600 px-3 text-[13px] font-bold text-white shadow-sm transition-all duration-150 hover:bg-indigo-700 hover:shadow-md dark:bg-indigo-500 dark:hover:bg-indigo-400"
                                            >
                                                <MaterialIcon
                                                    name="login"
                                                    size={20}
                                                />
                                                Sign in
                                            </Link>
                                        )}
                                    </div>
                                </aside>
                            </Transition>
                        </div>
                    )}
                </Teleport>

                <LogoutConfirmModal {...bindOpen(showLogoutModal)} />
            </div>
        );
    },
});

/* ------------------------------------------------------------------ */
/* AdminRail — staff side rail                                         */
/* ------------------------------------------------------------------ */

export const AdminRail = defineComponent({
    name: 'AdminRail',
    props: {
        navigation: {
            type: Array as PropType<AdminNavItem[]>,
            required: true,
        },
        collapsed: { type: Boolean, required: true },
    },
    emits: ['toggle'],
    setup(props, { emit }) {
        const page = usePage();
        const user = computed(
            () => page.props.auth?.user as AuthedUser | undefined,
        );
        const currentUrl = computed(() => String(page.url));
        const { can } = usePermissions();
        const { theme, toggle, setTheme } = useDarkMode();
        const showLogoutModal = ref(false);

        const handleClearCache = () => {
            if (confirm('Are you sure you want to clear all cache?')) {
                router.post('/admin/clear-cache');
            }
        };

        return () => (
            <aside
                class={[
                    'flex shrink-0 flex-col bg-white/70 backdrop-blur-xl transition-all duration-300 dark:bg-slate-900/60 dark:backdrop-blur-xl',
                    'sticky top-0 h-screen border-r border-slate-200/60 shadow-[1px_0_3px_rgba(0,0,0,0.02),4px_0_16px_rgba(0,0,0,0.03)] dark:border-slate-800/60 dark:shadow-none',
                    props.collapsed ? 'w-[72px]' : 'w-[280px]',
                ]}
                aria-label="Staff navigation"
            >
                {/* Header */}
                <div
                    class={[
                        'flex shrink-0 items-center border-b border-slate-200/60 bg-white/70 backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-900/60 dark:backdrop-blur-xl',
                        props.collapsed
                            ? 'h-auto flex-col justify-center gap-3 px-2 py-3'
                            : 'h-16 flex-row justify-between px-3',
                    ]}
                >
                    {!props.collapsed ? (
                        <AppLogo />
                    ) : (
                        <Link
                            href="/admin"
                            class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-slate-900 shadow-sm ring-1 ring-slate-900/10 dark:bg-gray-100"
                            aria-label="Staff Panel"
                        >
                            <img
                                src="/favicon.svg"
                                alt="HSCStack"
                                class="h-6 w-6 scale-120 object-cover"
                            />
                        </Link>
                    )}
                    <button
                        type="button"
                        onClick={() => emit('toggle')}
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition-all duration-150 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 hover:shadow dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                        aria-label={
                            props.collapsed
                                ? 'Expand sidebar'
                                : 'Collapse sidebar'
                        }
                    >
                        <MaterialIcon
                            name={
                                props.collapsed
                                    ? 'chevron_right'
                                    : 'chevron_left'
                            }
                            size={18}
                        />
                    </button>
                </div>

                {/* Scrollable admin nav */}
                <div class="flex flex-1 flex-col overflow-y-auto py-3.5">
                    {!props.collapsed && (
                        <div class="px-4 pb-2.5">
                            <p class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500">
                                Management
                            </p>
                        </div>
                    )}
                    <nav class="space-y-0.5 px-2.5">
                        {props.navigation.map((item) => (
                            <Link
                                key={item.to}
                                href={item.to}
                                class={[
                                    'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                                    isActiveAdminRoute(
                                        item.to,
                                        currentUrl.value,
                                    )
                                        ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                        : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                    props.collapsed
                                        ? 'justify-center px-2'
                                        : '',
                                ]}
                                title={props.collapsed ? item.name : undefined}
                            >
                                <MaterialIcon
                                    name={item.icon}
                                    size={22}
                                    class={`shrink-0 transition-colors duration-150 ${
                                        isActiveAdminRoute(
                                            item.to,
                                            currentUrl.value,
                                        )
                                            ? 'text-indigo-600 dark:text-indigo-300'
                                            : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                    }`}
                                />
                                {!props.collapsed && (
                                    <span class="truncate">{item.name}</span>
                                )}
                                {!props.collapsed &&
                                    isActiveAdminRoute(
                                        item.to,
                                        currentUrl.value,
                                    ) && (
                                        <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                    )}
                            </Link>
                        ))}
                    </nav>

                    {/* Clear cache */}
                    {can('clear cache') && (
                        <div class="mt-4 px-2.5">
                            <button
                                type="button"
                                onClick={handleClearCache}
                                class={[
                                    'flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight text-rose-600 transition-all duration-150 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10',
                                    props.collapsed
                                        ? 'justify-center px-2'
                                        : '',
                                ]}
                                title={
                                    props.collapsed ? 'Clear cache' : undefined
                                }
                            >
                                <MaterialIcon
                                    name="cached"
                                    size={22}
                                    class="shrink-0"
                                />
                                {!props.collapsed && (
                                    <span class="truncate">Clear cache</span>
                                )}
                            </button>
                        </div>
                    )}
                </div>

                {/* Footer controls */}
                <div class="border-t border-slate-200/60 bg-white/40 p-2 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/40 dark:backdrop-blur-sm">
                    <div class="space-y-1.5">
                        {/* Appearance */}
                        {!props.collapsed ? (
                            <div>
                                <p class="mb-1.5 px-2 text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500">
                                    Appearance
                                </p>
                                <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                                    <button
                                        type="button"
                                        onClick={() => setTheme('light')}
                                        class={[
                                            'flex items-center justify-center gap-1 rounded-lg py-2 text-xs font-semibold transition-all',
                                            theme.value === 'light'
                                                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-slate-100'
                                                : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200',
                                        ]}
                                    >
                                        <MaterialIcon
                                            name="light_mode"
                                            size={16}
                                        />
                                        Light
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setTheme('dark')}
                                        class={[
                                            'flex items-center justify-center gap-1 rounded-lg py-2 text-xs font-semibold transition-all',
                                            theme.value === 'dark'
                                                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-slate-100'
                                                : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200',
                                        ]}
                                    >
                                        <MaterialIcon
                                            name="dark_mode"
                                            size={16}
                                        />
                                        Dark
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setTheme('system')}
                                        class={[
                                            'flex items-center justify-center gap-1 rounded-lg py-2 text-xs font-semibold transition-all',
                                            theme.value === 'system'
                                                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-slate-100'
                                                : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200',
                                        ]}
                                    >
                                        <MaterialIcon
                                            name="computer"
                                            size={16}
                                        />
                                        System
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <button
                                type="button"
                                onClick={toggle}
                                aria-label="Toggle theme"
                                title={`Theme: ${theme.value}`}
                                class="flex h-11 w-full items-center justify-center rounded-xl border border-transparent text-slate-600 transition-all duration-150 hover:border-slate-200 hover:bg-white hover:text-slate-900 hover:shadow-sm dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                            >
                                <MaterialIcon
                                    name={
                                        theme.value === 'system'
                                            ? 'computer'
                                            : theme.value === 'light'
                                              ? 'light_mode'
                                              : 'dark_mode'
                                    }
                                    size={20}
                                />
                            </button>
                        )}

                        {/* Notifications */}
                        {user.value && (
                            <div
                                class={[
                                    'flex h-11 w-full items-center gap-2.5 px-3',
                                    props.collapsed
                                        ? 'justify-center px-0'
                                        : 'justify-start',
                                ]}
                            >
                                <NotificationDropdown
                                    plain={!props.collapsed}
                                />
                                {!props.collapsed && (
                                    <span class="text-[13px] font-semibold text-slate-600 dark:text-slate-400">
                                        Notifications
                                    </span>
                                )}
                            </div>
                        )}

                        {/* Back to site */}
                        <Link
                            href="/"
                            class={[
                                'flex h-11 w-full items-center gap-2.5 rounded-xl border px-3 text-[13px] font-semibold transition-all duration-150',
                                'border-transparent text-slate-600 hover:border-slate-200 hover:bg-white hover:text-slate-900 hover:shadow-sm dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-100',
                                props.collapsed ? 'justify-center' : '',
                            ]}
                            title={props.collapsed ? 'Back to site' : undefined}
                            aria-label="Back to site"
                        >
                            <MaterialIcon name="home" size={20} />
                            {!props.collapsed && <span>Back to site</span>}
                        </Link>

                        {/* User + logout */}
                        {user.value && (
                            <div class={props.collapsed ? 'px-0' : 'pt-1'}>
                                <div
                                    class={[
                                        'flex items-center gap-3 rounded-xl border bg-white px-3 py-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800',
                                        props.collapsed
                                            ? 'flex-col justify-center gap-2 py-3'
                                            : 'justify-between',
                                    ]}
                                >
                                    <Link
                                        href={
                                            user.value.username
                                                ? `/u/${user.value.username}`
                                                : '/profile'
                                        }
                                        class={[
                                            'flex items-center gap-2.5',
                                            props.collapsed
                                                ? 'justify-center'
                                                : 'min-w-0',
                                        ]}
                                        title={
                                            props.collapsed
                                                ? 'Profile'
                                                : undefined
                                        }
                                        aria-label={
                                            props.collapsed
                                                ? 'Profile'
                                                : undefined
                                        }
                                    >
                                        {user.value.image_url ? (
                                            <img
                                                src={user.value.image_url}
                                                alt={user.value.name}
                                                class="h-8 w-8 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700"
                                            />
                                        ) : (
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-xs font-bold text-white ring-1 ring-indigo-600/20">
                                                {user.value.name
                                                    .charAt(0)
                                                    .toUpperCase()}
                                            </span>
                                        )}
                                        {!props.collapsed && (
                                            <div class="min-w-0 text-left">
                                                <p class="truncate text-xs font-semibold text-slate-900 dark:text-slate-100">
                                                    {user.value.name}
                                                </p>
                                                <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">
                                                    {user.value.email}
                                                </p>
                                            </div>
                                        )}
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            showLogoutModal.value = true;
                                        }}
                                        title="Log out"
                                        aria-label="Log out"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-600 hover:shadow-sm dark:text-rose-400 dark:hover:bg-rose-950/40 dark:hover:text-rose-300"
                                    >
                                        <MaterialIcon name="logout" size={20} />
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <LogoutConfirmModal {...bindOpen(showLogoutModal)} />
            </aside>
        );
    },
});

/* ------------------------------------------------------------------ */
/* AdminDrawer — staff mobile drawer (teleported)                       */
/* ------------------------------------------------------------------ */

export const AdminDrawer = defineComponent({
    name: 'AdminDrawer',
    props: {
        navigation: {
            type: Array as PropType<AdminNavItem[]>,
            required: true,
        },
        isOpen: { type: Boolean, required: true },
    },
    emits: ['close'],
    setup(props, { emit }) {
        const { can } = usePermissions();
        const page = usePage();
        const user = computed(
            () => (page.props.auth as any)?.user as AuthedUser | undefined,
        );
        const currentUrl = computed(() => String(page.url));

        const showLogoutModal = ref(false);

        const askLogout = () => {
            emit('close');
            showLogoutModal.value = true;
        };

        const handleClearCache = () => {
            if (confirm('Are you sure you want to clear all cache?')) {
                emit('close');
                router.post('/admin/clear-cache');
            }
        };

        const close = () => emit('close');

        return () => (
            <>
                <Teleport to="body">
                    {props.isOpen && (
                        <div class="fixed inset-0 z-50 flex md:hidden">
                            <div
                                class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm dark:bg-black/60"
                                onClick={close}
                            ></div>

                            <Transition
                                appear
                                enterActiveClass="transition-transform duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]"
                                enterFromClass="-translate-x-full"
                                enterToClass="translate-x-0"
                                leaveActiveClass="transition-transform duration-200 ease-in"
                                leaveFromClass="translate-x-0"
                                leaveToClass="-translate-x-full"
                            >
                                <div class="relative flex w-full max-w-[320px] flex-1 flex-col justify-between border-r border-slate-200/60 bg-white/85 shadow-[8px_0_32px_rgba(0,0,0,0.12)] backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-900/70 dark:backdrop-blur-xl">
                                    <div class="flex-1 overflow-y-auto py-3.5">
                                        <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200/60 px-4 dark:border-slate-800/60">
                                            <AppLogo />
                                            <button
                                                onClick={close}
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition-all duration-150 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                                                aria-label="Close staff menu"
                                            >
                                                <MaterialIcon
                                                    name="close"
                                                    size={18}
                                                />
                                            </button>
                                        </div>

                                        <p class="mt-3 mb-2 px-4 text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500">
                                            Management
                                        </p>
                                        <nav class="space-y-0.5 px-2.5">
                                            {props.navigation.map((item) => (
                                                <div key={item.name}>
                                                    <Link
                                                        href={item.to}
                                                        onClick={close}
                                                        class={[
                                                            'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                                                            isActiveAdminRoute(
                                                                item.to,
                                                                currentUrl.value,
                                                            )
                                                                ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                                : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                                        ]}
                                                    >
                                                        <MaterialIcon
                                                            name={item.icon}
                                                            size={22}
                                                            class={`shrink-0 transition-colors duration-150 ${
                                                                isActiveAdminRoute(
                                                                    item.to,
                                                                    currentUrl.value,
                                                                )
                                                                    ? 'text-indigo-600 dark:text-indigo-300'
                                                                    : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                            }`}
                                                        />
                                                        <span class="truncate">
                                                            {item.name}
                                                        </span>
                                                        {isActiveAdminRoute(
                                                            item.to,
                                                            currentUrl.value,
                                                        ) && (
                                                            <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                                        )}
                                                    </Link>
                                                </div>
                                            ))}
                                        </nav>
                                    </div>

                                    <div class="space-y-1.5 border-t border-slate-200/60 bg-white/40 p-2 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/40 dark:backdrop-blur-sm">
                                        {can('clear cache') && (
                                            <button
                                                type="button"
                                                onClick={handleClearCache}
                                                class="group flex h-11 w-full items-center gap-2.5 rounded-xl px-3 text-[13px] font-semibold text-rose-600 transition-all duration-150 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                                            >
                                                <MaterialIcon
                                                    name="cached"
                                                    size={20}
                                                />
                                                <span>Clear Cache</span>
                                            </button>
                                        )}
                                        <Link
                                            href="/"
                                            onClick={close}
                                            class="flex h-11 w-full items-center gap-2.5 rounded-xl border border-transparent px-3 text-[13px] font-semibold text-slate-600 transition-all duration-150 hover:border-slate-200 hover:bg-white hover:text-slate-900 hover:shadow-sm dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                                        >
                                            <MaterialIcon
                                                name="home"
                                                size={20}
                                            />
                                            <span>Back to site</span>
                                        </Link>
                                        {user.value && (
                                            <button
                                                type="button"
                                                onClick={askLogout}
                                                class="flex h-11 w-full items-center gap-2.5 rounded-xl border border-transparent px-3 text-left text-[13px] font-bold text-rose-600 transition-all duration-150 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                            >
                                                <MaterialIcon
                                                    name="logout"
                                                    size={20}
                                                />
                                                <span>Log out</span>
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    )}
                </Teleport>

                <LogoutConfirmModal {...bindOpen(showLogoutModal)} />
            </>
        );
    },
});

/* ------------------------------------------------------------------ */
/* BottomNavCustomizer — Profile settings UI                            */
/* ------------------------------------------------------------------ */

export const BottomNavCustomizer = defineComponent({
    name: 'BottomNavCustomizer',
    setup() {
        const {
            bottomNavItems,
            availableItems,
            homeItem,
            accountItem,
            middleHrefs,
            canAdd,
            canRemove,
            addItem,
            removeItem,
            reorder,
            reset,
            MIN_TOTAL,
            MAX_TOTAL,
        } = useBottomNavCustomization();

        const dragIndex = ref<number | null>(null);

        const onDragStart = (index: number, e: DragEvent) => {
            dragIndex.value = index;

            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', String(index));
            }
        };

        const onDragOver = (e: DragEvent) => {
            e.preventDefault();

            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = 'move';
            }
        };

        const onDrop = (targetIndex: number, e: DragEvent) => {
            e.preventDefault();
            const from = dragIndex.value;

            if (from === null || from === targetIndex) {
                dragIndex.value = null;

                return;
            }

            reorder(from, targetIndex);
            dragIndex.value = null;
        };

        const onDragEnd = () => {
            dragIndex.value = null;
        };

        const handleAdd = (href: string) => {
            if (!canAdd.value) {
                return;
            }

            addItem(href);
        };

        const handleRemove = (href: string) => {
            if (!canRemove.value) {
                return;
            }

            removeItem(href);
        };

        return () => (
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-6">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-gray-100">
                        Bottom navigation
                    </h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                        Customize your mobile bottom bar (3–5 items). Home and
                        Account are pinned — drag the middle items to reorder.
                        Changes save automatically to this device.
                    </p>
                    <p class="mt-2 text-xs font-medium">
                        <span
                            class={
                                bottomNavItems.value.length < MIN_TOTAL ||
                                bottomNavItems.value.length > MAX_TOTAL
                                    ? 'text-amber-600'
                                    : 'text-slate-500 dark:text-gray-400'
                            }
                        >
                            {bottomNavItems.value.length} / {MAX_TOTAL} items
                        </span>
                        <span class="mx-2 text-slate-300">·</span>
                        <button
                            type="button"
                            onClick={reset}
                            class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                        >
                            <MaterialIcon name="restart_alt" size={14} /> Reset
                        </button>
                    </p>
                </div>

                {/* Current bottom bar (pinned + draggable middle) */}
                <div>
                    <p class="mb-2 text-[11px] font-bold tracking-widest text-slate-400 uppercase dark:text-gray-500">
                        Bottom bar — drag middle to reorder
                    </p>
                    <ul class="space-y-2">
                        {/* Home pinned */}
                        <li class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/60">
                            <MaterialIcon
                                name="lock"
                                size={16}
                                class="shrink-0 text-slate-400"
                            />
                            <MaterialIcon
                                name={homeItem.value.icon}
                                size={22}
                                class="shrink-0 text-slate-700 dark:text-gray-300"
                            />
                            <span class="flex-1 text-sm font-semibold text-slate-900 dark:text-gray-100">
                                {homeItem.value.label}
                            </span>
                            <span class="rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white dark:bg-white dark:text-slate-900">
                                Pinned first
                            </span>
                        </li>

                        {/* Middle draggable */}
                        {middleHrefs.value.map((href, idx) => (
                            <li
                                key={href}
                                draggable={true}
                                onDragstart={(e: DragEvent) =>
                                    onDragStart(idx, e)
                                }
                                onDragover={(e: DragEvent) => onDragOver(e)}
                                onDrop={(e: DragEvent) => onDrop(idx, e)}
                                onDragend={onDragEnd}
                                class={[
                                    'flex items-center gap-3 rounded-xl border bg-white px-3 py-2.5 shadow-sm transition dark:bg-gray-800',
                                    dragIndex.value === idx
                                        ? 'border-indigo-300 ring-2 ring-indigo-200 dark:border-indigo-700'
                                        : 'border-slate-200 dark:border-gray-700',
                                ]}
                            >
                                <MaterialIcon
                                    name="drag_indicator"
                                    size={16}
                                    class="shrink-0 cursor-grab text-slate-400 active:cursor-grabbing"
                                />
                                <MaterialIcon
                                    name={
                                        bottomNavItems.value[idx + 1]?.icon ??
                                        'help'
                                    }
                                    size={22}
                                    class="shrink-0 text-slate-600 dark:text-gray-300"
                                />
                                <span class="flex-1 text-sm font-medium text-slate-800 dark:text-gray-200">
                                    {bottomNavItems.value[idx + 1]?.label}
                                </span>
                                <button
                                    type="button"
                                    onClick={() => handleRemove(href)}
                                    disabled={!canRemove.value}
                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30 dark:hover:bg-rose-950/40"
                                    aria-label="Remove from bottom bar"
                                >
                                    <MaterialIcon name="close" size={16} />
                                </button>
                            </li>
                        ))}

                        {/* Account pinned last */}
                        <li class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/60">
                            <MaterialIcon
                                name="lock"
                                size={16}
                                class="shrink-0 text-slate-400"
                            />
                            <MaterialIcon
                                name={accountItem.value.icon}
                                size={22}
                                class="shrink-0 text-slate-700 dark:text-gray-300"
                            />
                            <span class="flex-1 text-sm font-semibold text-slate-900 dark:text-gray-100">
                                {accountItem.value.label}
                            </span>
                            <span class="rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white dark:bg-white dark:text-slate-900">
                                Pinned last
                            </span>
                        </li>
                    </ul>
                    {!canRemove.value && (
                        <p class="mt-2 text-[11px] text-amber-600 dark:text-amber-400">
                            Minimum {MIN_TOTAL} items — remove disabled.
                        </p>
                    )}
                    {!canAdd.value && (
                        <p class="mt-2 text-[11px] text-amber-600 dark:text-amber-400">
                            Maximum {MAX_TOTAL} items — add disabled.
                        </p>
                    )}
                </div>

                {/* Available pool */}
                <div class="mt-6">
                    <p class="mb-2 text-[11px] font-bold tracking-widest text-slate-400 uppercase dark:text-gray-500">
                        More items — tap to add to bottom bar
                    </p>
                    {availableItems.value.length === 0 ? (
                        <div class="rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-500 dark:border-gray-700 dark:text-gray-400">
                            All items are already in your bottom bar.
                        </div>
                    ) : (
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            {availableItems.value.map((item) => (
                                <button
                                    key={item.href}
                                    type="button"
                                    onClick={() => handleAdd(item.href)}
                                    disabled={!canAdd.value}
                                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-left text-sm font-medium transition hover:bg-slate-50 disabled:opacity-40 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700/60"
                                >
                                    <MaterialIcon
                                        name={item.icon}
                                        size={22}
                                        class="shrink-0 text-slate-500"
                                    />
                                    <span class="flex-1 text-slate-700 dark:text-gray-300">
                                        {item.label}
                                    </span>
                                    <MaterialIcon
                                        name="add"
                                        size={16}
                                        class="shrink-0 text-indigo-600 dark:text-indigo-400"
                                    />
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        );
    },
});
