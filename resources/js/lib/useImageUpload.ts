import { ref, onUnmounted } from 'vue';
import { compressImage, formatFileSize } from './imageCompression';
import type { CompressOptions } from './imageCompression';

export interface UseImageUploadOptions {
    maxOriginalSizeMB?: number; // Max size the file picker will accept before compression (default: 20MB)
    maxCompressedSizeMB?: number; // Max size allowed after compression (default: 5MB)
    allowedTypes?: string[]; // Allowed MIME types
    compressOptions?: CompressOptions;
    onCompressed?: (file: File) => void;
    onError?: (errorMsg: string) => void;
}

export function useImageUpload(options: UseImageUploadOptions = {}) {
    const maxOriginalSizeMB = options.maxOriginalSizeMB ?? 20;
    const maxCompressedSizeMB = options.maxCompressedSizeMB ?? 5;
    const allowedTypes = options.allowedTypes ?? [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    const file = ref<File | null>(null);
    const previewUrl = ref<string | null>(null);
    const isCompressing = ref(false);
    const error = ref<string | null>(null);
    const originalSizeFormatted = ref<string | null>(null);
    const compressedSizeFormatted = ref<string | null>(null);

    const cleanupPreview = () => {
        if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = null;
        }
    };

    const clear = () => {
        cleanupPreview();
        file.value = null;
        error.value = null;
        originalSizeFormatted.value = null;
        compressedSizeFormatted.value = null;
        isCompressing.value = false;
    };

    const processFile = async (rawFile: File): Promise<File | null> => {
        error.value = null;

        // 1. Validate file type
        if (!allowedTypes.includes(rawFile.type)) {
            const msg = 'অনুমোদিত ফরম্যাট: JPG, PNG, WEBP।';
            error.value = msg;
            options.onError?.(msg);

            return null;
        }

        // 2. Validate original size before compression (e.g. max 20MB)
        if (rawFile.size > maxOriginalSizeMB * 1024 * 1024) {
            const msg = `ফাইলের আকার ${maxOriginalSizeMB}MB এর চেয়ে কম হতে হবে।`;
            error.value = msg;
            options.onError?.(msg);

            return null;
        }

        originalSizeFormatted.value = formatFileSize(rawFile.size);
        cleanupPreview();

        try {
            isCompressing.value = true;
            let resultFile: File;

            try {
                resultFile = await compressImage(
                    rawFile,
                    options.compressOptions,
                );
            } catch (err: any) {
                console.error(
                    '[useImageUpload] Compression error, using original:',
                    err,
                );
                resultFile = rawFile;
            }

            // Enforce max size on the final compressed file
            if (resultFile.size > maxCompressedSizeMB * 1024 * 1024) {
                const msg = `ছবিটির আকার ${maxCompressedSizeMB}MB এর চেয়ে কম হতে হবে।`;
                error.value = msg;
                options.onError?.(msg);

                return null;
            }

            compressedSizeFormatted.value = formatFileSize(resultFile.size);
            file.value = resultFile;
            previewUrl.value = URL.createObjectURL(resultFile);

            options.onCompressed?.(resultFile);

            return resultFile;
        } finally {
            isCompressing.value = false;
        }
    };

    const handleFileInput = async (event: Event): Promise<File | null> => {
        const target = event.target as HTMLInputElement;

        if (!target.files || target.files.length === 0) {
            return null;
        }

        const selected = target.files[0];
        const result = await processFile(selected);
        // Reset input value so re-selecting the same file triggers change
        target.value = '';

        return result;
    };

    onUnmounted(() => {
        cleanupPreview();
    });

    return {
        file,
        previewUrl,
        isCompressing,
        error,
        originalSizeFormatted,
        compressedSizeFormatted,
        processFile,
        handleFileInput,
        clear,
    };
}
