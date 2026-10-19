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

/** Root container every admin page renders. One content width, one
 * vertical rhythm: every page centers at max-w-7xl and stacks its
 * sections with the same space-y-6 gap. */
export const adminPageClass =
    'mx-auto flex w-full max-w-7xl flex-col space-y-6';

/**
 * Shared glass surface tokens, taken from the site's unified SideRail:
 * translucent white over a blur, hairline rings instead of opaque
 * borders, and content that floats on the atmospheric background.
 * Every admin surface composes these instead of re-inventing them.
 */
export const adminSurfaceClass =
    'rounded-2xl bg-white/70 ring-1 ring-slate-200/60 backdrop-blur-xl dark:bg-slate-900/60 dark:ring-slate-800/60';

export const adminSurfaceHoverClass =
    'hover:bg-white/85 dark:hover:bg-slate-900/75';

export const adminHairlineClass =
    'border-slate-200/60 dark:border-slate-800/60';

/**
 * Standard page header row: title block left, actions right, hairline
 * below. Keeps header height/rhythm identical across pages so client
 * navigation doesn't shift layout.
 */
export const adminPageHeaderClass =
    'flex flex-col gap-3 border-b border-slate-200/60 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800/60';

export const adminPageTitleClass =
    'truncate text-base font-bold tracking-tight text-slate-900 sm:text-lg dark:text-gray-100';

export const adminPageDescriptionClass =
    'mt-0.5 text-xs text-slate-500 dark:text-gray-400';

/**
 * Uppercase section kicker (h2 "Recipients", "Access", ...). Every page
 * uses this token so label size/color never drift between pages.
 */
export const adminSectionTitleClass =
    'text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400';

/**
 * The one glass panel for form-style pages (EmailSend, NoticeEdit,
 * settings, user form): a page renders its form inside a single panel;
 * interior grouping uses hairlines + whitespace, never nested cards.
 */
export const adminPanelClass =
    'rounded-2xl bg-white/70 p-5 ring-1 ring-slate-200/60 backdrop-blur-xl sm:p-7 dark:bg-slate-900/60 dark:ring-slate-800/60';

/**
 * Single-line form field (text/select). Solid background on purpose —
 * fields sit on a glass panel and stay readable. One radius, height,
 * text size and focus treatment for every admin form.
 */
export const adminInputClass =
    'h-9 w-full rounded-[10px] border border-slate-200 bg-white px-3 text-xs text-slate-900 transition-colors outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 disabled:bg-slate-50 disabled:text-slate-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:disabled:bg-gray-800 dark:disabled:text-gray-400 dark:[color-scheme:dark]';

/**
 * Multi-line form field (textarea). Same vocabulary as inputs, but
 * natural height with `py-2` and relaxed leading for long content.
 * Width/extra classes are appended by callers (`w-16`, `pl-8`, ...);
 * don't strip the base classes — strip the string and re-declare with
 * the same colors instead.
 */
export const adminTextareaClass =
    'w-full rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-xs leading-relaxed text-slate-900 transition-colors outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 disabled:bg-slate-50 disabled:text-slate-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:disabled:bg-gray-800 dark:disabled:text-gray-400 dark:[color-scheme:dark]';

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
                ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 ring-inset dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30'
                : props.countTone === 'rose'
                  ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 ring-inset dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/30'
                  : 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20 ring-inset dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/30';

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

/**
 * Buttons match the site language: solid indigo CTAs, glass secondary
 * chips (`rounded-[10px]`, translucent, hairline ring) like the nav.
 */

/** Primary action (solid indigo chip). */
export const adminPrimaryBtnClass =
    'inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-[10px] bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600';

/** Secondary action (glass chip). */
export const adminSecondaryBtnClass =
    'inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-[10px] bg-white/70 px-2.5 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/60 backdrop-blur-xl transition-colors hover:bg-white/90 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:bg-slate-900/60 dark:text-gray-300 dark:ring-slate-800/60 dark:hover:bg-slate-900/85 dark:hover:text-gray-100';

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

/**
 * Horizontal sub-nav container used under forum/chat headers. Glass
 * chips using the rail's indigo-wash active state.
 */
export const adminSubNavClass =
    'no-scrollbar flex max-w-full items-center gap-1.5 overflow-x-auto';

/** Returns base sub-navigation link classes plus active or idle styling. */
export function adminSubNavLinkClass(isActive: boolean): string[] {
    return [
        'flex shrink-0 cursor-pointer items-center gap-1.5 rounded-[10px] px-3 py-1.5 text-xs font-semibold tracking-tight transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95',
        isActive
            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20'
            : 'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100',
    ];
}

/**
 * Count badge inside a sub-nav chip or pill surface.
 */
export const adminSubNavBadgeClass =
    'rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] leading-none font-bold text-white shadow-xs';

/* ------------------------------------------------------------------ */
/* Segmented filter tabs                                               */
/* ------------------------------------------------------------------ */

/**
 * Chip container behind a segmented filter control. Plain flex row —
 * glass chips don't need a recessed track.
 */
export const adminSegmentedClass =
    'flex max-w-full flex-wrap items-center gap-1.5';

