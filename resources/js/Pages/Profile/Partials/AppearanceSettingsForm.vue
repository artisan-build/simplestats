<script setup>
import {onMounted} from 'vue';
import {useForm} from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import {ComputerDesktopIcon, MoonIcon, SunIcon} from '@heroicons/vue/24/outline';

// Define the available theme options
const themeOptions = [
    {value: 'light', label: 'Light', icon: SunIcon},
    {value: 'dark', label: 'Dark', icon: MoonIcon},
    {value: 'system', label: 'System', icon: ComputerDesktopIcon}
];

// Get the current theme from localStorage or default to 'system'
const getCurrentTheme = () => {
    return localStorage.getItem('theme') || 'system';
};

const form = useForm({
    theme: getCurrentTheme(),
});

// Apply the theme based on user selection or system preference
const applyTheme = (theme) => {
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else if (theme === 'light') {
        document.documentElement.classList.remove('dark');
    } else if (theme === 'system') {
        // Use system preference
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
};

// Update the theme when the form is submitted
const updateAppearanceSettings = () => {
    // Save the theme preference to localStorage
    localStorage.setItem('theme', form.theme);

    // Apply the selected theme
    applyTheme(form.theme);

    // Dispatch a custom event to notify other scripts
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: form.theme }));

    // Mark the form as successfully submitted
    form.recentlySuccessful = true;

    // Reset the success message after a delay
    setTimeout(() => {
        form.recentlySuccessful = false;
    }, 2000);
};

// Handle theme change
const handleThemeChange = (value) => {
    form.theme = value;

    // Save the theme preference to localStorage
    localStorage.setItem('theme', value);

    // Apply the selected theme
    applyTheme(value);

    // Dispatch a custom event to notify other scripts
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: value }));
};

// Initialize the theme on component mount
onMounted(() => {
    applyTheme(form.theme);

    // Add listener for system preference changes if using 'system' theme
    if (form.theme === 'system') {
        const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        darkModeMediaQuery.addEventListener('change', () => {
            if (form.theme === 'system') {
                applyTheme('system');
            }
        });
    }
});
</script>

<template>
    <FormSection @submitted="updateAppearanceSettings">
        <template #title>
            Appearance Settings
        </template>

        <template #description>
            Choose your preferred appearance theme.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel value="Theme" />

                <div class="mt-2 flex space-x-1.5 p-1.5 bg-gray-200 dark:bg-dark-700 rounded-xl">
                    <div v-for="option in themeOptions" :key="option.value" class="flex-1">
                        <input
                            type="radio"
                            :id="option.value"
                            :value="option.value"
                            v-model="form.theme"
                            @change="handleThemeChange(option.value)"
                            class="hidden"
                        />
                        <label :for="option.value"
                               class="flex items-center justify-center gap-2 p-2 text-sm font-medium rounded-lg cursor-pointer transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-dark-800"
                               :class="[
                                   form.theme === option.value
                                   ? 'bg-white text-gray-700 dark:bg-dark-800 dark:text-dark-300'
                                   : 'bg-gray-200 text-gray-900 dark:bg-dark-700 dark:text-white'
                               ]">
                        <component :is="option.icon" class="h-5 w-5"/>
                            {{ option.label }}
                        </label>
                    </div>
                </div>
            </div>
        </template>
    </FormSection>
</template>
