import {
    Atom,
    BarChart3,
    BookOpen,
    Dna,
    FlaskConical,
    Laptop,
    PenTool,
    Search,
    Sigma,
} from 'lucide-vue-next';
import type { Component } from 'vue';

export const SUBJECT_ICONS: Record<string, Component> = {
    Atom,
    FlaskConical,
    Dna,
    Sigma,
    Laptop,
    BookOpen,
    PenTool,
    BarChart3,
    Search,
};

export const DEFAULT_SUBJECT_ICON = BookOpen;

export function getSubjectIcon(name?: string | null): Component {
    if (!name || !(name in SUBJECT_ICONS)) {
        return DEFAULT_SUBJECT_ICON;
    }

    return SUBJECT_ICONS[name];
}

export const SUBJECT_ICON_LIST = Object.entries(SUBJECT_ICONS).map(
    ([key, component]) => ({
        key,
        name: key,
        component,
    }),
);
