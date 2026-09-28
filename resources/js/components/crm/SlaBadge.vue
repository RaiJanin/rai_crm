<script setup lang="ts">
import { Clock } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { useFormatters } from '@/composables/useFormatters';
import { slaColors, slaLabels } from '@/lib/status';
import type { Sla } from '@/types';

const props = defineProps<{
    sla: Sla;
}>();

const { relative } = useFormatters();

const title = computed(() => {
    if (!props.sla.due_at) {
        return slaLabels[props.sla.state];
    }

    const target =
        props.sla.target === 'first_response' ? 'First reply' : 'Resolution';

    return `${target} due ${relative(props.sla.due_at)}`;
});
</script>

<template>
    <Badge
        class="border-transparent"
        :class="slaColors[sla.state]"
        :title="title"
    >
        <Clock />
        {{
            sla.state === 'on_track' || sla.state === 'due_soon'
                ? title
                : slaLabels[sla.state]
        }}
    </Badge>
</template>
