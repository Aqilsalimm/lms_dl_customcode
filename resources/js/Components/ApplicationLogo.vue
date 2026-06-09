<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    className: {
        type: String,
        default: 'h-10 w-auto object-contain',
    },
});

const logoUrl = computed(() => {
    const settings = usePage().props.settings;
    const customLogo = settings?.course_logo;
    if (customLogo && customLogo !== '/images/logo-placeholder.png') {
        return customLogo;
    }
    return '/images/logo/logo_dl.png';
});
</script>

<template>
    <img 
        :src="logoUrl" 
        alt="Drastha Learning Logo" 
        :class="className"
    />
</template>
