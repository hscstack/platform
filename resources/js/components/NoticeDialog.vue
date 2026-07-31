<script setup lang="ts">
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
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isVisible && notice"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-slate-950/20 backdrop-blur-sm"
                    @click="close"
                ></div>

                <div
                    class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-slate-900/5"
                    role="alertdialog"
                >
                    <button
                        @click="close"
                        class="absolute top-4 right-4 z-10 rounded-full bg-slate-100 p-2 text-slate-600 transition"
                        aria-label="Close notice"
                    >
                        <X class="h-5 w-5" />
                    </button>

                    <img
                        v-if="notice.image"
                        :src="notice.image"
                        class="h-40 w-full object-cover"
                        alt=""
                    />

                    <div class="p-6">
                        <h2
                            v-if="notice.title"
                            class="text-lg font-semibold text-slate-900"
                        >
                            {{ notice.title }}
                        </h2>
                        <p
                            v-if="notice.message"
                            class="mt-2 text-sm leading-relaxed whitespace-pre-line text-slate-600"
                        >
                            {{ notice.message }}
                        </p>

                        <div class="mt-6 flex gap-3">
                            <a
                                v-if="notice.show_button && notice.button_link"
                                :href="notice.button_link"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-center text-sm font-medium text-white transition hover:bg-slate-800"
                            >
                                {{ notice.button_title || 'Learn more' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
