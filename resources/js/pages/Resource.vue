<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowBigUp,
    User as UserIcon,
    X,
    Clock,
    AlertCircle,
    Check,
    Loader2,
    Pencil,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import NoteReader from '@/components/resources/NoteReader.vue';
import PdfViewer from '@/components/resources/PdfViewer.vue';
import ResourceCommentsSection from '@/components/resources/ResourceCommentsSection.vue';
import ResourceFormModal from '@/components/resources/ResourceFormModal.vue';
import ResourceVoteButtons from '@/components/resources/ResourceVoteButtons.vue';
import VideoSeriesPlayer from '@/components/resources/VideoSeriesPlayer.vue';

interface ResourceItem {
    id: number;
    resource_id: number;
    format: 'image' | 'video' | 'pdf' | string;
    title?: string | null;
    source: string;
    url: string;
}

interface ResourceData {
    id: number;
    node_id: number;
    user_id?: number | null;
    resource_type: string;
    title: string;
    description?: string | null;
    status?: 'pending' | 'approved' | 'rejected';
    admin_note?: string | null;
    created_at?: string;
    updated_at?: string;
    vote_score?: number;
    upvotes_count?: number;
    downvotes_count?: number;
    items?: ResourceItem[];
    node?: {
        id: number;
        name: string;
        slug: string;
    } | null;
    subject?: {
        id: number;
        name: string;
        slug: string;
    } | null;
    user?: {
        id: number;
        name: string;
        username: string;
        image_path?: string | null;
        image_url?: string | null;
    } | null;
}

interface Upvoter {
    id: number;
    name: string;
    username: string;
    image_path?: string | null;
    image_url?: string | null;
    institution?: string | null;
    is_verified?: boolean;
}

const props = defineProps<{
    resource: ResourceData;
    subjects?: any[];
    userVote?: number | null;
    upvoters?: Upvoter[];
    comments?: any[];
    can_moderate?: boolean;
    is_owner?: boolean;
}>();

const page = usePage();
const currentUser = computed(() => (page.props.auth as any)?.user);

// Upvoters Modal State
const showUpvotersModal = ref(false);
const localUpvoters = ref<Upvoter[]>([...(props.upvoters || [])]);

watch(
    () => props.upvoters,
    (val) => {
        localUpvoters.value = [...(val || [])];
    },
);

const handleVoted = ({
    userVote,
}: {
    resourceId: number;
    userVote: number | null;
    voteScore: number;
    upvotes: number;
    downvotes: number;
}) => {
    if (!currentUser.value) {
        return;
    }

    const u = currentUser.value;

    if (userVote === 1) {
        if (!localUpvoters.value.some((x) => x.id === u.id)) {
            localUpvoters.value.unshift({
                id: u.id,
                name: u.name,
                username: u.username,
                image_path: u.image_path,
                image_url: u.image_url,
                institution: u.institution,
                is_verified: u.is_verified,
            });
        }
    } else {
        localUpvoters.value = localUpvoters.value.filter((x) => x.id !== u.id);
    }
};

// Moderation Actions
const isApproving = ref(false);
const isRejecting = ref(false);
const showRejectForm = ref(false);
const rejectReason = ref('');
const showEditModal = ref(false);

const handleApprove = () => {
    if (
        !confirm('Are you sure you want to approve and publish this resource?')
    ) {
        return;
    }

    isApproving.value = true;
    router.post(
        '/admin/resources/approve',
        {
            ids: [props.resource.id],
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isApproving.value = false;
            },
        },
    );
};

const handleReject = () => {
    isRejecting.value = true;
    router.post(
        '/admin/resources/reject',
        {
            ids: [props.resource.id],
            admin_note: rejectReason.value.trim() || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isRejecting.value = false;
                showRejectForm.value = false;
            },
        },
    );
};

// Determine which renderer to mount based on items format
const primaryFormat = computed<'image' | 'video' | 'pdf'>(() => {
    const firstItem = props.resource?.items?.[0];

    if (firstItem?.format === 'video') {
        return 'video';
    }

    if (firstItem?.format === 'pdf') {
        return 'pdf';
    }

    if (firstItem?.format === 'image') {
        return 'image';
    }

    // Fallback based on resource category
    if (props.resource?.resource_type === 'video') {
        return 'video';
    }

    return 'image';
});

const subtitle = computed(() => {
    const parts: string[] = [];

    if (props.resource.node?.name) {
        parts.push(props.resource.node.name);
    }

    if (props.resource.subject?.name) {
        parts.push(props.resource.subject.name);
    }

    return parts.join(' · ');
});
</script>

