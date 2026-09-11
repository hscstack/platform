<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Zap,
    Clock,
    Plus,
    Trash2,
    RotateCcw,
    Save,
    CheckCircle2,
    Shield,
    Sparkles,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface PokePreset {
    id: string;
    icon: string;
    message: string;
}

interface PeerSettingsProps {
    settings: {
        enabled: boolean;
        cooldown_hours: number;
        presets: PokePreset[];
    };
}

const props = defineProps<PeerSettingsProps>();

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
    {
        id: 'lock_in',
        icon: '🔒',
        message: "Time to lock in. Let's crush today's study goals!",
    },
    {
        id: 'coffee',
        icon: '☕',
        message:
            'Grab a hot cup of tea/coffee and head over to your study desk.',
    },
    {
        id: 'exam_panic',
        icon: '💀',
        message:
            'Future you in the exam hall will thank you for studying right now.',
    },
    {
        id: 'study_buddy',
        icon: '🤝',
        message: 'I am studying right now, join the grind with me!',
    },
];

const form = useForm({
    enabled: props.settings.enabled,
    cooldown_hours: props.settings.cooldown_hours,
    presets: JSON.parse(
        JSON.stringify(props.settings.presets || defaultPresets),
    ) as PokePreset[],
});

const isSaved = ref(false);

const addPreset = () => {
    form.presets.push({
        id: 'preset_' + Date.now(),
        icon: '⚡',
        message: 'Time to open the books and make today count!',
    });
};

const removePreset = (index: number) => {
    if (form.presets.length <= 1) {
        alert('You must have at least one study poke preset.');

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
        onSuccess: () => {
            isSaved.value = true;
            setTimeout(() => {
                isSaved.value = false;
            }, 3000);
        },
    });
};
</script>

<template>
    <Head title="Peer & Study Poke Settings - Admin" />

    <div class="mx-auto max-w-5xl space-y-8 p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400"
                    >
                        <Zap class="h-5 w-5" />
                    </div>
                    <div>
                        <h1
                            class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                        >
                            Peer & Study Poke Settings
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Configure 1-to-1 peer study nudges, anti-spam
                            cooldowns, and motivational presets.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                >
                    <Save class="h-4 w-4" />
                    <span>{{
                        form.processing ? 'Saving...' : 'Save Settings'
                    }}</span>
                </button>
            </div>
        </div>

        <!-- Success Toast Indicator -->
        <div
            v-if="isSaved"
            class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 dark:border-emerald-800/30 dark:bg-emerald-950/40 dark:text-emerald-300"
        >
            <CheckCircle2
                class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
            />
            <span>Settings saved successfully!</span>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <!-- General Configuration Card -->
            <div
                class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="mb-6 flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-gray-800"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400"
                    >
                        <Shield class="h-4 w-4" />
                    </div>
                    <div>
                        <h2
                            class="text-sm font-bold text-slate-900 dark:text-gray-100"
                        >
                            General Controls & Anti-Spam
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Enable or pause study pokes globally and set
                            cooling-off limits.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Feature Toggle -->
                    <div
                        class="flex flex-col justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-4 dark:border-gray-800/80 dark:bg-gray-800/30"
                    >
                        <div>
                            <span
                                class="text-xs font-bold text-slate-900 dark:text-gray-100"
                            >
                                Study Poke Feature
                            </span>
                            <p
                                class="mt-1 text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                Allow students to send lightweight study nudges
                                and reminders to peers.
                            </p>
                        </div>
                        <div class="mt-4 flex items-center gap-3">
                            <label
                                class="relative inline-flex cursor-pointer items-center"
                            >
                                <input
                                    type="checkbox"
                                    v-model="form.enabled"
                                    class="peer sr-only"
                                />
                                <div
                                    class="peer h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-indigo-600 peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-gray-700 dark:peer-checked:bg-indigo-500"
                                ></div>
                            </label>
                            <span
                                class="text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                {{ form.enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>

                    <!-- Cooldown Hours -->
                    <div
                        class="flex flex-col justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-4 dark:border-gray-800/80 dark:bg-gray-800/30"
                    >
                        <div>
                            <div class="flex items-center gap-1.5">
                                <Clock
                                    class="h-3.5 w-3.5 text-slate-500 dark:text-gray-400"
                                />
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-gray-100"
                                >
                                    Cooldown Duration (Hours)
                                </span>
                            </div>
                            <p
                                class="mt-1 text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                Time a student must wait before poking the exact
                                same peer again.
                            </p>
                        </div>
                        <div class="mt-4 flex items-center gap-3">
                            <input
                                type="number"
                                v-model.number="form.cooldown_hours"
                                min="1"
                                max="72"
                                required
                                class="w-24 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            />
                            <span
                                class="text-xs text-slate-500 dark:text-gray-400"
                                >hours</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Presets Management Card -->
            <div
                class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="mb-6 flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400"
                        >
                            <Sparkles class="h-4 w-4" />
                        </div>
                        <div>
                            <h2
                                class="text-sm font-bold text-slate-900 dark:text-gray-100"
                            >
                                Motivational Presets & Copy
                            </h2>
                            <p
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                Customize the presets students see when they
                                click the "Study Poke" button.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="resetToDefaultPresets"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            <RotateCcw class="h-3.5 w-3.5" />
                            <span>Reset Defaults</span>
                        </button>
                        <button
                            type="button"
                            @click="addPreset"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600 transition hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-400 dark:hover:bg-indigo-900/60"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            <span>Add Preset</span>
                        </button>
                    </div>
                </div>

                <!-- Presets List -->
                <div class="space-y-3.5">
                    <div
                        v-for="(preset, idx) in form.presets"
                        :key="preset.id || idx"
                        class="flex flex-col gap-3 rounded-xl border border-slate-200/70 bg-slate-50/40 p-4 transition sm:flex-row sm:items-center dark:border-gray-800 dark:bg-gray-800/30"
                    >
                        <!-- Icon Picker / Emoji -->
                        <div class="w-14 shrink-0">
                            <label
                                class="mb-1 block text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                            >
                                Emoji
                            </label>
                            <input
                                type="text"
                                v-model="preset.icon"
                                maxlength="6"
                                required
                                class="w-full rounded-lg border border-slate-300 bg-white py-1.5 text-center text-base focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            />
                        </div>

                        <!-- Message -->
                        <div class="flex-1">
                            <label
                                class="mb-1 block text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                            >
                                Notification Message
                            </label>
                            <input
                                type="text"
                                v-model="preset.message"
                                placeholder="e.g. That syllabus isn't going to finish itself!"
                                required
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end sm:pt-4">
                            <button
                                type="button"
                                @click="removePreset(idx)"
                                :disabled="form.presets.length <= 1"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                                title="Delete Preset"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>
