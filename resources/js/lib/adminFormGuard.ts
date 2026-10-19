import { router } from '@inertiajs/vue3';

/**
 * Layout-level unsaved-changes guard for admin form pages.
 *
 * AdminLayout installs the Inertia listeners once; form pages only report
 * their dirty state via `markAdminFormDirty` (and call it again with `false`
 * on unmount so browser Back leaves no stale flag behind).
 *
 * - GET visits (links, pagination, sub-nav) are prompted while dirty; a
 *   declined confirm cancels the visit but keeps the flag. The flag is only
 *   cleared on a *successful* navigation (`navigate`) or unmount — so a
 *   failed visit never leaves editable unsaved data unprotected.
 * - Non-GET visits (form `post`/`patch` submits, row `delete`s) pass
 *   through: saving the form you just filled must not require consent to
 *   "discard" it.
 */
let isDirty = false;
let installed = false;

/** Replaces the shared dirty flag the admin navigation guard reads. */
export function markAdminFormDirty(value: boolean): void {
    isDirty = value;
}

/**
 * Installs the Inertia listeners once; repeated calls have no effect.
 * While dirty, GET visits (links, sub-nav, pagination) require confirmation;
 * declining cancels the visit and keeps the flag, so the form stays
 * protected. Non-GET visits (form posts/deletes) always pass through. The
 * flag clears on a successful navigation, never at confirm time. Direct
 * browser back/hard reloads are not guarded.
 */
export function installAdminFormGuard(): void {
    if (installed) {
        return;
    }

    installed = true;

    router.on('before', (visit) => {
        if (
            !isDirty ||
            !visit ||
            String(visit.method).toLowerCase() !== 'get'
        ) {
            return true;
        }

        return window.confirm(
            'You have unsaved changes. Leave this page without saving?',
        );
    });

    // Successful departure (including browser-back visits Inertia handles):
    // whatever page is mounted now is a fresh one.
    router.on('navigate', () => {
        isDirty = false;
    });
}
