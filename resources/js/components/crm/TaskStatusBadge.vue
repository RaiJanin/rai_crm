<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { taskStatusColors, taskStatusLabels } from '@/lib/status';
import type { TaskStatus } from '@/types';

defineProps<{
    status: TaskStatus;
}>();
</script>

<template>
    <Badge class="border-transparent" :class="taskStatusColors[status]">
        {{ taskStatusLabels[status] }}
    </Badge>
</template>
