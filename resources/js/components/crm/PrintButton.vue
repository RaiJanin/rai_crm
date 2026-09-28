<script setup lang="ts">
import { Printer } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    /** URL of the server-rendered print template. */
    href: string;
}>();
</script>

<template>
    <Button variant="outline" as-child>
        <a :href="href" target="_blank" rel="noopener">
            <Printer /> Print / Save PDF
        </a>
    </Button>
</template>
