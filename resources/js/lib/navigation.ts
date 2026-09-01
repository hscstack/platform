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
        href: '/',
        icon: 'home',
        match: (url) =>
            url === '/' ||
            url.startsWith('/?') ||
            url === '/ssc' ||
            url.startsWith('/ssc?'),
        showInBottom: true,
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
        showInBottom: true,
    },
];

export const overflowNavItems: NavItem[] = [
    {
        label: 'Blogs',
        href: '/blogs',
        icon: 'menu_book',
        match: (url) => url.startsWith('/blogs'),
    },
    {
        label: 'Support',
        href: '/support',
        icon: 'help',
        match: (url) => url.startsWith('/support'),
    },
    {
        label: 'About',
        href: '/about-us',
        icon: 'info',
        match: (url) => url.startsWith('/about-us'),
    },
    {
        label: 'Join',
        labelBn: 'Join Team',
        href: '/join',
        icon: 'group_add',
        match: (url) => url.startsWith('/join'),
    },
    {
        label: 'Donate',
        href: '/donate',
        icon: 'volunteer_activism',
        match: (url) => url.startsWith('/donate'),
    },
];

export const allNavItems: NavItem[] = [...primaryNavItems, ...overflowNavItems];
