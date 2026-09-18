<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Check,
    Clock,
    Heart,
    Pause,
    Play,
    RotateCcw,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    todaySeconds: number;
    isAuthenticated: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'require-auth'): void;
}>();

const isRunning = ref(false);
const startTimestamp = ref<number | null>(null);
const accumulatedSeconds = ref(0);
const elapsedSeconds = ref(0);
let timerInterval: number | null = null;

const STORAGE_KEY_START = 'hsc_tracker_stopwatch_start';
const STORAGE_KEY_ACCUMULATED = 'hsc_tracker_stopwatch_accumulated';
const STORAGE_KEY_RUNNING = 'hsc_tracker_stopwatch_running';

const isSubmitting = ref(false);
const showSaveModal = ref(false);
const showManualModal = ref(false);
const showResetTodayModal = ref(false);
const isResettingToday = ref(false);
const manualMinutes = ref(30);

const pendingSecondsToLog = ref(0);
const isPendingFromStopwatch = ref(false);

function syncElapsed() {
    if (isRunning.value && startTimestamp.value) {
        const diffMs = Date.now() - startTimestamp.value;
        const diffSec = Math.max(0, Math.floor(diffMs / 1000));
        elapsedSeconds.value = accumulatedSeconds.value + diffSec;
    } else {
        elapsedSeconds.value = accumulatedSeconds.value;
    }
}

function handleVisibilityOrFocus() {
    if (isRunning.value) {
        syncElapsed();
    }
}

onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityOrFocus);
    window.addEventListener('focus', handleVisibilityOrFocus);

    const savedRunning = localStorage.getItem(STORAGE_KEY_RUNNING) === 'true';
    const savedAccumulated = parseInt(
        localStorage.getItem(STORAGE_KEY_ACCUMULATED) || '0',
        10,
    );
    let savedStart = parseInt(
        localStorage.getItem(STORAGE_KEY_START) || '0',
        10,
    );

    // Support existing seconds-based timestamp if present
    if (savedStart > 0 && savedStart < 10000000000) {
        savedStart = savedStart * 1000;
    }

    accumulatedSeconds.value = isNaN(savedAccumulated) ? 0 : savedAccumulated;

    if (savedRunning && savedStart > 0) {
        startTimestamp.value = savedStart;
        syncElapsed();
        startTimer(false);
    } else {
        elapsedSeconds.value = accumulatedSeconds.value;
    }
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }

    document.removeEventListener('visibilitychange', handleVisibilityOrFocus);
    window.removeEventListener('focus', handleVisibilityOrFocus);
});

function startTimer(newStart = true) {
    if (timerInterval) {
        clearInterval(timerInterval);
    }

    isRunning.value = true;

    if (newStart || !startTimestamp.value) {
        startTimestamp.value = Date.now();
    }

    localStorage.setItem(STORAGE_KEY_START, startTimestamp.value.toString());
    localStorage.setItem(STORAGE_KEY_RUNNING, 'true');
    localStorage.setItem(
        STORAGE_KEY_ACCUMULATED,
        accumulatedSeconds.value.toString(),
    );

    syncElapsed();

    timerInterval = window.setInterval(() => {
        syncElapsed();
    }, 500);
}

function pauseTimer() {
    syncElapsed();

    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }

    isRunning.value = false;
    accumulatedSeconds.value = elapsedSeconds.value;
    startTimestamp.value = null;

    localStorage.setItem(STORAGE_KEY_RUNNING, 'false');
    localStorage.setItem(
        STORAGE_KEY_ACCUMULATED,
        accumulatedSeconds.value.toString(),
    );
    localStorage.removeItem(STORAGE_KEY_START);
}

function toggleTimer() {
    if (isRunning.value) {
        pauseTimer();
    } else {
        startTimer(true);
    }
}

function resetTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }

    isRunning.value = false;
    startTimestamp.value = null;
    accumulatedSeconds.value = 0;
    elapsedSeconds.value = 0;

    localStorage.removeItem(STORAGE_KEY_START);
    localStorage.removeItem(STORAGE_KEY_RUNNING);
    localStorage.removeItem(STORAGE_KEY_ACCUMULATED);
}

const maxMinutesAllowedForToday = computed(() => {
    const remainingSeconds = Math.max(0, 86400 - props.todaySeconds);

    return Math.floor(remainingSeconds / 60);
});

