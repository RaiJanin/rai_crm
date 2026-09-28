<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Percent, Trophy, XCircle } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import AccountReportsCard from '@/components/crm/AccountReportsCard.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import PrintButton from '@/components/crm/PrintButton.vue';
import StatCard from '@/components/crm/StatCard.vue';
import SupportReport from '@/components/crm/SupportReport.vue';
import type { SupportMetrics } from '@/components/crm/SupportReport.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFormatters } from '@/composables/useFormatters';
import { dealStageAccents, taskStatusAccents } from '@/lib/status';
import companiesRoutes from '@/routes/companies';
import reports from '@/routes/reports';
import type { Company, Contact, DealStage, TaskStatus } from '@/types';

type AccountTotals = {
    open_deals_count: number;
    open_tickets_count: number;
    pipeline_value: string | number | null;
    won_value: string | number | null;
    csat_average: string | number | null;
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Reports', href: reports.index() }],
    },
});

const props = defineProps<{
    byStage: {
        stage: DealStage;
        label: string;
        count: number;
        value: number;
    }[];
    monthly: { month: string; won: number; wonValue: number; lost: number }[];
    byTaskStatus: { status: TaskStatus; label: string; count: number }[];
    topCompanies: {
        company_id: number;
        hash_id: string;
        name: string;
        value: number;
        deals: number;
    }[];
    winRate: number | null;
    support: SupportMetrics;
    companies: (Company & AccountTotals)[];
    customers: (Contact & AccountTotals)[];
    generatedAt: string;
}>();

const { money } = useFormatters();

const maxStageValue = computed(() =>
    Math.max(1, ...props.byStage.map((row) => row.value)),
);
const maxMonthly = computed(() =>
    Math.max(1, ...props.monthly.map((row) => Math.max(row.won, row.lost))),
);
const totalTasks = computed(() =>
    props.byTaskStatus.reduce((sum, row) => sum + row.count, 0),
);
const totalWon = computed(() =>
    props.monthly.reduce((sum, row) => sum + row.wonValue, 0),
);
const totalLost = computed(() =>
    props.monthly.reduce((sum, row) => sum + row.lost, 0),
);
</script>

<template>
    <Head title="Reports" />

    <div
        class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8 print:gap-4 print:p-0"
    >
        <PageHeader
            title="Reports"
            description="Sales pipeline, outcomes and customer support at a glance."
        >
            <template #actions>
                <PrintButton
                    :href="reports.print.url({ query: { autoprint: 1 } })"
                />
            </template>
        </PageHeader>

        <AccountReportsCard :companies="companies" :customers="customers" />

        <div class="grid gap-4 sm:grid-cols-3 print:grid-cols-3">
            <StatCard
                title="Win rate"
                :value="winRate === null ? '—' : `${winRate}%`"
                hint="Won ÷ (won + lost), all time"
                :icon="Percent"
            />
            <StatCard
                title="Won, last 6 months"
                :value="money(totalWon)"
                :icon="Trophy"
            />
            <StatCard
                title="Lost, last 6 months"
                :value="totalLost"
                hint="deals"
                :icon="XCircle"
            />
        </div>

        <div class="grid gap-4 lg:grid-cols-2 print:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Value by stage</CardTitle>
                    <CardDescription
                        >All active and closed deals</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <ul class="space-y-3">
                        <li
                            v-for="row in byStage"
                            :key="row.stage"
                            class="space-y-1"
                        >
                            <div
                                class="flex items-baseline justify-between text-sm"
                            >
                                <span>
                                    {{ row.label }}
                                    <span class="text-muted-foreground">
                                        · {{ row.count }}
                                    </span>
                                </span>
                                <span class="tabular-nums">{{
                                    money(row.value)
                                }}</span>
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full"
                                    :class="dealStageAccents[row.stage]"
                                    :style="{
                                        width: `${(row.value / maxStageValue) * 100}%`,
                                    }"
                                />
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Won vs lost</CardTitle>
                    <CardDescription>Deals closed per month</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex h-48 items-end gap-3">
                        <div
                            v-for="row in monthly"
                            :key="row.month"
                            class="flex h-full flex-1 flex-col items-center gap-2"
                        >
                            <div
                                class="flex w-full flex-1 items-end justify-center gap-1"
                                :title="`${row.month}: ${row.won} won (${money(row.wonValue)}), ${row.lost} lost`"
                            >
                                <div
                                    class="w-1/3 max-w-5 rounded-t bg-success"
                                    :style="{
                                        height: `${(row.won / maxMonthly) * 100}%`,
                                    }"
                                />
                                <div
                                    class="w-1/3 max-w-5 rounded-t bg-danger"
                                    :style="{
                                        height: `${(row.lost / maxMonthly) * 100}%`,
                                    }"
                                />
                            </div>
                            <span
                                class="text-xs whitespace-nowrap text-muted-foreground"
                            >
                                {{ row.month.split(' ')[0] }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-4 text-xs text-muted-foreground">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-success" />
                            Won
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-danger" /> Lost
                        </span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Tasks by status</CardTitle>
                    <CardDescription
                        >{{ totalTasks }} tasks in total</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div
                        v-if="totalTasks"
                        class="flex h-3 overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            v-for="row in byTaskStatus"
                            :key="row.status"
                            :class="taskStatusAccents[row.status]"
                            :style="{
                                width: `${(row.count / totalTasks) * 100}%`,
                            }"
                        />
                    </div>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li
                            v-for="row in byTaskStatus"
                            :key="row.status"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="size-2 rounded-full"
                                :class="taskStatusAccents[row.status]"
                            />
                            {{ row.label }}
                            <span class="ml-auto tabular-nums">{{
                                row.count
                            }}</span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Top companies</CardTitle>
                    <CardDescription>By total won deal value</CardDescription>
                </CardHeader>
                <CardContent>
                    <ol v-if="topCompanies.length" class="space-y-3 text-sm">
                        <li
                            v-for="(company, index) in topCompanies"
                            :key="company.company_id"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="w-4 text-muted-foreground tabular-nums"
                            >
                                {{ index + 1 }}
                            </span>
                            <Link
                                :href="companiesRoutes.show(company.hash_id)"
                                class="font-medium hover:underline"
                            >
                                {{ company.name }}
                            </Link>
                            <span class="text-xs text-muted-foreground">
                                {{ company.deals }} won
                            </span>
                            <span class="ml-auto tabular-nums">
                                {{ money(company.value) }}
                            </span>
                        </li>
                    </ol>
                    <EmptyState
                        v-else
                        title="No won deals with a company yet"
                    />
                </CardContent>
            </Card>
        </div>

        <SupportReport :support="support" />
    </div>
</template>
