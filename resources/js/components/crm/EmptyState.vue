<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    title: string;
    description?: string;
    icon?: LucideIcon;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed px-6 py-10 text-center"
    >
        <component
            :is="icon"
            v-if="icon"
            class="size-8 text-muted-foreground/60"
        />
        <p class="text-sm font-medium">{{ title }}</p>
        <p v-if="description" class="max-w-sm text-sm text-muted-foreground">
            {{ description }}
        </p>
        <div v-if="$slots.default" class="mt-2">
            <slot />
        </div>
    </div>
</template>