const isDayMaxReached = computed(() => {
    return props.todaySeconds >= 86400;
});

function triggerStopwatchSave() {
    if (!props.isAuthenticated) {
        emit('require-auth');

        return;
    }

    if (elapsedSeconds.value < 10 || isDayMaxReached.value) {
        return;
    }

    const remainingAllowed = Math.max(0, 86400 - props.todaySeconds);
    const secondsToLog = Math.min(elapsedSeconds.value, remainingAllowed);

    if (secondsToLog <= 0) {
        return;
    }

    pauseTimer();
    pendingSecondsToLog.value = secondsToLog;
    isPendingFromStopwatch.value = true;
    showSaveModal.value = true;
}

function triggerQuickLog(minutes: number) {
    if (!props.isAuthenticated) {
        emit('require-auth');

        return;
    }

    if (!minutes || minutes <= 0 || isDayMaxReached.value) {
        return;
    }

    const requestedSeconds = minutes * 60;
    const remainingAllowed = Math.max(0, 86400 - props.todaySeconds);
    const secondsToLog = Math.min(requestedSeconds, remainingAllowed);

    if (secondsToLog <= 0) {
        return;
    }

    showManualModal.value = false;
    pendingSecondsToLog.value = secondsToLog;
    isPendingFromStopwatch.value = false;
    showSaveModal.value = true;
}

function confirmSaveSession() {
    const secondsToLog = pendingSecondsToLog.value;

    if (secondsToLog <= 0) {
        return;
    }

    isSubmitting.value = true;

    router.post(
        '/tracker/log-time',
        { seconds: secondsToLog },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showSaveModal.value = false;

                if (isPendingFromStopwatch.value) {
                    resetTimer();
                }

                pendingSecondsToLog.value = 0;
                isSubmitting.value = false;
            },
            onError: () => {
                isSubmitting.value = false;
            },
        },
    );
}

function confirmResetToday() {
    if (!props.isAuthenticated) {
        emit('require-auth');

        return;
    }

    isResettingToday.value = true;
    router.post(
        '/tracker/reset-today',
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showResetTodayModal.value = false;
                isResettingToday.value = false;
            },
            onError: () => {
                isResettingToday.value = false;
            },
        },
    );
}

const formattedStopwatch = computed(() => {
    const totalSec = elapsedSeconds.value;
    const hours = Math.floor(totalSec / 3600);
    const minutes = Math.floor((totalSec % 3600) / 60);
    const seconds = totalSec % 60;

    const pad = (n: number) => n.toString().padStart(2, '0');

    return {
        hours: pad(hours),
        minutes: pad(minutes),
        seconds: pad(seconds),
    };
});

const pendingDurationFormatted = computed(() => {
    const sec = pendingSecondsToLog.value;
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;

    if (h === 0 && m === 0) {
        return `${s}s`;
    }

    if (h === 0) {
        return `${m} min${m === 1 ? '' : 's'}`;
    }

    if (m === 0) {
        return `${h} hour${h === 1 ? '' : 's'}`;
    }

    return `${h} hr ${m} min`;
});

const todayFormatted = computed(() => {
    const sec = props.todaySeconds;

    if (sec === 0) {
        return '0 mins';
    }

    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);

    if (h === 0) {
        return `${m} mins`;
    }

    if (m === 0) {
        return `${h} ${h === 1 ? 'hour' : 'hours'}`;
    }

    return `${h}h ${m}m`;
});
</script>

