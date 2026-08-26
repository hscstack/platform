<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { UserPlus, Loader2, Save } from 'lucide-vue-next';
import { watch } from 'vue';

const props = defineProps({
    user: Object,
    permissions: Array,
});

const roles = [
    { value: 'admin', label: 'Admin' },
    { value: 'editor', label: 'Editor' },
    { value: 'manager', label: 'Manager' },
];

const availablePermissions = props.permissions;

const form = useForm({
    _method: props.user ? 'PATCH' : 'POST',
    name: props.user?.name || '',
    username: props.user?.username || '',
    email: props.user?.email || '',
    role: props.user?.roles?.[0]?.name || 'manager',
    permissions: props.user?.permissions?.map((p) => p.name) || ['view admin'],

    file: null,
    about: props.user?.about || '',
    title: props.user?.title || '',
    institution: props.user?.institution || '',
    facebook: props.user?.facebook || '',
    github: props.user?.github || '',
    instagram: props.user?.instagram || '',
});

watch(
    () => form.role,
    (newRole) => {
        if (newRole !== 'editor') {
            form.permissions = [];
        } else if (!form.permissions.includes('view admin')) {
            form.permissions.push('view admin');
        }
    },
);

const goBack = () => {
    window.history.back();
};

const submitForm = () => {
    if (props.user) {
        form.post(`/admin/users/${props.user.id}`, {
            preserveScroll: true,
        });
    } else {
        form.post('/admin/users', {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="user ? `Edit ${user.name}` : 'Create User'" />

    <div
        class="mobile-deep-border:p-4 flex min-h-full w-full flex-col justify-start bg-slate-50 p-4 lg:p-10 dark:bg-gray-950"
    >
        <div
            class="w-full rounded-2xl border border-gray-300 bg-white p-5 shadow-xs md:p-10 dark:border-gray-600 dark:bg-gray-900"
        >
            <div
                class="mb-8 flex flex-col justify-between gap-4 border-b border-gray-300 pb-6 sm:flex-row sm:items-center dark:border-gray-600"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-slate-900 dark:text-gray-100"
                    >
                        {{ props.user ? 'Edit' : 'Create New' }} User
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                        {{
                            props.user
                                ? 'Update team member details and system access rules.'
                                : 'Add a new team member and assign their system access role.'
                        }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="max-w-3xl space-y-6">
                <!-- Base fields section (Name, Username, Email) -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <label
                            for="name"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >Full Name</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            id="name"
                            placeholder="John Doe"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                            :class="
                                form.errors.name
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600'
                            "
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="username"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >Username</label
                        >
                        <div class="relative">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-semibold text-slate-400 dark:text-gray-500"
                            >
                                @
                            </span>
                            <input
                                v-model="form.username"
                                type="text"
                                id="username"
                                placeholder="johndoe"
                                :disabled="form.processing"
                                class="w-full rounded-lg border py-2.5 pr-4 pl-8 transition outline-none disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                                :class="
                                    form.errors.username
                                        ? 'border-rose-500 focus:ring-rose-500/20'
                                        : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600'
                                "
                            />
                        </div>
                        <p
                            v-if="form.errors.username"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.username }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="email"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >Email Address</label
                        >
                        <input
                            v-model="form.email"
                            type="email"
                            id="email"
                            placeholder="johndoe@example.com"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                            :class="
                                form.errors.email
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600'
                            "
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>
                </div>

                <!-- Role Selection -->
                <div>
                    <div>
                        <label
                            for="role"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >System Role</label
                        >
                        <select
                            id="role"
                            v-model="form.role"
                            :disabled="form.processing"
                            class="w-full rounded-lg border bg-white px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                            :class="
                                form.errors.role
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600'
                            "
                        >
                            <option
                                v-for="role in roles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.role"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.role }}
                        </p>
                    </div>
                </div>

                <!-- DYNAMIC EDITOR PERMISSIONS SECTION -->
                <div
                    v-if="form.role === 'editor'"
                    class="animate-fadeIn space-y-3 rounded-xl border border-blue-200 bg-blue-50/30 p-5 transition-all dark:border-blue-500/30 dark:bg-blue-500/10"
                >
                    <div>
                        <h3
                            class="text-sm font-semibold text-slate-900 dark:text-gray-100"
                        >
                            Editor Permissions
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Select the features this editor is authorized to
                            modify.
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-3 pt-2 sm:grid-cols-2 md:grid-cols-3"
                    >
                        <div
                            v-for="permission in availablePermissions"
                            :key="permission.name"
                            class="flex items-start gap-2.5 rounded-lg border border-gray-200 bg-white p-3 shadow-xs dark:border-gray-700 dark:bg-gray-900"
                        >
                            <input
                                type="checkbox"
                                :id="`perm-${permission.name}`"
                                :value="permission.name"
                                v-model="form.permissions"
                                :disabled="
                                    form.processing ||
                                    permission.name === 'view admin'
                                "
                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-60 dark:border-gray-600"
                            />
                            <label
                                :for="`perm-${permission.name}`"
                                class="cursor-pointer text-xs font-medium text-slate-700 select-none disabled:cursor-not-allowed dark:text-gray-300"
                                :class="{
                                    'text-slate-400 dark:text-gray-500':
                                        permission.name === 'view admin',
                                }"
                            >
                                {{ permission.name }}
                                <span
                                    v-if="permission.name === 'view admin'"
                                    class="block text-[10px] font-normal text-slate-400 dark:text-gray-500"
                                    >(Required)</span
                                >
                            </label>
                        </div>
                    </div>
                    <p
                        v-if="form.errors.permissions"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.permissions }}
                    </p>
                </div>

                <!-- Profile Meta details section -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label
                            for="file"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >Profile Image</label
                        >
                        <input
                            type="file"
                            id="file"
                            accept="image/*"
                            @change="form.file = $event.target.files[0]"
                            :disabled="form.processing"
                            class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-500 transition outline-none file:mr-4 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 file:transition hover:file:bg-slate-200 disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:text-gray-400 dark:file:bg-gray-800 dark:file:text-gray-300 dark:hover:file:bg-gray-700 dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                            :class="
                                form.errors.file
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600'
                            "
                        />
                        <p
                            v-if="form.errors.file"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.file }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="title"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >Title</label
                        >
                        <input
                            v-model="form.title"
                            type="text"
                            id="title"
                            placeholder="e.g. Lead Developer, Professor"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                            :class="
                                form.errors.title
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600'
                            "
                        />
                        <p
                            v-if="form.errors.title"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label
                            for="institution"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >Institution</label
                        >
                        <input
                            v-model="form.institution"
                            type="text"
                            id="institution"
                            placeholder="University / Company"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                            :class="
                                form.errors.institution
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600'
                            "
                        />
                        <p
                            v-if="form.errors.institution"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.institution }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="facebook"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >Facebook</label
                        >
                        <input
                            v-model="form.facebook"
                            type="text"
                            id="facebook"
                            placeholder="Facebook profile URL"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                            :class="
                                form.errors.facebook
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600'
                            "
                        />
                        <p
                            v-if="form.errors.facebook"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.facebook }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label
                            for="github"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >GitHub</label
                        >
                        <input
                            v-model="form.github"
                            type="text"
                            id="github"
                            placeholder="GitHub profile URL"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                            :class="
                                form.errors.github
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600'
                            "
                        />
                        <p
                            v-if="form.errors.github"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.github }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="instagram"
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >Instagram</label
                        >
                        <input
                            v-model="form.instagram"
                            type="text"
                            id="instagram"
                            placeholder="Instagram profile URL"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                            :class="
                                form.errors.instagram
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600'
                            "
                        />
                        <p
                            v-if="form.errors.instagram"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.instagram }}
                        </p>
                    </div>
                </div>

                <div>
                    <label
                        for="about"
                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >About</label
                    >
                    <textarea
                        v-model="form.about"
                        id="about"
                        rows="4"
                        placeholder="Short bio..."
                        :disabled="form.processing"
                        class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:disabled:bg-gray-800 dark:disabled:text-gray-400"
                        :class="
                            form.errors.about
                                ? 'border-rose-500 focus:ring-rose-500/20'
                                : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600'
                        "
                    ></textarea>
                    <p
                        v-if="form.errors.about"
                        class="mt-1 text-sm text-rose-600"
                    >
                        {{ form.errors.about }}
                    </p>
                </div>

                <!-- Role Permissions Reference Card -->
                <div
                    class="space-y-3.5 rounded-xl border border-gray-300 bg-slate-50/50 p-4.5 dark:border-gray-600 dark:bg-gray-800/50"
                >
                    <h4
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400"
                    >
                        Role Permissions Reference
                    </h4>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="space-y-1 border-l-2 border-red-500 pl-3">
                            <div class="text-xs font-bold text-red-700">
                                Admin
                            </div>
                            <div
                                class="text-xs leading-relaxed text-slate-600 dark:text-gray-400"
                            >
                                Superuser control. Automatically has all
                                permissions system-wide.
                            </div>
                        </div>
                        <div
                            class="space-y-1 border-l-2 border-purple-500 pl-3"
                        >
                            <div class="text-xs font-bold text-purple-700">
                                Editor
                            </div>
                            <div
                                class="text-xs leading-relaxed text-slate-600 dark:text-gray-400"
                            >
                                Customizable access. Permissions can be
                                checked/unchecked explicitly above.
                            </div>
                        </div>
                        <div class="space-y-1 border-l-2 border-amber-500 pl-3">
                            <div class="text-xs font-bold text-amber-700">
                                Manager
                            </div>
                            <div
                                class="text-xs leading-relaxed text-slate-600 dark:text-gray-400"
                            >
                                Dashboard view. Basic view access with no
                                modification capabilities.
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end space-x-3 border-t border-gray-300 pt-6 dark:border-gray-600"
                >
                    <button
                        type="button"
                        @click="goBack"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        <template v-else>
                            <Save v-if="props.user" class="h-4 w-4" />
                            <UserPlus v-else class="h-4 w-4" />
                        </template>
                        {{
                            form.processing
                                ? 'Saving...'
                                : props.user
                                  ? 'Update User'
                                  : 'Save User'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
