/**
 * Shared admin UI primitives.
 *
 * Single source of truth for the admin dashboard's repeated chrome:
 * page shell, headers, buttons, icon buttons, segmented filter tabs,
 * dark-safe native selects, list rows and badges. Pages should compose
 * these instead of re-declaring the same 200-char class strings so a
 * visual fix (e.g. dark-mode contrast) lands everywhere at once.
 */
import { defineComponent } from 'vue';
import type { PropType } from 'vue';

/* ------------------------------------------------------------------ */
/* Page shell + header                                                 */
/* ------------------------------------------------------------------ */

/** Root container every admin page renders (spacing comes from here). */
export const adminPageClass = 'space-y-6';

/**
 * Standard page header row: title block left, actions right, hairline
 * below. Keeps header height/rhythm identical across pages so client
 * navigation doesn't shift layout.
 */
export const adminPageHeaderClass =
    'flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800';

export const adminPageTitleClass =
    'truncate text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100';

export const adminPageDescriptionClass =
    'mt-0.5 text-xs text-slate-500 dark:text-gray-400';

export const AdminPageHeader = defineComponent({
    name: 'AdminPageHeader',
    props: {
        title: { type: String, required: true },
        /** Number/count badge shown next to the title. */
        count: {
            type: [Number, String] as PropType<number | string>,
            default: undefined,
        },
        /** Small badge variant. */
        countTone: {
            type: String as PropType<'indigo' | 'amber' | 'rose'>,
            default: 'indigo',
        },
        description: { type: String, default: '' },
    },
    setup(props, { slots }) {
        const badgeClass =
            props.countTone === 'amber'
                ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'
                : props.countTone === 'rose'
                  ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400'
                  : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400';

        return () => (
            <div class={adminPageHeaderClass}>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class={adminPageTitleClass}>{props.title}</h1>
                        {props.count !== undefined && (
                            <span
                                class={[
                                    'inline-flex items-center rounded-md px-1.5 py-0.5 text-[11px] font-bold',
                                    badgeClass,
                                ]}
                            >
                                {props.count}
                            </span>
                        )}
                    </div>
                    {props.description && (
                        <p class={adminPageDescriptionClass}>
                            {props.description}
                        </p>
                    )}
                </div>
                {slots.actions && (
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        {slots.actions()}
                    </div>
                )}
            </div>
        );
    },
});

/* ------------------------------------------------------------------ */
/* Buttons                                                             */
/* ------------------------------------------------------------------ */

/** Primary action (indigo). */
export const adminPrimaryBtnClass =
    'inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600';

/** Secondary action (bordered). */
export const adminSecondaryBtnClass =
    'inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800';

/** Ghost icon-only action (neutral). */
export const adminIconBtnClass =
    'flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300';

/** Ghost icon-only action (danger). */
export const adminDangerIconBtnClass =
    'flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400';

/* ------------------------------------------------------------------ */
/* Small text buttons (row-level actions like Resolve / Dismiss)       */
/* ------------------------------------------------------------------ */

const adminTextBtnBase =
    'inline-flex cursor-pointer items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold transition focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95';

/** Small danger text button (Suspend, Clear all). */
export const adminDangerTextBtnClass = `${adminTextBtnBase} text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10`;

/** Small success text button (Resolve). */
export const adminSuccessTextBtnClass = `${adminTextBtnBase} text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10`;

/** Small muted text button (Dismiss). */
export const adminMutedTextBtnClass = `${adminTextBtnBase} text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200`;

/* ------------------------------------------------------------------ */
/* Sub-navigation (Discussions / Reports / Settings switcher)          */
/* ------------------------------------------------------------------ */

/** Horizontal sub-nav container used under forum/chat headers. */
export const adminSubNavClass =
    'flex max-w-full items-center gap-1 overflow-x-auto';

export function adminSubNavLinkClass(isActive: boolean): string[] {
    return [
        'flex shrink-0 cursor-pointer items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold transition focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95',
        isActive
            ? 'text-indigo-600 underline decoration-2 underline-offset-4 dark:text-indigo-400'
            : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
    ];
}

/** Count badge inside a sub-nav link. */
export const adminSubNavBadgeClass =
    'rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] leading-none font-bold text-white';

/* ------------------------------------------------------------------ */
/* Segmented filter tabs                                               */
/* ------------------------------------------------------------------ */

/** Pill container behind a segmented filter control. */
export const adminSegmentedClass =
    'inline-flex max-w-full flex-wrap items-center gap-0.5 self-start rounded-xl bg-slate-100 p-1 dark:bg-gray-800';

/**
 * One segmented tab. NOTE: the active state must contrast with the
 * container in both themes — `dark:bg-gray-800` on a `dark:bg-gray-800`
 * container is invisible, which is why this lives here instead of being
 * re-declared per page.
 */
export const adminSegmentedActiveClass =
    'bg-white text-slate-900 shadow-2xs ring-1 ring-slate-900/5 dark:bg-gray-700 dark:text-white dark:shadow-none dark:ring-0';

export const adminSegmentedIdleClass =
    'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200';

