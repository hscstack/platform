/**
 * AdminPeerSettings — TSX port of the former `PeerSettings.vue`.
 *
 * Same UI/behavior: Inertia `useForm` for the poke feature toggle, cooldown
 * and message presets (add/remove/reset). Resolved via the explicit
 * dual-extension (`*.vue` + `*.tsx`) page resolver in `resources/js/app.ts`.
 */
import { Head, useForm } from '@inertiajs/vue3';
import { Clock, Loader2, Plus, RotateCcw, Save, Trash2 } from 'lucide-vue-next';
import { defineComponent, onUnmounted, watch } from 'vue';
import type { PropType } from 'vue';

import {
    AdminPageHeader,
    adminDangerIconBtnClass,
    adminInputClass,
    adminPageClass,
    adminPanelClass,
    adminPrimaryBtnClass,
    adminSectionTitleClass,
    adminSecondaryBtnClass,
} from '@/components/admin/ui';
import { markAdminFormDirty } from '@/lib/adminFormGuard';

interface PokePreset {
    id: string;
    icon: string;
    message: string;
}

interface PeerSettingsProps {
    settings: {
        enabled: boolean;
        cooldown_minutes: number;
        presets: PokePreset[];
    };
}

const defaultPresets: PokePreset[] = [
    {
        id: 'syllabus',
        icon: '🔥',
        message: "That syllabus isn't going to finish itself! Open the books!",
    },
    {
        id: 'phone_down',
        icon: '📱',
        message: 'Close the tabs, put the phone on DND, and start studying.',
    },
];

