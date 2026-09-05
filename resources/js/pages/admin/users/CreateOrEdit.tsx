/**
 * AdminUsersCreateOrEdit — TSX port of the former `CreateOrEdit.vue`.
 *
 * Same UI/behavior as the SFC: Inertia `useForm` (create POST vs edit
 * PATCH), editor-permissions section, avatar file input wired via
 * `handleAvatarSelect`. Resolved via the explicit dual-extension
 * (`*.vue` + `*.tsx`) page resolver in `resources/js/app.ts`.
 */
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BadgeCheck, Loader2, Save, UserPlus } from 'lucide-vue-next';
import { defineComponent, ref, watch } from 'vue';
import type { PropType } from 'vue';

interface Role {
    id?: number;
    name: string;
}

interface PermissionItem {
    id?: number | string;
    name: string;
}

interface User {
    id: number;
    name?: string | null;
    username?: string | null;
    email?: string | null;
    is_verified?: boolean | null;
    image_url?: string | null;
    about?: string | null;
    title?: string | null;
    institution?: string | null;
    facebook?: string | null;
    github?: string | null;
    instagram?: string | null;
    roles?: Role[];
    permissions?: PermissionItem[];
}

export default defineComponent({
    name: 'AdminUsersCreateOrEdit',
    props: {
        user: { type: Object as PropType<User | null>, default: undefined },
        permissions: {
            type: Array as PropType<PermissionItem[]>,
            default: undefined,
        },
    },
    setup(props) {
        const availablePermissions = props.permissions ?? [];

        const roles = [
            { value: '', label: 'Student (No Role)' },
            { value: 'admin', label: 'Admin' },
            { value: 'editor', label: 'Editor' },
            { value: 'manager', label: 'Manager' },
        ];

        const form = useForm({
            _method: props.user ? 'PATCH' : 'POST',
            name: props.user?.name || '',
            username: props.user?.username || '',
            email: props.user?.email || '',
            is_verified: Boolean(props.user?.is_verified ?? false),
            role: props.user?.roles?.[0]?.name || '',
            permissions: props.user?.permissions?.map((p) => p.name) || [
                'view admin',
            ],

            file: null as File | null,
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

        const avatarPreview = ref<string | null>(null);

        const handleAvatarSelect = (event: Event) => {
            const target = event.target as HTMLInputElement;

            if (target.files && target.files[0]) {
                form.file = target.files[0];

                if (avatarPreview.value) {
                    URL.revokeObjectURL(avatarPreview.value);
                }

                avatarPreview.value = URL.createObjectURL(target.files[0]);
            }
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

        const onSubmit = (e: Event) => {
            e.preventDefault();
            submitForm();
        };

        const onPermissionToggle = (
            permissionName: string,
            checked: boolean,
        ) => {
            if (checked) {
                if (!form.permissions.includes(permissionName)) {
                    form.permissions.push(permissionName);
                }
            } else {
                form.permissions = form.permissions.filter(
                    (p) => p !== permissionName,
                );
            }
        };

        const avatarSrc = () =>
            avatarPreview.value || props.user?.image_url || null;

        const avatarInitial = () =>
            (props.user?.name || form.name || 'U').charAt(0).toUpperCase();

        const labelClass =
            'mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300';
        const errorClass = 'mt-1 text-xs text-rose-600 dark:text-rose-400';
        const hintClass = 'mt-1 text-[11px] text-slate-400 dark:text-gray-500';
        const sectionTitleClass =
            'text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400';

        const inputClass = (hasError: unknown) => [
            'h-9 w-full rounded-xl border bg-white px-3 text-xs text-slate-900 transition outline-none placeholder:text-slate-400 focus:ring-2 disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:disabled:bg-gray-800 dark:disabled:text-gray-400',
            hasError
                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20'
                : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 dark:border-gray-700 dark:focus:border-indigo-400',
        ];

        const textareaClass = (hasError: unknown) => [
            'w-full rounded-xl border bg-white px-3 py-2 text-xs text-slate-900 transition outline-none placeholder:text-slate-400 focus:ring-2 disabled:bg-slate-50 disabled:text-slate-500 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:disabled:bg-gray-800 dark:disabled:text-gray-400',
            hasError
                ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20'
                : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 dark:border-gray-700 dark:focus:border-indigo-400',
        ];

        return () => (
            <>
                <Head
                    title={
                        props.user ? `Edit ${props.user.name}` : 'Create User'
                    }
                />

                <div class="space-y-6">
                    {/* Page header */}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h1 class="truncate text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                    {props.user ? 'Edit User' : 'Create User'}
                                </h1>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                {props.user
                                    ? 'Update team member details and system access.'
                                    : 'Add a new team member and assign their role.'}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Link
                                href="/admin/users"
                                class="inline-flex h-9 cursor-pointer items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </Link>
                            <button
                                type="button"
                                onClick={submitForm}
                                disabled={form.processing}
                                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                {form.processing ? (
                                    <Loader2 class="h-3.5 w-3.5 animate-spin" />
                                ) : props.user ? (
                                    <Save class="h-3.5 w-3.5" />
                                ) : (
                                    <UserPlus class="h-3.5 w-3.5" />
                                )}
                                {form.processing
                                    ? 'Saving...'
                                    : props.user
                                      ? 'Update User'
                                      : 'Save User'}
                            </button>
                        </div>
                    </div>

                    <form onSubmit={onSubmit} class="max-w-3xl space-y-5">
                        {/* Account */}
                        <div class="space-y-4">
                            <h2 class={sectionTitleClass}>Account</h2>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="name" class={labelClass}>
                                        Full Name
                                    </label>
                                    <input
                                        value={form.name}
                                        onInput={(e: Event) => {
                                            form.name = (
                                                e.target as HTMLInputElement
                                            ).value;
                                        }}
                                        type="text"
                                        id="name"
                                        placeholder="John Doe"
                                        disabled={form.processing}
                                        class={inputClass(form.errors.name)}
                                    />
                                    {form.errors.name && (
                                        <p class={errorClass}>
                                            {form.errors.name}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label for="username" class={labelClass}>
                                        Username
                                    </label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-medium text-slate-400 dark:text-gray-500">
                                            @
                                        </span>
                                        <input
                                            value={form.username}
                                            onInput={(e: Event) => {
                                                form.username = (
                                                    e.target as HTMLInputElement
                                                ).value;
                                            }}
                                            type="text"
                                            id="username"
                                            placeholder="johndoe"
                                            disabled={form.processing}
                                            class={[
                                                ...inputClass(
                                                    form.errors.username,
                                                ),
                                                'pl-7',
                                            ]}
                                        />
                                    </div>
                                    {form.errors.username && (
                                        <p class={errorClass}>
                                            {form.errors.username}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <label for="email" class={labelClass}>
                                    Email Address
                                </label>
                                <input
                                    value={form.email}
                                    onInput={(e: Event) => {
                                        form.email = (
                                            e.target as HTMLInputElement
                                        ).value;
                                    }}
                                    type="email"
                                    id="email"
                                    placeholder="johndoe@example.com"
                                    disabled={form.processing}
                                    class={inputClass(form.errors.email)}
                                />
                                {form.errors.email && (
                                    <p class={errorClass}>
                                        {form.errors.email}
                                    </p>
                                )}
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="role" class={labelClass}>
                                        System Role
                                    </label>
                                    <select
                                        id="role"
                                        value={form.role}
                                        onChange={(e: Event) => {
                                            form.role = (
                                                e.target as HTMLSelectElement
                                            ).value;
                                        }}
                                        disabled={form.processing}
                                        class={inputClass(form.errors.role)}
                                    >
                                        {roles.map((role) => (
                                            <option
                                                key={role.value}
                                                value={role.value}
                                            >
                                                {role.label}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.role && (
                                        <p class={errorClass}>
                                            {form.errors.role}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <span class={labelClass}>
                                        Verified Contributor
                                    </span>
                                    <div class="flex h-9 items-center gap-2">
                                        <input
                                            type="checkbox"
                                            id="is_verified"
                                            checked={form.is_verified}
                                            onChange={(e: Event) => {
                                                form.is_verified = (
                                                    e.target as HTMLInputElement
                                                ).checked;
                                            }}
                                            disabled={form.processing}
                                            class="h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900"
                                        />
                                        <label
                                            for="is_verified"
                                            class="flex cursor-pointer items-center gap-1.5 text-xs font-medium text-slate-700 select-none focus-visible:outline-2 focus-visible:outline-indigo-500 dark:text-gray-300"
                                        >
                                            <BadgeCheck class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                                            Verified badge
                                        </label>
                                    </div>
                                    <p class={hintClass}>
                                        Shows verified badge in chat and
                                        profile.
                                    </p>
                                    {form.errors.is_verified && (
                                        <p class={errorClass}>
                                            {form.errors.is_verified}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Profile */}
                        <div class="space-y-4 border-t border-slate-100 pt-5 dark:border-gray-800">
                            <h2 class={sectionTitleClass}>Profile</h2>

                            <div class="flex items-start gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 text-sm font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400">
                                    {avatarSrc() ? (
                                        <img
                                            src={avatarSrc() as string}
                                            alt="Avatar preview"
                                            class="h-full w-full object-cover"
                                        />
                                    ) : (
                                        <span>{avatarInitial()}</span>
                                    )}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <label for="file" class={labelClass}>
                                        Profile Image
                                    </label>
                                    <input
                                        type="file"
                                        id="file"
                                        accept="image/*"
                                        onChange={handleAvatarSelect}
                                        disabled={form.processing}
                                        class={[
                                            'w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-500 transition outline-none file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-slate-100 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 focus:border-indigo-500 disabled:bg-slate-50 disabled:text-slate-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:file:bg-gray-800 dark:file:text-gray-300 dark:hover:file:bg-gray-700 dark:focus:border-indigo-400 dark:disabled:bg-gray-800 dark:disabled:text-gray-400',
                                            form.errors.file
                                                ? 'border-rose-500'
                                                : '',
                                        ]}
                                    />
                                    <p class={hintClass}>
                                        PNG or JPG, square works best.
                                    </p>
                                    {form.errors.file && (
                                        <p class={errorClass}>
                                            {form.errors.file}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="title" class={labelClass}>
                                        Title
                                    </label>
                                    <input
                                        value={form.title}
                                        onInput={(e: Event) => {
                                            form.title = (
                                                e.target as HTMLInputElement
                                            ).value;
                                        }}
                                        type="text"
                                        id="title"
                                        placeholder="e.g. Lead Developer, Professor"
                                        disabled={form.processing}
                                        class={inputClass(form.errors.title)}
                                    />
                                    {form.errors.title && (
                                        <p class={errorClass}>
                                            {form.errors.title}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label for="institution" class={labelClass}>
                                        Institution
                                    </label>
                                    <input
                                        value={form.institution}
                                        onInput={(e: Event) => {
                                            form.institution = (
                                                e.target as HTMLInputElement
                                            ).value;
                                        }}
                                        type="text"
                                        id="institution"
                                        placeholder="University / Company"
                                        disabled={form.processing}
                                        class={inputClass(
                                            form.errors.institution,
                                        )}
                                    />
                                    {form.errors.institution && (
                                        <p class={errorClass}>
                                            {form.errors.institution}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label for="facebook" class={labelClass}>
                                        Facebook
                                    </label>
                                    <input
                                        value={form.facebook}
                                        onInput={(e: Event) => {
                                            form.facebook = (
                                                e.target as HTMLInputElement
                                            ).value;
                                        }}
                                        type="text"
                                        id="facebook"
                                        placeholder="Facebook profile URL"
                                        disabled={form.processing}
                                        class={inputClass(form.errors.facebook)}
                                    />
                                    {form.errors.facebook && (
                                        <p class={errorClass}>
                                            {form.errors.facebook}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label for="github" class={labelClass}>
                                        GitHub
                                    </label>
                                    <input
                                        value={form.github}
                                        onInput={(e: Event) => {
                                            form.github = (
                                                e.target as HTMLInputElement
                                            ).value;
                                        }}
                                        type="text"
                                        id="github"
                                        placeholder="GitHub profile URL"
                                        disabled={form.processing}
                                        class={inputClass(form.errors.github)}
                                    />
                                    {form.errors.github && (
                                        <p class={errorClass}>
                                            {form.errors.github}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label for="instagram" class={labelClass}>
                                        Instagram
                                    </label>
                                    <input
                                        value={form.instagram}
                                        onInput={(e: Event) => {
                                            form.instagram = (
                                                e.target as HTMLInputElement
                                            ).value;
                                        }}
                                        type="text"
                                        id="instagram"
                                        placeholder="Instagram profile URL"
                                        disabled={form.processing}
                                        class={inputClass(
                                            form.errors.instagram,
                                        )}
                                    />
                                    {form.errors.instagram && (
                                        <p class={errorClass}>
                                            {form.errors.instagram}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <label for="about" class={labelClass}>
                                    About
                                </label>
                                <textarea
                                    value={form.about}
                                    onInput={(e: Event) => {
                                        form.about = (
                                            e.target as HTMLInputElement
                                        ).value;
                                    }}
                                    id="about"
                                    rows={4}
                                    placeholder="Short bio..."
                                    disabled={form.processing}
                                    class={textareaClass(form.errors.about)}
                                />
                                {form.errors.about && (
                                    <p class={errorClass}>
                                        {form.errors.about}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Permissions */}
                        {form.role === 'editor' && (
                            <div class="space-y-3 border-t border-slate-100 pt-5 dark:border-gray-800">
                                <div>
                                    <h2 class={sectionTitleClass}>
                                        Permissions
                                    </h2>
                                    <p class={hintClass}>
                                        Select the features this editor is
                                        authorized to modify.
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    {availablePermissions.map((permission) => (
                                        <label
                                            key={permission.name}
                                            for={`perm-${permission.name}`}
                                            class="flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-700 select-none focus-visible:outline-2 focus-visible:outline-indigo-500 dark:text-gray-300"
                                        >
                                            <input
                                                type="checkbox"
                                                id={`perm-${permission.name}`}
                                                value={permission.name}
                                                checked={form.permissions.includes(
                                                    permission.name,
                                                )}
                                                onChange={(e: Event) => {
                                                    onPermissionToggle(
                                                        permission.name,
                                                        (
                                                            e.target as HTMLInputElement
                                                        ).checked,
                                                    );
                                                }}
                                                disabled={
                                                    form.processing ||
                                                    permission.name ===
                                                        'view admin'
                                                }
                                                class="h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900"
                                            />
                                            <span
                                                class={[
                                                    permission.name ===
                                                    'view admin'
                                                        ? 'text-slate-500 dark:text-gray-400'
                                                        : '',
                                                ]}
                                            >
                                                {permission.name}
                                                {permission.name ===
                                                    'view admin' && (
                                                    <span class="text-[11px] font-normal text-slate-500 dark:text-gray-400">
                                                        {' '}
                                                        (Required)
                                                    </span>
                                                )}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                                {form.errors.permissions && (
                                    <p class={errorClass}>
                                        {form.errors.permissions}
                                    </p>
                                )}
                            </div>
                        )}

                        {/* Role reference */}
                        <div class="grid grid-cols-1 gap-3 border-t border-slate-100 pt-5 sm:grid-cols-3 dark:border-gray-800">
                            <div class="space-y-1 border-l-2 border-rose-500 pl-3">
                                <div class="text-xs font-bold text-rose-700 dark:text-rose-400">
                                    Admin
                                </div>
                                <div class="text-[11px] leading-relaxed text-slate-500 dark:text-gray-400">
                                    Superuser control. Automatically has all
                                    permissions system-wide.
                                </div>
                            </div>
                            <div class="space-y-1 border-l-2 border-indigo-500 pl-3">
                                <div class="text-xs font-bold text-indigo-700 dark:text-indigo-400">
                                    Editor
                                </div>
                                <div class="text-[11px] leading-relaxed text-slate-500 dark:text-gray-400">
                                    Customizable access. Permissions can be
                                    checked explicitly above.
                                </div>
                            </div>
                            <div class="space-y-1 border-l-2 border-amber-500 pl-3">
                                <div class="text-xs font-bold text-amber-700 dark:text-amber-400">
                                    Manager
                                </div>
                                <div class="text-[11px] leading-relaxed text-slate-500 dark:text-gray-400">
                                    Dashboard view. Basic view access with no
                                    modification capabilities.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </>
        );
    },
});
