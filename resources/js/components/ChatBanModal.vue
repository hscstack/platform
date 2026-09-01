<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Ban, Clock, Loader2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import { formatDateTime } from '@/lib/useDate';

export interface ChatBanUser {
    id: number;
    name: string;
    username?: string | null;
    banned_until?: string | null;
    is_banned?: boolean;
}

const props = defineProps<{
    isOpen: boolean;
    user: ChatBanUser | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const banUntilInput = ref('');
const isSubmittingBan = ref(false);

const banPresets = [
    { label: '3 Mins', minutes: 3 },
    { label: '5 Mins', minutes: 5 },
    { label: '15 Mins', minutes: 15 },
    { label: '1 Hour', minutes: 60 },
    { label: '24 Hours', minutes: 24 * 60 },
    { label: '3 Days', minutes: 3 * 24 * 60 },
    { label: '7 Days', minutes: 7 * 24 * 60 },
];

const formatForDatetimeLocal = (dateString?: string | null) => {
    if (!dateString) {
        return '';
    }

    const d = new Date(dateString);

    if (isNaN(d.getTime())) {
        return '';
    }

    const offset = d.getTimezoneOffset() * 60000;
    const localISOTime = new Date(d.getTime() - offset)
        .toISOString()
        .slice(0, 16);

    return localISOTime;
};

const formatDate = formatDateTime;

const applyBanPreset = (minutes: number) => {
    const futureDate = new Date(Date.now() + minutes * 60 * 1000);
    banUntilInput.value = formatForDatetimeLocal(futureDate.toISOString());
};

const clearBanInput = () => {
    banUntilInput.value = '';
};

watch(
    () => props.isOpen,
    (open) => {
        if (open && props.user) {
            const currentBan = props.user.banned_until;
            banUntilInput.value = formatForDatetimeLocal(currentBan);
        } else {
            banUntilInput.value = '';
        }
    },
    { immediate: true },
);

const handleClose = () => {
    emit('close');
};

const submitBan = () => {
    if (!props.user || isSubmittingBan.value) {
        return;
    }

    isSubmittingBan.value = true;
    const isoDate = banUntilInput.value
        ? new Date(banUntilInput.value).toISOString()
        : null;

    router.post(
        `/admin/chat/users/${props.user.id}/ban`,
        {
            banned_until: isoDate,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmittingBan.value = false;
                emit('close');
            },
        },
    );
};
</script>

<template>
    <BaseModal
        v-if="user"
        :is-open="isOpen"
        max-width="md"
        position="center"
        @close="handleClose"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
                >
                    <Ban class="h-5 w-5" />
                </div>
                <div>
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        Community Moderation
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        Manage suspension timer for
                        <strong class="text-slate-700 dark:text-gray-300">{{
                            user.name
                        }}</strong>
                        <span v-if="user.username">
                            (@{{ user.username }})</span
                        >
                    </p>
                </div>
            </div>
        </template>

        <div class="p-6 pt-4">
            <!-- Current status banner (when ban status is available) -->
            <div
                v-if="user.banned_until || user.is_banned"
                class="rounded-xl border border-rose-200 bg-rose-50/70 p-3 text-xs text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
            >
                <div class="flex items-center gap-2">
                    <Clock class="h-3.5 w-3.5 shrink-0" />
                    <div>
                        <span class="font-bold">Current status: </span>
                        <span>
                            Banned until
                            {{ formatDate(user.banned_until) }}
                        </span>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submitBan" class="mt-4 space-y-4">
                <!-- Quick Presets -->
                <div>
                    <label
                        class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Quick Presets
                    </label>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <button
                            v-for="preset in banPresets"
                            :key="preset.label"
                            type="button"
                            @click="applyBanPreset(preset.minutes)"
                            class="cursor-pointer rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                        >
                            +{{ preset.label }}
                        </button>
                        <button
                            type="button"
                            @click="clearBanInput"
                            class="cursor-pointer rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                        >
                            Clear / Unban
                        </button>
                    </div>
                </div>

                <!-- Custom Date & Time Picker -->
                <div class="space-y-1.5">
                    <label
                        for="reusable_modal_banned_until"
                        class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Ban Until (Date & Time)
                    </label>
                    <input
                        id="reusable_modal_banned_until"
                        type="datetime-local"
                        v-model="banUntilInput"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 shadow-2xs focus:border-rose-500 focus:ring-1 focus:ring-rose-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                    />
                    <p class="text-[11px] text-slate-400 dark:text-gray-500">
                        Leave blank to unban or lift restrictions.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="handleClose"
                        class="cursor-pointer rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmittingBan"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white transition disabled:opacity-50"
                        :class="
                            !banUntilInput
                                ? 'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600'
                                : 'bg-rose-600 hover:bg-rose-700 dark:bg-rose-500 dark:hover:bg-rose-600'
                        "
                    >
                        <Loader2
                            v-if="isSubmittingBan"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <Ban v-else-if="banUntilInput" class="h-3.5 w-3.5" />
                        <span>
                            {{
                                !banUntilInput
                                    ? 'Unban User'
                                    : 'Apply Ban Timer'
                            }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </BaseModal>
</template>
