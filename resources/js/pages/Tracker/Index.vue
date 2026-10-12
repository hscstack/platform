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
    group: 'science' | 'humanities' | 'commerce';
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
        <div class="mb-6 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 sm:gap-3">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-2xs sm:h-10 sm:w-10 dark:bg-white dark:text-slate-900"
                >
                    <Clock class="h-4.5 w-4.5 sm:h-5 sm:w-5" />
                </div>
                <div>
                    <h1
                        class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl dark:text-gray-100"
                    >
                        Study <span class="text-indigo-600">Tracker</span>
                    </h1>
                    <p
                        class="text-xs text-slate-500 sm:mt-0.5 sm:text-sm dark:text-gray-400"
                    >
                        দৈনিক পড়ার সময় ট্র্যাক করুন ও সিলেবাসের অগ্রগতি রাখুন
                    </p>
                </div>
            </div>

            <!-- View Activity Link (if logged in) or Guest Prompt -->
            <div
                v-if="isAuthenticated && user"
                class="flex shrink-0 items-center gap-2"
            >
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
            <div v-else class="flex shrink-0 items-center gap-2">
                <Link
                    :href="`/login?redirect=${encodeURIComponent($page.url)}`"
                    class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-800 active:scale-95 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                >
                    <span>Log in</span>
                    <ArrowRight class="h-3 w-3" />
                </Link>
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
                    :group="group"
                    :subjects="subjects"
                    :completed-node-ids="completedNodeIds"
                    :is-authenticated="isAuthenticated"
                    @require-auth="handleAuthRequired"
                />
            </div>
        </div>
    </div>

    <!-- Auth Modal -->
    <AuthModal v-model="showAuthModal" :message="authModalMessage" />
</template>
