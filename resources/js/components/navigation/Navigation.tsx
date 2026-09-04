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
    nextTick,
    onBeforeUnmount,
    ref,
    toRef,
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

function isActiveAdminRoute(to: string, currentUrl: string): boolean {
    if (to === '/admin') {
        return currentUrl === '/admin' || currentUrl.startsWith('/admin?');
    }

    return currentUrl.startsWith(to);
}

function getCollapsedAdminLabel(name: string): string {
    const map: Record<string, string> = {
        'Manage Contents': 'Contents',
        'Manage Blogs': 'Blogs',
        'Manage Forum': 'Forum',
        'Support Tickets': 'Support',
        'Site Notice': 'Notice',
        'Global Chat': 'Chat',
        'Send Emails': 'Emails',
    };

    return map[name] || name.replace(/^Manage\s+/i, '');
}

/**
 * Opens the dropdown from the row label. Stops propagation so the
 * label-targeted event never reaches the document outside-click
 * listener (which would instantly close what just opened).
 */
function openNotificationsFromLabel(
    e: MouseEvent | KeyboardEvent,
    scope: Ref<HTMLElement | null>,
) {
    e.stopPropagation();
    e.preventDefault();
    scope.value?.querySelector('button')?.click();
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

/**
 * Keyboard accessibility for a modal drawer panel: Escape closes,
 * focus moves to `initial` on open and returns to whatever had focus
 * (usually the hamburger trigger) on close, and Tab cycles inside
 * `panel` while open.
 */
function useDialogA11y(options: {
    open: Ref<boolean>;
    panel: Ref<HTMLElement | null>;
    initial: Ref<HTMLElement | null>;
    onEscape: () => void;
}) {
    const { open, panel, initial, onEscape } = options;
    let lastFocused: Element | null = null;

    const focusables = (): HTMLElement[] => {
        if (!panel.value) {
            return [];
        }

        return Array.from(
            panel.value.querySelectorAll<HTMLElement>(
                'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])',
            ),
        ).filter((el) => el.offsetParent !== null);
    };

    const onKeyDown = (e: KeyboardEvent) => {
        if (e.key === 'Escape') {
            onEscape();

            return;
        }

        if (e.key !== 'Tab') {
            return;
        }

        const items = focusables();

        if (items.length === 0) {
            return;
        }

        const first = items[0];
        const last = items[items.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    };

    watch(open, (isOpen) => {
        if (typeof document === 'undefined') {
            return;
        }

        if (isOpen) {
            lastFocused = document.activeElement;
            document.addEventListener('keydown', onKeyDown);
            nextTick(() => initial.value?.focus());
        } else {
            document.removeEventListener('keydown', onKeyDown);

            if (
                lastFocused &&
                document.contains(lastFocused) &&
                lastFocused instanceof HTMLElement
            ) {
                lastFocused.focus();
            }

            lastFocused = null;
        }
    });

    onBeforeUnmount(() => {
        if (typeof document !== 'undefined') {
            document.removeEventListener('keydown', onKeyDown);
        }
    });
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
        const notifRowRef = ref<HTMLElement | null>(null);
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
                    'sticky top-0 z-30 flex h-screen shrink-0 flex-col overflow-x-hidden bg-white/70 backdrop-blur-xl transition-[width] duration-300 ease-in-out dark:bg-slate-900/60 dark:backdrop-blur-xl',
                    'border-r border-slate-200/60 shadow-[1px_0_3px_rgba(0,0,0,0.02),4px_0_16px_rgba(0,0,0,0.03)] dark:border-slate-800/60 dark:shadow-none',
                    props.collapsed ? 'w-[72px]' : 'w-[280px]',
                ]}
                aria-label="Side navigation"
            >
                {/* Fixed container width ensures children never recalculate middle during width transition */}
                <div class="flex h-full w-[280px] flex-col">
                    {/* Header: Hamburger + Logo */}
                    <div class="flex h-16 shrink-0 items-center border-b border-slate-200/60 bg-white/70 backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-900/60 dark:backdrop-blur-xl">
                        <div class="flex w-[72px] shrink-0 items-center justify-center">
                            <button
                                type="button"
                                onClick={() => emit('toggle')}
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-slate-700 transition-colors hover:bg-slate-100 active:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-800"
                                aria-label={
                                    props.collapsed
                                        ? 'Expand sidebar'
                                        : 'Collapse sidebar'
                                }
                            >
                                <MaterialIcon name="menu" size={24} />
                            </button>
                        </div>
                        <div
                            class={[
                                'flex items-center overflow-hidden transition-all duration-300 ease-in-out',
                                props.collapsed
                                    ? 'pointer-events-none w-0 opacity-0'
                                    : 'w-[200px] opacity-100',
                            ]}
                        >
                            <AppLogo />
                        </div>
                    </div>

                    {/* Scrollable nav */}
                    <div class="flex flex-1 flex-col overflow-y-auto py-2">
                        {props.collapsed ? (
                            /* Collapsed: Compact YouTube Mini-Guide (strictly w-[72px] fixed) */
                            <nav class="flex w-[72px] flex-col items-center space-y-1 px-1">
                                {allNavItems.map((item) => {
                                    const active = isActive(
                                        item.href,
                                        item.match,
                                    );
                                    const label =
                                        item.href === '/support'
                                            ? 'Support'
                                            : item.label;

                                    return (
                                        <Link
                                            key={item.href}
                                            href={
                                                item.href === '/'
                                                    ? homeHref.value
                                                    : item.href
                                            }
                                            class={[
                                                'group flex h-[60px] w-full flex-col items-center justify-center rounded-xl px-1 text-center transition-colors duration-150',
                                                active
                                                    ? 'bg-indigo-50/80 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200'
                                                    : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                            ]}
                                            title={item.label}
                                        >
                                            <MaterialIcon
                                                name={item.icon}
                                                size={22}
                                                class={`shrink-0 transition-colors duration-150 ${
                                                    active
                                                        ? 'text-indigo-600 dark:text-indigo-300'
                                                        : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                }`}
                                            />
                                            <span class="mt-1 max-w-[64px] truncate text-[10px] leading-tight font-medium">
                                                {label}
                                            </span>
                                        </Link>
                                    );
                                })}
                                {canAccessAdmin.value && (
                                    <Link
                                        href="/admin"
                                        class={[
                                            'group flex h-[60px] w-full flex-col items-center justify-center rounded-xl px-1 text-center transition-colors duration-150',
                                            isActiveAdminRoute(
                                                '/admin',
                                                currentUrl.value,
                                            )
                                                ? 'bg-indigo-50/80 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200'
                                                : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                        ]}
                                        title="Dashboard"
                                    >
                                        <MaterialIcon
                                            name="dashboard"
                                            size={22}
                                            class={`shrink-0 transition-colors duration-150 ${
                                                isActiveAdminRoute(
                                                    '/admin',
                                                    currentUrl.value,
                                                )
                                                    ? 'text-indigo-600 dark:text-indigo-300'
                                                    : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                            }`}
                                        />
                                        <span class="mt-1 max-w-[64px] truncate text-[10px] leading-tight font-medium">
                                            Dashboard
                                        </span>
                                    </Link>
                                )}
                            </nav>
                        ) : (
                            /* Expanded: Standard 280px Guide */
                            <nav class="w-[280px] space-y-1 px-2.5">
                                {allNavItems.map((item) => {
                                    const active = isActive(
                                        item.href,
                                        item.match,
                                    );
                                    const label =
                                        item.href === '/chat'
                                            ? 'Global Chat'
                                            : item.label;

                                    return (
                                        <Link
                                            key={item.href}
                                            href={
                                                item.href === '/'
                                                    ? homeHref.value
                                                    : item.href
                                            }
                                            class={[
                                                'group flex h-10 items-center gap-3 rounded-[10px] px-3 text-[13px] font-medium tracking-tight transition-colors duration-150',
                                                active
                                                    ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                    : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                            ]}
                                        >
                                            <MaterialIcon
                                                name={item.icon}
                                                size={22}
                                                class={`shrink-0 transition-colors duration-150 ${
                                                    active
                                                        ? 'text-indigo-600 dark:text-indigo-300'
                                                        : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                }`}
                                            />
                                            <span class="truncate">
                                                {label}
                                            </span>
                                            {active && (
                                                <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                            )}
                                        </Link>
                                    );
                                })}
                                {canAccessAdmin.value && (
                                    <Link
                                        href="/admin"
                                        class={[
                                            'group flex h-10 items-center gap-3 rounded-[10px] px-3 text-[13px] font-medium tracking-tight transition-colors duration-150',
                                            isActiveAdminRoute(
                                                '/admin',
                                                currentUrl.value,
                                            )
                                                ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                        ]}
                                    >
                                        <MaterialIcon
                                            name="dashboard"
                                            size={22}
                                            class={`shrink-0 transition-colors duration-150 ${
                                                isActiveAdminRoute(
                                                    '/admin',
                                                    currentUrl.value,
                                                )
                                                    ? 'text-indigo-600 dark:text-indigo-300'
                                                    : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                            }`}
                                        />
                                        <span class="truncate">Dashboard</span>
                                        {isActiveAdminRoute(
                                            '/admin',
                                            currentUrl.value,
                                        ) && (
                                            <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                        )}
                                    </Link>
                                )}

                                {/* Platform & Community Section */}
                                <div class="mt-4 border-t border-slate-100 pt-3 dark:border-slate-800/60">
                                    <p class="mb-2 px-3 text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500">
                                        Platform
                                    </p>
                                    <div class="space-y-1">
                                        <Link
                                            href="/about-us"
                                            class={[
                                                'group flex h-10 items-center gap-3 rounded-[10px] px-3 text-[13px] font-medium tracking-tight transition-colors duration-150',
                                                isActive('/about-us')
                                                    ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                    : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                            ]}
                                        >
                                            <MaterialIcon
                                                name="groups"
                                                size={22}
                                                class={`shrink-0 transition-colors duration-150 ${
                                                    isActive('/about-us')
                                                        ? 'text-indigo-600 dark:text-indigo-300'
                                                        : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                }`}
                                            />
                                            <span class="truncate">
                                                About Us
                                            </span>
                                            {isActive('/about-us') && (
                                                <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                            )}
                                        </Link>
                                        <Link
                                            href="/join"
                                            class={[
                                                'group flex h-10 items-center gap-3 rounded-[10px] px-3 text-[13px] font-medium tracking-tight transition-colors duration-150',
                                                isActive('/join')
                                                    ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                    : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                            ]}
                                        >
                                            <MaterialIcon
                                                name="person_add"
                                                size={22}
                                                class={`shrink-0 transition-colors duration-150 ${
                                                    isActive('/join')
                                                        ? 'text-indigo-600 dark:text-indigo-300'
                                                        : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                }`}
                                            />
                                            <span class="truncate">
                                                Join Our Team
                                            </span>
                                            {isActive('/join') && (
                                                <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                            )}
                                        </Link>
                                        <Link
                                            href="/projects"
                                            class={[
                                                'group flex h-10 items-center gap-3 rounded-[10px] px-3 text-[13px] font-medium tracking-tight transition-colors duration-150',
                                                isActive('/projects')
                                                    ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                    : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                            ]}
                                        >
                                            <MaterialIcon
                                                name="apps"
                                                size={22}
                                                class={`shrink-0 transition-colors duration-150 ${
                                                    isActive('/projects')
                                                        ? 'text-indigo-600 dark:text-indigo-300'
                                                        : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                }`}
                                            />
                                            <span class="truncate">
                                                More From Us
                                            </span>
                                            {isActive('/projects') && (
                                                <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                            )}
                                        </Link>
                                    </div>
                                </div>

                                {/* Legal Links */}
                                <div class="mt-3 border-t border-slate-100 px-3 pt-3 dark:border-slate-800/60">
                                    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[11px] font-medium text-slate-400 dark:text-slate-500">
                                        <Link
                                            href="/privacy-policy"
                                            class="transition hover:text-slate-700 dark:hover:text-slate-300"
                                        >
                                            Privacy
                                        </Link>
                                        <span>•</span>
                                        <Link
                                            href="/terms-service"
                                            class="transition hover:text-slate-700 dark:hover:text-slate-300"
                                        >
                                            Terms
                                        </Link>
                                        <span>•</span>
                                        <Link
                                            href="/content-policy"
                                            class="transition hover:text-slate-700 dark:hover:text-slate-300"
                                        >
                                            Content Policy
                                        </Link>
                                    </div>
                                </div>
                            </nav>
                        )}
                    </div>

                    {/* Footer controls */}
                    <div class="border-t border-slate-200/60 bg-white/40 p-2 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/40 dark:backdrop-blur-sm">
                        {props.collapsed ? (
                            /* Collapsed footer (strictly w-[56px] centered within 72px sidebar) */
                            <div class="flex w-[56px] flex-col items-center space-y-1.5">
                                {/* Appearance */}
                                <button
                                    type="button"
                                    onClick={toggle}
                                    class="flex h-10 w-full items-center justify-center rounded-xl text-slate-600 transition-colors hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100"
                                    title={`Current theme: ${theme.value}. Click to switch.`}
                                    aria-label="Toggle theme"
                                >
                                    <MaterialIcon
                                        name={
                                            theme.value === 'dark'
                                                ? 'dark_mode'
                                                : theme.value === 'light'
                                                  ? 'light_mode'
                                                  : 'computer'
                                        }
                                        size={22}
                                        class="shrink-0 text-slate-500 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-300"
                                    />
                                </button>

                                {/* Notifications */}
                                {user.value && (
                                    <div
                                        ref={notifRowRef}
                                        class="flex h-10 w-full items-center justify-center"
                                    >
                                        <NotificationDropdown plain dropUp />
                                    </div>
                                )}

                                {/* PWA Install */}
                                {canInstallApp.value && (
                                    <button
                                        type="button"
                                        aria-label="Install App"
                                        title="Install App"
                                        onClick={handleInstallApp}
                                        class="flex h-10 w-full items-center justify-center rounded-xl border border-slate-900 bg-slate-900 text-white shadow-sm transition-all hover:border-slate-700 hover:bg-slate-800 dark:border-white dark:bg-white dark:text-slate-900 dark:hover:border-slate-200 dark:hover:bg-slate-200"
                                    >
                                        <MaterialIcon
                                            name="download"
                                            size={20}
                                        />
                                    </button>
                                )}

                                {/* Auth */}
                                {!user.value ? (
                                    <Link
                                        href="/login"
                                        title="Login"
                                        aria-label="Login"
                                        class="flex h-10 w-full items-center justify-center rounded-xl border border-transparent bg-indigo-600 text-white shadow-sm transition-all hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                                    >
                                        <MaterialIcon name="login" size={20} />
                                    </Link>
                                ) : (
                                    <div class="flex flex-col items-center py-1">
                                        <Link
                                            href={
                                                user.value.username
                                                    ? `/u/${user.value.username}`
                                                    : '/profile'
                                            }
                                            title="Profile"
                                            aria-label="Profile"
                                            class="flex items-center justify-center"
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
                                        </Link>
                                    </div>
                                )}
                            </div>
                        ) : (
                            /* Expanded footer (w-[264px]) */
                            <div class="w-[264px] space-y-1.5 overflow-hidden">
                                {/* Appearance */}
                                <div class="transition-opacity duration-300">
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

                                {/* Notifications */}
                                {user.value && (
                                    <div
                                        ref={notifRowRef}
                                        class="flex h-11 w-full items-center justify-start gap-2.5 px-3"
                                    >
                                        <NotificationDropdown plain dropUp />
                                        <span
                                            role="button"
                                            tabindex={0}
                                            onClick={(e: MouseEvent) =>
                                                openNotificationsFromLabel(
                                                    e,
                                                    notifRowRef,
                                                )
                                            }
                                            onKeydown={(e: KeyboardEvent) => {
                                                if (
                                                    e.key === 'Enter' ||
                                                    e.key === ' '
                                                ) {
                                                    openNotificationsFromLabel(
                                                        e,
                                                        notifRowRef,
                                                    );
                                                }
                                            }}
                                            class="flex-1 cursor-pointer truncate text-left text-[13px] font-semibold text-slate-600 transition-colors hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                                        >
                                            Notifications
                                        </span>
                                    </div>
                                )}

                                {/* PWA Install */}
                                {canInstallApp.value && (
                                    <button
                                        type="button"
                                        aria-label="Install App"
                                        onClick={handleInstallApp}
                                        class="flex h-11 w-full items-center justify-center gap-2.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3 text-[13px] font-bold text-indigo-700 shadow-sm transition-all duration-150 hover:bg-indigo-100 hover:shadow-sm dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20"
                                    >
                                        <MaterialIcon
                                            name="download"
                                            size={20}
                                        />
                                        <span>Install App</span>
                                    </button>
                                )}

                                {/* Auth */}
                                <div class="pt-1">
                                    {!user.value ? (
                                        <Link
                                            href="/login"
                                            aria-label="Login"
                                            class="flex h-11 w-full items-center justify-center gap-2.5 rounded-xl border border-transparent bg-indigo-600 px-3 text-[13px] font-bold text-white shadow-sm transition-all duration-150 hover:border-indigo-700 hover:bg-indigo-700 hover:shadow-md dark:border-transparent dark:bg-indigo-500 dark:hover:border-indigo-400 dark:hover:bg-indigo-400"
                                        >
                                            <MaterialIcon
                                                name="login"
                                                size={20}
                                            />
                                            <span>Login</span>
                                        </Link>
                                    ) : (
                                        <div class="flex items-center justify-between gap-3 rounded-xl border bg-white px-3 py-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                            <Link
                                                href={
                                                    user.value.username
                                                        ? `/u/${user.value.username}`
                                                        : '/profile'
                                                }
                                                class="flex min-w-0 items-center gap-2.5"
                                            >
                                                {user.value.image_url ? (
                                                    <img
                                                        src={
                                                            user.value.image_url
                                                        }
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
                                                <div class="min-w-0 text-left">
                                                    <p class="truncate text-xs font-semibold text-slate-900 dark:text-slate-100">
                                                        {user.value.name}
                                                    </p>
                                                    <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">
                                                        {user.value.email}
                                                    </p>
                                                </div>
                                            </Link>
                                            <div class="flex shrink-0 items-center gap-1">
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
                        )}
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
        const panelRef = ref<HTMLElement | null>(null);
        const closeButtonRef = ref<HTMLElement | null>(null);

        const close = () => {
            emit('update:open', false);
            emit('close');
        };

        useDialogA11y({
            open: toRef(props, 'open'),
            panel: panelRef,
            initial: closeButtonRef,
            onEscape: close,
        });

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
            <>
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
                        {user.value ? (
                            <NotificationDropdown />
                        ) : (
                            <Link
                                href="/login"
                                class="flex h-8 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 text-[13px] font-semibold text-white transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                            >
                                <MaterialIcon name="login" size={18} />
                                Login
                            </Link>
                        )}
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
                                <aside
                                    ref={panelRef}
                                    role="dialog"
                                    aria-modal="true"
                                    aria-label="Site menu"
                                    class="relative flex h-full w-[84%] max-w-[320px] flex-col border-r border-slate-200/60 bg-white/85 shadow-[8px_0_32px_rgba(0,0,0,0.12)] backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-900/70 dark:backdrop-blur-xl"
                                >
                                    <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200/60 px-4 dark:border-slate-800/60">
                                        <AppLogo />
                                        <button
                                            ref={closeButtonRef}
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
                                            {canAccessAdmin.value && (
                                                <Link
                                                    href="/admin"
                                                    onClick={close}
                                                    class={[
                                                        'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                                                        isActiveAdminRoute(
                                                            '/admin',
                                                            currentUrl.value,
                                                        )
                                                            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                            : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                                    ]}
                                                >
                                                    <MaterialIcon
                                                        name="dashboard"
                                                        size={22}
                                                        class={`shrink-0 transition-colors duration-150 ${
                                                            isActiveAdminRoute(
                                                                '/admin',
                                                                currentUrl.value,
                                                            )
                                                                ? 'text-indigo-600 dark:text-indigo-300'
                                                                : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                        }`}
                                                    />
                                                    <span class="truncate">
                                                        Dashboard
                                                    </span>
                                                    {isActiveAdminRoute(
                                                        '/admin',
                                                        currentUrl.value,
                                                    ) && (
                                                        <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                                    )}
                                                </Link>
                                            )}
                                        </nav>

                                        {/* Platform & Community Links */}
                                        <div class="mt-4 border-t border-slate-100 pt-3 dark:border-slate-800/60">
                                            <p class="mb-2 px-4 text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500">
                                                Platform
                                            </p>
                                            <nav class="space-y-0.5 px-2.5">
                                                <Link
                                                    href="/about-us"
                                                    onClick={close}
                                                    class={[
                                                        'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                                                        isActive('/about-us')
                                                            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                            : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                                    ]}
                                                >
                                                    <MaterialIcon
                                                        name="groups"
                                                        size={22}
                                                        class={`shrink-0 transition-colors duration-150 ${
                                                            isActive(
                                                                '/about-us',
                                                            )
                                                                ? 'text-indigo-600 dark:text-indigo-300'
                                                                : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                        }`}
                                                    />
                                                    <span class="truncate">
                                                        About Us
                                                    </span>
                                                    {isActive('/about-us') && (
                                                        <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                                    )}
                                                </Link>
                                                <Link
                                                    href="/join"
                                                    onClick={close}
                                                    class={[
                                                        'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                                                        isActive('/join')
                                                            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                            : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                                    ]}
                                                >
                                                    <MaterialIcon
                                                        name="person_add"
                                                        size={22}
                                                        class={`shrink-0 transition-colors duration-150 ${
                                                            isActive('/join')
                                                                ? 'text-indigo-600 dark:text-indigo-300'
                                                                : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                        }`}
                                                    />
                                                    <span class="truncate">
                                                        Join Our Team
                                                    </span>
                                                    {isActive('/join') && (
                                                        <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                                    )}
                                                </Link>
                                                <Link
                                                    href="/projects"
                                                    onClick={close}
                                                    class={[
                                                        'group flex items-center gap-2.5 rounded-[10px] px-2.5 py-2 text-[13px] font-medium tracking-tight transition-all duration-150 ease-out',
                                                        isActive('/projects')
                                                            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                            : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                                    ]}
                                                >
                                                    <MaterialIcon
                                                        name="apps"
                                                        size={22}
                                                        class={`shrink-0 transition-colors duration-150 ${
                                                            isActive(
                                                                '/projects',
                                                            )
                                                                ? 'text-indigo-600 dark:text-indigo-300'
                                                                : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                        }`}
                                                    />
                                                    <span class="truncate">
                                                        More From Us
                                                    </span>
                                                    {isActive('/projects') && (
                                                        <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                                    )}
                                                </Link>
                                            </nav>
                                        </div>

                                        {/* Legal & Info Links */}
                                        <div class="mt-3 border-t border-slate-100 px-4 pt-3 dark:border-slate-800/60">
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-medium text-slate-400 dark:text-slate-500">
                                                <Link
                                                    href="/privacy-policy"
                                                    onClick={close}
                                                    class="transition hover:text-slate-700 dark:hover:text-slate-300"
                                                >
                                                    Privacy
                                                </Link>
                                                <span>•</span>
                                                <Link
                                                    href="/terms-service"
                                                    onClick={close}
                                                    class="transition hover:text-slate-700 dark:hover:text-slate-300"
                                                >
                                                    Terms
                                                </Link>
                                                <span>•</span>
                                                <Link
                                                    href="/content-policy"
                                                    onClick={close}
                                                    class="transition hover:text-slate-700 dark:hover:text-slate-300"
                                                >
                                                    Content Policy
                                                </Link>
                                            </div>
                                        </div>

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
                                    </div>

                                    {/* Pinned footer: appearance + account never scroll away */}
                                    <div class="shrink-0 space-y-2 border-t border-slate-200/60 bg-white/40 p-2 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/40 dark:backdrop-blur-sm">
                                        <div>
                                            <p class="mb-1.5 px-2 text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase dark:text-slate-500">
                                                Appearance
                                            </p>
                                            <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setTheme('light')
                                                    }
                                                    class={[
                                                        'flex items-center justify-center gap-1 rounded-lg py-1.5 text-xs font-semibold transition-all',
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
                                                    onClick={() =>
                                                        setTheme('dark')
                                                    }
                                                    class={[
                                                        'flex items-center justify-center gap-1 rounded-lg py-1.5 text-xs font-semibold transition-all',
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
                                                    onClick={() =>
                                                        setTheme('system')
                                                    }
                                                    class={[
                                                        'flex items-center justify-center gap-1 rounded-lg py-1.5 text-xs font-semibold transition-all',
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
            </>
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
        const showLogoutModal = ref(false);
        const notifRowRef = ref<HTMLElement | null>(null);

        const handleClearCache = () => {
            if (confirm('Are you sure you want to clear all cache?')) {
                router.post('/admin/clear-cache');
            }
        };

        return () => (
            <aside
                class={[
                    'sticky top-0 z-30 flex h-screen shrink-0 flex-col overflow-x-hidden bg-white/70 backdrop-blur-xl transition-[width] duration-300 ease-in-out dark:bg-slate-900/60 dark:backdrop-blur-xl',
                    'border-r border-slate-200/60 shadow-[1px_0_3px_rgba(0,0,0,0.02),4px_0_16px_rgba(0,0,0,0.03)] dark:border-slate-800/60 dark:shadow-none',
                    props.collapsed ? 'w-[72px]' : 'w-[280px]',
                ]}
                aria-label="Staff navigation"
            >
                {/* Fixed inner width to prevent text reflow & dance during collapse transition */}
                <div class="flex h-full w-[280px] flex-col">
                    {/* Header: Hamburger + Logo */}
                    <div class="flex h-16 shrink-0 items-center border-b border-slate-200/60 bg-white/70 backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-900/60 dark:backdrop-blur-xl">
                        <div class="flex w-[72px] shrink-0 items-center justify-center">
                            <button
                                type="button"
                                onClick={() => emit('toggle')}
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-slate-700 transition-colors hover:bg-slate-100 active:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-800"
                                aria-label={
                                    props.collapsed
                                        ? 'Expand sidebar'
                                        : 'Collapse sidebar'
                                }
                            >
                                <MaterialIcon name="menu" size={24} />
                            </button>
                        </div>
                        <div
                            class={[
                                'flex items-center overflow-hidden transition-all duration-300 ease-in-out',
                                props.collapsed
                                    ? 'pointer-events-none w-0 opacity-0'
                                    : 'w-[200px] opacity-100',
                            ]}
                        >
                            <div class="flex min-w-0 items-center gap-2">
                                <AppLogo />
                                <span class="shrink-0 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold tracking-wider text-indigo-600 uppercase dark:bg-indigo-500/10 dark:text-indigo-300">
                                    Staff
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Scrollable admin nav */}
                    <div class="flex flex-1 flex-col overflow-y-auto py-2">
                        {props.collapsed ? (
                            <nav class="flex w-[72px] flex-col items-center space-y-1 px-1">
                                {props.navigation.map((item) => {
                                    const active = isActiveAdminRoute(
                                        item.to,
                                        currentUrl.value,
                                    );
                                    const label = getCollapsedAdminLabel(
                                        item.name,
                                    );

                                    return (
                                        <Link
                                            key={item.to}
                                            href={item.to}
                                            class={[
                                                'group flex h-[60px] w-full flex-col items-center justify-center rounded-xl px-1 text-center transition-colors duration-150',
                                                active
                                                    ? 'bg-indigo-50/80 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200'
                                                    : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                            ]}
                                            title={item.name}
                                        >
                                            <MaterialIcon
                                                name={item.icon}
                                                size={22}
                                                class={`shrink-0 transition-colors duration-150 ${
                                                    active
                                                        ? 'text-indigo-600 dark:text-indigo-300'
                                                        : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                }`}
                                            />
                                            <span class="mt-1 max-w-[64px] truncate text-[10px] leading-tight font-medium">
                                                {label}
                                            </span>
                                        </Link>
                                    );
                                })}

                                {can('clear cache') && (
                                    <button
                                        type="button"
                                        onClick={handleClearCache}
                                        class="group flex h-[60px] w-full flex-col items-center justify-center rounded-xl px-1 text-center text-rose-600 transition-colors duration-150 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10"
                                        title="Clear cache"
                                    >
                                        <MaterialIcon
                                            name="cached"
                                            size={22}
                                            class="shrink-0"
                                        />
                                        <span class="mt-1 max-w-[64px] truncate text-[10px] leading-tight font-medium">
                                            Clear cache
                                        </span>
                                    </button>
                                )}
                            </nav>
                        ) : (
                            <nav class="w-[280px] space-y-1 px-2.5">
                                {props.navigation.map((item) => {
                                    const active = isActiveAdminRoute(
                                        item.to,
                                        currentUrl.value,
                                    );

                                    return (
                                        <Link
                                            key={item.to}
                                            href={item.to}
                                            class={[
                                                'group flex h-10 items-center gap-3 rounded-[10px] px-3 text-[13px] font-medium tracking-tight transition-colors duration-150',
                                                active
                                                    ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
                                                    : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
                                            ]}
                                        >
                                            <MaterialIcon
                                                name={item.icon}
                                                size={22}
                                                class={`shrink-0 transition-colors duration-150 ${
                                                    active
                                                        ? 'text-indigo-600 dark:text-indigo-300'
                                                        : 'text-slate-500 group-hover:text-slate-700 dark:text-slate-500 dark:group-hover:text-slate-300'
                                                }`}
                                            />
                                            <span class="truncate">
                                                {item.name}
                                            </span>
                                            {active && (
                                                <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                                            )}
                                        </Link>
                                    );
                                })}

                                {can('clear cache') && (
                                    <div class="pt-2">
                                        <button
                                            type="button"
                                            onClick={handleClearCache}
                                            class="flex h-10 w-full items-center gap-3 rounded-[10px] px-3 text-[13px] font-medium tracking-tight text-rose-600 transition-colors duration-150 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10"
                                            title="Clear cache"
                                        >
                                            <MaterialIcon
                                                name="cached"
                                                size={22}
                                                class="shrink-0"
                                            />
                                            <span class="truncate">
                                                Clear cache
                                            </span>
                                        </button>
                                    </div>
                                )}
                            </nav>
                        )}
                    </div>

                    {/* Footer controls */}
                    <div class="border-t border-slate-200/60 bg-white/40 p-2 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/40 dark:backdrop-blur-sm">
                        {props.collapsed ? (
                            <div class="flex w-[56px] flex-col items-center space-y-1.5">
                                {/* Notifications */}
                                {user.value && (
                                    <div
                                        ref={notifRowRef}
                                        class="flex h-10 w-full items-center justify-center"
                                    >
                                        <NotificationDropdown plain dropUp />
                                    </div>
                                )}

                                {/* User + logout */}
                                {user.value && (
                                    <div class="flex flex-col items-center py-1">
                                        <Link
                                            href={
                                                user.value.username
                                                    ? `/u/${user.value.username}`
                                                    : '/profile'
                                            }
                                            title="Profile"
                                            aria-label="Profile"
                                            class="flex items-center justify-center"
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
                                        </Link>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div class="w-[264px] space-y-1.5 overflow-hidden">
                                {/* Notifications */}
                                {user.value && (
                                    <div
                                        ref={notifRowRef}
                                        class="flex h-11 w-full items-center justify-start gap-2.5 px-3"
                                    >
                                        <NotificationDropdown plain dropUp />
                                        <span
                                            role="button"
                                            tabindex={0}
                                            onClick={(e: MouseEvent) =>
                                                openNotificationsFromLabel(
                                                    e,
                                                    notifRowRef,
                                                )
                                            }
                                            onKeydown={(e: KeyboardEvent) => {
                                                if (
                                                    e.key === 'Enter' ||
                                                    e.key === ' '
                                                ) {
                                                    openNotificationsFromLabel(
                                                        e,
                                                        notifRowRef,
                                                    );
                                                }
                                            }}
                                            class="flex-1 cursor-pointer truncate text-left text-[13px] font-semibold text-slate-600 transition-colors hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                                        >
                                            Notifications
                                        </span>
                                    </div>
                                )}

                                {/* User + logout */}
                                {user.value && (
                                    <div class="pt-1">
                                        <div class="flex items-center justify-between gap-3 rounded-xl border bg-white px-3 py-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                            <Link
                                                href={
                                                    user.value.username
                                                        ? `/u/${user.value.username}`
                                                        : '/profile'
                                                }
                                                class="flex min-w-0 items-center gap-2.5"
                                            >
                                                {user.value.image_url ? (
                                                    <img
                                                        src={
                                                            user.value.image_url
                                                        }
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
                                                <div class="min-w-0 text-left">
                                                    <p class="truncate text-xs font-semibold text-slate-900 dark:text-slate-100">
                                                        {user.value.name}
                                                    </p>
                                                    <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">
                                                        {user.value.email}
                                                    </p>
                                                </div>
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
                                                <MaterialIcon
                                                    name="logout"
                                                    size={20}
                                                />
                                            </button>
                                        </div>
                                    </div>
                                )}
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
        const panelRef = ref<HTMLElement | null>(null);
        const closeButtonRef = ref<HTMLElement | null>(null);

        const close = () => emit('close');

        useDialogA11y({
            open: toRef(props, 'isOpen'),
            panel: panelRef,
            initial: closeButtonRef,
            onEscape: close,
        });

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
                                <div
                                    ref={panelRef}
                                    role="dialog"
                                    aria-modal="true"
                                    aria-label="Staff menu"
                                    class="relative flex w-full max-w-[320px] flex-1 flex-col justify-between border-r border-slate-200/60 bg-white/85 shadow-[8px_0_32px_rgba(0,0,0,0.12)] backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-900/70 dark:backdrop-blur-xl"
                                >
                                    <div class="flex-1 overflow-y-auto py-3.5">
                                        <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200/60 px-4 dark:border-slate-800/60">
                                            <div class="flex min-w-0 items-center gap-2">
                                                <AppLogo />
                                                <span class="shrink-0 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold tracking-wider text-indigo-600 uppercase dark:bg-indigo-500/10 dark:text-indigo-300">
                                                    Staff
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <Link
                                                    href="/"
                                                    onClick={close}
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition-all duration-150 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                                                    aria-label="Home"
                                                    title="Home"
                                                >
                                                    <MaterialIcon
                                                        name="home"
                                                        size={18}
                                                    />
                                                </Link>
                                                <button
                                                    ref={closeButtonRef}
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
                                        </div>

                                        <nav class="mt-3 space-y-0.5 px-2.5">
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

                                    <div class="space-y-2 border-t border-slate-200/60 bg-white/40 p-2 backdrop-blur-sm dark:border-slate-800/60 dark:bg-slate-900/40 dark:backdrop-blur-sm">
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
                                        {user.value && (
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
            middleItems,
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
            <div class="space-y-5 pt-1">
                <div>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
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

                        {/* Middle draggable (resolved items keep icon/label/remove aligned) */}
                        {middleItems.value.map((item, idx) => (
                            <li
                                key={item.href}
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
                                    name={item.icon}
                                    size={22}
                                    class="shrink-0 text-slate-600 dark:text-gray-300"
                                />
                                <span class="flex-1 text-sm font-medium text-slate-800 dark:text-gray-200">
                                    {item.label}
                                </span>
                                <button
                                    type="button"
                                    onClick={() => reorder(idx, idx - 1)}
                                    disabled={idx === 0}
                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 disabled:opacity-30 dark:hover:bg-slate-700/60"
                                    aria-label={`Move ${item.label} earlier`}
                                >
                                    <MaterialIcon
                                        name="keyboard_arrow_up"
                                        size={16}
                                    />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => reorder(idx, idx + 1)}
                                    disabled={
                                        idx === middleItems.value.length - 1
                                    }
                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 disabled:opacity-30 dark:hover:bg-slate-700/60"
                                    aria-label={`Move ${item.label} later`}
                                >
                                    <MaterialIcon
                                        name="keyboard_arrow_down"
                                        size={16}
                                    />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleRemove(item.href)}
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
