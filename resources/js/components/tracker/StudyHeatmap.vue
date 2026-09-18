<script setup lang="ts">
import { Calendar, Check, ChevronDown, Clock, X } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

export interface HeatmapItem {
    date: string; // YYYY-MM-DD
    seconds: number;
    level: number; // 0, 1, 2, 3, 4
}

export interface TrackerStats {
    currentStreak?: number;
    longestStreak?: number;
    totalSeconds?: number;
    totalDays?: number;
}

interface Props {
    heatmapData: HeatmapItem[];
    stats?: TrackerStats;
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Study Activity',
});

const scrollContainer = ref<HTMLElement | null>(null);

// Time Range Selection State
interface RangeOption {
    label: string;
    days: number;
}

const RANGES: RangeOption[] = [
    { label: 'Last 1 month', days: 30 },
    { label: 'Last 3 months', days: 90 },
    { label: 'Last 6 months', days: 180 },
    { label: 'Last 12 months', days: 365 },
];

const selectedRange = ref<RangeOption>(RANGES[0]);
const isDropdownOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

// Day Detail Modal State (for click / mobile tap)
const selectedDay = ref<{
    date: string;
    seconds: number;
    level: number;
} | null>(null);

const showLegendModal = ref(false);

function handleCellClick(day: DayCell) {
    if (!day.date) {
        return;
    }

    activeHover.value = null;
    selectedDay.value = {
        date: day.date,
        seconds: day.seconds,
        level: day.level,
    };
}

function closeDayModal() {
    selectedDay.value = null;
}

function selectRange(range: RangeOption) {
    selectedRange.value = range;
    isDropdownOpen.value = false;
}

function handleClickOutside(event: MouseEvent) {
    if (
        dropdownRef.value &&
        !dropdownRef.value.contains(event.target as Node)
    ) {
        isDropdownOpen.value = false;
    }
}

// Tooltip state
const activeHover = ref<{
    date: string;
    seconds: number;
    x: number;
    y: number;
} | null>(null);

interface DayCell {
    date: string;
    seconds: number;
    level: number;
    dayOfWeek: number; // 0 = Sun ... 6 = Sat
    monthName: string;
    monthIndex: number;
}

const MONTH_NAMES = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];

function parseLocalDate(dateStr: string): Date {
    const parts = dateStr.split('-').map(Number);

    return new Date(parts[0], parts[1] - 1, parts[2]);
}

const weeks = computed(() => {
    if (!props.heatmapData || props.heatmapData.length === 0) {
        return [];
    }

    const result: DayCell[][] = [];
    let currentWeek: DayCell[] = [];

    const firstDate = parseLocalDate(props.heatmapData[0].date);
    const startDayOfWeek = firstDate.getDay();

    // Pad beginning of first week
    for (let i = 0; i < startDayOfWeek; i++) {
        currentWeek.push({
            date: '',
            seconds: 0,
            level: -1,
            dayOfWeek: i,
            monthName: '',
            monthIndex: -1,
        });
    }

    for (const item of props.heatmapData) {
        const d = parseLocalDate(item.date);
        const dayOfWeek = d.getDay();
        const monthIndex = d.getMonth();

        currentWeek.push({
            date: item.date,
            seconds: item.seconds,
            level: item.level,
            dayOfWeek,
            monthName: MONTH_NAMES[monthIndex],
            monthIndex,
        });

        if (dayOfWeek === 6) {
            result.push(currentWeek);
            currentWeek = [];
        }
    }

    if (currentWeek.length > 0) {
        while (currentWeek.length < 7) {
            currentWeek.push({
                date: '',
                seconds: 0,
                level: -1,
                dayOfWeek: currentWeek.length,
                monthName: '',
                monthIndex: -1,
            });
        }

        result.push(currentWeek);
    }

    return result;
});

