<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileBarChart, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useFormatters } from '@/composables/useFormatters';
import reports from '@/routes/reports';
import type { Company, Contact } from '@/types';

type AccountTotals = {
    open_deals_count: number;
    open_tickets_count: number;
    pipeline_value: string | number | null;
    won_value: string | number | null;
    csat_average: string | number | null;
};

const props = defineProps<{
    companies: (Company & AccountTotals)[];
    customers: (Contact & AccountTotals)[];
}>();

const { money } = useFormatters();
const search = ref('');

const matches = (text: string) =>
    text.toLowerCase().includes(search.value.trim().toLowerCase());

const filteredCompanies = computed(() =>
    props.companies.filter((company) =>
        matches(`${company.name} ${company.industry ?? ''}`),
    ),
);

const filteredCustomers = computed(() =>
    props.customers.filter((customer) =>
        matches(`${customer.full_name} ${customer.company?.name ?? ''}`),
    ),
);

function csat(value: string | number | null): string {
    return value === null ? '—' : `${Number(value).toFixed(1)}/5`;
}
</script>

<template>
    <Card class="print:hidden">
        <CardHeader>
            <CardTitle>Account reports</CardTitle>
            <CardDescription>
                Open a printable report for any company or customer. Won revenue
                covers the last 12 months.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Tabs default-value="companies">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <TabsList>
                        <TabsTrigger value="companies">
                            Companies ({{ companies.length }})
                        </TabsTrigger>
                        <TabsTrigger value="customers">
                            Customers ({{ customers.length }})
                        </TabsTrigger>
                    </TabsList>
                    <div class="relative w-full max-w-xs">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            placeholder="Filter accounts…"
                            aria-label="Filter accounts"
                            class="pl-9"
                        />
                    </div>
                </div>

                <TabsContent value="companies" class="pt-4">
                    <Table v-if="filteredCompanies.length">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Company</TableHead>
                                <TableHead class="text-right"
                                    >Open pipeline</TableHead
                                >
                                <TableHead class="text-right"
                                    >Won (12 mo)</TableHead
                                >
                                <TableHead class="text-right"
                                    >Open tickets</TableHead
                                >
                                <TableHead class="text-right">CSAT</TableHead>
                                <TableHead
                                    ><span class="sr-only"
                                        >Report</span
                                    ></TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="company in filteredCompanies"
                                :key="company.company_id"
                            >
                                <TableCell>
                                    <span class="font-medium">{{
                                        company.name
                                    }}</span>
                                    <p class="text-xs text-muted-foreground">
                                        {{ company.industry ?? '—' }}
                                    </p>
                                </TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ money(company.pipeline_value) }}
                                    <p class="text-xs text-muted-foreground">
                                        {{ company.open_deals_count }} deals
                                    </p>
                                </TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ money(company.won_value) }}
                                </TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ company.open_tickets_count }}
                                </TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ csat(company.csat_average) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Link
                                        :href="reports.company(company.hash_id)"
                                        class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                                    >
                                        <FileBarChart class="size-4" /> Report
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <EmptyState v-else title="No companies match" />
                </TabsContent>

                <TabsContent value="customers" class="pt-4">
                    <Table v-if="filteredCustomers.length">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Customer</TableHead>
                                <TableHead class="text-right"
                                    >Open pipeline</TableHead
                                >
                                <TableHead class="text-right"
                                    >Won (12 mo)</TableHead
                                >
                                <TableHead class="text-right"
                                    >Open tickets</TableHead
                                >
                                <TableHead class="text-right">CSAT</TableHead>
                                <TableHead
                                    ><span class="sr-only"
                                        >Report</span
                                    ></TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="customer in filteredCustomers"
                                :key="customer.contact_id"
                            >
                                <TableCell>
                                    <span class="font-medium">{{
                                        customer.full_name
                                    }}</span>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            [
                                                customer.title,
                                                customer.company?.name,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ') || '—'
                                        }}
                                    </p>
                                </TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ money(customer.pipeline_value) }}
                                    <p class="text-xs text-muted-foreground">
                                        {{ customer.open_deals_count }} deals
                                    </p>
                                </TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ money(customer.won_value) }}
                                </TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ customer.open_tickets_count }}
                                </TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ csat(customer.csat_average) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Link
                                        :href="
                                            reports.contact(customer.hash_id)
                                        "
                                        class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                                    >
                                        <FileBarChart class="size-4" /> Report
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <EmptyState v-else title="No customers match" />
                </TabsContent>
            </Tabs>
        </CardContent>
    </Card>
</template>
