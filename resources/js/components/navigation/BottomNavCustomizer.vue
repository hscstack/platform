<script setup lang="ts">
import { ref } from 'vue';

import MaterialIcon from '@/components/ui/MaterialIcon.vue';
import { useBottomNavCustomization } from '@/lib/useBottomNavCustomization';

const {
    bottomNavItems,
    availableItems,
    homeItem,
    accountItem,
    middleHrefs,
    canAdd,
    canRemove,
    addItem,
    removeItem,
    reorder,
    reset,
    MIN_TOTAL,
    MAX_TOTAL,
} = useBottomNavCustomization();

const dragIndex = ref<number | null>(null);

const onDragStart = (index: number, e: DragEvent) => {
    dragIndex.value = index;

    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(index));
    }
};

const onDragOver = (e: DragEvent) => {
    e.preventDefault();

    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'move';
    }
};

const onDrop = (targetIndex: number, e: DragEvent) => {
    e.preventDefault();
    const from = dragIndex.value;

    if (from === null || from === targetIndex) {
        dragIndex.value = null;

        return;
    }

    reorder(from, targetIndex);
    dragIndex.value = null;
};

const onDragEnd = () => {
    dragIndex.value = null;
};

const handleAdd = (href: string) => {
    if (!canAdd.value) {
        return;
    }

    addItem(href);
};

const handleRemove = (href: string) => {
    if (!canRemove.value) {
        return;
    }

    removeItem(href);
};
</script>

<template>
    <div
        class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
    >
        <div class="mb-6">
            <h3
                class="text-base font-semibold text-slate-900 dark:text-gray-100"
            >
                Bottom navigation
            </h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                Customize your mobile bottom bar (3–5 items). Home and Account
                are pinned — drag the middle items to reorder. Changes save
                automatically to this device.
            </p>
            <p class="mt-2 text-xs font-medium">
                <span
                    :class="
                        bottomNavItems.length < MIN_TOTAL ||
                        bottomNavItems.length > MAX_TOTAL
                            ? 'text-amber-600'
                            : 'text-slate-500 dark:text-gray-400'
                    "
                >
                    {{ bottomNavItems.length }} / {{ MAX_TOTAL }} items
                </span>
                <span class="mx-2 text-slate-300">·</span>
                <button
                    type="button"
                    @click="reset"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                >
                    <MaterialIcon name="restart_alt" :size="14" /> Reset
                </button>
            </p>
        </div>

        <!-- Current bottom bar (pinned + draggable middle) -->
        <div>
            <p
                class="mb-2 text-[11px] font-bold tracking-widest text-slate-400 uppercase dark:text-gray-500"
            >
                Bottom bar — drag middle to reorder
            </p>
            <ul class="space-y-2">
                <!-- Home pinned -->
                <li
                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/60"
                >
                    <MaterialIcon
                        name="lock"
                        :size="16"
                        class="shrink-0 text-slate-400"
                    />
                    <MaterialIcon
                        :name="homeItem.icon"
                        :size="20"
                        class="shrink-0 text-slate-700 dark:text-gray-300"
                    />
                    <span
                        class="flex-1 text-sm font-semibold text-slate-900 dark:text-gray-100"
                        >{{ homeItem.label }}</span
                    >
                    <span
                        class="rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white dark:bg-white dark:text-slate-900"
                        >Pinned first</span
                    >
                </li>

                <!-- Middle draggable -->
                <li
                    v-for="(href, idx) in middleHrefs"
                    :key="href"
                    draggable="true"
                    @dragstart="onDragStart(idx, $event)"
                    @dragover="onDragOver"
                    @drop="onDrop(idx, $event)"
                    @dragend="onDragEnd"
                    :class="[
                        'flex items-center gap-3 rounded-xl border bg-white px-3 py-2.5 shadow-sm transition dark:bg-gray-800',
                        dragIndex === idx
                            ? 'border-indigo-300 ring-2 ring-indigo-200 dark:border-indigo-700'
                            : 'border-slate-200 dark:border-gray-700',
                    ]"
                >
                    <MaterialIcon
                        name="drag_indicator"
                        :size="16"
                        class="shrink-0 cursor-grab text-slate-400 active:cursor-grabbing"
                    />
                    <MaterialIcon
                        :name="bottomNavItems[idx + 1]?.icon ?? 'help'"
                        :size="20"
                        class="shrink-0 text-slate-600 dark:text-gray-300"
                    />
                    <span
                        class="flex-1 text-sm font-medium text-slate-800 dark:text-gray-200"
                        >{{ bottomNavItems[idx + 1]?.label }}</span
                    >
                    <button
                        type="button"
                        @click="handleRemove(href)"
                        :disabled="!canRemove"
                        class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30 dark:hover:bg-rose-950/40"
                        aria-label="Remove from bottom bar"
                    >
                        <MaterialIcon name="close" :size="16" />
                    </button>
                </li>

                <!-- Account pinned last -->
                <li
                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/60"
                >
                    <MaterialIcon
                        name="lock"
                        :size="16"
                        class="shrink-0 text-slate-400"
                    />
                    <MaterialIcon
                        :name="accountItem.icon"
                        :size="20"
                        class="shrink-0 text-slate-700 dark:text-gray-300"
                    />
                    <span
                        class="flex-1 text-sm font-semibold text-slate-900 dark:text-gray-100"
                        >{{ accountItem.label }}</span
                    >
                    <span
                        class="rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white dark:bg-white dark:text-slate-900"
                        >Pinned last</span
                    >
                </li>
            </ul>
            <p
                v-if="!canRemove"
                class="mt-2 text-[11px] text-amber-600 dark:text-amber-400"
            >
                Minimum {{ MIN_TOTAL }} items — remove disabled.
            </p>
            <p
                v-if="!canAdd"
                class="mt-2 text-[11px] text-amber-600 dark:text-amber-400"
            >
                Maximum {{ MAX_TOTAL }} items — add disabled.
            </p>
        </div>

        <!-- Available pool -->
        <div class="mt-6">
            <p
                class="mb-2 text-[11px] font-bold tracking-widest text-slate-400 uppercase dark:text-gray-500"
            >
                More items — tap to add to bottom bar
            </p>
            <div
                v-if="availableItems.length === 0"
                class="rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-500 dark:border-gray-700 dark:text-gray-400"
            >
                All items are already in your bottom bar.
            </div>
            <div v-else class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button
                    v-for="item in availableItems"
                    :key="item.href"
                    type="button"
                    @click="handleAdd(item.href)"
                    :disabled="!canAdd"
                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-left text-sm font-medium transition hover:bg-slate-50 disabled:opacity-40 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700/60"
                >
                    <MaterialIcon
                        :name="item.icon"
                        :size="20"
                        class="shrink-0 text-slate-500"
                    />
                    <span class="flex-1 text-slate-700 dark:text-gray-300">{{
                        item.label
                    }}</span>
                    <MaterialIcon
                        name="add"
                        :size="16"
                        class="shrink-0 text-indigo-600 dark:text-indigo-400"
                    />
                </button>
            </div>
        </div>
    </div>
</template>