const monthLabels = computed(() => {
    const labels: { name: string; colIndex: number; x: number }[] = [];
    let lastColIndex = -10;

    weeks.value.forEach((week, colIdx) => {
        const firstValidDay = week.find((d) => d.date && d.monthName);

        if (!firstValidDay) {
            return;
        }

        const prevWeek = weeks.value[colIdx - 1];
        const prevMonth = prevWeek?.find((d) => d.date)?.monthIndex;

        if (
            prevMonth !== undefined &&
            firstValidDay.monthIndex !== prevMonth &&
            colIdx - lastColIndex >= 3
        ) {
            labels.push({
                name: firstValidDay.monthName,
                colIndex: colIdx,
                x: 28 + colIdx * 13,
            });
            lastColIndex = colIdx;
        } else if (colIdx === 0) {
            labels.push({
                name: firstValidDay.monthName,
                colIndex: colIdx,
                x: 28,
            });
            lastColIndex = 0;
        }
    });

    return labels;
});

const svgWidth = computed(() => {
    return Math.max(720, 28 + weeks.value.length * 13 + 10);
});

function scrollToCurrentWeek() {
    if (scrollContainer.value) {
        scrollContainer.value.scrollLeft = scrollContainer.value.scrollWidth;
    }
}

watch(
    () => props.heatmapData,
    () => {
        nextTick(() => {
            scrollToCurrentWeek();
            setTimeout(scrollToCurrentWeek, 50);
            setTimeout(scrollToCurrentWeek, 200);
        });
    },
    { deep: true },
);

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('touchstart', handleClickOutside);
    nextTick(() => {
        scrollToCurrentWeek();
        requestAnimationFrame(() => {
            scrollToCurrentWeek();
        });
        setTimeout(scrollToCurrentWeek, 50);
        setTimeout(scrollToCurrentWeek, 150);
        setTimeout(scrollToCurrentWeek, 300);
        setTimeout(scrollToCurrentWeek, 600);
    });
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('touchstart', handleClickOutside);
});

const selectedRangeSeconds = computed(() => {
    if (!props.heatmapData || props.heatmapData.length === 0) {
        return 0;
    }

    const recent = props.heatmapData.slice(-selectedRange.value.days);

    return recent.reduce((sum, item) => sum + item.seconds, 0);
});

const selectedRangeFormatted = computed(() => {
    const totalSec = selectedRangeSeconds.value;
    const h = Math.floor(totalSec / 3600);
    const m = Math.floor((totalSec % 3600) / 60);

    if (h === 0 && m === 0) {
        return '0 mins';
    }

    if (h === 0) {
        return `${m} mins`;
    }

    if (m === 0) {
        return `${h} ${h === 1 ? 'hour' : 'hours'}`;
    }

    return `${h} ${h === 1 ? 'hour' : 'hours'} ${m} ${m === 1 ? 'min' : 'mins'}`;
});

function formatDuration(seconds: number): string {
    if (seconds === 0) {
        return 'No study time';
    }

    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);

    if (h === 0) {
        return `${m} mins`;
    }

    if (m === 0) {
        return `${h} hours`;
    }

    return `${h}h ${m}m`;
}

