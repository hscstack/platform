import {
    BookOpen,
    HeartHandshake,
    Home,
    Info,
    LifeBuoy,
    MessageCircle,
    MessageSquare,
    Sparkles,
    Users,
} from 'lucide-vue-next';
import type { Component } from 'vue';

export type NavItem = {
    label: string;
    labelBn?: string;
    href: string;
    icon: Component;
    match: (url: string) => boolean;
    showInBottom?: boolean;
};

export const primaryNavItems: NavItem[] = [
    {
        label: 'Home',
        href: '/',
        icon: Home,
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
        icon: MessageSquare,
        match: (url) => url.startsWith('/forum'),
        showInBottom: true,
    },
    {
        label: 'Chat',
        labelBn: 'Global Chat',
        href: '/chat',
        icon: MessageCircle,
        match: (url) => url.startsWith('/chat'),
        showInBottom: true,
    },
    {
        label: 'AI',
        labelBn: 'HSCStack AI',
        href: '/ai',
        icon: Sparkles,
        match: (url) => url.startsWith('/ai'),
        showInBottom: true,
    },
];

export const overflowNavItems: NavItem[] = [
    {
        label: 'Blogs',
        href: '/blogs',
        icon: BookOpen,
        match: (url) => url.startsWith('/blogs'),
    },
    {
        label: 'Support',
        href: '/support',
        icon: LifeBuoy,
        match: (url) => url.startsWith('/support'),
    },
    {
        label: 'About',
        href: '/about-us',
        icon: Info,
        match: (url) => url.startsWith('/about-us'),
    },
    {
        label: 'Join',
        labelBn: 'Join Team',
        href: '/join',
        icon: Users,
        match: (url) => url.startsWith('/join'),
    },
    {
        label: 'Donate',
        href: '/donate',
        icon: HeartHandshake,
        match: (url) => url.startsWith('/donate'),
    },
];

export const allNavItems: NavItem[] = [...primaryNavItems, ...overflowNavItems];
