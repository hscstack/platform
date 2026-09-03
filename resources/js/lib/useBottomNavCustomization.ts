import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import { allNavItems } from '@/lib/navigation';
import type { NavItem } from '@/lib/navigation';

const STORAGE_KEY = 'hscstack:bottom-nav:v1';
const MIN_TOTAL = 3;
const MAX_TOTAL = 5;

// Home is always first, Account is always last
const HOME_HREF = '/';
const CUSTOMIZABLE_POOL: NavItem[] = allNavItems.filter(
    (i) => i.href !== HOME_HREF,
);

const DEFAULT_MIDDLE_HREFS = ['/forum', '/chat', '/ai']; // 3 middle → 5 total with Home+Account

type BottomNavUser = {
    name: string;
    email: string;
    username?: string | null;
    image_url?: string | null;
};

function isValidHref(href: string): boolean {
    return CUSTOMIZABLE_POOL.some((i) => i.href === href);
}

function loadStored(): string[] | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as unknown;

        if (!Array.isArray(parsed)) {
            return null;
        }

        const filtered = (parsed as string[]).filter(isValidHref);
        // Dedupe
        const deduped = [...new Set(filtered)];

        if (deduped.length < MIN_TOTAL - 2 || deduped.length > MAX_TOTAL - 2) {
            return null;
        }

        return deduped;
    } catch {
        return null;
    }
}

function persist(hrefs: string[]) {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(hrefs));
    } catch {}
}

// Client-side module-level singleton — shared across all consumers
const sharedMiddleHrefs = ref<string[]>(
    loadStored() ?? [...DEFAULT_MIDDLE_HREFS],
);
let hasPersistWatcher = false;

function ensurePersistWatcher() {
    if (hasPersistWatcher || typeof window === 'undefined') {
        return;
    }

    hasPersistWatcher = true;
    watch(sharedMiddleHrefs, (v) => persist(v), { deep: true });
}

export function useBottomNavCustomization() {
    const page = usePage();
    const user = computed(
        () => page.props.auth?.user as BottomNavUser | undefined,
    );

    ensurePersistWatcher();
    const middleHrefs = sharedMiddleHrefs;

    const homeItem = computed<NavItem>(
        () => allNavItems.find((i) => i.href === HOME_HREF)!,
    );

    const accountItem = computed<NavItem>(() => {
        if (user.value) {
            const href = user.value.username
                ? `/u/${user.value.username}`
                : '/profile';

            return {
                label: 'Account',
                href,
                icon: 'person',
                match: (url: string) =>
                    url.startsWith('/u/') || url.startsWith('/profile'),
            };
        }

        return {
            label: 'Login',
            href: '/login',
            icon: 'login',
            match: (url: string) => url.startsWith('/login'),
        };
    });

    const middleItems = computed<NavItem[]>(() => {
        return middleHrefs.value
            .map((href) => CUSTOMIZABLE_POOL.find((i) => i.href === href))
            .filter((i): i is NavItem => Boolean(i));
    });

    const bottomNavItems = computed<NavItem[]>(() => {
        return [homeItem.value, ...middleItems.value, accountItem.value];
    });

    const availableItems = computed<NavItem[]>(() => {
        const used = new Set(middleHrefs.value);

        return CUSTOMIZABLE_POOL.filter((i) => !used.has(i.href));
    });

    const canAdd = computed(() => bottomNavItems.value.length < MAX_TOTAL);
    const canRemove = computed(() => bottomNavItems.value.length > MIN_TOTAL);

    const addItem = (href: string) => {
        if (!canAdd.value) {
            return;
        }

        if (!isValidHref(href)) {
            return;
        }

        if (middleHrefs.value.includes(href)) {
            return;
        }

        middleHrefs.value = [...middleHrefs.value, href];
    };

    const removeItem = (href: string) => {
        if (!canRemove.value) {
            return;
        }

        if (homeItem.value.href === href) {
            return;
        }

        if (accountItem.value.href === href) {
            return;
        }

        middleHrefs.value = middleHrefs.value.filter((h) => h !== href);
    };

    const reorder = (fromIndex: number, toIndex: number) => {
        // from/to are indices within middleHrefs (0 .. middle-1)
        if (fromIndex < 0 || toIndex < 0) {
            return;
        }

        if (
            fromIndex >= middleHrefs.value.length ||
            toIndex >= middleHrefs.value.length
        ) {
            return;
        }

        const copy = [...middleHrefs.value];
        const [moved] = copy.splice(fromIndex, 1);
        copy.splice(toIndex, 0, moved);
        middleHrefs.value = copy;
    };

    const reset = () => {
        middleHrefs.value = [...DEFAULT_MIDDLE_HREFS];
    };

    return {
        // constants
        MIN_TOTAL,
        MAX_TOTAL,
        // state
        middleHrefs,
        middleItems,
        bottomNavItems,
        availableItems,
        homeItem,
        accountItem,
        canAdd,
        canRemove,
        // actions
        addItem,
        removeItem,
        reorder,
        reset,
        // for customizer UI
        customizablePool: CUSTOMIZABLE_POOL,
    };
}
