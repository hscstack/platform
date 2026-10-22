<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, User as UserIcon } from 'lucide-vue-next';
import { computed } from 'vue';

import BreadcrumbNav from '@/components/BreadcrumbNav.vue';
import NoteReader from '@/components/resources/NoteReader.vue';
import PdfViewer from '@/components/resources/PdfViewer.vue';
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
}>();

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

            <!-- Title & Navigation -->
            <div class="flex items-center gap-3">
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

            <!-- Author Row -->
            <div
                v-if="resource.user"
                class="mt-3 flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-gray-400"
            >
                <img
                    v-if="resource.user.image_url || resource.user.image_path"
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
</template>
