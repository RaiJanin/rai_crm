<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { ticketPriorityColors } from '@/lib/status';
import type { TicketPriority } from '@/types';

defineProps<{
    priority: TicketPriority;
}>();
</script>

<template>
    <Badge
        class="border-transparent capitalize"
        :class="ticketPriorityColors[priority]"
    >
        {{ priority }}
    </Badge>
</template>
