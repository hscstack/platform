<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    User,
    AtSign,
    GraduationCap,
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Camera,
    Loader2,
    Heart,
} from 'lucide-vue-next';
import { computed, onUnmounted, ref } from 'vue';
import VerifiedBadge from '@/components/VerifiedBadge.vue';
import { compressImage } from '@/lib/imageCompression';

interface OnboardingUser {
    google_id: string;
    email: string;
    name: string;
    avatar?: string | null;
}

interface Contributor {
    id: number;
    name: string;
    username: string;
    image_path?: string | null;
    image_url?: string | null;
    institution?: string | null;
    is_verified?: boolean;
}

const props = defineProps<{
    user?: OnboardingUser;
    suggestedContributors?: Contributor[];
}>();

const page = usePage();
const flashError = computed(() => (page.props as any).flash?.error);

const currentStep = ref<1 | 2>(1);

const form = useForm<{
    name: string;
    username: string;
    school: string;
    image: File | null;
    appreciations: number[];
}>({
    name: props.user?.name || '',
    username: '',
    school: '',
    image: null,
    appreciations: (props.suggestedContributors || [])
        .filter((_, index) => index === 0 || index === 2)
        .map((c) => c.id),
});

const previewUrl = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isCompressing = ref(false);

const contributors = computed(() => props.suggestedContributors || []);
const hasContributors = computed(() => contributors.value.length > 0);

const handleImageChange = async (e: Event) => {
    const target = e.target as HTMLInputElement;
    const rawFile = target.files?.[0];

    if (rawFile) {
        form.errors.image = '';
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!allowed.includes(rawFile.type)) {
            form.errors.image = 'অনুমোদিত ফরম্যাট: JPG, PNG, WEBP।';

            return;
        }

        try {
            isCompressing.value = true;
            let resultFile: File;

            try {
                resultFile = await compressImage(rawFile, {
                    maxWidth: 512,
                    maxHeight: 512,
                    quality: 0.85,
                });
            } catch {
                resultFile = rawFile;
            }

            if (resultFile.size > 5 * 1024 * 1024) {
                form.errors.image = 'ছবিটির আকার ৫MB এর চেয়ে কম হতে হবে।';

                return;
            }

            form.image = resultFile;

            if (previewUrl.value) {
                URL.revokeObjectURL(previewUrl.value);
            }

            previewUrl.value = URL.createObjectURL(resultFile);
        } finally {
            isCompressing.value = false;
        }
    }
};

const triggerFileInput = () => {
    if (isCompressing.value) {
        return;
    }

    fileInputRef.value?.click();
};

