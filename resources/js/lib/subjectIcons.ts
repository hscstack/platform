import {
    Atom,
    BarChart3,
    Binary,
    BookMarked,
    BookOpen,
    Brain,
    Briefcase,
    Building2,
    Calculator,
    CircleDollarSign,
    Coins,
    Compass,
    Cpu,
    Dna,
    Factory,
    FileText,
    FlaskConical,
    Globe,
    GraduationCap,
    Landmark,
    Languages,
    Laptop,
    Lightbulb,
    Palette,
    PenTool,
    PieChart,
    Receipt,
    Scale,
    Scroll,
    Search,
    Sigma,
    Sparkles,
    TrendingUp,
} from 'lucide-vue-next';
import type { Component } from 'vue';

export const SUBJECT_ICONS: Record<string, Component> = {
    // Science & Technology
    Atom,
    FlaskConical,
    Dna,
    Sigma,
    Cpu,
    Laptop,
    Binary,

    // Languages & Humanities
    BookOpen,
    BookMarked,
    PenTool,
    Languages,
    Brain,
    Scroll,
    Globe,
    Scale,
    Compass,
    Palette,

    // Commerce & Economics
    Calculator,
    Briefcase,
    Building2,
    Coins,
    CircleDollarSign,
    Receipt,
    TrendingUp,
    BarChart3,
    PieChart,
    Factory,
    Landmark,

    // General & Guidelines
    GraduationCap,
    Sparkles,
    Lightbulb,
    FileText,
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
