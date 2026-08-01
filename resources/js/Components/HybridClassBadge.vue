<script setup>
import { computed } from 'vue';
import { Video, MapPin, Layers } from 'lucide-vue-next';

const props = defineProps({
  mode: {
    type: String,
    default: 'online'
  },
  size: {
    type: String,
    default: 'md'
  }
});

const badgeClasses = computed(() => {
  const normalized = (props.mode || 'online').toLowerCase();
  
  const sizeClass = props.size === 'sm' 
    ? 'px-2.5 py-1 text-[10px]' 
    : (props.size === 'lg' ? 'px-4 py-2 text-xs' : 'px-3 py-1.5 text-xs');

  if (normalized === 'hybrid') {
    return `${sizeClass} bg-blue-50 text-blue-700 border border-blue-200 font-extrabold rounded-full flex items-center gap-1.5 shadow-sm`;
  }
  if (normalized === 'offline') {
    return `${sizeClass} bg-amber-50 text-amber-700 border border-amber-200 font-extrabold rounded-full flex items-center gap-1.5 shadow-sm`;
  }
  // Online
  return `${sizeClass} bg-emerald-50 text-emerald-700 border border-emerald-200 font-extrabold rounded-full flex items-center gap-1.5 shadow-sm`;
});

const modeLabel = computed(() => {
  const normalized = (props.mode || 'online').toLowerCase();
  if (normalized === 'hybrid') return 'Kelas Hybrid';
  if (normalized === 'offline') return 'Tatap Muka (Offline)';
  return 'Kelas Online';
});
</script>

<template>
  <span :class="badgeClasses">
    <Layers v-if="(mode || '').toLowerCase() === 'hybrid'" :size="size === 'sm' ? 12 : 14" class="text-blue-600" />
    <MapPin v-else-if="(mode || '').toLowerCase() === 'offline'" :size="size === 'sm' ? 12 : 14" class="text-amber-600" />
    <Video v-else :size="size === 'sm' ? 12 : 14" class="text-emerald-600" />
    {{ modeLabel }}
  </span>
</template>
