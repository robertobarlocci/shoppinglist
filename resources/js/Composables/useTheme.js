import { ref, watch, onMounted } from 'vue';

export function useTheme() {
    const isDark = ref(true); // Default to dark mode

    const toggleTheme = () => {
        isDark.value = !isDark.value;
    };

    const setTheme = (dark) => {
        isDark.value = dark;
    };

    // Apply theme to document
    watch(isDark, (dark) => {
        if (dark) {
            document.documentElement.classList.add('dark');
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
        }
    }, { immediate: true });

    onMounted(() => {
        // Load theme from localStorage
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            isDark.value = savedTheme === 'dark';
        }
    });

    return {
        isDark,
        toggleTheme,
        setTheme,
    };
}