<template>
    <div
        class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs sm:p-6 dark:border-gray-800 dark:bg-gray-900"
    >
        <!-- Header -->
        <div class="flex items-center justify-between pb-2">
            <div class="flex items-center gap-2">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    <Clock class="h-4 w-4" />
                </span>
                <div>
                    <h2
                        class="text-sm font-bold text-slate-900 dark:text-white"
                    >
                        Stopwatch
                    </h2>
                </div>
            </div>

            <!-- Today's Total Study Time -->
            <div
                class="flex items-center gap-2 rounded-xl border border-slate-100 bg-slate-50 px-2.5 py-1 text-xs dark:border-gray-800 dark:bg-gray-800/60"
            >
                <div class="flex items-center gap-1">
                    <span class="text-slate-400 dark:text-gray-500"
                        >Today:</span
                    >
                    <span
                        class="font-bold text-slate-900 sm:text-sm dark:text-white"
                    >
                        {{ todayFormatted }}
                    </span>
                </div>

                <!-- Reset Today Button (when today has logged time) -->
                <button
                    v-if="todaySeconds > 0"
                    type="button"
                    @click="showResetTodayModal = true"
                    class="cursor-pointer rounded-md p-0.5 text-slate-400 transition hover:bg-slate-200/70 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-rose-400"
                    title="Clear today's recorded time"
                    aria-label="Clear today's recorded time"
                >
                    <RotateCcw class="h-3 w-3" />
                </button>
            </div>
        </div>

        <!-- Digital Timer Display -->
        <div
            class="my-4 flex flex-col items-center justify-center py-2 sm:my-6"
        >
            <div
                class="font-mono text-5xl font-extrabold tracking-tight text-slate-900 sm:text-6xl dark:text-white"
            >
                <span
                    :class="{
                        'text-emerald-600 dark:text-emerald-400': isRunning,
                    }"
                >
                    {{ formattedStopwatch.hours }}:{{
                        formattedStopwatch.minutes
                    }}:{{ formattedStopwatch.seconds }}
                </span>
            </div>

            <div class="mt-2 flex flex-col items-center gap-1 text-center">
                <div class="flex items-center gap-1.5 text-xs">
                    <span
                        v-if="isRunning"
                        class="inline-flex items-center gap-1.5 font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        <span
                            class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"
                        />
                        Running
                    </span>
                    <span
                        v-else-if="elapsedSeconds > 0"
                        class="font-semibold text-amber-600 dark:text-amber-400"
                    >
                        Paused
                    </span>
                    <span
                        v-else
                        class="font-medium text-slate-400 dark:text-gray-500"
                        >Ready</span
                    >
                </div>
                <p
                    v-if="isRunning"
                    class="text-[11px] text-slate-400 dark:text-gray-500"
                >
                    You can safely switch tabs or minimize your browser
                </p>
            </div>
        </div>

        <!-- Controls -->
        <div class="flex items-center justify-center gap-2.5">
            <button
                type="button"
                @click="toggleTimer"
                :class="[
                    isRunning
                        ? 'bg-amber-600 text-white hover:bg-amber-700'
                        : 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100',
                ]"
                class="flex h-11 min-w-[130px] cursor-pointer items-center justify-center gap-2 rounded-xl px-5 text-sm font-bold transition active:scale-95"
            >
                <component
                    :is="isRunning ? Pause : Play"
                    class="h-4 w-4 fill-current"
                />
                <span>{{
                    isRunning
                        ? 'Pause'
                        : elapsedSeconds > 0
                          ? 'Resume'
                          : 'Start'
                }}</span>
            </button>

            <button
                v-if="elapsedSeconds >= 10"
                type="button"
                @click="triggerStopwatchSave"
                class="flex h-11 cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-emerald-600 bg-emerald-50 px-4 text-xs font-bold text-emerald-700 hover:bg-emerald-100 active:scale-95 dark:border-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-900/60"
            >
                <Check class="h-4 w-4 stroke-[3]" />
                <span>Save</span>
            </button>

            <button
                v-if="elapsedSeconds > 0"
                type="button"
                @click="resetTimer"
                title="Reset stopwatch"
                class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-100 active:scale-95 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
            >
                <RotateCcw class="h-4 w-4" />
            </button>
        </div>

        <!-- Offline Study Time Prompt -->
        <div
            class="mt-5 flex items-center justify-center gap-1.5 border-t border-slate-100 pt-3.5 text-xs dark:border-gray-800/80"
        >
            <span class="text-slate-500 dark:text-gray-400">
                Read when offline?
            </span>
            <button
                type="button"
                @click="showManualModal = true"
                class="cursor-pointer font-bold text-slate-900 underline decoration-slate-300 underline-offset-2 transition hover:text-indigo-600 hover:decoration-indigo-400 dark:text-white dark:decoration-gray-600 dark:hover:text-indigo-400"
            >
                Add now
            </button>
        </div>

        <!-- Cute Save Confirmation Modal (Used for both Stopwatch & Quick Log) -->
        <Teleport to="body">
            <div
                v-if="showSaveModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
                    @click="showSaveModal = false"
                />

                <div
                    class="animate-in fade-in zoom-in-95 relative z-10 w-full max-w-xs rounded-2xl border border-slate-200 bg-white p-5 shadow-xl duration-150 dark:border-gray-800 dark:bg-gray-900"
                    role="dialog"
                    aria-modal="true"
                    @click.stop
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-400"
                            >
                                <Heart class="h-4 w-4 fill-current" />
                            </span>
                            <h3
                                class="text-sm font-bold text-slate-900 dark:text-white"
                            >
                                Save Study Session
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="showSaveModal = false"
                            class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Session Time & Loyalty Message -->
                    <div
                        class="mt-4 rounded-xl border border-slate-100 bg-slate-50/80 p-3.5 text-center dark:border-gray-800 dark:bg-gray-800/50"
                    >
                        <div
                            class="font-mono text-2xl font-black tracking-tight text-slate-900 dark:text-white"
                        >
                            {{ pendingDurationFormatted }}
                        </div>
                        <p
                            class="mt-2 text-xs leading-relaxed font-medium text-slate-600 dark:text-gray-300"
                        >
                            পুরো সময়টা কি আসলেই পড়াশোনা করেছেন? নিজের প্রতি সৎ
                            থাকুন — নিজের ভবিষ্যতের সাথে ফাঁকি দেবেন না! 🎯
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="showSaveModal = false"
                            class="cursor-pointer rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            No, Close
                        </button>
                        <button
                            type="button"
                            @click="confirmSaveSession"
                            :disabled="isSubmitting"
                            class="cursor-pointer rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                        >
                            {{ isSubmitting ? 'Saving...' : 'Yes, Save' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Custom Manual Log Modal -->
        <Teleport to="body">
            <div
                v-if="showManualModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
                    @click="showManualModal = false"
                />

                <div
                    class="animate-in fade-in zoom-in-95 relative z-10 w-full max-w-xs rounded-2xl border border-slate-200 bg-white p-5 shadow-xl duration-150 dark:border-gray-800 dark:bg-gray-900"
                    role="dialog"
                    aria-modal="true"
                    @click.stop
                >
                    <div class="flex items-start justify-between gap-3">
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-white"
                        >
                            Log Study Time
                        </h3>
                        <button
                            type="button"
                            @click="showManualModal = false"
                            class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                        Add study minutes completed today:
                    </p>

                    <div class="mt-3">
                        <input
                            v-model.number="manualMinutes"
                            type="number"
                            min="1"
                            :max="Math.min(720, maxMinutesAllowedForToday)"
                            :placeholder="`Minutes (max ${Math.min(720, maxMinutesAllowedForToday)})`"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 focus:border-slate-900 focus:outline-hidden dark:border-gray-800 dark:bg-gray-800 dark:text-white dark:focus:border-white"
                        />
                    </div>

                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="showManualModal = false"
                            class="cursor-pointer rounded-xl border border-slate-200 px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="triggerQuickLog(manualMinutes)"
                            :disabled="
                                !manualMinutes ||
                                manualMinutes <= 0 ||
                                manualMinutes > maxMinutesAllowedForToday
                            "
                            class="cursor-pointer rounded-xl bg-slate-900 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                        >
                            Continue
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Reset Today Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showResetTodayModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div
                        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
                        @click="showResetTodayModal = false"
                    />

                    <div
                        class="animate-in fade-in zoom-in-95 relative z-10 w-full max-w-xs rounded-2xl border border-slate-200 bg-white p-5 shadow-xl duration-150 dark:border-gray-800 dark:bg-gray-900"
                        role="dialog"
                        aria-modal="true"
                        @click.stop
                    >
                        <div class="flex items-start justify-between gap-3">
                            <h3
                                class="text-sm font-bold text-slate-900 dark:text-white"
                            >
                                Clear Today's Time
                            </h3>
                            <button
                                type="button"
                                @click="showResetTodayModal = false"
                                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <p
                            class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-gray-300"
                        >
                            Are you sure you want to reset today's recorded
                            study time ({{ todayFormatted }}) back to
                            <strong>0 mins</strong>?
                        </p>

                        <div class="mt-5 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="showResetTodayModal = false"
                                class="cursor-pointer rounded-xl border border-slate-200 px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                @click="confirmResetToday"
                                :disabled="isResettingToday"
                                class="cursor-pointer rounded-xl bg-rose-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-rose-700 disabled:opacity-50 dark:bg-rose-600 dark:hover:bg-rose-700"
                            >
                                {{
                                    isResettingToday
                                        ? 'Clearing...'
                                        : 'Yes, Clear'
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