export function adminSegmentedTabClass(isActive: boolean): string[] {
    return [
        'cursor-pointer rounded-lg px-2.5 py-1 text-xs font-semibold transition-all focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95',
        isActive ? adminSegmentedActiveClass : adminSegmentedIdleClass,
    ];
}

export interface AdminTabOption {
    value: string;
    label: string;
    icon?: unknown;
    /** Small count pill rendered after the label. */
    badge?: string | number;
}

export const SegmentedTabs = defineComponent({
    name: 'SegmentedTabs',
    props: {
        tabs: { type: Array as PropType<AdminTabOption[]>, required: true },
        active: { type: String, required: true },
    },
    emits: ['select'],
    setup(props, { emit }) {
        return () => (
            <div class={adminSegmentedClass}>
                {props.tabs.map((tab) => {
                    const Icon = tab.icon as
                        | (new (...args: unknown[]) => unknown)
                        | undefined;
                    const isActive = props.active === tab.value;

                    return (
                        <button
                            key={tab.value}
                            type="button"
                            onClick={() => emit('select', tab.value)}
                            class={[
                                ...adminSegmentedTabClass(isActive),
                                Icon || tab.badge !== undefined
                                    ? 'inline-flex items-center gap-1.5'
                                    : '',
                            ]}
                        >
                            {Icon && (
                                <Icon class="h-3.5 w-3.5" aria-hidden="true" />
                            )}
                            <span>{tab.label}</span>
                            {tab.badge !== undefined && (
                                <span
                                    class={[
                                        'rounded-md px-1.5 py-0.5 text-[10px] font-bold',
                                        isActive
                                            ? 'bg-slate-100 text-slate-700 dark:bg-gray-600 dark:text-gray-200'
                                            : 'bg-slate-200/70 text-slate-600 dark:bg-gray-700 dark:text-gray-400',
                                    ]}
                                >
                                    {tab.badge}
                                </span>
                            )}
                        </button>
                    );
                })}
            </div>
        );
    },
});

/* ------------------------------------------------------------------ */
/* Native select (dark-safe)                                           */
/* ------------------------------------------------------------------ */

/**
 * Base classes for native `<select>` elements. `dark:[color-scheme:dark]`
 * is what makes the OS-rendered dropdown list dark; option elements get
 * explicit light/dark colors via `adminOptionClass` since browsers
 * otherwise inherit the page's (light) default for the popup.
 */
export const adminSelectClass =
    'h-8 cursor-pointer rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 transition outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 active:scale-95 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-400 dark:[color-scheme:dark]';

export const adminOptionClass =
    'bg-white text-slate-900 dark:bg-gray-900 dark:text-gray-100';

/**
 * Add to any other native `<select>` (e.g. full-width form selects that
 * keep their own input styling) so the OS-rendered dropdown list follows
 * the dark theme. Pair with `adminOptionClass` on each `<option>`.
 */
export const adminNativePopupClass = 'dark:[color-scheme:dark]';

export const AdminSelect = defineComponent({
    name: 'AdminSelect',
    inheritAttrs: false,
    props: {
        /** Extra classes merged after the base (e.g. status tinting). */
        toneClass: {
            type: [String, Array, Object] as PropType<
                string | string[] | Record<string, boolean>
            >,
            default: '',
        },
    },
    emits: ['change'],
    setup(props, { slots, attrs, emit }) {
        return () => {
            // `class` merges separately below so it lands after the base.
            const { class: callerClass, ...rest } = attrs;

            return (
                <select
                    {...rest}
                    onChange={(e: Event) => emit('change', e)}
                    class={[adminSelectClass, props.toneClass, callerClass]}
                >
                    {slots.default?.()}
                </select>
            );
        };
    },
});

/* ------------------------------------------------------------------ */
/* Lists                                                               */
/* ------------------------------------------------------------------ */

/** Hairline-separated vertical list container (static rows, no hover). */
export const adminListClass = 'divide-y divide-slate-100 dark:divide-gray-800';

/**
 * Spaced list container for rows with a hover fill. Never combine
 * `divide-y` hairlines with rounded hover pills — the straight divider
 * cutting across rounded corners is what produces the broken outline.
 */
export const adminHoverListClass = 'flex flex-col gap-1';

/**
 * Standard list row: horizontal padding + rounded corners so hover
 * states don't bleed to the container edge (flush rows look broken).
 */
export const adminRowClass =
    'flex items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 dark:hover:bg-gray-800/50';

/* ------------------------------------------------------------------ */
/* Misc                                                                */
/* ------------------------------------------------------------------ */

/** Search input with leading icon slot space (pl-8). */
export const adminSearchInputClass =
    'h-9 w-full rounded-xl border border-slate-200 bg-white pr-3 pl-8 text-xs text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400';

/** Table head shared by admin data tables. */
export const adminTableHeadClass =
    'border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-500 uppercase dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400';

/**
 * Scroll container boxing an admin data table: hairline border +
 * rounded corners so the table reads as one unit instead of loose rows.
 * Corner rounding on the edge cells (see th/td notes in forums/Index)
 * keeps the header/row backgrounds inside the border radius.
 */
export const adminTableWrapClass =
    'overflow-x-auto rounded-xl border border-slate-100 dark:border-gray-800';
