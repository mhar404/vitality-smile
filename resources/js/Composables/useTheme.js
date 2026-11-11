import { ref, computed } from 'vue';

// Shared singleton state across all components and pages
const isDark = ref(false);

const updateDocumentTheme = (val) => {
    if (typeof document === 'undefined') return;
    if (val) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

export const initTheme = () => {
    if (typeof window === 'undefined') return;
    try {
        const saved = localStorage.getItem('theme');
        // Requirement: Default to Light Mode
        const shouldBeDark = saved === 'dark';
        isDark.value = shouldBeDark;
        updateDocumentTheme(shouldBeDark);
    } catch (e) {
        isDark.value = false;
        updateDocumentTheme(false);
    }
};

export const toggleTheme = () => {
    isDark.value = !isDark.value;
    updateDocumentTheme(isDark.value);
    try {
        localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
    } catch (e) {}
};

export const setTheme = (mode) => {
    const shouldBeDark = mode === 'dark';
    isDark.value = shouldBeDark;
    updateDocumentTheme(shouldBeDark);
    try {
        localStorage.setItem('theme', shouldBeDark ? 'dark' : 'light');
    } catch (e) {}
};

// Auto-initialize immediately on module evaluation
if (typeof window !== 'undefined') {
    initTheme();
}

export function useTheme() {
    const theme = computed(() => (isDark.value ? 'dark' : 'light'));

    return {
        isDark,
        theme,
        toggleTheme,
        setTheme,
        initTheme,
    };
}

