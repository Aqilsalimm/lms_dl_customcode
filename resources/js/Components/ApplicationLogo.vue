<script setup>
defineProps({
    className: {
        type: String,
        default: 'h-10 w-auto',
    },
});
</script>

<template>
    <img 
        src="/images/logo/logo_dl.png" 
        alt="Drastha Learning Logo" 
        :class="className"
    />
</template>
