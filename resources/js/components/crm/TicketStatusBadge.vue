<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { ticketStatusColors, ticketStatusLabels } from '@/lib/status';
import type { TicketStatus } from '@/types';

defineProps<{
    status: TicketStatus;
}>();
</script>

<template>
    <Badge class="border-transparent" :class="ticketStatusColors[status]">
        {{ ticketStatusLabels[status] }}
    </Badge>
</template>
