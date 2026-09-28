<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';

defineProps<{
    title: string;
    value: string | number;
    hint?: string;
    icon?: LucideIcon;
}>();
</script>

<template>
    <Card class="gap-0 py-5">
        <CardContent class="flex items-start justify-between gap-4 px-5">
            <div class="min-w-0 space-y-1">
                <p class="text-sm text-muted-foreground">{{ title }}</p>
                <p class="truncate text-2xl font-semibold tracking-tight">
                    {{ value }}
                </p>
                <p v-if="hint" class="text-xs text-muted-foreground">
                    {{ hint }}
                </p>
            </div>
            <div
                v-if="icon"
                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-soft text-primary"
            >
                <component :is="icon" class="size-4" />
            </div>
        </CardContent>
    </Card>
</template>
