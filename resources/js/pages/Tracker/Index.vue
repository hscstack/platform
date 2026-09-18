<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Clock, User as UserIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import AuthModal from '@/components/AuthModal.vue';
import StopwatchWidget from '@/components/tracker/StopwatchWidget.vue';
import SubjectChecklist from '@/components/tracker/SubjectChecklist.vue';
import type { SubjectItem } from '@/components/tracker/SubjectChecklist.vue';
import { useAuth } from '@/lib/useAuth';

interface Props {
    course: 'hsc' | 'ssc';
    userCurriculum?: 'hsc' | 'ssc';
    subjects: SubjectItem[];
    completedNodeIds: number[];
    todaySeconds: number;
}

defineProps<Props>();

const { user, requireAuth, showAuthModal, authModalMessage } = useAuth();
const isAuthenticated = computed(() => !!user.value);

function handleAuthRequired(
    message = 'Please log in to track your study sessions and chapter progress.',
) {
    requireAuth(message);
}
</script>

<template>
    <Head>
        <title>Study Tracker - HSC Stack</title>
        <meta
            name="description"
            content="Track your daily study time with a simple stopwatch and tick off completed syllabus chapters on HSC Stack."
        />
    </Head>

    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-8">
        <!-- Page Header -->
        <div
            class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white shadow-2xs dark:bg-white dark:text-slate-900"
                >
                    <Clock class="h-5 w-5" />
                </div>
                <div>
                    <h1
                        class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl dark:text-white"
                    >
                        Study Tracker
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        দৈনিক পড়ার সময় ট্র্যাক করুন ও সিলেবাসের অগ্রগতি রাখুন
                    </p>
                </div>
            </div>

            <!-- View Activity Link (if logged in) or Guest Prompt -->
            <div v-if="isAuthenticated && user" class="flex items-center gap-2">
                <Link
                    :href="`/u/${user.username}`"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <UserIcon
                        class="h-3.5 w-3.5 text-slate-500 dark:text-gray-400"
                    />
                    <span>View activity</span>
                </Link>
            </div>
            <div
                v-else
                class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-2xs sm:self-auto dark:border-gray-800 dark:bg-gray-900"
            >
                <span
                    class="text-xs font-medium text-slate-600 dark:text-gray-300"
                >
                    Log in to save your study sessions & progress
                </span>
                <button
                    type="button"
                    @click="handleAuthRequired()"
                    class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-slate-800 active:scale-95 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                >
                    <span>Log in</span>
                    <ArrowRight class="h-3 w-3" />
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="space-y-6">
            <!-- Focused Stopwatch Widget -->
            <div>
                <StopwatchWidget
                    :today-seconds="todaySeconds"
                    :is-authenticated="isAuthenticated"
                    @require-auth="
                        handleAuthRequired(
                            'Log in to save your study sessions.',
                        )
                    "
                />
            </div>

            <!-- Subject & Chapter Checklist -->
            <div>
                <SubjectChecklist
                    :course="course"
                    :user-curriculum="userCurriculum"
                    :subjects="subjects"
                    :completed-node-ids="completedNodeIds"
                    :is-authenticated="isAuthenticated"
                    @require-auth="
                        handleAuthRequired(
                            'Log in to save your chapter checklist progress.',
                        )
                    "
                />
            </div>
        </div>
    </div>

    <!-- Auth Modal -->
    <AuthModal v-model="showAuthModal" :message="authModalMessage" />
</template>
