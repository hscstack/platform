import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, Loader2, Save, Trash2, Upload } from 'lucide-vue-next';
import { computed, defineComponent, ref } from 'vue';
import type { PropType } from 'vue';

interface SiteNotice {
    title?: string | null;
    message?: string | null;
    image?: string | null;
    show_button?: boolean;
    button_title?: string | null;
    button_link?: string | null;
    is_active?: boolean;
}

interface NoticeFormFields {
    title: string;
    message: string;
    image: File | null;
    remove_image: boolean;
    show_button: boolean;
    button_title: string;
    button_link: string;
    is_active: boolean;
}

export default defineComponent({
    name: 'AdminNoticeEdit',
    props: {
        notice: {
            type: Object as PropType<SiteNotice>,
            default: undefined,
        },
    },
    setup(props) {
        const form = useForm<NoticeFormFields>({
            title: props.notice?.title || '',
            message: props.notice?.message || '',
            image: null as File | null,
            remove_image: false,
            show_button: props.notice?.show_button ?? false,
            button_title: props.notice?.button_title || '',
            button_link: props.notice?.button_link || '',
            is_active: props.notice?.is_active ?? false,
        });

        const imagePreview = ref<string | null>(props.notice?.image ?? null);
        const fileInput = ref<HTMLInputElement | null>(null);

        const hasContent = computed(
            () => form.title.trim() !== '' || form.message.trim() !== '',
        );

        const handleFileSelect = (event: Event): void => {
            const target = event.target as HTMLInputElement;

            if (target.files && target.files[0]) {
                const file = target.files[0];
                form.image = file;
                form.remove_image = false;
                imagePreview.value = URL.createObjectURL(file);
            }
        };

        const handleRemoveImage = (): void => {
            form.image = null;
            form.remove_image = true;
            imagePreview.value = null;

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        };

        const submitForm = (): void => {
            form.post('/admin/notice', {
                preserveScroll: true,
                forceFormData: true,
            });
        };

        const onTitleInput = (e: Event) => {
            form.title = (e.target as HTMLInputElement).value;
        };

        const onMessageInput = (e: Event) => {
            form.message = (e.target as HTMLTextAreaElement).value;
        };

        const onButtonTitleInput = (e: Event) => {
            form.button_title = (e.target as HTMLInputElement).value;
        };

        const onButtonLinkInput = (e: Event) => {
            form.button_link = (e.target as HTMLInputElement).value;
        };

        const onShowButtonChange = (e: Event) => {
            form.show_button = (e.target as HTMLInputElement).checked;
        };

        const onIsActiveChange = (e: Event) => {
            form.is_active = (e.target as HTMLInputElement).checked;
        };

        return () => (
            <>
                <Head title="Site Notice" />

                <div class="space-y-6">
                    {/* Page header */}
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div>
                            <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-gray-100">
                                Site Notice
                            </h1>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                Configure the announcement dialog shown on the
                                home page. Only one notice is displayed at a
                                time.
                            </p>
                        </div>

                        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                            <Link
                                href="/admin"
                                class="inline-flex h-9 w-full cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 sm:w-auto dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </Link>
                            <button
                                type="button"
                                onClick={submitForm}
                                disabled={form.processing}
                                class="inline-flex h-9 w-full cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                {form.processing ? (
                                    <Loader2 class="h-3.5 w-3.5 animate-spin" />
                                ) : (
                                    <Save class="h-3.5 w-3.5" />
                                )}
                                {form.processing ? 'Saving...' : 'Save Notice'}
                            </button>
                        </div>
                    </div>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            submitForm();
                        }}
                        class="max-w-3xl space-y-5"
                    >
                        {/* Content */}
                        <section class="space-y-4">
                            <div>
                                <h2 class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                    Content
                                </h2>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                    Title, message and cover image shown in the
                                    dialog.
                                </p>
                            </div>

                            <div>
                                <label
                                    for="title"
                                    class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                                >
                                    Title
                                </label>
                                <input
                                    value={form.title}
                                    onInput={onTitleInput}
                                    type="text"
                                    id="title"
                                    placeholder="Important announcement"
                                    disabled={form.processing}
                                    class={[
                                        'w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 transition placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none disabled:opacity-50 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500',
                                        form.errors.title
                                            ? 'border-rose-300 focus:border-rose-500 dark:border-rose-500/40'
                                            : 'border-slate-200 focus:border-indigo-500 dark:border-gray-700',
                                    ]}
                                />
                                {form.errors.title && (
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                        {form.errors.title}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    for="message"
                                    class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                                >
                                    Message
                                </label>
                                <textarea
                                    value={form.message}
                                    onInput={onMessageInput}
                                    id="message"
                                    rows={5}
                                    placeholder="Write the notice message for visitors..."
                                    disabled={form.processing}
                                    class={[
                                        'w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 transition placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none disabled:opacity-50 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500',
                                        form.errors.message
                                            ? 'border-rose-300 focus:border-rose-500 dark:border-rose-500/40'
                                            : 'border-slate-200 focus:border-indigo-500 dark:border-gray-700',
                                    ]}
                                ></textarea>
                                {form.errors.message && (
                                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                        {form.errors.message}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300">
                                    Cover Image
                                </label>

                                <input
                                    ref={fileInput}
                                    type="file"
                                    id="notice_image_upload"
                                    class="hidden"
                                    accept="image/png,image/jpeg,image/jpg,image/webp"
                                    disabled={form.processing}
                                    onChange={handleFileSelect}
                                />

                                {imagePreview.value ? (
                                    <div class="flex items-center gap-3">
                                        <img
                                            src={imagePreview.value}
                                            alt="Notice preview"
                                            class="h-16 w-16 shrink-0 rounded-xl border border-slate-200 object-cover dark:border-gray-700"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs font-semibold text-slate-700 dark:text-gray-200">
                                                {form.image?.name ??
                                                    'Current cover image'}
                                            </p>
                                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                                PNG, JPG or WEBP (Max 10MB)
                                            </p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1.5">
                                            <label
                                                for="notice_image_upload"
                                                class="inline-flex h-8 cursor-pointer items-center rounded-xl border border-slate-200 bg-white px-2.5 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50 active:scale-95 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                                            >
                                                Change
                                            </label>
                                            <button
                                                type="button"
                                                onClick={handleRemoveImage}
                                                class="inline-flex h-8 cursor-pointer items-center gap-1 rounded-xl px-2.5 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 dark:text-rose-400 dark:hover:bg-rose-500/10"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                                Remove
                                            </button>
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
                                            for="notice_image_upload"
                                            class="flex cursor-pointer flex-col items-center justify-center"
                                        >
                                            <Upload class="mb-1.5 h-4 w-4 text-slate-400 dark:text-gray-500" />
                                            <span class="text-xs font-semibold text-slate-700 dark:text-gray-200">
                                                Click to upload cover image
                                            </span>
                                            <span class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                                PNG, JPG or WEBP (Max 10MB)
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
                        </section>

                        {/* Visibility */}
                        <section class="space-y-4">
                            <div>
                                <h2 class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-gray-400">
                                    Visibility
                                </h2>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                    Control the call-to-action button and
                                    whether the notice is live.
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold text-slate-900 dark:text-gray-100">
                                        Action button
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                        Optional call-to-action link in the
                                        dialog.
                                    </p>
                                </div>
                                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                    <input
                                        checked={form.show_button}
                                        onChange={onShowButtonChange}
                                        type="checkbox"
                                        class="peer sr-only"
                                        disabled={form.processing}
                                    />
                                    <span class="peer h-6 w-11 cursor-pointer rounded-full bg-slate-200 transition peer-checked:bg-indigo-600 peer-focus-visible:outline-2 peer-focus-visible:outline-indigo-500 peer-disabled:opacity-50 after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5 dark:bg-gray-700 dark:peer-checked:bg-indigo-500"></span>
                                </label>
                            </div>

                            {form.show_button && (
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label
                                            for="button_title"
                                            class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Button title
                                        </label>
                                        <input
                                            value={form.button_title}
                                            onInput={onButtonTitleInput}
                                            type="text"
                                            id="button_title"
                                            placeholder="Learn more"
                                            disabled={form.processing}
                                            class={[
                                                'w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 transition placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none disabled:opacity-50 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500',
                                                form.errors.button_title
                                                    ? 'border-rose-300 focus:border-rose-500 dark:border-rose-500/40'
                                                    : 'border-slate-200 focus:border-indigo-500 dark:border-gray-700',
                                            ]}
                                        />
                                        {form.errors.button_title && (
                                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                                {form.errors.button_title}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label
                                            for="button_link"
                                            class="mb-1 block text-xs font-semibold text-slate-700 dark:text-gray-300"
                                        >
                                            Button link
                                        </label>
                                        <input
                                            value={form.button_link}
                                            onInput={onButtonLinkInput}
                                            type="text"
                                            id="button_link"
                                            placeholder="https://example.com/details"
                                            disabled={form.processing}
                                            class={[
                                                'w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 transition placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none disabled:opacity-50 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500',
                                                form.errors.button_link
                                                    ? 'border-rose-300 focus:border-rose-500 dark:border-rose-500/40'
                                                    : 'border-slate-200 focus:border-indigo-500 dark:border-gray-700',
                                            ]}
                                        />
                                        {form.errors.button_link && (
                                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                                {form.errors.button_link}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}

                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold text-slate-900 dark:text-gray-100">
                                        Show Notice
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">
                                        Display this notice to visitors on the
                                        home page.
                                    </p>
                                </div>
                                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                    <input
                                        checked={form.is_active}
                                        onChange={onIsActiveChange}
                                        type="checkbox"
                                        class="peer sr-only"
                                        disabled={form.processing}
                                    />
                                    <span class="peer h-6 w-11 cursor-pointer rounded-full bg-slate-200 transition peer-checked:bg-indigo-600 peer-focus-visible:outline-2 peer-focus-visible:outline-indigo-500 peer-disabled:opacity-50 after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5 dark:bg-gray-700 dark:peer-checked:bg-indigo-500"></span>
                                </label>
                            </div>

                            {form.is_active && !hasContent.value && (
                                <p class="flex items-start gap-1.5 rounded-xl bg-amber-50 px-3 py-2 text-[11px] font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                    <AlertCircle class="mt-px h-3.5 w-3.5 shrink-0" />
                                    <span>
                                        Please add a title or message body
                                        before switching this notice alive.
                                    </span>
                                </p>
                            )}
                        </section>

                        <div class="flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end dark:border-gray-800">
                            <Link
                                href="/admin"
                                class="inline-flex h-9 w-full cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 sm:w-auto dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={form.processing}
                                class="inline-flex h-9 w-full cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                {form.processing ? (
                                    <Loader2 class="h-3.5 w-3.5 animate-spin" />
                                ) : (
                                    <Save class="h-3.5 w-3.5" />
                                )}
                                {form.processing ? 'Saving...' : 'Save Notice'}
                            </button>
                        </div>
                    </form>
                </div>
            </>
        );
    },
});
