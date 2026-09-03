<script setup lang="ts">
/**
 * Material Symbols Rounded — SVG-based via font glyphs
 * Uses Google Material Symbols Rounded font with FILL=1, wght=400, GRAD=0, opsz=24
 * Rendered as <span> with font-variation-settings — vector (SVG) glyphs, filled, weight 400 (bolder)
 * Bridging: allows TypeScript usage in Vue SFCs without importing individual SVG files
 */
type Props = {
    name: string;
    filled?: boolean;
    weight?: number;
    opsz?: number;
    size?: number | string;
    class?: string;
};

withDefaults(defineProps<Props>(), {
    filled: true,
    weight: 400,
    opsz: 24,
    size: 24,
});
</script>

<template>
    <span
        class="material-symbols-rounded select-none"
        :class="($props.class as string)"
        :style="{
            fontVariationSettings: `'FILL' ${filled ? 1 : 0}, 'wght' ${weight}, 'GRAD' 0, 'opsz' ${opsz}`,
            fontSize: typeof size === 'number' ? `${size}px` : (size as string),
        }"
        aria-hidden="true"
        >{{ name }}</span
    >
</template>
