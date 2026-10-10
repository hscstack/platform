<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    Clock,
    User,
    ExternalLink,
    FileText,
    FileArchive,
    ChevronRight,
} from 'lucide-vue-next';
import BaseModal from '@/components/BaseModal.vue';
import { usePermissions } from '@/lib/usePermissions';

defineProps<{
    changeRequest: any | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const { can } = usePermissions();
</script>

<template>
    <BaseModal
        :is-open="changeRequest !== null"
        max-width="xl"
        @close="emit('close')"
    >
        <template #header>
            <div class="flex items-center gap-2.5">
                <div
                    v-if="changeRequest?.status === 'rejected'"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                >
                    <AlertCircle class="h-4.5 w-4.5" />
                </div>
                <div
                    v-else
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400"
                >
                    <Clock class="h-4.5 w-4.5 animate-pulse" />
                </div>
                <div>
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-gray-100"
                    >
                        {{
                            changeRequest?.status === 'rejected'
                                ? 'Rejected Resource Submission'
                                : 'Resource Submission'
                        }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        {{
                            changeRequest?.status === 'rejected'
                                ? 'This submission was reviewed and rejected'
                                : 'Awaiting moderation review before public release'
                        }}
                    </p>
                </div>
            </div>
        </template>

        <div v-if="changeRequest" class="space-y-5 p-4 sm:p-6">
            <!-- Status & Submission Meta Banner -->
            <div
                v-if="changeRequest.status === 'rejected'"
                class="space-y-2.5 rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-xs dark:border-rose-900/50 dark:bg-rose-950/30"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full bg-rose-600 px-2.5 py-0.5 text-[11px] font-bold text-white shadow-2xs"
                        >
                            Rejected
                        </span>
                        <span
                            class="font-medium text-rose-900 dark:text-rose-300"
                        >
                            Action:
                            {{
                                changeRequest.action_type === 'update'
                                    ? 'Edit Resource'
                                    : changeRequest.action_type === 'delete'
                                      ? 'Delete Resource'
                                      : 'Create Resource'
                            }}
                        </span>
                    </div>

                    <div
                        v-if="changeRequest.reviewer?.name"
                        class="text-slate-600 dark:text-gray-400"
                    >
                        Reviewed by
                        <span
                            class="font-semibold text-slate-900 dark:text-gray-100"
                        >
                            {{ changeRequest.reviewer.name }}
                        </span>
                    </div>
                </div>

                <div
                    class="border-t border-rose-200/60 pt-2 dark:border-rose-900/40"
                >
                    <div class="font-semibold text-rose-900 dark:text-rose-200">
                        Feedback / Reason:
                    </div>
                    <p
                        class="mt-0.5 text-sm whitespace-pre-wrap text-rose-800 dark:text-rose-300"
                    >
                        {{
                            changeRequest.rejection_reason ||
                            'No specific reason provided.'
                        }}
                    </p>
                </div>
            </div>

            <div
                v-else
                class="flex flex-wrap items-center justify-between gap-2.5 rounded-xl border border-amber-200/80 bg-amber-50/60 px-4 py-3 text-xs dark:border-amber-900/40 dark:bg-amber-950/20"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center rounded-full bg-amber-500 px-2.5 py-0.5 text-[11px] font-bold text-white shadow-2xs"
                    >
                        Pending Review
                    </span>
                    <span
                        class="font-medium text-amber-900/80 dark:text-amber-300"
                    >
                        Action:
                        {{
                            changeRequest.action_type === 'update'
                                ? 'Edit Resource'
                                : changeRequest.action_type === 'delete'
                                  ? 'Delete Resource'
                                  : 'Create Resource'
                        }}
                    </span>
                </div>

                <div
                    v-if="changeRequest.user?.name"
                    class="flex items-center gap-1.5 text-slate-600 dark:text-gray-400"
                >
                    <User class="h-3.5 w-3.5 text-slate-400" />
                    <span>
                        Submitted by
                        <Link
                            v-if="changeRequest.user?.username"
                            :href="`/u/${changeRequest.user.username}`"
                            class="font-semibold text-slate-900 transition-colors hover:text-indigo-600 hover:underline dark:text-gray-100 dark:hover:text-indigo-400"
                        >
                            {{ changeRequest.user.name }}
                        </Link>
                        <strong
                            v-else
                            class="font-semibold text-slate-900 dark:text-gray-100"
                        >
                            {{ changeRequest.user.name }}
                        </strong>
                    </span>
                </div>
            </div>

            <!-- Main Card: Title & Type -->
            <div
                class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-2xs dark:border-gray-800 dark:bg-gray-900/50"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <span
                            class="text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                            >Resource Title</span
                        >
                        <h2
                            class="mt-0.5 text-lg font-bold break-words text-slate-900 dark:text-white"
                        >
                            {{
                                changeRequest.payload?.title ||
                                changeRequest.targetResource?.title ||
                                '(Untitled)'
                            }}
                        </h2>
                    </div>
                    <span
                        class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold tracking-wide text-slate-700 uppercase dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                    >
                        {{
                            changeRequest.payload?.resource_type ||
                            changeRequest.targetResource?.resource_type ||
                            'Resource'
                        }}
                    </span>
                </div>

                <!-- Description / Body -->
                <div
                    v-if="changeRequest.payload?.content"
                    class="mt-4 border-t border-slate-100 pt-3 dark:border-gray-800/80"
                >
                    <span
                        class="text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                        >Description / Content</span
                    >
                    <p
                        class="mt-1.5 text-sm leading-relaxed whitespace-pre-wrap text-slate-700 dark:text-gray-300"
                    >
                        {{ changeRequest.payload.content }}
                    </p>
                </div>

                <!-- External URL -->
                <div
                    v-if="changeRequest.payload?.external_url"
                    class="mt-4 border-t border-slate-100 pt-3 dark:border-gray-800/80"
                >
                    <span
                        class="text-[10px] font-bold tracking-wider text-slate-400 uppercase dark:text-gray-500"
                        >External URL</span
                    >
                    <div class="mt-1">
                        <a
                            :href="changeRequest.payload.external_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400"
                        >
                            <span class="break-all">{{
                                changeRequest.payload.external_url
                            }}</span>
                            <ExternalLink class="h-3.5 w-3.5 shrink-0" />
                        </a>
                    </div>
                </div>
            </div>

            <!-- Attached File Media Card -->
            <div
                v-if="changeRequest.staged_file_url"
                class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-2xs dark:border-gray-800 dark:bg-gray-900/50"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300"
                >
                    <span class="flex items-center gap-1.5 font-bold">
                        <FileText
                            class="h-4 w-4 text-slate-500 dark:text-gray-400"
                        />
                        Attached Media / File
                    </span>
                    <a
                        :href="changeRequest.staged_file_url"
                        target="_blank"
                        class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                    >
                        <span>Open file in new tab</span>
                        <ExternalLink class="h-3 w-3" />
                    </a>
                </div>

                <div class="p-4">
                    <div
                        v-if="
                            changeRequest.payload?.resource_type === 'image' ||
                            changeRequest.staged_file_url.match(
                                /\.(jpeg|jpg|gif|png|webp|svg)$/i,
                            )
                        "
                        class="flex flex-col items-center justify-center rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-gray-800 dark:bg-gray-950/40"
                    >
                        <img
                            :src="changeRequest.staged_file_url"
                            alt="Staged Preview"
                            class="max-h-80 w-auto rounded-lg object-contain shadow-2xs"
                        />
                    </div>

                    <div
                        v-else
                        class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-gray-800 dark:bg-gray-800/40"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400"
                            >
                                <FileArchive class="h-5 w-5" />
                            </div>
                            <div>
                                <div
                                    class="text-xs font-semibold text-slate-900 dark:text-white"
                                >
                                    Uploaded Attachment
                                </div>
                                <div
                                    class="text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    Click to download or view file content
                                </div>
                            </div>
                        </div>
                        <a
                            :href="changeRequest.staged_file_url"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            <ExternalLink class="h-3.5 w-3.5" />
                            <span>Open File</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-3">
                <Link
                    v-if="
                        changeRequest?.status === 'pending' &&
                        can('moderate resources')
                    "
                    href="/admin/moderation/resources"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-amber-400 bg-amber-500 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-amber-600 dark:border-amber-500 dark:bg-amber-600 dark:hover:bg-amber-700"
                >
                    <span>Review in Moderation Queue</span>
                    <ChevronRight class="h-3.5 w-3.5" />
                </Link>

                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Close
                </button>
            </div>
        </template>
    </BaseModal>
</template>
