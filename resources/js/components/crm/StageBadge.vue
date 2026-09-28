<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { dealStageColors } from '@/lib/status';
import type { DealStage } from '@/types';

defineProps<{
    stage: DealStage;
}>();
</script>

<template>
    <Badge
        class="border-transparent capitalize"
        :class="dealStageColors[stage]"
    >
        {{ stage }}
    </Badge>
</template>
