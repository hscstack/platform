<script setup lang="ts">
import { Lock } from 'lucide-vue-next';
import BaseModal from '@/components/BaseModal.vue';

defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();
</script>

<template>
    <BaseModal
        :is-open="isOpen"
        max-width="sm"
        position="center"
        @close="emit('close')"
    >
        <div class="p-6 text-center">
            <div
                class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
            >
                <Lock class="h-6 w-6 stroke-[2.2]" />
            </div>

            <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">
                ফোল্ডারটি লক করা রয়েছে
            </h3>

            <p
                class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-gray-400"
            >
                এই ফোল্ডারটি বর্তমানে লক করা রয়েছে। তাই এখানে কোনো কিছু আপলোড
                করা যাবে না।
            </p>

            <div class="mt-5">
                <button
                    type="button"
                    @click="emit('close')"
                    class="w-full cursor-pointer rounded-xl bg-slate-900 py-2.5 text-xs font-semibold text-white transition hover:bg-slate-800 active:scale-95 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                >
                    ঠিক আছে
                </button>
            </div>
        </div>
    </BaseModal>
</template>
