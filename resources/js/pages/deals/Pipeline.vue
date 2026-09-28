<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, List, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { updateStage } from '@/actions/App/Http/Controllers/Crm/DealController';
import PageHeader from '@/components/crm/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useFormatters } from '@/composables/useFormatters';
import { dealStageColors } from '@/lib/status';
import dealsRoutes from '@/routes/deals';
import type { Deal, DealStage, Option } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pipeline', href: dealsRoutes.pipeline() }],
    },
});

const props = defineProps<{
    stages: Option<DealStage>[];
    deals: Deal[];
}>();

const { money, date, isOverdue } = useFormatters();

const columns = computed(() =>
    props.stages.map((stage) => {
        const deals = props.deals.filter((deal) => deal.stage === stage.value);

        return {
            ...stage,
            deals,
            total: deals.reduce((sum, deal) => sum + Number(deal.value), 0),
        };
    }),
);

const draggingId = ref<number | null>(null);
const dropTarget = ref<DealStage | null>(null);

function onDragStart(event: DragEvent, deal: Deal) {
    draggingId.value = deal.deal_id;
    event.dataTransfer?.setData('text/plain', String(deal.deal_id));

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }
}

function onDragEnd() {
    draggingId.value = null;
    dropTarget.value = null;
}

function onDrop(event: DragEvent, stage: DealStage) {
    const id = Number(event.dataTransfer?.getData('text/plain'));
    const deal = props.deals.find((d) => d.deal_id === id);

    onDragEnd();

    if (!deal || deal.stage === stage) {
        return;
    }

    moveDeal(deal, stage);
}

function moveDeal(deal: Deal, stage: DealStage) {
    // Move the card immediately; Inertia rolls back if the request fails.
    router
        .optimistic<{ deals: Deal[] }>((current) => ({
            deals: current.deals.map((d) =>
                d.deal_id === deal.deal_id ? { ...d, stage } : d,
            ),
        }))
        .visit(updateStage(deal), {
            data: { stage },
            preserveScroll: true,
            preserveState: true,
            only: ['deals'],
        });
}
</script>

<template>
    <Head title="Pipeline" />

    <div class="flex h-full min-w-0 flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            title="Pipeline"
            description="Drag deals between stages. Won and lost columns show the last 90 days."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="dealsRoutes.index()"><List /> All deals</Link>
                </Button>
                <Button variant="cta" as-child>
                    <Link :href="dealsRoutes.create()"><Plus /> New deal</Link>
                </Button>
            </template>
        </PageHeader>

        <div
            class="-mx-4 flex flex-1 gap-3 overflow-x-auto px-4 pb-2 md:-mx-6 md:px-6 lg:-mx-8 lg:px-8"
        >
            <section
                v-for="column in columns"
                :key="column.value"
                class="flex w-72 shrink-0 flex-col rounded-xl border border-border bg-primary-soft/60 transition-colors"
                :class="{
                    'border-primary bg-primary-soft ring-2 ring-primary/20':
                        dropTarget === column.value,
                }"
                :aria-label="`${column.label} stage`"
                @dragover.prevent="dropTarget = column.value"
                @dragleave.self="dropTarget = null"
                @drop.prevent="onDrop($event, column.value)"
            >
                <header class="flex items-center gap-2 px-3 pt-3 pb-2">
                    <h2>
                        <Badge
                            class="border-transparent"
                            :class="dealStageColors[column.value]"
                        >
                            {{ column.label }}
                        </Badge>
                    </h2>
                    <span class="text-xs font-medium text-muted-foreground">
                        {{ column.deals.length }}
                    </span>
                    <span
                        class="ml-auto text-xs text-muted-foreground tabular-nums"
                    >
                        {{ money(column.total) }}
                    </span>
                </header>

                <div class="flex min-h-24 flex-1 flex-col gap-2 p-2 pt-0">
                    <Card
                        v-for="deal in column.deals"
                        :key="deal.deal_id"
                        draggable="true"
                        class="cursor-grab gap-2 px-3 py-3 transition-shadow hover:shadow-md active:cursor-grabbing"
                        :class="{ 'opacity-40': draggingId === deal.deal_id }"
                        @dragstart="onDragStart($event, deal)"
                        @dragend="onDragEnd"
                    >
                        <Link
                            :href="dealsRoutes.show(deal.hash_id)"
                            class="text-sm leading-snug font-medium hover:underline"
                            draggable="false"
                        >
                            {{ deal.title }}
                        </Link>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ deal.contact?.full_name }}
                            <template v-if="deal.company">
                                · {{ deal.company.name }}
                            </template>
                        </p>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium tabular-nums">
                                {{ money(deal.value) }}
                            </span>
                            <span
                                v-if="deal.expected_close_date"
                                class="inline-flex items-center gap-1 text-muted-foreground"
                                :class="{
                                    'text-danger':
                                        !['won', 'lost'].includes(deal.stage) &&
                                        isOverdue(deal.expected_close_date),
                                }"
                            >
                                <CalendarDays class="size-3" />
                                {{ date(deal.expected_close_date) }}
                            </span>
                        </div>
                    </Card>

                    <p
                        v-if="column.deals.length === 0"
                        class="rounded-lg border border-dashed py-6 text-center text-xs text-muted-foreground"
                    >
                        Drop deals here
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
