<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { UserPlus, Loader2, Save } from 'lucide-vue-next';

defineProps({
    user: Object,
});

const roles = [
    { value: 'admin', label: 'Admin' },
    { value: 'manager', label: 'Manager' },
    { value: 'editor', label: 'Editor' },
];


const form = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    password: '',
    role: props.user?.roles?.[0]?.name || 'manager',

    image: props.user?.image || '',
    about: props.user?.about || '',
    institution: props.user?.institution || '',
    facebook: props.user?.facebook || '',
    github: props.user?.github || '',
});

const goBack = () => {
    window.history.back();
};

const submitForm = () => {
    if (props.user) {
        form.patch(`/admin/users/${props.user.id}`, {
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
    <div
        class="mobile-deep-border:p-4 flex min-h-full w-full flex-col justify-start bg-slate-50 p-4 lg:p-10"
    >
        <div
            class="w-full rounded-2xl border border-gray-300 bg-white p-5 shadow-xs md:p-10"
        >
            <div
                class="mb-8 flex flex-col justify-between gap-4 border-b border-gray-300 pb-6 sm:flex-row sm:items-center"
            >
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        {{ props.user ? 'Edit' : 'Create New' }} User
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{
                            props.user
                                ? 'Update team member details and system access rules.'
                                : 'Add a new team member and assign their system access role.'
                        }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="max-w-3xl space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label
                            for="name"
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >Full Name</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            id="name"
                            placeholder="John Doe"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500"
                            :class="
                                form.errors.name
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
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
                            for="email"
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >Email Address</label
                        >
                        <input
                            v-model="form.email"
                            type="email"
                            id="email"
                            placeholder="johndoe@example.com"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500"
                            :class="
                                form.errors.email
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
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

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label
                            for="password"
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >
                            Password
                            <span
                                v-if="props.user"
                                class="text-xs font-normal text-slate-400"
                                >(Leave blank to keep current)</span
                            >
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            id="password"
                            placeholder="••••••••"
                            :required="!props.user"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500"
                            :class="
                                form.errors.password
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
                            "
                        />
                        <p
                            v-if="form.errors.password"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="role"
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >System Role</label
                        >
                        <select
                            id="role"
                            v-model="form.role"
                            :disabled="form.processing"
                            class="w-full rounded-lg border bg-white px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500"
                            :class="
                                form.errors.role
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
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

                <!-- NEW FIELDS START -->

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label
                            for="image"
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >
                            Profile Image URL
                        </label>
                        <input
                            v-model="form.image"
                            type="text"
                            id="image"
                            placeholder="https://example.com/image.jpg"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500"
                            :class="
                                form.errors.image
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
                            "
                        />
                        <p v-if="form.errors.image" class="mt-1 text-sm text-rose-600">
                            {{ form.errors.image }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="institution"
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >
                            Institution
                        </label>
                        <input
                            v-model="form.institution"
                            type="text"
                            id="institution"
                            placeholder="University / Company"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500"
                            :class="
                                form.errors.institution
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
                            "
                        />
                        <p v-if="form.errors.institution" class="mt-1 text-sm text-rose-600">
                            {{ form.errors.institution }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label
                            for="facebook"
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >
                            Facebook
                        </label>
                        <input
                            v-model="form.facebook"
                            type="text"
                            id="facebook"
                            placeholder="Facebook profile URL"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500"
                            :class="
                                form.errors.facebook
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
                            "
                        />
                        <p v-if="form.errors.facebook" class="mt-1 text-sm text-rose-600">
                            {{ form.errors.facebook }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="github"
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                        >
                            GitHub
                        </label>
                        <input
                            v-model="form.github"
                            type="text"
                            id="github"
                            placeholder="GitHub profile URL"
                            :disabled="form.processing"
                            class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500"
                            :class="
                                form.errors.github
                                    ? 'border-rose-500 focus:ring-rose-500/20'
                                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
                            "
                        />
                        <p v-if="form.errors.github" class="mt-1 text-sm text-rose-600">
                            {{ form.errors.github }}
                        </p>
                    </div>
                </div>

                <div>
                    <label
                        for="about"
                        class="mb-1.5 block text-sm font-semibold text-slate-700"
                    >
                        About
                    </label>
                    <textarea
                        v-model="form.about"
                        id="about"
                        rows="4"
                        placeholder="Short bio..."
                        :disabled="form.processing"
                        class="w-full rounded-lg border px-4 py-2.5 transition outline-none disabled:bg-slate-50 disabled:text-slate-500"
                        :class="
                            form.errors.about
                                ? 'border-rose-500 focus:ring-rose-500/20'
                                : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20'
                        "
                    ></textarea>
                    <p v-if="form.errors.about" class="mt-1 text-sm text-rose-600">
                        {{ form.errors.about }}
                    </p>
                </div>

                <!-- NEW FIELDS END -->

                <div
                    class="space-y-3.5 rounded-xl border border-gray-300 bg-slate-50/50 p-4.5"
                >
                    <h4
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase"
                    >
                        Role Permissions Reference
                    </h4>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="space-y-1 border-l-2 border-red-500 pl-3">
                            <div class="text-xs font-bold text-red-700">
                                Admin
                            </div>
                            <div class="text-xs leading-relaxed text-slate-600">
                                Superuser control. Can create, update, and
                                delete any resource or user.
                            </div>
                        </div>
                        <div class="space-y-1 border-l-2 border-amber-500 pl-3">
                            <div class="text-xs font-bold text-amber-700">
                                Manager
                            </div>
                            <div class="text-xs leading-relaxed text-slate-600">
                                Dashboard view. Authorized to explore and
                                inspect the admin panel areas.
                            </div>
                        </div>
                        <div
                            class="space-y-1 border-l-2 border-purple-500 pl-3"
                        >
                            <div class="text-xs font-bold text-purple-700">
                                Editor
                            </div>
                            <div class="text-xs leading-relaxed text-slate-600">
                                Content control. Can create new archives.
                                Restricted from managing accounts.
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end space-x-3 border-t border-gray-300 pt-6"
                >
                    <button
                        type="button"
                        @click="goBack"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
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