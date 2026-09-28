<script setup lang="ts">
import { Clock, Headset, ShieldCheck, Star } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import StatCard from '@/components/crm/StatCard.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFormatters } from '@/composables/useFormatters';
import { ticketPriorityAccents, ticketStatusAccents } from '@/lib/status';
import type { TicketPriority, TicketStatus, TicketType } from '@/types';

type Breakdown<T extends string> = { value: T; label: string; count: number }[];

export type SupportMetrics = {
    total: number;
    byStatus: Breakdown<TicketStatus>;
    byPriority: Breakdown<TicketPriority>;
    byType: Breakdown<TicketType>;
    avgFirstResponseHours: number | null;
    avgResolutionHours: number | null;
    slaCompliance: number | null;
    csat: { average: number; satisfied: number; responses: number } | null;
};

const props = defineProps<{
    support: SupportMetrics;
}>();

const { duration } = useFormatters();

const maxType = computed(() =>
    Math.max(1, ...props.support.byType.map((row) => row.count)),
);
</script>

<template>
    <section class="space-y-4" aria-labelledby="support-heading">
        <div>
            <h2 id="support-heading" class="text-lg font-semibold">
                Customer support
            </h2>
            <p class="text-sm text-muted-foreground">
                Tickets opened in the last 90 days ({{ support.total }})
            </p>
        </div>

        <EmptyState
            v-if="support.total === 0"
            :icon="Headset"
            title="No tickets in the last 90 days"
        />

        <template v-else>
            <div
                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 print:grid-cols-4"
            >
                <StatCard
                    title="Avg. first response"
                    :value="duration(support.avgFirstResponseHours)"
                    :icon="Clock"
                />
                <StatCard
                    title="Avg. resolution time"
                    :value="duration(support.avgResolutionHours)"
                    :icon="Clock"
                />
                <StatCard
                    title="SLA compliance"
                    :value="
                        support.slaCompliance === null
                            ? '—'
                            : `${support.slaCompliance}%`
                    "
                    hint="Resolved within the resolution target"
                    :icon="ShieldCheck"
                />
                <StatCard
                    title="Customer satisfaction"
                    :value="support.csat ? `${support.csat.average} / 5` : '—'"
                    :hint="
                        support.csat
                            ? `${support.csat.satisfied}% satisfied · ${support.csat.responses} ratings`
                            : 'No ratings yet'
                    "
                    :icon="Star"
                />
            </div>

            <div class="grid gap-4 lg:grid-cols-3 print:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>By status</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="flex h-3 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                v-for="row in support.byStatus"
                                :key="row.value"
                                :class="ticketStatusAccents[row.value]"
                                :style="{
                                    width: `${(row.count / support.total) * 100}%`,
                                }"
                            />
                        </div>
                        <ul class="mt-4 space-y-2 text-sm">
                            <li
                                v-for="row in support.byStatus"
                                :key="row.value"
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="size-2 rounded-full"
                                    :class="ticketStatusAccents[row.value]"
                                />
                                {{ row.label }}
                                <span class="ml-auto tabular-nums">
                                    {{ row.count }}
                                </span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>By priority</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-3">
                            <li
                                v-for="row in support.byPriority"
                                :key="row.value"
                                class="space-y-1"
                            >
                                <div class="flex justify-between text-sm">
                                    <span>{{ row.label }}</span>
                                    <span class="tabular-nums">{{
                                        row.count
                                    }}</span>
                                </div>
                                <div
                                    class="h-2 overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full rounded-full"
                                        :class="
                                            ticketPriorityAccents[row.value]
                                        "
                                        :style="{
                                            width: `${(row.count / support.total) * 100}%`,
                                        }"
                                    />
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>By type</CardTitle>
                        <CardDescription
                            >What customers contact you about</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-3">
                            <li
                                v-for="row in support.byType"
                                :key="row.value"
                                class="space-y-1"
                            >
                                <div class="flex justify-between text-sm">
                                    <span>{{ row.label }}</span>
                                    <span class="tabular-nums">{{
                                        row.count
                                    }}</span>
                                </div>
                                <div
                                    class="h-2 overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary"
                                        :style="{
                                            width: `${(row.count / maxType) * 100}%`,
                                        }"
                                    />
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </template>
    </section>
</template>
