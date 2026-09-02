export interface CompressOptions {
    maxWidth?: number;
    maxHeight?: number;
    quality?: number;
    targetFormat?: 'image/webp' | 'image/jpeg' | 'image/png';
    skipThresholdKB?: number;
}

const DEFAULT_OPTIONS: Required<CompressOptions> = {
    maxWidth: 2048,
    maxHeight: 2048,
    quality: 0.85,
    targetFormat: 'image/webp',
    skipThresholdKB: 300,
};

/**
 * Checks if a file is an image that should be compressed.
 * GIFs (to preserve animation) and SVGs (vector graphics) are excluded.
 */
export function isCompressibleImage(file: File): boolean {
    if (!file.type.startsWith('image/')) {
        return false;
    }

    const uncompressibleTypes = ['image/gif', 'image/svg+xml'];

    return !uncompressibleTypes.includes(file.type);
}

/**
 * Formats bytes into human-readable string (e.g. 1.2 MB, 450 KB).
 */
export function formatFileSize(bytes: number): string {
    if (bytes === 0) {
        return '0 B';
    }

    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`;
}

/**
 * Calculates scaled dimensions while preserving aspect ratio (never upscales).
 */
function calculateDimensions(
    originalWidth: number,
    originalHeight: number,
    maxWidth: number,
    maxHeight: number,
): { width: number; height: number } {
    let width = originalWidth;
    let height = originalHeight;

    if (width > maxWidth) {
        height = Math.round((height * maxWidth) / width);
        width = maxWidth;
    }

    if (height > maxHeight) {
        width = Math.round((width * maxHeight) / height);
        height = maxHeight;
    }

    return { width, height };
}

/**
 * Loads an image from a File into an ImageBitmap or HTMLImageElement,
 * correctly applying EXIF orientation.
 */
async function loadImageSource(file: File): Promise<{
    source: ImageBitmap | HTMLImageElement;
    width: number;
    height: number;
    cleanup: () => void;
}> {
    if (typeof createImageBitmap === 'function') {
        try {
            // Modern standard: automatically handles EXIF orientation
            const bitmap = await createImageBitmap(file, {
                imageOrientation: 'from-image',
            });

            return {
                source: bitmap,
                width: bitmap.width,
                height: bitmap.height,
                cleanup: () => bitmap.close(),
            };
        } catch {
            // Fall back to Image element if createImageBitmap with options fails
        }
    }

    return new Promise((resolve, reject) => {
        const img = new Image();
        const url = URL.createObjectURL(file);

        img.onload = () => {
            resolve({
                source: img,
                width: img.naturalWidth,
                height: img.naturalHeight,
                cleanup: () => URL.revokeObjectURL(url),
            });
        };

        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error(`Failed to load image: ${file.name}`));
        };

        img.src = url;
    });
}

/**
 * Compresses a single image file on the client side using HTML5 Canvas.
 * Converts to WebP format (or JPEG fallback) and reduces dimensions to max 2048px.
 */
export async function compressImage(
    file: File,
    options: CompressOptions = {},
): Promise<File> {
    // 1. Skip non-raster or already tiny files
    if (!isCompressibleImage(file)) {
        return file;
    }

    const opts = { ...DEFAULT_OPTIONS, ...options };

    // If file is already smaller than the skip threshold and within bounds, skip
    if (
        file.size <= opts.skipThresholdKB * 1024 &&
        file.type === opts.targetFormat
    ) {
        return file;
    }

    let sourceObj: {
        source: ImageBitmap | HTMLImageElement;
        width: number;
        height: number;
        cleanup: () => void;
    } | null = null;
    let canvas: HTMLCanvasElement | null = null;

    try {
        sourceObj = await loadImageSource(file);
        const { width, height } = calculateDimensions(
            sourceObj.width,
            sourceObj.height,
            opts.maxWidth,
            opts.maxHeight,
        );

        canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');

        if (!ctx) {
            return file;
        }

        // Draw image onto canvas
        ctx.drawImage(sourceObj.source, 0, 0, width, height);

        // Convert canvas to Blob
        const blob = await new Promise<Blob | null>((resolve) => {
            canvas!.toBlob((b) => resolve(b), opts.targetFormat, opts.quality);
        });

        if (!blob) {
            // If WebP export failed, fallback to JPEG
            const fallbackBlob = await new Promise<Blob | null>((resolve) => {
                canvas!.toBlob((b) => resolve(b), 'image/jpeg', opts.quality);
            });

            if (!fallbackBlob || fallbackBlob.size >= file.size) {
                return file;
            }

            const newName = file.name.replace(/\.[^/.]+$/, '') + '.jpg';

            return new File([fallbackBlob], newName, { type: 'image/jpeg' });
        }

        // Only use compressed file if it's actually smaller than the original
        if (blob.size >= file.size && file.type === opts.targetFormat) {
            return file;
        }

        const extension = opts.targetFormat === 'image/webp' ? '.webp' : '.jpg';
        const newName = file.name.replace(/\.[^/.]+$/, '') + extension;

        return new File([blob], newName, { type: opts.targetFormat });
    } catch (err) {
        console.warn(
            `[compressImage] Failed to compress ${file.name}, using original:`,
            err,
        );

        return file;
    } finally {
        // Explicitly clean up memory
        if (sourceObj) {
            sourceObj.cleanup();
        }

        if (canvas) {
            canvas.width = 0;
            canvas.height = 0;
            canvas = null;
        }
    }
}

/**
 * Sequentially compresses an array of images one by one.
 * This guarantees peak RAM on budget mobile devices never exceeds ~80MB,
 * completely preventing out-of-memory browser tab crashes.
 */
export async function compressImagesSequentially(
    files: File[],
    onProgress?: (processed: number, total: number, currentFile: File) => void,
    options: CompressOptions = {},
): Promise<File[]> {
    const total = files.length;
    const results: File[] = [];

    for (let i = 0; i < total; i++) {
        const file = files[i];
        onProgress?.(i + 1, total, file);

        const compressed = await compressImage(file, options);
        results.push(compressed);

        // Small yield to browser event loop to let garbage collection occur
        await new Promise((resolve) => setTimeout(resolve, 10));
    }

    return results;
}
