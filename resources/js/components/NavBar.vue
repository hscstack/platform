<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, LayoutDashboard, Home, ChevronDown } from 'lucide-vue-next';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import AppLogo from './AppLogo.vue';

defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const user = computed(() => usePage().props.auth?.user);

const dropdownOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = () => {
    dropdownOpen.value = false;
};

const handleLogout = () => {
    router.post('/logout');
};

const handleClickOutside = (e: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <nav
        class="sticky top-0 z-50 border-b border-slate-200/60 bg-white/80 backdrop-blur-md"
    >
        <div
            class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6"
        >
            <div class="flex items-center gap-2">
                <AppLogo />

                <span
                    v-if="isAdmin"
                    class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold tracking-wider text-slate-400 uppercase"
                >
                    Admin
                </span>
            </div>

            <div class="flex items-center gap-6">
                <Link
                    href="/blogs"
                    class="text-sm font-medium text-slate-600 transition-colors hover:text-slate-900"
                >
                    Blogs
                </Link>

                <!-- Guest: Login link -->
                <Link
                    v-if="!user"
                    href="/admin"
                    class="text-sm font-medium text-slate-600 transition-colors hover:text-slate-900"
                >
                    Login
                </Link>

                <!-- Logged in: User dropdown -->
                <div v-else ref="dropdownRef" class="relative">
                    <button
                        @click="toggleDropdown"
                        class="flex items-center gap-2 rounded-full py-1 pr-1 pl-1 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900"
                    >
                        <img
                            v-if="user.image_url"
                            :src="user.image_url"
                            :alt="user.name"
                            class="h-7 w-7 rounded-full object-cover"
                        />
                        <span
                            v-else
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white"
                        >
                            {{ user.name.charAt(0).toUpperCase() }}
                        </span>
                        <span class="hidden sm:inline">{{ user.name }}</span>
                        <ChevronDown
                            class="h-3.5 w-3.5 text-slate-400 transition-transform"
                            :class="{ 'rotate-180': dropdownOpen }"
                        />
                    </button>

                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="scale-95 opacity-0"
                        enter-to-class="scale-100 opacity-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="scale-100 opacity-100"
                        leave-to-class="scale-95 opacity-0"
                    >
                        <div
                            v-if="dropdownOpen"
                            class="absolute right-0 mt-2 w-48 origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg"
                        >
                            <div class="border-b border-slate-100 px-3 py-2">
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ user.name }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ user.email }}
                                </p>
                            </div>

                            <Link
                                :href="isAdmin ? '/' : '/admin'"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                                @click="closeDropdown"
                            >
                                <component
                                    :is="isAdmin ? Home : LayoutDashboard"
                                    class="h-4 w-4 text-slate-400"
                                />
                                {{ isAdmin ? 'Home' : 'Staff Panel' }}
                            </Link>

                            <button
                                @click="handleLogout"
                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                            >
                                <LogOut class="h-4 w-4" />
                                Logout
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </nav>
</template>
