<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Share2,
    Check,
    Loader2,
    LogIn,
    X,
    Link as LinkIcon,
} from 'lucide-vue-next';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const isMenuOpen = ref(false);
const isLoading = ref(false);
const isCopied = ref(false);
const showAuthModal = ref(false);

const toolbarRef = ref<HTMLElement | null>(null);

const getCsrfToken = (): string => {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
};

const copyToClipboard = async (text: string): Promise<boolean> => {
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);

            return true;
        } catch {
            // Fall back to execCommand
        }
    }

    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        const ok = document.execCommand('copy');
        document.body.removeChild(textArea);

        return ok;
    } catch {
        document.body.removeChild(textArea);

        return false;
    }
};

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const closeMenu = () => {
    isMenuOpen.value = false;
};

const handleCopyShortLink = async () => {
    if (!user.value) {
        closeMenu();
        showAuthModal.value = true;

        return;
    }

    if (isLoading.value) {
        return;
    }

    isLoading.value = true;

    try {
        const res = await fetch('/api/short-urls', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                original_url: window.location.href,
            }),
        });

        const data = await res.json();

        if (res.ok && data.short_url) {
            const ok = await copyToClipboard(data.short_url);

            if (ok) {
                isCopied.value = true;
                setTimeout(() => {
                    isCopied.value = false;
                }, 2000);
            }
        }
    } catch {
        // Silently fail or reset
    } finally {
        isLoading.value = false;
    }
};

const handleClickOutside = (e: MouseEvent) => {
    if (toolbarRef.value && !toolbarRef.value.contains(e.target as Node)) {
        closeMenu();
    }
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
        closeMenu();
        showAuthModal.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <!-- Floating Toolbar (Universally visible across all pages) -->
    <div
        ref="toolbarRef"
        class="fixed right-5 bottom-6 z-40 sm:right-6 sm:bottom-6"
    >
        <div class="relative">
            <!-- Share Popover Menu (Minimalist) -->
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="scale-95 opacity-0 translate-y-1"
                enter-to-class="scale-100 opacity-100 translate-y-0"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="scale-100 opacity-100 translate-y-0"
                leave-to-class="scale-95 opacity-0 translate-y-1"
            >
                <div
                    v-if="isMenuOpen"
                    class="absolute right-0 bottom-full mb-2.5 min-w-[190px] rounded-2xl border border-slate-200/90 bg-white/95 p-1.5 shadow-2xl backdrop-blur-md dark:border-gray-800 dark:bg-gray-900/95"
                >
                    <button
                        @click="handleCopyShortLink"
                        :disabled="isLoading"
                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                        :class="
                            isCopied
                                ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white'
                        "
                    >
                        <Loader2
                            v-if="isLoading"
                            class="h-3.5 w-3.5 animate-spin text-indigo-600 dark:text-indigo-400"
                        />
                        <Check
                            v-else-if="isCopied"
                            class="h-3.5 w-3.5 stroke-[2.5]"
                        />
                        <LinkIcon
                            v-else
                            class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400"
                        />

                        <span class="truncate">
                            {{
                                isLoading
                                    ? 'Shortening link...'
                                    : isCopied
                                      ? 'Link copied!'
                                      : 'Copy short link'
                            }}
                        </span>
                    </button>
                </div>
            </Transition>

            <!-- Main Floating Pill Trigger (High-Contrast, Visible & Compact) -->
            <button
                @click="toggleMenu"
                class="group inline-flex items-center gap-2 rounded-full border border-slate-700/10 bg-slate-900 px-3.5 py-2 text-xs font-bold text-white shadow-xl shadow-slate-900/25 backdrop-blur-md transition-all duration-200 hover:scale-105 hover:bg-slate-800 hover:shadow-2xl active:scale-95 dark:border-white/10 dark:bg-white dark:text-slate-900 dark:shadow-black/40 dark:hover:bg-slate-100"
                :class="{
                    'ring-2 ring-indigo-500/40': isMenuOpen,
                }"
                title="Share this page"
                aria-label="Share this page"
            >
                <Share2
                    class="h-3.5 w-3.5 transition-transform duration-200 group-hover:rotate-12"
                />
                <span class="tracking-tight">Share</span>
            </button>
        </div>
    </div>

    <!-- Minimal Sign-in Dialog for Guests -->
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
                v-if="showAuthModal"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs dark:bg-black/50"
            >
                <div
                    class="relative w-full max-w-xs rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        @click="showAuthModal = false"
                        class="absolute top-3.5 right-3.5 rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <h3
                        class="text-sm font-bold text-slate-900 dark:text-gray-100"
                    >
                        Sign in required
                    </h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                        Please sign in to generate and copy short share links.
                    </p>

                    <div class="mt-4 flex items-center gap-2">
                        <Link
                            :href="`/login?redirect=${encodeURIComponent($page.url)}`"
                            @click="showAuthModal = false"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-slate-900 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                        >
                            <LogIn class="h-3.5 w-3.5" />
                            <span>Sign in</span>
                        </Link>
                        <button
                            @click="showAuthModal = false"
                            class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