/**
 * One segmented tab (glass chip). Active = the SideRail's indigo wash
 * (`bg-indigo-50` + hairline indigo ring); idle = translucent hover,
 * identical to the rail's expanded nav items.
 */
export const adminSegmentedActiveClass =
    'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-500/20';

export const adminSegmentedIdleClass =
    'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-slate-100';

/** Returns base segmented-tab classes plus active or idle styling. */
export function adminSegmentedTabClass(isActive: boolean): string[] {
    return [
        'inline-flex cursor-pointer items-center gap-1.5 rounded-[10px] px-3 py-1.5 text-xs font-semibold tracking-tight transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95',
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
                            aria-pressed={isActive}
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
                                            ? 'bg-indigo-100/80 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200'
                                            : 'bg-slate-200/70 text-slate-600 dark:bg-gray-800 dark:text-gray-400',
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
 * Base classes for native `<select>` elements — glass chips matching
 * the rail's translucent surfaces. `dark:[color-scheme:dark]`
 * is what makes the OS-rendered dropdown list dark; option elements get
 * explicit light/dark colors via `adminOptionClass` since browsers
 * otherwise inherit the page's (light) default for the popup.
 */
export const adminSelectClass =
    'h-8 cursor-pointer rounded-[10px] bg-white/70 px-2.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/60 backdrop-blur-xl transition-colors outline-none hover:bg-white/90 focus:ring-2 focus:ring-indigo-500/30 active:scale-95 dark:bg-slate-900/60 dark:text-gray-300 dark:ring-slate-800/60 dark:hover:bg-slate-900/85 dark:[color-scheme:dark]';

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
export const adminListClass =
    'divide-y divide-slate-200/60 dark:divide-slate-800/60';

/**
 * Spaced list container for rows with a hover state. Rows are floating
 * glass cards, spaced like the node browser: gap-2.5 on mobile,
 * gap-3 on sm.
 */
export const adminHoverListClass = 'flex flex-col gap-2.5 sm:gap-3';

/**
 * Glass list card — the standard row treatment inside glass lists:
 * translucent fill, hairline ring, and on hover the surface brightens
 * with a tinted ring (indigo), mirroring the SideRail's active/hover.
 * `tone` selects the hover ring color so amber-toned surfaces (e.g.
 * resource moderation rows) keep their semantic signal.
 */
/**
 * Glass list card — the standard row treatment inside glass lists:
 * translucent fill, hairline ring, and on hover the surface brightens
 * with a tinted ring (indigo), mirroring the SideRail's active/hover.
 * `tone` selects the hover ring color so amber-toned surfaces (e.g.
 * resource moderation rows) keep their semantic signal. Alignment is
 * left to callers so top-aligned rows can override `items-center`
 * without two conflicting alignment classes in one list.
 */
export function adminCardRowClass(
    tone: 'indigo' | 'amber' = 'indigo',
): string[] {
    return [
        // No `cursor-pointer` here: callers are Links/buttons that supply
        // their own cursor; non-interactive rows (e.g. reports) shouldn't
        // advertise clickability.
        'group flex gap-3 rounded-xl bg-white/70 p-3 ring-1 ring-slate-200/60 backdrop-blur-xl transition-all duration-150 hover:bg-white/85 sm:p-3.5 dark:bg-slate-900/60 dark:ring-slate-800/60 dark:hover:bg-slate-900/75',
        tone === 'amber'
            ? 'hover:ring-amber-300/70 dark:hover:ring-amber-500/40'
            : 'hover:ring-indigo-300/70 dark:hover:ring-indigo-500/40',
    ];
}

/** Default indigo tone kept as a constant for one-off rows. */
export const adminRowClass = [
    ...adminCardRowClass('indigo'),
    'items-center',
].join(' ');

/* ------------------------------------------------------------------ */
/* Misc                                                                */
/* ------------------------------------------------------------------ */

/** Search input with leading icon slot space (pl-8) — glass field. */
export const adminSearchInputClass =
    'h-9 w-full rounded-[10px] bg-white/70 pr-3 pl-8 text-xs text-slate-900 ring-1 ring-slate-200/60 backdrop-blur-xl transition outline-none placeholder:text-slate-400 hover:bg-white/90 focus:ring-2 focus:ring-indigo-500/30 dark:bg-slate-900/60 dark:text-gray-100 dark:ring-slate-800/60 dark:placeholder:text-gray-500 dark:hover:bg-slate-900/85 dark:[color-scheme:dark]';

/** Table head shared by admin data tables — faint glass tint strip. */
export const adminTableHeadClass =
    'border-b border-slate-200/60 bg-white/50 text-[11px] font-bold text-slate-500 uppercase dark:border-slate-800/60 dark:bg-white/[0.03] dark:text-gray-400';

/**
 * Glass box wrapping an admin data table: translucent surface + hairline
 * ring so the table reads as one floating unit. Corner rounding on the
 * edge cells keeps the header/row tint inside the radius.
 */
export const adminTableWrapClass = `${adminSurfaceClass} overflow-x-auto`;