<template>
    <Head>
        <title>{{ resource.title }}</title>
        <meta
            name="description"
            :content="resource.description || `${resource.title} - HSCStack`"
        />
        <meta property="og:title" :content="`${resource.title} - HSCStack`" />
        <meta
            property="og:description"
            :content="resource.description || `${resource.title} - HSCStack`"
        />
    </Head>

    <div class="mx-auto max-w-5xl px-3 pt-6 pb-24 sm:px-6">
        <!-- Moderation / Ownership Status Banner -->
        <!-- 1. Pending for Moderators: Full Review Controls -->
        <div
            v-if="resource.status === 'pending' && can_moderate"
            class="mb-6 rounded-2xl border border-amber-300 bg-amber-50/90 p-4 shadow-2xs sm:p-5 dark:border-amber-700/60 dark:bg-amber-950/40"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300"
                    >
                        <Clock class="h-5 w-5 animate-pulse" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3
                                class="text-sm font-bold text-amber-900 dark:text-amber-200"
                            >
                                পর্যালোচনার অপেক্ষায় (Awaiting Moderation)
                            </h3>
                            <span
                                class="rounded bg-amber-200/70 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-800/40 dark:text-amber-300"
                            >
                                Pending
                            </span>
                        </div>
                        <p
                            class="mt-0.5 text-xs text-amber-800/80 dark:text-amber-300/80"
                        >
                            এই রিসোর্সটি এখনও অনুমোদিত হয়নি। শুধুমাত্র আপনি ও
                            আপলোডার দেখতে পাচ্ছেন। কনটেন্টটি যাচাই করে অনুমোদন
                            বা বাতিল করুন।
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div
                    v-if="!showRejectForm"
                    class="flex shrink-0 items-center gap-2"
                >
                    <button
                        type="button"
                        @click="showEditModal = true"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-amber-300 bg-white px-3.5 py-2 text-xs font-bold text-amber-900 shadow-2xs transition hover:bg-amber-100/60 dark:border-amber-700 dark:bg-gray-900 dark:text-amber-200 dark:hover:bg-amber-950/40"
                    >
                        <Pencil class="h-3.5 w-3.5" />
                        <span>সম্পাদনা</span>
                    </button>

                    <button
                        type="button"
                        :disabled="isApproving || isRejecting"
                        @click="handleApprove"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-700 disabled:opacity-50"
                    >
                        <Loader2
                            v-if="isApproving"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <Check v-else class="h-3.5 w-3.5 stroke-[2.5]" />
                        <span>অনুমোদন করুন</span>
                    </button>

                    <button
                        type="button"
                        :disabled="isApproving || isRejecting"
                        @click="showRejectForm = true"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3.5 py-2 text-xs font-bold text-rose-700 shadow-2xs transition hover:bg-rose-50 disabled:opacity-50 dark:border-rose-800 dark:bg-gray-900 dark:text-rose-300 dark:hover:bg-rose-950/40"
                    >
                        <X class="h-3.5 w-3.5 stroke-[2.5]" />
                        <span>বাতিল করুন</span>
                    </button>
                </div>
            </div>

            <!-- Inline Rejection Note Form -->
            <div
                v-if="showRejectForm"
                class="mt-4 border-t border-amber-200/80 pt-4 dark:border-amber-800/40"
            >
                <label
                    class="block text-xs font-bold text-amber-900 dark:text-amber-200"
                >
                    বাতিলের কারণ (শিক্ষার্থী দেখতে পাবে):
                </label>
                <div
                    class="mt-1.5 flex flex-col gap-2 sm:flex-row sm:items-center"
                >
                    <input
                        v-model="rejectReason"
                        type="text"
                        placeholder="উদাঃ ঝাপসা ছবি, ভুল বা অসম্পূর্ণ তথ্য..."
                        maxlength="500"
                        class="w-full flex-1 rounded-xl border border-amber-300 bg-white px-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-amber-500 focus:outline-none dark:border-amber-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500"
                    />
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :disabled="isRejecting"
                            @click="handleReject"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-rose-700 disabled:opacity-50"
                        >
                            <Loader2
                                v-if="isRejecting"
                                class="h-3.5 w-3.5 animate-spin"
                            />
                            <span>বাতিল নিশ্চিত করুন</span>
                        </button>
                        <button
                            type="button"
                            @click="showRejectForm = false"
                            class="cursor-pointer rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-amber-100/60 dark:text-gray-400 dark:hover:bg-amber-950/60"
                        >
                            ফেরত
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Pending for Owner: Informational Banner -->
        <div
            v-else-if="resource.status === 'pending' && is_owner"
            class="mb-6 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50/80 p-4 text-xs text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-300"
        >
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300"
            >
                <Clock class="h-4.5 w-4.5" />
            </div>
            <div>
                <p class="font-bold">
                    আপনার রিসোর্সটি পর্যালোচনার জন্য জমা দেওয়া হয়েছে
                </p>
                <p class="text-[11px] opacity-90">
                    এটি বর্তমানে শুধুমাত্র আপনার এবং মডারেটরদের জন্য দৃশ্যমান।
                    মডারেটর অনুমোদন করলে এটি সবার জন্য উন্মুক্ত হবে।
                </p>
            </div>
        </div>

        <!-- 3. Rejected Banner -->
        <div
            v-else-if="resource.status === 'rejected'"
            class="mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-300"
        >
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300"
            >
                <AlertCircle class="h-4.5 w-4.5" />
            </div>
            <div class="flex-1">
                <p class="font-bold">এই রিসোর্সটি বাতিল করা হয়েছে</p>
                <p v-if="resource.admin_note" class="mt-0.5 text-[11px]">
                    <span class="font-bold">কারণ:</span>
                    {{ resource.admin_note }}
                </p>
            </div>
        </div>

        <!-- Title & Header -->
        <header class="mb-6">
            <!-- Context Subtitle: Chapter · Subject -->
            <div
                v-if="subtitle"
                class="mb-2 flex items-center gap-1.5 text-xs font-semibold text-slate-500 sm:text-sm dark:text-gray-400"
            >
                <span class="truncate">{{ subtitle }}</span>
            </div>

            <!-- Title & Back Button -->
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <!-- Clear Back Button -->
                    <Link
                        href="/notes"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-500 shadow-xs transition hover:border-indigo-200 hover:bg-indigo-50/50 hover:text-indigo-600 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                        title="নোট আর্কাইভে ফিরে যান"
                    >
                        <ArrowLeft class="h-4 w-4 stroke-[2.3]" />
                    </Link>

                    <h1
                        class="truncate text-xl font-black tracking-tight text-slate-900 sm:text-2xl md:text-3xl dark:text-gray-100"
                    >
                        {{ resource.title }}
                    </h1>
                </div>

                <!-- Admin Edit Button -->
                <button
                    v-if="can_moderate"
                    type="button"
                    @click="showEditModal = true"
                    class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/90 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:border-indigo-300 hover:bg-indigo-50/50 hover:text-indigo-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                    title="রিসোর্স সম্পাদনা করুন"
                >
                    <Pencil class="h-3.5 w-3.5" />
                    <span>সম্পাদনা</span>
                </button>
            </div>

            <!-- Author Info -->
            <div
                v-if="resource.user"
                class="mt-3 flex items-center gap-2 text-xs text-slate-500 sm:text-sm dark:text-gray-400"
            >
                <span class="font-medium text-slate-400 dark:text-gray-500"
                    >By</span
                >
                <Link
                    :href="`/u/${resource.user.username}`"
                    class="inline-flex items-center gap-2 font-bold text-slate-800 transition hover:text-indigo-600 dark:text-gray-200 dark:hover:text-indigo-400"
                >
                    <img
                        v-if="
                            resource.user.image_url || resource.user.image_path
                        "
                        :src="
                            resource.user.image_url ||
                            '/storage/' + resource.user.image_path
                        "
                        :alt="resource.user.name"
                        class="h-5 w-5 rounded-full border border-slate-200 object-cover dark:border-gray-700"
                    />
                    <div
                        v-else
                        class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-gray-800"
                    >
                        <UserIcon class="h-3 w-3" />
                    </div>
                    <span>{{ resource.user.name }}</span>
                </Link>
            </div>

            <!-- Vote Buttons & Upvoters Stack (Only when approved) -->
            <div
                v-if="!resource.status || resource.status === 'approved'"
                class="mt-4 flex flex-wrap items-center gap-3"
            >
                <ResourceVoteButtons
                    :resource-id="resource.id"
                    :initial-upvotes="resource.upvotes_count || 0"
                    :initial-downvotes="resource.downvotes_count || 0"
                    :initial-score="resource.vote_score || 0"
                    :initial-user-vote="userVote"
                    direction="horizontal"
                    size="md"
                    @voted="handleVoted"
                />

                <!-- Upvoters Avatar Stack & Modal Trigger (Forum Style) -->
                <button
                    v-if="localUpvoters.length > 0"
                    type="button"
                    @click="showUpvotersModal = true"
                    class="group flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800"
                    title="আপভোটকারী শিক্ষার্থীদের তালিকা দেখুন"
                >
                    <div class="flex -space-x-1.5 overflow-hidden">
                        <div
                            v-for="upvoter in localUpvoters.slice(0, 3)"
                            :key="upvoter.id"
                            class="inline-block h-5 w-5 overflow-hidden rounded-full ring-2 ring-white dark:ring-gray-900"
                        >
                            <img
                                v-if="upvoter.image_url || upvoter.image_path"
                                :src="
                                    upvoter.image_url ||
                                    '/storage/' + upvoter.image_path
                                "
                                :alt="upvoter.name"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-indigo-100 text-[9px] font-bold text-indigo-700 uppercase dark:bg-indigo-950 dark:text-indigo-300"
                            >
                                {{ upvoter.name?.charAt(0) || 'U' }}
                            </div>
                        </div>
                    </div>

                    <span
                        class="font-medium text-slate-700 underline-offset-2 group-hover:underline dark:text-gray-300"
                    >
                        {{ localUpvoters.length }}
                        {{ localUpvoters.length === 1 ? 'upvote' : 'upvotes' }}
                    </span>
                </button>
            </div>

            <!-- Author's Note / Description Box -->
            <div
                v-if="resource.description"
                class="mt-4 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 text-sm text-slate-700 dark:border-gray-800 dark:bg-gray-900/60 dark:text-gray-300"
            >
                <p class="leading-relaxed whitespace-pre-line">
                    {{ resource.description }}
                </p>
            </div>
        </header>

        <!-- Dynamic Content Renderer Engine -->
        <main>
            <!-- 1. Video Series Player -->
            <VideoSeriesPlayer
                v-if="primaryFormat === 'video'"
                :items="resource.items || []"
            />

            <!-- 2. PDF Document Viewer -->
            <PdfViewer
                v-else-if="primaryFormat === 'pdf'"
                :items="resource.items || []"
            />

            <!-- 3. Multi-Image Note Reader -->
            <NoteReader v-else :items="resource.items || []" />
        </main>

        <!-- Resource Comments / Discussion Section -->
        <ResourceCommentsSection
            :resource-id="resource.id"
            :resource-author-id="resource.user_id"
            :comments="comments || []"
        />
    </div>

    <!-- Upvoters Modal (Forum Style) -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showUpvotersModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
                @click.self="showUpvotersModal = false"
            >
                <div
                    class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        type="button"
                        @click="showUpvotersModal = false"
                        class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <div class="mb-4 flex items-center gap-2.5">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <ArrowBigUp
                                class="h-5 w-5 fill-indigo-600 text-indigo-600 dark:fill-indigo-400 dark:text-indigo-400"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold text-slate-900 dark:text-gray-100"
                            >
                                আপভোটকারী শিক্ষার্থী
                            </h3>
                            <p
                                class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                            >
                                সাম্প্রতিক ২০ জন আপভোটকারী
                            </p>
                        </div>
                    </div>

                    <div
                        class="no-scrollbar max-h-72 space-y-2 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="upvoter in localUpvoters"
                            :key="upvoter.id"
                            class="flex items-center justify-between gap-3 rounded-xl p-2 transition hover:bg-slate-50 dark:hover:bg-gray-800/60"
                        >
                            <Link
                                :href="`/u/${upvoter.username}`"
                                class="flex min-w-0 flex-1 items-center gap-2.5"
                            >
                                <img
                                    v-if="
                                        upvoter.image_url || upvoter.image_path
                                    "
                                    :src="
                                        upvoter.image_url ||
                                        '/storage/' + upvoter.image_path
                                    "
                                    :alt="upvoter.name"
                                    class="h-8 w-8 shrink-0 rounded-full border border-slate-200 object-cover dark:border-gray-700"
                                />
                                <div
                                    v-else
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    {{ upvoter.name?.charAt(0) || 'U' }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <p
                                            class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                                        >
                                            {{ upvoter.name }}
                                        </p>
                                        <span
                                            v-if="upvoter.is_verified"
                                            class="inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"
                                            title="Verified"
                                        />
                                    </div>
                                    <p
                                        v-if="upvoter.institution"
                                        class="truncate text-[10px] text-slate-400 dark:text-gray-500"
                                    >
                                        {{ upvoter.institution }}
                                    </p>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Edit Resource Modal (for owner/moderator) -->
    <ResourceFormModal
        v-if="can_moderate || is_owner"
        v-model:is-open="showEditModal"
        :resource="resource"
        :subjects="subjects || []"
        @save="() => {}"
    />
</template>
