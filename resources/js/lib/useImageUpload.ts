import { ref, onUnmounted } from 'vue';
import { compressImage, formatFileSize } from './imageCompression';
import type { CompressOptions } from './imageCompression';

export interface UseImageUploadOptions {
    maxOriginalSizeMB?: number; // Max size the file picker will accept before compression (default: 20MB)
    allowedTypes?: string[]; // Allowed MIME types
    compressOptions?: CompressOptions;
    onCompressed?: (file: File) => void;
    onError?: (errorMsg: string) => void;
}

export function useImageUpload(options: UseImageUploadOptions = {}) {
    const maxOriginalSizeMB = options.maxOriginalSizeMB ?? 20;
    const allowedTypes = options.allowedTypes ?? [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
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
            const msg = 'অনুমোদিত ফরম্যাট: JPG, PNG, WEBP (অথবা GIF)।';
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
            const compressed = await compressImage(
                rawFile,
                options.compressOptions,
            );
            compressedSizeFormatted.value = formatFileSize(compressed.size);

            file.value = compressed;
            previewUrl.value = URL.createObjectURL(compressed);

            options.onCompressed?.(compressed);

            return compressed;
        } catch (err: any) {
            console.error('[useImageUpload] Compression error:', err);
            // Fall back to original file if compression fails unexpectedly
            file.value = rawFile;
            previewUrl.value = URL.createObjectURL(rawFile);

            return rawFile;
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