export default defineComponent({
    name: 'AdminPeerSettings',
    props: {
        settings: {
            type: Object as PropType<PeerSettingsProps['settings']>,
            required: true,
        },
    },
    setup(props) {
        const form = useForm({
            enabled: props.settings.enabled,
            cooldown_minutes: props.settings.cooldown_minutes,
            presets: JSON.parse(
                JSON.stringify(props.settings.presets || defaultPresets),
            ) as PokePreset[],
        });

        watch(
            () => form.isDirty,
            (v) => markAdminFormDirty(v),
        );

        onUnmounted(() => markAdminFormDirty(false));

        const addPreset = () => {
            form.presets.push({
                id: 'preset_' + Date.now(),
                icon: '⚡',
                message: 'Time to open the books and make today count!',
            });
        };

        /** Removes the preset at a zero-based index, alerting if only one remains. */
        const removePreset = (index: number) => {
            if (form.presets.length <= 1) {
                alert('You must have at least one poke preset.');

                return;
            }

            form.presets.splice(index, 1);
        };

        const resetToDefaultPresets = () => {
            if (confirm('Reset all presets to default recommendations?')) {
                form.presets = JSON.parse(JSON.stringify(defaultPresets));
            }
        };

        const submit = () => {
            form.post('/admin/peers/settings', {
                preserveScroll: true,
            });
        };

        // Numeric field with the shared field vocabulary, fixed width + centered.
        const cooldownInputClass =
            'h-9 w-24 rounded-[10px] border border-slate-200 bg-white px-2.5 text-center text-xs font-semibold text-slate-900 transition-colors outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-400';

        // Emoji picker cell: same vocabulary, larger glyph.
        const emojiInputClass =
            'h-9 w-14 rounded-[10px] border border-slate-200 bg-white px-1 text-center text-base text-slate-900 transition-colors outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-400';

        const toggleWrapClass =
            'relative inline-flex shrink-0 cursor-pointer items-center';
        const toggleTrackClass =
            "peer relative h-6 w-11 rounded-full bg-slate-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-indigo-500 dark:bg-gray-700 dark:peer-checked:bg-indigo-500";

        return () => (
            <>
                <Head title="Peer &amp; Poke Settings - Admin" />

                <div class={adminPageClass}>
                    <AdminPageHeader
                        title="Peer & Poke Settings"
                        description="Configure peer pokes, anti-spam cooldowns, and message presets."
                    >
                        {{
                            actions: () => (
                                <button
                                    type="button"
                                    onClick={submit}
                                    disabled={form.processing}
                                    class={adminPrimaryBtnClass}
                                >
                                    {form.processing ? (
                                        <Loader2 class="h-3.5 w-3.5 animate-spin" />
                                    ) : (
                                        <Save class="h-3.5 w-3.5" />
                                    )}
                                    <span>
                                        {form.processing
                                            ? 'Saving...'
                                            : 'Save Settings'}
                                    </span>
                                </button>
                            ),
                        }}
                    </AdminPageHeader>

                    {/* Single glass panel: sections separated by whitespace,
                        not cards. */}
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            submit();
                        }}
                        class={[adminPanelClass, 'mx-auto max-w-4xl space-y-8']}
                    >
                        {/* General Controls */}
                        <section class="space-y-5">
                            <div>
                                <h2 class={adminSectionTitleClass}>
                                    General Controls &amp; Anti-Spam
                                </h2>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                    Enable or pause peer pokes globally and set
                                    cooling-off limits.
                                </p>
                            </div>

                            <div class="grid gap-6 sm:grid-cols-2">
                                {/* Poke feature toggle (plain row, no box) */}
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <span class="text-xs font-semibold text-slate-900 dark:text-gray-100">
                                            Poke Feature
                                        </span>
                                        <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                            Allow users to send pokes to peers.
                                        </p>
                                    </div>
                                    <label class={toggleWrapClass}>
                                        <input
                                            type="checkbox"
                                            v-model={form.enabled}
                                            aria-label="Toggle Poke feature"
                                            class="peer sr-only"
                                        />
                                        <div class={toggleTrackClass}></div>
                                    </label>
                                </div>

                                {/* Cooldown */}
                                <div class="flex items-start justify-between gap-4 sm:justify-end">
                                    <div>
                                        <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-900 dark:text-gray-100">
                                            <Clock class="h-3.5 w-3.5 text-slate-500 dark:text-gray-400" />
                                            Cooldown Duration
                                        </span>
                                        <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">
                                            Wait time before poking the same
                                            peer again.
                                        </p>
                                    </div>
                                    <input
                                        type="number"
                                        v-model={form.cooldown_minutes}
                                        min="1"
                                        max="10080"
                                        required
                                        aria-label="Cooldown duration in minutes"
                                        class={cooldownInputClass}
                                    />
                                </div>
                            </div>
                        </section>

                        {/* Message Presets */}
                        <section class="space-y-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 class={adminSectionTitleClass}>
                                        Message Presets
                                    </h2>
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                        Shown when users click the "Poke"
                                        button.
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={resetToDefaultPresets}
                                        class={adminSecondaryBtnClass}
                                    >
                                        <RotateCcw class="h-3.5 w-3.5" />
                                        <span>Reset Defaults</span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={addPreset}
                                        class={adminPrimaryBtnClass}
                                    >
                                        <Plus class="h-3.5 w-3.5" />
                                        <span>Add Preset</span>
                                    </button>
                                </div>
                            </div>

                            {/* Preset rows: hairline dividers, no boxes */}
                            <div class="divide-y divide-slate-200/60 dark:divide-slate-800/60">
                                {form.presets.map((preset, idx) => (
                                    <div
                                        key={preset.id || idx}
                                        class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center"
                                    >
                                        <div class="w-16 shrink-0">
                                            <input
                                                type="text"
                                                v-model={preset.icon}
                                                maxlength="6"
                                                required
                                                aria-label="Preset emoji"
                                                class={emojiInputClass}
                                            />
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <input
                                                type="text"
                                                v-model={preset.message}
                                                placeholder="e.g. Time to open the books and make today count!"
                                                required
                                                aria-label="Preset message"
                                                class={adminInputClass}
                                            />
                                        </div>

                                        <button
                                            type="button"
                                            onClick={() => removePreset(idx)}
                                            disabled={form.presets.length <= 1}
                                            title="Delete Preset"
                                            class={[
                                                adminDangerIconBtnClass,
                                                'disabled:cursor-not-allowed disabled:opacity-30',
                                            ]}
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </section>
                    </form>
                </div>
            </>
        );
    },
});
