<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    Check,
    X,
    User as UserIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AuthModal from '@/components/AuthModal.vue';
import BreadcrumbNav from '@/components/BreadcrumbNav.vue';
import NoteReader from '@/components/resources/NoteReader.vue';
import PdfViewer from '@/components/resources/PdfViewer.vue';
import VideoSeriesPlayer from '@/components/resources/VideoSeriesPlayer.vue';
import { useAuth } from '@/lib/useAuth';

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
    created_at?: string;
    updated_at?: string;
    items?: ResourceItem[];
    user?: {
        id: number;
        name: string;
        username: string;
        image_path?: string | null;
        image_url?: string | null;
    } | null;
}

const props = defineProps<{
    resource: ResourceData;
    subject?: {
        id: number;
        name: string;
        slug: string;
    } | null;
    breadcrumb?: Array<{ name: string; slug: string }>;
    isCompleted?: boolean;
    completionsCount?: number;
    completers?: Array<{
        id: number;
        name: string;
        username: string;
        image_path?: string | null;
        image_url?: string | null;
        institution?: string | null;
    }>;
}>();

const { requireAuth } = useAuth();
const showCompletersModal = ref(false);

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

// Chapter link for direct return
const chapterUrl = computed(() => {
    const subjectSlug = props.subject?.slug ? `/${props.subject.slug}` : '';

    if (!props.breadcrumb || props.breadcrumb.length === 0) {
        return subjectSlug || '/';
    }

    const path = props.breadcrumb.map((c) => c.slug).join('/');

    return `${subjectSlug}/${path}`;
});

// Toggle Mark as Completed
const toggleComplete = () => {
    requireAuth('পড়া সম্পন্ন করতে সাইন ইন করুন', () => {
        router.post(
            `/resources/${props.resource.id}/complete`,
            {},
            { preserveScroll: true },
        );
    });
};
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
        <!-- Breadcrumb Navigation -->
        <header class="mb-6">
            <BreadcrumbNav
                :breadcrumb="breadcrumb"
                :subject="subject"
                class="mb-3"
            />

            <!-- Title & Top Mark-as-Done Action -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <!-- Clear Back Button -->
                    <Link
                        :href="chapterUrl"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-500 shadow-xs transition hover:border-indigo-200 hover:bg-indigo-50/50 hover:text-indigo-600 active:scale-95 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
                        title="অধ্যায়ে ফিরে যান"
                    >
                        <ArrowLeft class="h-4 w-4 stroke-[2.3]" />
                    </Link>

                    <h1
                        class="truncate text-xl font-black tracking-tight text-slate-900 sm:text-2xl md:text-3xl dark:text-gray-100"
                    >
                        {{ resource.title }}
                    </h1>
                </div>

                <!-- Mark as Done Button at the Top -->
                <div class="flex shrink-0 items-center gap-3">
                    <button
                        type="button"
                        @click="toggleComplete"
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition active:scale-95 sm:text-sm"
                        :class="
                            isCompleted
                                ? 'bg-emerald-600 text-white shadow-xs hover:bg-emerald-700 dark:bg-emerald-500'
                                : 'border border-slate-200/90 bg-white text-slate-700 shadow-2xs hover:border-slate-300 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'
                        "
                    >
                        <CheckCircle2 v-if="isCompleted" class="h-4 w-4" />
                        <Check v-else class="h-4 w-4" />
                        <span>{{
                            isCompleted ? 'সম্পন্ন হয়েছে' : 'পড়া সম্পন্ন'
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- Author & Completions Row -->
            <div
                class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-semibold text-slate-500 dark:text-gray-400"
            >
                <!-- Author with resolved image_url -->
                <div v-if="resource.user" class="flex items-center gap-2">
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

                    <Link
                        :href="`/users/${resource.user.username}`"
                        class="font-bold text-slate-700 hover:text-indigo-600 hover:underline dark:text-gray-200 dark:hover:text-indigo-400"
                    >
                        {{ resource.user.name }}
                    </Link>
                </div>

                <!-- Clickable Completions Count -->
                <button
                    v-if="completionsCount && completionsCount > 0"
                    type="button"
                    @click="showCompletersModal = true"
                    class="inline-flex cursor-pointer items-center gap-1.5 text-slate-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                    title="সম্পন্নকারী শিক্ষার্থীদের তালিকা দেখুন"
                >
                    <span>•</span>
                    <span class="underline decoration-dotted underline-offset-2"
                        >{{ completionsCount }} জন শিক্ষার্থী সম্পন্ন
                        করেছেন</span
                    >
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
    </div>

    <!-- Student Completers Modal -->
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
                v-if="showCompletersModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
                @click.self="showCompletersModal = false"
            >
                <div
                    class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        type="button"
                        @click="showCompletersModal = false"
                        class="absolute top-3.5 right-3.5 cursor-pointer rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <div class="mb-4 flex items-center gap-2.5">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="h-4 w-4 stroke-[2.2]" />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold text-slate-900 dark:text-gray-100"
                            >
                                সম্পন্নকারী শিক্ষার্থী তালিকা
                            </h3>
                            <p
                                class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                            >
                                {{ completionsCount }} জন শিক্ষার্থী পড়া সম্পন্ন
                                করেছেন
                            </p>
                        </div>
                    </div>

                    <div
                        class="-mx-1 max-h-72 divide-y divide-slate-100 overflow-y-auto px-1 dark:divide-gray-800/80"
                    >
                        <div
                            v-if="!completers || completers.length === 0"
                            class="py-6 text-center text-xs text-slate-500 dark:text-gray-400"
                        >
                            এখনো কেউ সম্পন্ন করেননি।
                        </div>

                        <Link
                            v-for="completer in completers"
                            :key="completer.id"
                            :href="`/users/${completer.username}`"
                            class="flex items-center gap-3 rounded-xl px-2 py-2.5 transition hover:bg-slate-50 dark:hover:bg-gray-800/50"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-50 font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                            >
                                <img
                                    v-if="
                                        completer.image_url ||
                                        completer.image_path
                                    "
                                    :src="
                                        completer.image_url ||
                                        '/storage/' + completer.image_path
                                    "
                                    :alt="completer.name"
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-else
                                    class="text-xs font-bold uppercase"
                                >
                                    {{ completer.name?.charAt(0) || 'U' }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-xs font-bold text-slate-900 dark:text-gray-100"
                                >
                                    {{ completer.name }}
                                </p>
                                <p
                                    v-if="completer.institution"
                                    class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    {{ completer.institution }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <AuthModal />
</template>