const removeCustomImage = () => {
    if (isCompressing.value) {
        return;
    }

    form.image = null;
    form.errors.image = '';

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

onUnmounted(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

const toggleAppreciation = (userId: number) => {
    const index = form.appreciations.indexOf(userId);

    if (index > -1) {
        form.appreciations.splice(index, 1);
    } else {
        form.appreciations.push(userId);
    }
};

const selectAllContributors = () => {
    if (form.appreciations.length === contributors.value.length) {
        form.appreciations = [];
    } else {
        form.appreciations = contributors.value.map((c) => c.id);
    }
};

const goToStep2 = () => {
    form.errors.name = '';
    form.errors.username = '';
    form.errors.school = '';

    if (!form.name.trim()) {
        form.errors.name = 'Please enter your full name.';

        return;
    }

    if (!form.username.trim()) {
        form.errors.username = 'Please choose a username.';

        return;
    }

    if (!/^[a-zA-Z0-9_]{3,30}$/.test(form.username.trim())) {
        form.errors.username =
            'Username must be 3-30 characters (letters, numbers, underscores).';

        return;
    }

    if (!form.school.trim()) {
        form.errors.school = 'Please enter your institution name.';

        return;
    }

    if (!hasContributors.value) {
        submit();

        return;
    }

    currentStep.value = 2;
};

const submit = () => {
    if (isCompressing.value || form.errors.image) {
        return;
    }

    form.post('/onboarding', {
        forceFormData: true,
        onError: (errors) => {
            if (
                errors.name ||
                errors.username ||
                errors.school ||
                errors.image
            ) {
                currentStep.value = 1;
            }
        },
    });
};

const getContributorAvatar = (contributor: Contributor) => {
    if (contributor.image_url) {
        return contributor.image_url;
    }

    if (contributor.image_path) {
        return `/storage/${contributor.image_path}`;
    }

    return null;
};
</script>

<template>
    <Head>
        <title>Complete Your Profile - HSCStack</title>
        <meta
            name="description"
            content="Set up your profile and meet HSCStack community contributors."
        />
    </Head>

    <div
        class="relative z-10 flex min-h-[85vh] items-center justify-center px-4 py-8 sm:px-6 sm:py-10"
    >
        <div class="w-full max-w-lg">
            <!-- Header -->
            <div class="mb-5 text-center">
                <h1
                    class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl dark:text-gray-100"
                >
                    {{
                        currentStep === 1
                            ? 'Almost there!'
                            : 'Appreciate Others'
                    }}
                </h1>
                <p
                    class="mt-1.5 text-xs font-semibold text-slate-500 dark:text-gray-400"
                >
                    {{
                        currentStep === 1
                            ? 'অ্যাকাউন্ট তৈরি সম্পন্ন করতে আপনার তথ্যগুলো নিশ্চিত করুন'
                            : "আমাদের কমিউনিটিতে 'Appreciate' হলো Follow এর বিকল্প — Appreciate করে অন্যদের সমর্থন জানান"
                    }}
                </p>
            </div>

            <!-- Flash Error Alert -->
            <div
                v-if="flashError"
                class="mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-xs font-medium text-rose-700 backdrop-blur-sm dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400"
            >
                <AlertCircle
                    class="h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400"
                />
                <div class="flex-1">{{ flashError }}</div>
            </div>

            <!-- Main Card -->
            <div
                class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-[0_20px_50px_rgba(8,11,46,0.08)] backdrop-blur-xl sm:p-8 dark:border-gray-800 dark:bg-gray-900/90 dark:shadow-[0_20px_50px_rgba(0,0,0,0.4)]"
            >
                <!-- STEP 1: Profile Information -->
                <div v-show="currentStep === 1">
                    <!-- Connected Google Account Badge & Avatar Upload -->
                    <div
                        v-if="props.user?.email"
                        class="mb-6 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-gray-800 dark:bg-gray-800/40"
                    >
                        <div class="flex items-center gap-3.5">
                            <!-- Avatar with upload trigger overlay -->
                            <div class="group relative shrink-0">
                                <div
                                    v-if="isCompressing"
                                    class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-dashed border-indigo-400 bg-indigo-50 dark:bg-indigo-950/40"
                                >
                                    <Loader2
                                        class="h-5 w-5 animate-spin text-indigo-600 dark:text-indigo-400"
                                    />
                                </div>
                                <img
                                    v-else-if="previewUrl || props.user.avatar"
                                    :src="previewUrl || props.user.avatar!"
                                    :alt="props.user.name"
                                    class="h-14 w-14 rounded-full border-2 border-indigo-500/20 object-cover shadow-xs dark:border-indigo-400/30"
                                />
                                <div
                                    v-else
                                    class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100 text-lg font-bold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300"
                                >
                                    {{
                                        props.user.name
                                            ?.charAt(0)
                                            ?.toUpperCase() || 'U'
                                    }}
                                </div>

                                <!-- Camera Overlay Button -->
                                <button
                                    type="button"
                                    @click="triggerFileInput"
                                    :disabled="isCompressing"
                                    class="absolute -right-1 -bottom-1 flex h-6 w-6 cursor-pointer items-center justify-center rounded-full bg-indigo-600 text-white shadow-md transition hover:scale-110 hover:bg-indigo-700 active:scale-95 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                    title="Upload custom photo"
                                >
                                    <Camera class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <!-- Email & Info -->
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-xs font-bold text-slate-800 dark:text-gray-200"
                                >
                                    {{ props.user.email }}
                                </p>
                                <p
                                    class="text-[11px] text-slate-400 dark:text-gray-500"
                                >
                                    Connected via Google
                                </p>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="triggerFileInput"
                                        :disabled="isCompressing"
                                        class="cursor-pointer text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 hover:underline disabled:opacity-50 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        {{
                                            isCompressing
                                                ? 'Optimizing...'
                                                : previewUrl
                                                  ? 'Change Photo'
                                                  : 'Upload Photo'
                                        }}
                                    </button>
                                    <span
                                        v-if="previewUrl"
                                        class="text-slate-300 dark:text-gray-600"
                                        >•</span
                                    >
                                    <button
                                        v-if="previewUrl"
                                        type="button"
                                        @click="removeCustomImage"
                                        :disabled="isCompressing"
                                        class="cursor-pointer text-[11px] font-medium text-rose-500 hover:text-rose-600 hover:underline disabled:opacity-50 dark:text-rose-400"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <!-- Hidden File Input -->
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                                @change="handleImageChange"
                            />
                        </div>

                        <p
                            v-if="form.errors.image"
                            class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400"
                        >
                            {{ form.errors.image }}
                        </p>
                    </div>

                    <!-- Onboarding Form Inputs -->
                    <form
                        @submit.prevent="
                            hasContributors ? goToStep2() : submit()
                        "
                        class="space-y-4"
                    >
                        <!-- Full Name -->
                        <div>
                            <label
                                for="name"
                                class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                Full Name <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-gray-500"
                                >
                                    <User class="h-4 w-4" />
                                </div>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    id="name"
                                    required
                                    placeholder="Your full name"
                                    :disabled="form.processing"
                                    class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pr-3.5 pl-10 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-50 disabled:text-slate-500 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/20 dark:disabled:bg-gray-800/50 dark:disabled:text-gray-400"
                                    :class="{
                                        'border-rose-500 focus:ring-rose-500/20 dark:border-rose-500 dark:focus:border-rose-400 dark:focus:ring-rose-400/20':
                                            form.errors.name,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.name"
                                class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Username -->
                        <div>
                            <label
                                for="username"
                                class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                Username <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-gray-500"
                                >
                                    <AtSign class="h-4 w-4" />
                                </div>
                                <input
                                    v-model="form.username"
                                    type="text"
                                    id="username"
                                    required
                                    placeholder="your_username"
                                    :disabled="form.processing"
                                    class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pr-3.5 pl-10 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-50 disabled:text-slate-500 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/20 dark:disabled:bg-gray-800/50 dark:disabled:text-gray-400"
                                    :class="{
                                        'border-rose-500 focus:ring-rose-500/20 dark:border-rose-500 dark:focus:border-rose-400 dark:focus:ring-rose-400/20':
                                            form.errors.username,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.username"
                                class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                            >
                                {{ form.errors.username }}
                            </p>
                            <p
                                v-else
                                class="mt-1 text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                Letters, numbers, and underscores (3–30 chars).
                            </p>
                        </div>

                        <!-- School / Institution -->
                        <div>
                            <label
                                for="school"
                                class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                            >
                                School / College / Institution
                                <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-gray-500"
                                >
                                    <GraduationCap class="h-4 w-4" />
                                </div>
                                <input
                                    v-model="form.school"
                                    type="text"
                                    id="school"
                                    required
                                    placeholder="e.g., Notre Dame College, Dhaka College, BUET"
                                    :disabled="form.processing"
                                    class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pr-3.5 pl-10 text-sm text-slate-900 transition outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-50 disabled:text-slate-500 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/20 dark:disabled:bg-gray-800/50 dark:disabled:text-gray-400"
                                    :class="{
                                        'border-rose-500 focus:ring-rose-500/20 dark:border-rose-500 dark:focus:border-rose-400 dark:focus:ring-rose-400/20':
                                            form.errors.school,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.school"
                                class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                            >
                                {{ form.errors.school }}
                            </p>
                        </div>

                        <!-- Next / Submit Button -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="form.processing || isCompressing"
                                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-3.5 text-sm font-bold text-white shadow-xs transition-all hover:bg-indigo-700 hover:shadow-md active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                <Loader2
                                    v-if="form.processing"
                                    class="h-4 w-4 animate-spin"
                                />
                                <span>
                                    {{
                                        form.processing
                                            ? 'Please wait...'
                                            : 'Continue'
                                    }}
                                </span>
                                <ArrowRight
                                    v-if="!form.processing"
                                    class="h-4 w-4"
                                />
                            </button>
                        </div>
                    </form>
                </div>

                <!-- STEP 2: Appreciate Contributors -->
                <div v-show="currentStep === 2" class="space-y-4">
                    <!-- Top control row: Count & Select All -->
                    <div
                        class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-gray-800"
                    >
                        <div
                            class="text-xs font-semibold text-slate-600 dark:text-gray-300"
                        >
                            <span
                                class="font-bold text-indigo-600 dark:text-indigo-400"
                                >{{ form.appreciations.length }}</span
                            >
                            of {{ contributors.length }} appreciated
                        </div>

                        <button
                            type="button"
                            @click="selectAllContributors"
                            class="cursor-pointer text-xs font-bold text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            {{
                                form.appreciations.length ===
                                contributors.length
                                    ? 'Deselect All'
                                    : 'Appreciate All'
                            }}
                        </button>
                    </div>

                    <!-- Contributors List -->
                    <div class="space-y-2.5">
                        <div
                            v-for="contributor in contributors"
                            :key="contributor.id"
                            class="group relative flex items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-3 shadow-2xs transition-all hover:border-slate-300 sm:px-3.5 sm:py-3 dark:border-gray-800 dark:bg-gray-900/60 dark:hover:border-gray-700"
                        >
                            <!-- Contributor Info -->
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="h-10 w-10 shrink-0 overflow-hidden rounded-full ring-2 ring-slate-100 sm:h-11 sm:w-11 dark:ring-gray-800"
                                >
                                    <img
                                        v-if="getContributorAvatar(contributor)"
                                        :src="
                                            getContributorAvatar(contributor)!
                                        "
                                        :alt="contributor.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-500 to-indigo-600 text-xs font-bold text-white uppercase sm:text-sm"
                                    >
                                        {{
                                            contributor.name
                                                ?.charAt(0)
                                                ?.toUpperCase() || 'U'
                                        }}
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <p
                                            class="truncate text-xs font-bold text-slate-900 sm:text-sm dark:text-gray-100"
                                        >
                                            {{ contributor.name }}
                                        </p>
                                        <VerifiedBadge
                                            v-if="contributor.is_verified"
                                            class="shrink-0"
                                        />
                                    </div>
                                    <p
                                        v-if="contributor.institution"
                                        class="mt-0.5 truncate text-[11px] font-medium text-slate-500 dark:text-gray-400"
                                    >
                                        {{ contributor.institution }}
                                    </p>
                                </div>
                            </div>

                            <!-- Heart Toggle Button (matching /u/profile) -->
                            <button
                                type="button"
                                @click="toggleAppreciation(contributor.id)"
                                class="group/btn inline-flex h-8.5 shrink-0 cursor-pointer items-center gap-1.5 rounded-xl px-3 text-xs font-bold transition-all duration-150 select-none active:scale-95 sm:h-9 sm:px-3.5"
                                :class="[
                                    form.appreciations.includes(contributor.id)
                                        ? 'border border-rose-200 bg-rose-50 text-rose-600 shadow-2xs hover:bg-rose-100/80 dark:border-rose-900/60 dark:bg-rose-950/60 dark:text-rose-400 dark:hover:bg-rose-950/90'
                                        : 'border border-slate-200 bg-white text-slate-700 shadow-2xs hover:border-slate-300 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-gray-600 dark:hover:bg-gray-700/70',
                                ]"
                            >
                                <Heart
                                    class="h-3.5 w-3.5 transition-transform group-hover/btn:scale-110"
                                    :class="[
                                        form.appreciations.includes(
                                            contributor.id,
                                        )
                                            ? 'fill-rose-500 text-rose-500 dark:fill-rose-400 dark:text-rose-400'
                                            : 'stroke-[2.2] text-slate-400 group-hover/btn:text-rose-500 dark:text-gray-400 dark:group-hover/btn:text-rose-400',
                                    ]"
                                />
                                <span>{{
                                    form.appreciations.includes(contributor.id)
                                        ? 'Appreciated'
                                        : 'Appreciate'
                                }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Appreciations validation warning -->
                    <p
                        v-if="form.errors.appreciations"
                        class="text-xs font-medium text-rose-600 dark:text-rose-400"
                    >
                        {{ form.errors.appreciations }}
                    </p>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="button"
                            @click="currentStep = 1"
                            :disabled="form.processing"
                            class="flex items-center justify-center gap-1.5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-xs font-bold text-slate-700 transition hover:bg-slate-100 active:scale-[0.98] disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/80"
                        >
                            <ArrowLeft class="h-3.5 w-3.5" />
                            <span>Back</span>
                        </button>

                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing"
                            class="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-3.5 text-sm font-bold text-white shadow-xs transition-all hover:bg-indigo-700 hover:shadow-md active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin"
                            />
                            <span>{{
                                form.processing
                                    ? 'Creating Account...'
                                    : form.appreciations.length > 0
                                      ? 'Appreciate & Create Account'
                                      : 'Create Account'
                            }}</span>
                        </button>
                    </div>
                </div>

                <!-- Terms & Privacy subtext -->
                <div
                    class="mt-6 border-t border-slate-100 pt-4 text-center dark:border-gray-800"
                >
                    <p
                        class="text-[11px] leading-relaxed text-slate-400 dark:text-gray-500"
                    >
                        অ্যাকাউন্ট তৈরির মাধ্যমে আপনি আমাদের
                        <Link
                            href="/terms-service"
                            class="font-medium text-slate-600 underline decoration-slate-300 hover:text-slate-900 dark:text-gray-400 dark:decoration-gray-600 dark:hover:text-gray-200"
                        >
                            Terms of Service
                        </Link>
                        ও
                        <Link
                            href="/privacy-policy"
                            class="font-medium text-slate-600 underline decoration-slate-300 hover:text-slate-900 dark:text-gray-400 dark:decoration-gray-600 dark:hover:text-gray-200"
                        >
                            Privacy Policy </Link
                        >-তে সম্মতি দিচ্ছেন।
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
