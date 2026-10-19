import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    Loader2,
    Mail,
    Bell,
    Send,
    Users,
    GraduationCap,
    ShieldCheck,
    AlertCircle,
    CheckCircle2,
    Eye,
    X,
    Upload,
    Trash2,
    Download,
    ChevronDown,
} from 'lucide-vue-next';
import {
    Teleport,
    computed,
    defineComponent,
    onBeforeUnmount,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue';

import {
    AdminPageHeader,
    adminDangerIconBtnClass,
    adminInputClass,
    adminPanelClass,
    adminPageClass,
    adminPrimaryBtnClass,
    adminSectionTitleClass,
    adminSecondaryBtnClass,
} from '@/components/admin/ui';
import HTMLEditor from '@/components/HTMLEditor.vue';
import { markAdminFormDirty } from '@/lib/adminFormGuard';

interface EmailFormFields {
    recipients: string;
    subject: string;
    body: string;
    image: File | null;
    send_email: boolean;
    send_in_app: boolean;
    action_url: string;
}

interface RecipientsResponse {
    emails?: unknown;
}

export default defineComponent({
    name: 'AdminEmailSend',
    props: {
        allUsersCount: { type: Number, default: 0 },
        recipientCount: { type: Number, default: 0 },
        studentsCount: { type: Number, default: 0 },
        staffCount: { type: Number, default: 0 },
    },
    setup(props) {
        const page = usePage();
        const appName = computed(() => {
            const propsRecord = page.props as Record<string, unknown>;

            return typeof propsRecord.appName === 'string'
                ? propsRecord.appName
                : 'HSCStack';
        });

        const showConfirmModal = ref(false);
        const showPreviewModal = ref(false);
        const isImportDropdownOpen = ref(false);
        const isImporting = ref(false);

        const importDropdownRef = ref<HTMLDivElement | null>(null);
        const imagePreview = ref<string | null>(null);
        const fileInput = ref<HTMLInputElement | null>(null);

        const form = useForm<EmailFormFields>({
            recipients: '',
            subject: '',
            body: '',
            image: null as File | null,
            send_email: true,
            send_in_app: false,
            action_url: '',
        });

        watch(
            () => form.isDirty,
            (v) => markAdminFormDirty(v),
        );

        onUnmounted(() => markAdminFormDirty(false));

        const formattedCurrentDate = computed(() => {
            return new Date().toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        });

        // Click outside handler for import dropdown
        const handleClickOutside = (event: MouseEvent): void => {
            if (
                importDropdownRef.value &&
                !importDropdownRef.value.contains(event.target as Node)
            ) {
                isImportDropdownOpen.value = false;
            }
        };

        onMounted(() => {
            document.addEventListener('click', handleClickOutside);
        });

        onBeforeUnmount(() => {
            document.removeEventListener('click', handleClickOutside);
        });

        // Parse and analyze recipient emails in real-time
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        const recipientStats = computed(() => {
            const raw = form.recipients.trim();

            if (!raw) {
                return {
                    validEmails: [] as string[],
                    invalidItems: [] as string[],
                    totalLines: 0,
                    duplicateCount: 0,
                };
            }

            const tokens = raw
                .split(/[\r\n,;]+/)
                .map((t) => t.trim())
                .filter(Boolean);
            const seen = new Set<string>();
            const validEmails: string[] = [];
            const invalidItems: string[] = [];
            let duplicateCount = 0;

            for (const token of tokens) {
                const lower = token.toLowerCase();

                if (emailRegex.test(lower)) {
                    if (seen.has(lower)) {
                        duplicateCount++;
                    } else {
                        seen.add(lower);
                        validEmails.push(lower);
                    }
                } else {
                    invalidItems.push(token);
                }
            }

            return {
                validEmails,
                invalidItems,
                totalLines: tokens.length,
                duplicateCount,
            };
        });

        const handleImageSelect = (event: Event): void => {
            const target = event.target as HTMLInputElement;

            if (target.files && target.files[0]) {
                const file = target.files[0];
                form.image = file;
                imagePreview.value = URL.createObjectURL(file);
            }
        };

        const handleRemoveImage = (): void => {
            form.image = null;
            imagePreview.value = null;

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        };

        /**
         * Merges subscriber strings for the selected audience into the list.
         * Existing entries are trimmed and lowercased; exact duplicates are
         * removed across both lists. Concurrent calls are ignored. HTTP,
         * network, or JSON parsing failures leave recipients unchanged and
         * do not reject the returned promise.
         */
        const importSubscribers = async (
            type: 'subscribers' | 'all' | 'students' | 'staff',
        ): Promise<void> => {
            if (isImporting.value) {
                return;
            }

            isImporting.value = true;
            isImportDropdownOpen.value = false;

            try {
                const response = await fetch(
                    `/admin/emails/recipients?type=${type}`,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                );

                if (response.ok) {
                    const data = (await response.json()) as RecipientsResponse;
                    const rawEmails: unknown = data.emails;
                    const importedList: string[] = Array.isArray(rawEmails)
                        ? rawEmails.filter(
                              (e): e is string => typeof e === 'string',
                          )
                        : [];

                    // Merge with existing emails avoiding duplicates
                    const currentTokens = form.recipients
                        .split(/[\r\n,;]+/)
                        .map((t) => t.trim().toLowerCase())
                        .filter(Boolean);

                    const mergedSet = new Set([
                        ...currentTokens,
                        ...importedList,
                    ]);
                    form.recipients = Array.from(mergedSet).join('\n');
                }
            } catch (e) {
                console.error('Failed to import recipients:', e);
            } finally {
                isImporting.value = false;
            }
        };

        /**
         * Replaces the list with lowercase, unique valid addresses, one per
         * line, discarding invalid entries separated by newlines, commas, or
         * semicolons. Validity uses the page's basic email pattern.
         */
        const cleanAndFormatRecipients = (): void => {
            const valid = recipientStats.value.validEmails;
            form.recipients = valid.join('\n');
        };

        /**
         * Clears prior errors and opens confirmation when at least one valid
         * recipient and a nonblank subject and body exist. Otherwise sets
         * field errors; this does not clean the recipient list or send mail.
         */
        const handleSendClick = (): void => {
            // Client-side validation is idempotent here; without clearing,
            // errors from a previous failed attempt stick around until the
            // server response replaces them.
            form.clearErrors();

            // Guard also covers Enter-key implicit submission, which fires
            // the form handler without touching the disabled submit button.
            if (!form.send_email && !form.send_in_app) {
                return;
            }

            if (recipientStats.value.validEmails.length === 0) {
                form.setError(
                    'recipients',
                    'Please provide at least one valid recipient email.',
                );

                return;
            }

            if (!form.subject.trim() || !form.body.trim()) {
                if (!form.subject.trim()) {
                    form.setError('subject', 'Subject is required.');
                }

                if (!form.body.trim()) {
                    form.setError('body', 'Body is required.');
                }

                return;
            }

            showConfirmModal.value = true;
        };

        /** Closes confirmation and posts the current fields as multipart data. */
        const submitForm = (): void => {
            showConfirmModal.value = false;
            form.post('/admin/emails/send', {
                preserveScroll: true,
                forceFormData: true,
            });
        };

        const onRecipientsInput = (e: Event) => {
            form.recipients = (e.target as HTMLTextAreaElement).value;
        };

        const onSubjectInput = (e: Event) => {
            form.subject = (e.target as HTMLInputElement).value;
        };

        return () => (
            <>
                <Head title="Send Email" />

                <div class={adminPageClass}>
                    {/* Page header */}
                    <AdminPageHeader
                        title="Send Email"
                        description="Compose and dispatch email announcements to custom recipient lists or imported platform subscribers."
                    >
                        {{
                            actions: () => (
                                <>
                                    <span class="inline-flex items-center gap-1.5 text-[11px] text-slate-500 sm:mr-1 dark:text-gray-400">
                                        <Users class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                                        <span>
                                            <span class="font-bold text-slate-700 dark:text-gray-200">
                                                {props.recipientCount}
                                            </span>{' '}
                                            total subscribers
                                        </span>
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            showPreviewModal.value = true;
                                        }}
                                        class={adminSecondaryBtnClass}
                                    >
                                        <Eye class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                                        <span>Preview</span>
                                    </button>
                                </>
                            ),
                        }}
                    </AdminPageHeader>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            handleSendClick();
                        }}
                        class={[adminPanelClass, 'mx-auto max-w-4xl space-y-8']}
                    >
                        {/* 1. Delivery Channels */}
                        <section class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-bold text-white dark:bg-indigo-500">
                                    1
                                </span>
                                <h2 class={adminSectionTitleClass}>
                                    Delivery Channels
                                </h2>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={form.send_email}
                                    onClick={() => {
                                        form.send_email = !form.send_email;
                                    }}
                                    class={[
                                        'flex cursor-pointer items-start gap-3 rounded-xl p-3.5 text-left ring-1 transition-colors focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-[0.99]',
                                        form.send_email
                                            ? 'bg-indigo-50/40 ring-indigo-300/70 dark:bg-indigo-500/10 dark:ring-indigo-500/40'
                                            : 'bg-white/40 ring-slate-200/60 hover:bg-white/70 dark:bg-slate-900/40 dark:ring-slate-800/60 dark:hover:bg-slate-900/70',
                                    ]}
                                >
                                    <span
                                        class={[
                                            'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors',
                                            form.send_email
                                                ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                                : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-gray-500',
                                        ]}
                                    >
                                        <Mail class="h-4 w-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-xs font-semibold text-slate-900 dark:text-gray-100">
                                            Email Broadcast
                                        </span>
                                        <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500 dark:text-gray-400">
                                            Rich HTML announcement delivered to
                                            recipients&apos; inboxes.
                                        </span>
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={form.send_in_app}
                                    onClick={() => {
                                        form.send_in_app = !form.send_in_app;
                                    }}
                                    class={[
                                        'flex cursor-pointer items-start gap-3 rounded-xl p-3.5 text-left ring-1 transition-colors focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-[0.99]',
                                        form.send_in_app
                                            ? 'bg-indigo-50/40 ring-indigo-300/70 dark:bg-indigo-500/10 dark:ring-indigo-500/40'
                                            : 'bg-white/40 ring-slate-200/60 hover:bg-white/70 dark:bg-slate-900/40 dark:ring-slate-800/60 dark:hover:bg-slate-900/70',
                                    ]}
                                >
                                    <span
                                        class={[
                                            'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors',
                                            form.send_in_app
                                                ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                                : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-gray-500',
                                        ]}
                                    >
                                        <Bell class="h-4 w-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-xs font-semibold text-slate-900 dark:text-gray-100">
                                            In-App Notification
                                        </span>
                                        <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500 dark:text-gray-400">
                                            Push to notification bells with an
                                            interactive details modal.
                                        </span>
                                    </span>
                                </button>
                            </div>

                            {form.send_in_app && (
                                <div class="space-y-1.5">
                                    <label
                                        for="action_url"
                                        class="block text-xs font-semibold text-slate-700 dark:text-gray-300"
                                    >
                                        Action Link{' '}
                                        <span class="font-normal text-slate-400 dark:text-gray-500">
                                            (optional)
                                        </span>
                                    </label>
                                    <input
                                        id="action_url"
                                        type="url"
                                        value={form.action_url}
                                        onInput={(e) => {
                                            form.action_url = (
                                                e.target as HTMLInputElement
                                            ).value;
                                        }}
                                        placeholder="/blogs or https://example.com/campaign"
                                        maxlength={255}
                                        class={adminInputClass}
                                    />
                                    <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                        Leave blank to open the full
                                        announcement details in the notification
                                        modal instead.
                                    </p>
                                </div>
                            )}
                        </section>

                        {/* 2. Recipients */}
                        <section class="space-y-3">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-bold text-white dark:bg-indigo-500">
                                        2
                                    </span>
                                    <h2 class={adminSectionTitleClass}>
                                        Recipients
                                    </h2>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <div
                                        ref={importDropdownRef}
                                        class="relative"
                                    >
                                        <button
                                            type="button"
                                            disabled={isImporting.value}
                                            onClick={() => {
                                                isImportDropdownOpen.value =
                                                    !isImportDropdownOpen.value;
                                            }}
                                            class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-[10px] bg-white/70 px-3 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/60 backdrop-blur transition-colors hover:bg-white/90 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-900/60 dark:text-gray-200 dark:ring-slate-800/60 dark:hover:bg-slate-900/85"
                                        >
                                            {isImporting.value ? (
                                                <Loader2 class="h-3.5 w-3.5 animate-spin" />
                                            ) : (
                                                <Download class="h-3.5 w-3.5" />
                                            )}
                                            <span>Import Subscribers</span>
                                            <ChevronDown
                                                class={[
                                                    'h-3.5 w-3.5 text-slate-400 transition-transform duration-200 dark:text-gray-500',
                                                    isImportDropdownOpen.value
                                                        ? 'rotate-180'
                                                        : '',
                                                ]}
                                            />
                                        </button>

                                        {isImportDropdownOpen.value && (
                                            <div class="absolute right-0 z-30 mt-1.5 w-52 rounded-xl bg-white/90 p-1 shadow-lg ring-1 ring-slate-200/70 backdrop-blur-xl dark:bg-slate-900/90 dark:ring-slate-800/70">
                                                <button
                                                    type="button"
                                                    disabled={
                                                        props.recipientCount ===
                                                        0
                                                    }
                                                    onClick={() =>
                                                        importSubscribers(
                                                            'subscribers',
                                                        )
                                                    }
                                                    class="flex w-full cursor-pointer items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-xs font-semibold text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
                                                >
                                                    <span class="flex items-center gap-2">
                                                        <Mail class="h-3.5 w-3.5 text-indigo-500 dark:text-indigo-400" />
                                                        <span>
                                                            All Subscribers
                                                        </span>
                                                    </span>
                                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400">
                                                        {props.recipientCount}
                                                    </span>
                                                </button>

                                                <button
                                                    type="button"
                                                    disabled={
                                                        props.allUsersCount ===
                                                        0
                                                    }
                                                    onClick={() =>
                                                        importSubscribers('all')
                                                    }
                                                    class="flex w-full cursor-pointer items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-xs font-semibold text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
                                                >
                                                    <span class="flex items-center gap-2">
                                                        <Users class="h-3.5 w-3.5 text-indigo-500 dark:text-indigo-400" />
                                                        <span>
                                                            All Registered Users
                                                        </span>
                                                    </span>
                                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400">
                                                        {props.allUsersCount}
                                                    </span>
                                                </button>

                                                <button
                                                    type="button"
                                                    disabled={
                                                        props.studentsCount ===
                                                        0
                                                    }
                                                    onClick={() =>
                                                        importSubscribers(
                                                            'students',
                                                        )
                                                    }
                                                    class="flex w-full cursor-pointer items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-xs font-semibold text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
                                                >
                                                    <span class="flex items-center gap-2">
                                                        <GraduationCap class="h-3.5 w-3.5 text-indigo-500 dark:text-indigo-400" />
                                                        <span>
                                                            All Students
                                                        </span>
                                                    </span>
                                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400">
                                                        {props.studentsCount}
                                                    </span>
                                                </button>

                                                <button
                                                    type="button"
                                                    disabled={
                                                        props.staffCount === 0
                                                    }
                                                    onClick={() =>
                                                        importSubscribers(
                                                            'staff',
                                                        )
                                                    }
                                                    class="flex w-full cursor-pointer items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-xs font-semibold text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-200 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
                                                >
                                                    <span class="flex items-center gap-2">
                                                        <ShieldCheck class="h-3.5 w-3.5 text-indigo-500 dark:text-indigo-400" />
                                                        <span>All Staff</span>
                                                    </span>
                                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400">
                                                        {props.staffCount}
                                                    </span>
                                                </button>
                                            </div>
                                        )}
                                    </div>

                                    {form.recipients.trim() && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                form.recipients = '';
                                            }}
                                            title="Clear all recipients"
                                            class={adminDangerIconBtnClass}
                                        >
                                            <Trash2 class="h-3 w-3" />
                                        </button>
                                    )}
                                </div>
                            </div>

                            <div>
                                <label
                                    for="recipients"
                                    class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                                >
                                    Recipients (One email per line)
                                </label>
                                <textarea
                                    id="recipients"
                                    value={form.recipients}
                                    onInput={onRecipientsInput}
                                    rows={6}
                                    placeholder={
                                        'user1@example.com\nuser2@gmail.com\ncustom-lead@domain.com'
                                    }
                                    disabled={
                                        form.processing || isImporting.value
                                    }
                                    class={[
                                        'w-full rounded-[10px] border border-slate-200 bg-white px-3 py-2 font-mono text-xs leading-relaxed text-slate-900 transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400',
                                        form.errors.recipients
                                            ? 'border-rose-300 focus:border-rose-500 dark:border-rose-500/40'
                                            : 'border-slate-200 focus:border-indigo-500 dark:border-gray-700',
                                    ]}
                                ></textarea>

                                {form.errors.recipients && (
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                        {form.errors.recipients}
                                    </p>
                                )}

                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                            <CheckCircle2 class="h-3 w-3" />
                                            <span>
                                                {
                                                    recipientStats.value
                                                        .validEmails.length
                                                }{' '}
                                                valid unique
                                            </span>
                                        </span>

                                        {recipientStats.value.duplicateCount >
                                            0 && (
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-gray-800 dark:text-gray-400">
                                                <span>
                                                    {
                                                        recipientStats.value
                                                            .duplicateCount
                                                    }{' '}
                                                    duplicate
                                                    {recipientStats.value
                                                        .duplicateCount > 1
                                                        ? 's'
                                                        : ''}{' '}
                                                    auto-merged
                                                </span>
                                            </span>
                                        )}

                                        {recipientStats.value.invalidItems
                                            .length > 0 && (
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                                <AlertCircle class="h-3 w-3" />
                                                <span>
                                                    {
                                                        recipientStats.value
                                                            .invalidItems.length
                                                    }{' '}
                                                    invalid ignored
                                                </span>
                                            </span>
                                        )}
                                    </div>

                                    {(recipientStats.value.duplicateCount > 0 ||
                                        recipientStats.value.invalidItems
                                            .length > 0) && (
                                        <button
                                            type="button"
                                            onClick={cleanAndFormatRecipients}
                                            class="cursor-pointer text-[11px] font-semibold text-indigo-600 underline-offset-2 transition hover:text-indigo-700 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            Format & Clean List
                                        </button>
                                    )}
                                </div>

                                <p class="mt-1.5 text-[11px] text-slate-500 dark:text-gray-400">
                                    Paste any third-party list, single email, or
                                    import registered platform subscribers.
                                </p>
                            </div>
                        </section>

                        {/* 3. Content */}
                        <section class="space-y-4">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-bold text-white dark:bg-indigo-500">
                                    2
                                </span>
                                <h2 class={adminSectionTitleClass}>Content</h2>
                            </div>

                            <div>
                                <label
                                    for="subject"
                                    class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                                >
                                    Email Subject
                                </label>
                                <input
                                    value={form.subject}
                                    onInput={onSubjectInput}
                                    type="text"
                                    id="subject"
                                    required
                                    placeholder="e.g. Important Announcement: New Resources & Learning Updates"
                                    disabled={form.processing}
                                    class={[
                                        'w-full rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-indigo-400',
                                        form.errors.subject
                                            ? 'border-rose-300 focus:border-rose-500 dark:border-rose-500/40'
                                            : 'border-slate-200 focus:border-indigo-500 dark:border-gray-700',
                                    ]}
                                />
                                {form.errors.subject && (
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                        {form.errors.subject}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300">
                                    Cover / Banner Image (Optional)
                                </label>

                                <input
                                    ref={fileInput}
                                    type="file"
                                    id="email_image_upload"
                                    class="hidden"
                                    accept="image/png,image/jpeg,image/jpg,image/webp"
                                    disabled={form.processing}
                                    onChange={handleImageSelect}
                                />

                                {imagePreview.value ? (
                                    <div class="space-y-2">
                                        <img
                                            src={imagePreview.value}
                                            alt="Banner preview"
                                            class="max-h-56 w-full rounded-xl border border-slate-200 object-cover dark:border-gray-700"
                                        />
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="min-w-0 truncate text-[11px] text-slate-500 dark:text-gray-400">
                                                {form.image?.name}
                                            </span>
                                            <div class="flex shrink-0 items-center gap-1.5">
                                                <label
                                                    for="email_image_upload"
                                                    class="inline-flex h-8 cursor-pointer items-center rounded-[10px] bg-white/70 px-2.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200/60 backdrop-blur transition-colors hover:bg-white/90 active:scale-95 dark:bg-slate-900/60 dark:text-gray-300 dark:ring-slate-800/60 dark:hover:bg-slate-900/85"
                                                >
                                                    Change
                                                </label>
                                                <button
                                                    type="button"
                                                    onClick={handleRemoveImage}
                                                    class="inline-flex h-8 cursor-pointer items-center gap-1 rounded-[10px] px-2.5 text-[11px] font-semibold text-rose-600 transition-colors hover:bg-rose-500/10 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-rose-400"
                                                >
                                                    <Trash2 class="h-3 w-3" />
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div
                                        class={[
                                            'rounded-xl border border-dashed p-5 text-center transition dark:bg-gray-900',
                                            form.errors.image
                                                ? 'border-rose-300 dark:border-rose-500/40'
                                                : 'border-slate-200 dark:border-gray-700',
                                        ]}
                                    >
                                        <label
                                            for="email_image_upload"
                                            class="flex cursor-pointer flex-col items-center justify-center"
                                        >
                                            <Upload class="mb-1.5 h-4 w-4 text-slate-400 dark:text-gray-500" />
                                            <span class="text-xs font-semibold text-slate-700 dark:text-gray-200">
                                                Click to upload header banner
                                                image
                                            </span>
                                            <span class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                                PNG, JPG or WEBP (Max 5MB)
                                            </span>
                                        </label>
                                    </div>
                                )}

                                {form.errors.image && (
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                        {form.errors.image}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300">
                                    Email Body (HTML)
                                </label>

                                <HTMLEditor
                                    modelValue={form.body}
                                    onUpdate:modelValue={(v: string) => {
                                        form.body = v;
                                    }}
                                    error={form.errors.body}
                                    placeholder="Write your email content here... (supports links, quotes, and lists)"
                                />

                                {form.errors.body && (
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                        {form.errors.body}
                                    </p>
                                )}
                            </div>
                        </section>

                        {/* 4. Review & Send */}
                        <div class="sticky bottom-0 flex flex-col gap-3 border-t border-slate-200/60 bg-white/90 py-3 backdrop-blur sm:flex-row sm:items-center sm:justify-between dark:border-slate-800/60 dark:bg-slate-900/90">
                            <div>
                                <p class="text-xs font-semibold text-slate-900 dark:text-gray-100">
                                    Send to{' '}
                                    {recipientStats.value.validEmails.length}{' '}
                                    {recipientStats.value.validEmails.length ===
                                    1
                                        ? 'Recipient'
                                        : 'Recipients'}
                                </p>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                    Broadcasts are queued and delivered
                                    asynchronously. Duplicates and invalid
                                    addresses are automatically handled.
                                </p>
                            </div>

                            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                                <button
                                    type="button"
                                    onClick={() => {
                                        showPreviewModal.value = true;
                                    }}
                                    class={[
                                        adminSecondaryBtnClass,
                                        'w-full sm:w-auto',
                                    ]}
                                >
                                    <Eye class="h-3.5 w-3.5" />
                                    <span>Preview</span>
                                </button>

                                <button
                                    type="submit"
                                    disabled={
                                        form.processing ||
                                        (!form.send_email &&
                                            !form.send_in_app) ||
                                        recipientStats.value.validEmails
                                            .length === 0
                                    }
                                    class={[
                                        adminPrimaryBtnClass,
                                        'w-full sm:w-auto',
                                    ]}
                                >
                                    {form.processing ? (
                                        <Loader2 class="h-3.5 w-3.5 animate-spin" />
                                    ) : (
                                        <Send class="h-3.5 w-3.5" />
                                    )}
                                    <span>
                                        Send to{' '}
                                        {
                                            recipientStats.value.validEmails
                                                .length
                                        }{' '}
                                        {recipientStats.value.validEmails
                                            .length === 1
                                            ? 'Recipient'
                                            : 'Recipients'}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {/* Live PC / Desktop Email Client Preview Modal */}
                <Teleport to="body">
                    {showPreviewModal.value && (
                        <div class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-3 backdrop-blur-xs sm:p-6 dark:bg-black/70">
                            <div class="relative my-auto w-full max-w-2xl overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-2xl dark:border-gray-700 dark:bg-gray-900">
                                {/* Desktop Window Titlebar */}
                                <div class="flex items-center justify-between border-b border-slate-200 bg-white/80 px-4 py-2.5 backdrop-blur-md dark:border-gray-700 dark:bg-gray-950/80">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block h-3 w-3 rounded-full bg-rose-500/80"></span>
                                        <span class="inline-block h-3 w-3 rounded-full bg-amber-500/80"></span>
                                        <span class="inline-block h-3 w-3 rounded-full bg-emerald-500/80"></span>
                                        <div class="ml-2 flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-gray-300">
                                            <Mail class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                                            <span>Email Preview</span>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() => {
                                            showPreviewModal.value = false;
                                        }}
                                        title="Close preview"
                                        aria-label="Close preview"
                                        class="cursor-pointer rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>

                                {/* Email Client Header Info */}
                                <div class="border-b border-slate-200 bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-950">
                                    <h2 class="text-sm font-bold text-slate-900 dark:text-gray-100">
                                        {form.subject.trim() ||
                                            '(No subject specified)'}
                                    </h2>

                                    <div class="mt-1.5 space-y-1 text-xs text-slate-500 dark:text-gray-400">
                                        <div class="flex items-center gap-2">
                                            <span class="w-12 font-semibold text-slate-500 dark:text-gray-400">
                                                From:
                                            </span>
                                            <span class="font-medium text-slate-800 dark:text-gray-200">
                                                {appName.value}{' '}
                                                {'<team@example.com>'}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-12 font-semibold text-slate-500 dark:text-gray-400">
                                                To:
                                            </span>
                                            <span class="font-medium text-slate-800 dark:text-gray-200">
                                                {
                                                    recipientStats.value
                                                        .validEmails.length
                                                }{' '}
                                                unique recipient
                                                {recipientStats.value
                                                    .validEmails.length === 1
                                                    ? ''
                                                    : 's'}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-12 font-semibold text-slate-500 dark:text-gray-400">
                                                Date:
                                            </span>
                                            <span>
                                                {formattedCurrentDate.value}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {/* Email Body Container (Exact 600px Template Rendering) */}
                                <div class="max-h-[65vh] overflow-y-auto bg-slate-100 p-4 dark:bg-gray-900">
                                    <div class="mx-auto max-w-[580px] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-gray-700 dark:bg-gray-950">
                                        {/* Template Header with Logo */}
                                        <div class="border-b border-slate-200/60 bg-white py-4 text-center dark:border-slate-800/60 dark:bg-gray-950">
                                            <div class="inline-flex items-center gap-2.5">
                                                <img
                                                    src="/favicon.svg"
                                                    alt="HSCStack"
                                                    class="h-8 w-8 rounded-lg shadow-xs"
                                                />
                                                <span class="text-lg font-extrabold tracking-tight text-slate-900 dark:text-gray-100">
                                                    HSC
                                                    <span class="text-indigo-600 dark:text-indigo-400">
                                                        Stack
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        {/* Template Content */}
                                        <div class="p-6 text-sm leading-relaxed text-slate-700 dark:text-gray-300">
                                            <p class="mb-4 font-semibold text-slate-900 dark:text-gray-100">
                                                Hello{' '}
                                                {recipientStats.value
                                                    .validEmails.length === 1
                                                    ? recipientStats.value
                                                          .validEmails[0]
                                                    : '[Recipient Name]'}
                                                ,
                                            </p>

                                            {/* Banner Image in Preview */}
                                            {imagePreview.value && (
                                                <div class="mb-5 text-center">
                                                    <img
                                                        src={imagePreview.value}
                                                        alt="Announcement preview"
                                                        class="max-h-64 w-full rounded-xl border border-slate-200 object-cover dark:border-gray-700"
                                                    />
                                                </div>
                                            )}

                                            {/* Rendered HTML Content */}
                                            {form.body.trim() ? (
                                                <div
                                                    class="email-rendered-preview space-y-3"
                                                    innerHTML={form.body}
                                                ></div>
                                            ) : (
                                                <div class="py-8 text-center text-xs text-slate-500 italic dark:text-gray-400">
                                                    Your composed email content
                                                    will appear here...
                                                </div>
                                            )}
                                        </div>

                                        {/* Template Footer */}
                                        <div class="border-t border-slate-200/60 bg-slate-50 px-6 py-5 text-center text-xs text-slate-500 dark:border-slate-800/60 dark:bg-gray-900 dark:text-gray-400">
                                            <p class="mb-2 font-semibold text-slate-700 dark:text-gray-200">
                                                HSCStack — The Open Learning
                                                Platform
                                            </p>

                                            <div class="my-2 flex items-center justify-center gap-3 text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                                <span>Visit Platform</span>
                                                <span>•</span>
                                                <span>Read Blogs</span>
                                                <span>•</span>
                                                <span>Support Us</span>
                                            </div>

                                            <p class="mt-3 text-[11px] text-slate-500 dark:text-gray-400">
                                                Manage email preferences in your{' '}
                                                <span class="text-indigo-600 underline dark:text-indigo-400">
                                                    Account Settings
                                                </span>
                                                , or visit our{' '}
                                                <span class="text-indigo-600 underline dark:text-indigo-400">
                                                    Support Center
                                                </span>{' '}
                                                if you need assistance or
                                                don&apos;t have an account.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Preview Footer Modal Action */}
                                <div class="flex items-center justify-end border-t border-slate-200 bg-white/80 px-4 py-2.5 dark:border-gray-700 dark:bg-gray-950/80">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            showPreviewModal.value = false;
                                        }}
                                        class="inline-flex h-8 cursor-pointer items-center rounded-[10px] bg-indigo-600 px-3.5 text-xs font-semibold text-white transition-colors hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                    >
                                        Done Previewing
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </Teleport>

                {/* Send Confirmation Modal */}
                <Teleport to="body">
                    {showConfirmModal.value && (
                        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs dark:bg-black/60">
                            <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-gray-700 dark:bg-gray-900">
                                <div class="mb-3 flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                                        <Send class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-gray-100">
                                            Confirm Broadcast Queue
                                        </h3>
                                        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                            {form.send_email && form.send_in_app
                                                ? 'Email & In-App Notification'
                                                : form.send_email
                                                  ? `Email Only (${recipientStats.value.validEmails.length} recipient${recipientStats.value.validEmails.length === 1 ? '' : 's'})`
                                                  : 'In-App Bell Notification Only'}
                                        </p>
                                    </div>
                                </div>

                                <p class="mb-4 text-xs leading-relaxed text-slate-600 dark:text-gray-300">
                                    Are you sure you want to send this
                                    broadcast? The broadcast will be queued and
                                    delivered asynchronously.
                                    {form.send_email &&
                                        recipientStats.value.duplicateCount >
                                            0 && (
                                            <span class="mt-1 block font-medium text-slate-500 dark:text-gray-400">
                                                (
                                                {
                                                    recipientStats.value
                                                        .duplicateCount
                                                }{' '}
                                                duplicates were detected and
                                                will only receive 1 email).
                                            </span>
                                        )}
                                </p>

                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            showConfirmModal.value = false;
                                        }}
                                        class="inline-flex h-8 cursor-pointer items-center rounded-[10px] bg-white/70 px-3.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/60 backdrop-blur transition-colors hover:bg-white/90 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:bg-slate-900/60 dark:text-gray-200 dark:ring-slate-800/60 dark:hover:bg-slate-900/85"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="button"
                                        onClick={submitForm}
                                        class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-[10px] bg-indigo-600 px-3.5 text-xs font-semibold text-white transition-colors hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                                    >
                                        <Send class="h-3.5 w-3.5" />
                                        <span>Confirm & Send</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </Teleport>
            </>
        );
    },
});
