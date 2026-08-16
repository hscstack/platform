import type { Auth } from '@/types/auth';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}

declare module 'konsta/vue' {
    import type { DefineComponent } from 'vue';

    export const kApp: DefineComponent<
        {
            theme?: 'ios' | 'material';
            dark?: boolean;
            touchRipple?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kPage: DefineComponent<
        Record<string, unknown>,
        Record<string, unknown>,
        unknown
    >;
    export const kNavbar: DefineComponent<
        {
            title?: string;
            subtitle?: string;
            backLink?: string | boolean;
            transparent?: boolean;
            blur?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kBlock: DefineComponent<
        {
            strong?: boolean;
            inset?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kButton: DefineComponent<
        {
            text?: string;
            icon?: string;
            iconOnly?: boolean;
            fill?: boolean;
            outline?: boolean;
            rounded?: boolean;
            small?: boolean;
            large?: boolean;
            raised?: boolean;
            disabled?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kCard: DefineComponent<
        {
            header?: string;
            footer?: string;
            outline?: boolean;
            expandable?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kList: DefineComponent<
        {
            strong?: boolean;
            inset?: boolean;
            dividers?: boolean;
            outline?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kListItem: DefineComponent<
        {
            title?: string;
            subtitle?: string;
            text?: string;
            media?: string;
            after?: string;
            link?: boolean | string;
            header?: string;
            footer?: string;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kDialog: DefineComponent<
        {
            opened?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kToast: DefineComponent<
        {
            opened?: boolean;
            position?: string;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kInput: DefineComponent<
        {
            type?: string;
            value?: string | number;
            placeholder?: string;
            error?: string;
            info?: string;
            label?: string;
            outline?: boolean;
            disabled?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kSelect: DefineComponent<
        {
            value?: string | number;
            placeholder?: string;
            label?: string;
            outline?: boolean;
            disabled?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kTextarea: DefineComponent<
        {
            value?: string;
            placeholder?: string;
            label?: string;
            outline?: boolean;
            disabled?: boolean;
            resizable?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kToggle: DefineComponent<
        {
            checked?: boolean;
            disabled?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kCheckbox: DefineComponent<
        {
            checked?: boolean;
            name?: string;
            value?: string | number;
            disabled?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kBadge: DefineComponent<
        {
            color?: string;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kChip: DefineComponent<
        {
            text?: string;
            delete?: boolean;
            outline?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kPreloader: DefineComponent<
        {
            size?: string;
            color?: string;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kLink: DefineComponent<
        {
            text?: string;
            icon?: string;
            iconOnly?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kPanel: DefineComponent<
        {
            opened?: boolean;
            side?: 'left' | 'right';
            effect?: string;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kPopover: DefineComponent<
        {
            opened?: boolean;
            target?: string;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kSearchbar: DefineComponent<
        {
            value?: string;
            placeholder?: string;
            disableButton?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kBreadcrumbs: DefineComponent<
        Record<string, unknown>,
        Record<string, unknown>,
        unknown
    >;
    export const kBreadcrumbsItem: DefineComponent<
        {
            active?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kBlockTitle: DefineComponent<
        {
            medium?: boolean;
            large?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kSegmented: DefineComponent<
        {
            strong?: boolean;
            outline?: boolean;
            round?: boolean;
        },
        Record<string, unknown>,
        unknown
    >;
    export const kTabbar: DefineComponent<
        {
            labels?: boolean;
            outline?: boolean;
            fill?: boolean;
            translucent?: boolean;
            position?: 'top' | 'bottom';
        },
        Record<string, unknown>,
        unknown
    >;
    export const kTabbarLink: DefineComponent<
        {
            text?: string;
            icon?: string;
            active?: boolean;
            badge?: string | number;
        },
        Record<string, unknown>,
        unknown
    >;

    export function useTheme(): { value: 'ios' | 'material' };
}