function formatDateDisplay(dateStr: string): string {
    if (!dateStr) {
        return '';
    }

    const date = parseLocalDate(dateStr);

    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatDateFull(dateStr: string): string {
    if (!dateStr) {
        return '';
    }

    const date = parseLocalDate(dateStr);

    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatDayModalDuration(seconds: number): string {
    if (seconds === 0) {
        return '0 mins';
    }

    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);

    if (h === 0) {
        return `${m} min${m === 1 ? '' : 's'}`;
    }

    if (m === 0) {
        return `${h} hour${h === 1 ? '' : 's'}`;
    }

    return `${h} hr ${m} min`;
}

function handleCellMouseEnter(day: DayCell, event: MouseEvent | TouchEvent) {
    if (!day.date) {
        return;
    }

    const target = event.currentTarget as SVGElement;
    const rect = target.getBoundingClientRect();
    activeHover.value = {
        date: day.date,
        seconds: day.seconds,
        x: rect.left + rect.width / 2,
        y: rect.top - 8,
    };
}

function handleCellMouseLeave() {
    activeHover.value = null;
}

function getLevelFillClass(level: number): string {
    switch (level) {
        case 0:
            return 'fill-slate-100 dark:fill-gray-800';
        case 1:
            return 'fill-emerald-200 dark:fill-emerald-950';
        case 2:
            return 'fill-emerald-400 dark:fill-emerald-800';
        case 3:
            return 'fill-emerald-500 dark:fill-emerald-600';
        case 4:
            return 'fill-emerald-600 dark:fill-emerald-400';
        case 5:
            return 'fill-red-500 dark:fill-red-500';
        default:
            return 'fill-transparent';
    }
}
</script>

<template>
    <div
        class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs sm:p-5 dark:border-gray-800 dark:bg-gray-900"
    >
        <!-- Minimal Header -->
        <div class="flex items-baseline justify-between gap-2">
            <div>
                <h3
                    class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                >
                    Study Activity
                </h3>
                <div class="mt-1 flex items-center gap-1.5">
                    <span
                        class="text-base font-bold text-slate-900 sm:text-lg dark:text-white"
                    >
                        {{ selectedRangeFormatted }}
                    </span>

                    <!-- Time Range Dropdown Trigger -->
                    <div
                        ref="dropdownRef"
                        class="relative inline-flex items-center"
                    >
                        <button
                            type="button"
                            @click.stop="isDropdownOpen = !isDropdownOpen"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-md px-1.5 py-0.5 text-xs text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 focus:outline-hidden dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                            aria-haspopup="true"
                            :aria-expanded="isDropdownOpen"
                        >
                            <span>({{ selectedRange.label }})</span>
                            <ChevronDown
                                class="h-3 w-3 transition-transform duration-150"
                                :class="{
                                    'rotate-180 text-slate-600 dark:text-gray-300':
                                        isDropdownOpen,
                                }"
                            />
                        </button>

                        <!-- Dropdown Menu -->
                        <div
                            v-if="isDropdownOpen"
                            class="animate-in fade-in zoom-in-95 absolute top-full left-0 z-30 mt-1 min-w-[130px] rounded-xl border border-slate-200 bg-white py-1 shadow-lg backdrop-blur-xs duration-100 dark:border-gray-700 dark:bg-gray-800"
                        >
                            <button
                                v-for="range in RANGES"
                                :key="range.days"
                                type="button"
                                @click.stop="selectRange(range)"
                                class="flex w-full cursor-pointer items-center justify-between gap-2 px-3 py-1.5 text-left text-xs font-medium transition-colors hover:bg-slate-50 dark:hover:bg-gray-700/60"
                                :class="[
                                    selectedRange.days === range.days
                                        ? 'bg-emerald-50/50 font-semibold text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400'
                                        : 'text-slate-700 dark:text-gray-200',
                                ]"
                            >
                                <span>{{ range.label }}</span>
                                <Check
                                    v-if="selectedRange.days === range.days"
                                    class="h-3.5 w-3.5 shrink-0 text-emerald-600 dark:text-emerald-400"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Heatmap SVG Canvas Container -->
        <div class="mt-4">
            <div
                ref="scrollContainer"
                class="-mx-1 scrollbar-thin overflow-x-auto px-1 pt-1 pb-1"
            >
                <svg
                    :width="svgWidth"
                    height="116"
                    :viewBox="`0 0 ${svgWidth} 116`"
                    class="block overflow-visible select-none"
                >
                    <!-- Month Labels -->
                    <g
                        class="fill-slate-400 text-[10px] font-medium dark:fill-gray-500"
                    >
                        <text
                            v-for="m in monthLabels"
                            :key="m.name + m.colIndex"
                            :x="m.x"
                            y="12"
                        >
                            {{ m.name }}
                        </text>
                    </g>

                    <!-- Day of Week Labels (Mon, Wed, Fri) -->
                    <g
                        class="fill-slate-400 text-[9px] font-medium dark:fill-gray-500"
                        text-anchor="start"
                    >
                        <text x="4" y="39">Mon</text>
                        <text x="4" y="65">Wed</text>
                        <text x="4" y="91">Fri</text>
                    </g>

                    <!-- Heatmap Grid Squares -->
                    <g transform="translate(28, 18)">
                        <g
                            v-for="(week, colIdx) in weeks"
                            :key="colIdx"
                            :transform="`translate(${colIdx * 13}, 0)`"
                        >
                            <rect
                                v-for="(day, rowIdx) in week"
                                :key="rowIdx"
                                :y="rowIdx * 13"
                                width="10.5"
                                height="10.5"
                                rx="2.5"
                                ry="2.5"
                                :class="[
                                    day.level === -1
                                        ? 'pointer-events-none opacity-0'
                                        : [
                                              getLevelFillClass(day.level),
                                              'cursor-pointer transition-opacity hover:opacity-80',
                                          ],
                                ]"
                                @mouseenter="
                                    (e) => handleCellMouseEnter(day, e)
                                "
                                @mouseleave="handleCellMouseLeave"
                                @click.stop="handleCellClick(day)"
                            />
                        </g>
                    </g>
                </svg>
            </div>

            <!-- Footer: Legend (Clickable to view groupings) -->
            <div class="mt-2 flex items-center justify-end">
                <button
                    type="button"
                    @click="showLegendModal = true"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-1.5 py-0.5 text-[10px] text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                    title="Click to view study time level breakdown"
                >
                    <span>Less</span>
                    <span
                        class="h-2.5 w-2.5 rounded-[2px] bg-slate-100 dark:bg-gray-800"
                    />
                    <span
                        class="h-2.5 w-2.5 rounded-[2px] bg-emerald-200 dark:bg-emerald-950"
                    />
                    <span
                        class="h-2.5 w-2.5 rounded-[2px] bg-emerald-400 dark:bg-emerald-800"
                    />
                    <span
                        class="h-2.5 w-2.5 rounded-[2px] bg-emerald-500 dark:bg-emerald-600"
                    />
                    <span
                        class="h-2.5 w-2.5 rounded-[2px] bg-emerald-600 dark:bg-emerald-400"
                    />
                    <span
                        class="h-2.5 w-2.5 rounded-[2px] bg-red-500 dark:bg-red-500"
                    />
                    <span>More</span>
                </button>
            </div>
        </div>

        <!-- Floating Hover Tooltip (Desktop) -->
        <Teleport to="body">
            <div
                v-if="activeHover"
                :style="{
                    position: 'fixed',
                    left: `${activeHover.x}px`,
                    top: `${activeHover.y}px`,
                    transform: 'translate(-50%, -100%)',
                }"
                class="pointer-events-none z-50 rounded-lg border border-slate-200/80 bg-slate-900/95 px-2.5 py-1 text-center shadow-lg backdrop-blur-xs dark:border-gray-700 dark:bg-gray-950/95"
            >
                <div class="text-[11px] font-bold text-white">
                    {{ formatDuration(activeHover.seconds) }}
                </div>
                <div class="text-[10px] text-slate-300 dark:text-gray-400">
                    {{ formatDateDisplay(activeHover.date) }}
                </div>
            </div>
        </Teleport>

        <!-- Day Detail Modal (Click / Phone Tap) -->
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
                    v-if="selectedDay"
                    class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
                >
                    <!-- Backdrop overlay -->
                    <div
                        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
                        @click="closeDayModal"
                    />

                    <!-- Dialog Card -->
                    <div
                        class="animate-in fade-in zoom-in-95 relative z-10 w-full max-w-xs rounded-2xl border border-slate-200 bg-white p-5 shadow-xl duration-150 dark:border-gray-800 dark:bg-gray-900"
                        role="dialog"
                        aria-modal="true"
                        @click.stop
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div
                                class="flex items-center gap-2 text-slate-500 dark:text-gray-400"
                            >
                                <Calendar class="h-4 w-4" />
                                <span class="text-xs font-semibold"
                                    >Daily Study Log</span
                                >
                            </div>
                            <button
                                type="button"
                                @click="closeDayModal"
                                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="mt-3">
                            <h4
                                class="text-sm font-bold text-slate-900 dark:text-white"
                            >
                                {{ formatDateFull(selectedDay.date) }}
                            </h4>

                            <div
                                class="mt-3 flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-gray-800 dark:bg-gray-800/60"
                            >
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                    :class="
                                        selectedDay.seconds > 0
                                            ? 'bg-emerald-500 text-white shadow-xs'
                                            : 'bg-slate-200 text-slate-500 dark:bg-gray-700 dark:text-gray-400'
                                    "
                                >
                                    <Clock class="h-4 w-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="text-[11px] font-medium text-slate-400 dark:text-gray-500"
                                    >
                                        Study Time
                                    </div>
                                    <div
                                        class="text-sm font-extrabold text-slate-900 dark:text-white"
                                    >
                                        {{
                                            formatDayModalDuration(
                                                selectedDay.seconds,
                                            )
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button
                                type="button"
                                @click="closeDayModal"
                                class="w-full cursor-pointer rounded-xl bg-slate-900 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 active:scale-[0.98] dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Grouping / Legend Breakdown Modal -->
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
                    v-if="showLegendModal"
                    class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
                >
                    <!-- Backdrop overlay -->
                    <div
                        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
                        @click="showLegendModal = false"
                    />

                    <!-- Dialog Card -->
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
                                Study Time Levels
                            </h3>
                            <button
                                type="button"
                                @click="showLegendModal = false"
                                class="cursor-pointer rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="mt-4 space-y-2 text-xs">
                            <div
                                class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2 dark:border-gray-800/80 dark:bg-gray-800/40"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="h-3.5 w-3.5 rounded-[3px] border border-slate-200 bg-slate-100 dark:border-gray-700 dark:bg-gray-800"
                                    />
                                    <span
                                        class="font-medium text-slate-700 dark:text-gray-300"
                                        >Level 0</span
                                    >
                                </div>
                                <span
                                    class="font-semibold text-slate-500 dark:text-gray-400"
                                    >0 mins</span
                                >
                            </div>

                            <div
                                class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2 dark:border-gray-800/80 dark:bg-gray-800/40"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="h-3.5 w-3.5 rounded-[3px] bg-emerald-200 dark:bg-emerald-950"
                                    />
                                    <span
                                        class="font-medium text-slate-700 dark:text-gray-300"
                                        >Level 1</span
                                    >
                                </div>
                                <span
                                    class="font-semibold text-slate-900 dark:text-white"
                                    >&lt; 2 hours</span
                                >
                            </div>

                            <div
                                class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2 dark:border-gray-800/80 dark:bg-gray-800/40"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="h-3.5 w-3.5 rounded-[3px] bg-emerald-400 dark:bg-emerald-800"
                                    />
                                    <span
                                        class="font-medium text-slate-700 dark:text-gray-300"
                                        >Level 2</span
                                    >
                                </div>
                                <span
                                    class="font-semibold text-slate-900 dark:text-white"
                                    >2 – 4 hours</span
                                >
                            </div>

                            <div
                                class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2 dark:border-gray-800/80 dark:bg-gray-800/40"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="h-3.5 w-3.5 rounded-[3px] bg-emerald-500 dark:bg-emerald-600"
                                    />
                                    <span
                                        class="font-medium text-slate-700 dark:text-gray-300"
                                        >Level 3</span
                                    >
                                </div>
                                <span
                                    class="font-semibold text-slate-900 dark:text-white"
                                    >4 – 6 hours</span
                                >
                            </div>

                            <div
                                class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2 dark:border-gray-800/80 dark:bg-gray-800/40"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="h-3.5 w-3.5 rounded-[3px] bg-emerald-600 dark:bg-emerald-400"
                                    />
                                    <span
                                        class="font-medium text-slate-700 dark:text-gray-300"
                                        >Level 4</span
                                    >
                                </div>
                                <span
                                    class="font-semibold text-slate-900 dark:text-white"
                                    >6 – 8 hours</span
                                >
                            </div>

                            <div
                                class="flex items-center justify-between rounded-xl border border-rose-100 bg-rose-50/40 px-3 py-2 dark:border-red-900/40 dark:bg-red-950/20"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="h-3.5 w-3.5 rounded-[3px] bg-red-500 dark:bg-red-500"
                                    />
                                    <span
                                        class="font-medium text-red-700 dark:text-red-300"
                                        >Level 5</span
                                    >
                                </div>
                                <span
                                    class="font-bold text-red-600 dark:text-red-400"
                                    >8+ hours</span
                                >
                            </div>
                        </div>

                        <div class="mt-4">
                            <button
                                type="button"
                                @click="showLegendModal = false"
                                class="w-full cursor-pointer rounded-xl bg-slate-900 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 active:scale-[0.98] dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
