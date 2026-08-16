<script setup lang="ts">
import { kDialog, kButton } from 'konsta/vue';
import { X } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    notice: { type: Object, default: null },
});

const isVisible = ref(false);

const getStorageKey = () => {
    return props.notice?.updated_at
        ? `session_notice_${props.notice.updated_at}`
        : 'session_notice_dismissed';
};

const checkVisibility = () => {
    if (!props.notice) {
        return false;
    }

    return sessionStorage.getItem(getStorageKey()) !== 'true';
};

onMounted(() => {
    isVisible.value = checkVisibility();
});

watch(
    () => props.notice,
    () => {
        isVisible.value = checkVisibility();
    },
);

const close = () => {
    if (props.notice) {
        sessionStorage.setItem(getStorageKey(), 'true');
    }

    isVisible.value = false;
};
</script>

<template>
    <Teleport to="body">
        <kDialog :opened="isVisible && !!notice" @opened:change="close">
            <div
                class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-slate-900/5 dark:bg-gray-900 dark:ring-gray-700/50"
            >
                <k-button
                    clear
                    small
                    @click="close"
                    class="absolute top-4 right-4 z-10"
                    aria-label="Close notice"
                >
                    <X class="h-5 w-5" />
                </k-button>

                <img
                    v-if="notice.image"
                    :src="notice.image"
                    class="h-40 w-full object-cover"
                    alt=""
                />

                <div class="p-6">
                    <h2
                        v-if="notice.title"
                        class="text-lg font-semibold text-slate-900 dark:text-gray-100"
                    >
                        {{ notice.title }}
                    </h2>
                    <p
                        v-if="notice.message"
                        class="mt-2 text-sm leading-relaxed whitespace-pre-line text-slate-600 dark:text-gray-400"
                    >
                        {{ notice.message }}
                    </p>

                    <div class="mt-6 flex gap-3">
                        <k-button
                            v-if="notice.show_button && notice.button_link"
                            fill
                            rounded
                            large
                            :href="notice.button_link"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex-1"
                        >
                            {{ notice.button_title || 'Learn more' }}
                        </k-button>
                    </div>
                </div>
            </div>
        </kDialog>
    </Teleport>
</template>
