import { ref } from 'vue';

const isDark = ref(true);

export function useTheme() {
  function initTheme() {
    if (typeof window === 'undefined') return;
    const saved = localStorage.getItem('theme');
    if (saved === 'light') {
      isDark.value = false;
      document.documentElement.classList.remove('dark');
      document.documentElement.classList.add('light');
    } else {
      isDark.value = true;
      document.documentElement.classList.add('dark');
      document.documentElement.classList.remove('light');
    }
  }

  function toggleTheme() {
    if (typeof window === 'undefined') return;
    isDark.value = !isDark.value;
    if (isDark.value) {
      document.documentElement.classList.add('dark');
      document.documentElement.classList.remove('light');
      localStorage.setItem('theme', 'dark');
    } else {
      document.documentElement.classList.remove('dark');
      document.documentElement.classList.add('light');
      localStorage.setItem('theme', 'light');
    }
  }

  return {
    isDark,
    initTheme,
    toggleTheme,
  };
}
