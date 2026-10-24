export type NavItem = {
    label: string;
    labelBn?: string;
    href: string;
    /** Material Symbols Rounded name — rendered via MaterialIcon (filled, wght 300) */
    icon: string;
    match: (url: string) => boolean;
    showInBottom?: boolean;
};

export const primaryNavItems: NavItem[] = [
    {
        label: 'Home',
        labelBn: 'হোম',
        href: '/',
        icon: 'home',
        match: (url) => url === '/' || url.startsWith('/feed'),
        showInBottom: true,
    },
    {
        label: 'Notes',
        labelBn: 'নোট আর্কাইভ',
        href: '/notes',
        icon: 'auto_stories',
        match: (url) =>
            url.startsWith('/notes') || url.startsWith('/resources'),
        showInBottom: true,
    },
    {
        label: 'Tracker',
        labelBn: 'স্টাডি ট্র্যাকার',
        href: '/tracker',
        icon: 'timer',
        match: (url) => url.startsWith('/tracker'),
        showInBottom: true,
    },
    {
        label: 'Leaderboard',
        labelBn: 'লিডারবোর্ড',
        href: '/leaderboard',
        icon: 'leaderboard',
        match: (url) => url.startsWith('/leaderboard'),
        showInBottom: true,
    },
    {
        label: 'Blogs',
        href: '/blogs',
        icon: 'menu_book',
        match: (url) => url.startsWith('/blogs'),
        showInBottom: false,
    },
    {
        label: 'Forum',
        href: '/forum',
        icon: 'forum',
        match: (url) => url.startsWith('/forum'),
        showInBottom: true,
    },
    {
        label: 'Chat',
        labelBn: 'Global Chat',
        href: '/chat',
        icon: 'chat',
        match: (url) => url.startsWith('/chat'),
        showInBottom: true,
    },
    {
        label: 'AI',
        labelBn: 'HSCStack AI',
        href: '/ai',
        icon: 'smart_toy',
        match: (url) => url.startsWith('/ai'),
        showInBottom: false,
    },
];

export const overflowNavItems: NavItem[] = [
    {
        label: 'Support Center',
        href: '/support',
        icon: 'help',
        match: (url) => url.startsWith('/support'),
    },
    {
        label: 'Donate',
        href: '/donate',
        icon: 'volunteer_activism',
        match: (url) => url.startsWith('/donate'),
    },
];

export const allNavItems: NavItem[] = [...primaryNavItems, ...overflowNavItems];
