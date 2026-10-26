<script setup lang="ts">
import { AlertCircle, CheckCircle, Flag, Loader2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import BaseModal from '@/components/BaseModal.vue';
import { getCsrfToken } from '@/lib/useCsrf';

const props = defineProps<{
    isOpen: boolean;
    reportableType:
        | 'chat_message'
        | 'forum_post'
        | 'forum_answer'
        | 'resource_comment'
        | 'blog_comment';
    reportableId: number | null;
    authorName?: string | null;
    contentPreview?: string | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submitted'): void;
}>();

const reportReasons = [
    'Harassment, bullying or threats',
    'Spam, advertising or promotion',
    'Inappropriate or adult content',
    'Misinformation or cheating material',
    'Offensive language or hate speech',
    'Other reason',
];

const selectedReason = ref(reportReasons[0]);
const details = ref('');
const isSubmitting = ref(false);
const successMessage = ref<string | null>(null);
const errorMessage = ref<string | null>(null);

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            selectedReason.value = reportReasons[0];
            details.value = '';
            successMessage.value = null;
            errorMessage.value = null;
        }
    },
);

const submitReport = async () => {
    if (!props.reportableId || isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = null;
    successMessage.value = null;

    try {
        const res = await fetch('/reports', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                reportable_type: props.reportableType,
                reportable_id: props.reportableId,
                reason: selectedReason.value,
                details: details.value.trim() || undefined,
            }),
        });

        const data = await res.json();

        if (res.ok) {
            successMessage.value =
                data.message ||
                'Report received. Our moderation team will review it.';
            setTimeout(() => {
                emit('submitted');
                emit('close');
            }, 1400);
        } else {
            errorMessage.value =
                data.error ||
                data.message ||
                'Failed to submit report. Please try again.';
        }
    } catch {
        errorMessage.value = 'Network error. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <BaseModal
        :is-open="isOpen"
        title="Report Content"
        subtitle="Help keep our learning community safe, respectful, and helpful."
        max-width="max-w-md"
        @close="emit('close')"
    >
        <div class="space-y-4">
            <!-- Alert Messages -->
            <div
                v-if="successMessage"
                class="flex items-center gap-2 rounded-xl bg-emerald-50 p-3 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
            >
                <CheckCircle
                    class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                />
                <span>{{ successMessage }}</span>
            </div>

            <div
                v-if="errorMessage"
                class="flex items-center gap-2 rounded-xl bg-rose-50 p-3 text-xs font-semibold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300"
            >
                <AlertCircle
                    class="h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                />
                <span>{{ errorMessage }}</span>
            </div>

            <!-- Content Snapshot Context -->
            <div
                v-if="contentPreview"
                class="rounded-xl border border-slate-200/90 bg-slate-50/70 p-3 text-xs dark:border-gray-800 dark:bg-gray-800/60"
            >
                <span
                    class="mb-1 block text-[10px] font-bold text-slate-400 uppercase"
                >
                    Reporting content by {{ authorName || 'user' }}:
                </span>
                <p
                    class="line-clamp-3 text-slate-600 italic dark:text-gray-300"
                >
                    "{{ contentPreview }}"
                </p>
            </div>

            <!-- Reason Selector -->
            <div>
                <label
                    class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-gray-300"
                >
                    Why are you reporting this?
                </label>
                <select
                    v-model="selectedReason"
                    class="h-9 w-full rounded-xl border border-slate-200/90 bg-white px-3 text-xs font-semibold text-slate-800 transition focus:border-indigo-500 focus:outline-hidden dark:border-gray-800 dark:bg-gray-800 dark:text-gray-100"
                >
                    <option v-for="r in reportReasons" :key="r" :value="r">
                        {{ r }}
                    </option>
                </select>
            </div>

            <!-- Optional Details -->
            <div>
                <label
                    class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-gray-300"
                >
                    Additional Details (Optional)
                </label>
                <textarea
                    v-model="details"
                    rows="3"
                    maxlength="500"
                    placeholder="Provide additional context for our moderators..."
                    class="w-full rounded-xl border border-slate-200/90 bg-white p-3 text-xs text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:outline-hidden dark:border-gray-800 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
                ></textarea>
            </div>

            <!-- Action Buttons -->
            <div
                class="flex items-center justify-end gap-2 border-t border-slate-100 pt-2 dark:border-gray-800"
            >
                <button
                    type="button"
                    @click="emit('close')"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="submitReport"
                    :disabled="isSubmitting || Boolean(successMessage)"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-1.5 text-xs font-bold text-white shadow-2xs transition hover:bg-rose-700 disabled:opacity-60"
                >
                    <Loader2
                        v-if="isSubmitting"
                        class="h-3.5 w-3.5 animate-spin"
                    />
                    <Flag v-else class="h-3.5 w-3.5" />
                    <span>Submit Report</span>
                </button>
            </div>
        </div>
    </BaseModal>
</template>
