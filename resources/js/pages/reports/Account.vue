<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Briefcase,
    Clock,
    Headset,
    Percent,
    ShieldCheck,
    Star,
    Trophy,
} from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import PrintButton from '@/components/crm/PrintButton.vue';
import SlaBadge from '@/components/crm/SlaBadge.vue';
import StageBadge from '@/components/crm/StageBadge.vue';
import StatCard from '@/components/crm/StatCard.vue';
import type { SupportMetrics } from '@/components/crm/SupportReport.vue';
import TicketPriorityBadge from '@/components/crm/TicketPriorityBadge.vue';
import TicketStatusBadge from '@/components/crm/TicketStatusBadge.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { NativeSelect } from '@/components/ui/native-select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFilters } from '@/composables/useFilters';
import { useFormatters } from '@/composables/useFormatters';
import companiesRoutes from '@/routes/companies';
import contactsRoutes from '@/routes/contacts';
import dealsRoutes from '@/routes/deals';
import reports from '@/routes/reports';
import ticketsRoutes from '@/routes/tickets';
import type { Company, Contact, Deal, Option, Task, Ticket } from '@/types';

const props = defineProps<{
    kind: 'company' | 'contact';
    account: Company | Contact;
    contacts: Contact[];
    period: string;
    periods: Option[];
    since: string | null;
    generatedAt: string;
    summary: {
        wonValue: number;
        wonCount: number;
        lostCount: number;
        pipelineValue: number;
        openDeals: number;
        winRate: number | null;
        openTickets: number;
        breachedTickets: number;
    };
    deals: Deal[];
    tickets: Ticket[];
    support: SupportMetrics;
    followUps: Task[];
}>();

const { money, date, duration } = useFormatters();

const isCompany = computed(() => props.kind === 'company');
const company = computed(() => props.account as Company);
const contact = computed(() => props.account as Contact);

const name = computed(() =>
    isCompany.value ? company.value.name : contact.value.full_name,
);

const subtitle = computed(() => {
    if (isCompany.value) {
        return company.value.industry ?? '';
    }

    return [contact.value.title, contact.value.company?.name]
        .filter(Boolean)
        .join(' · ');
});

const reportRoute = () =>
    isCompany.value
        ? reports.company(props.account.hash_id)
        : reports.contact(props.account.hash_id);

const recordRoute = computed(() =>
    isCompany.value
        ? companiesRoutes.show(props.account.hash_id)
        : contactsRoutes.show(props.account.hash_id),
);

const periodLabel = computed(
    () =>
        props.periods.find((option) => option.value === props.period)?.label ??
        '',
);

const periodRange = computed(() =>
    props.since
        ? `${date(props.since)} – ${date(props.generatedAt)}`
        : `All time to ${date(props.generatedAt)}`,
);

setLayoutProps({
    breadcrumbs: [
        { title: 'Reports', href: reports.index() },
        { title: name.value, href: reportRoute() },
    ],
});

const filters = useFilters({ period: props.period }, reportRoute);

// Opens the server-rendered print template for the selected period.
const printUrl = computed(() => {
    const options = { query: { period: filters.period, autoprint: 1 } };

    return isCompany.value
        ? reports.company.print.url(props.account.hash_id, options)
        : reports.contact.print.url(props.account.hash_id, options);
});
</script>

<template>
    <Head :title="`${name} · Report`" />

    <div
        class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8 print:gap-4 print:p-0"
    >
        <PageHeader :title="name" :description="subtitle || undefined">
            <template #meta>
                <p class="pt-1 text-sm text-muted-foreground print:hidden">
                    {{ isCompany ? 'Company' : 'Customer' }} account report ·
                    {{ periodRange }}
                </p>
            </template>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2 print:hidden">
                    <Link
                        :href="recordRoute"
                        class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="size-4" />
                        Back to {{ isCompany ? 'company' : 'contact' }}
                    </Link>
                    <NativeSelect
                        v-model="filters.period"
                        aria-label="Reporting period"
                        class="w-44"
                    >
                        <option
                            v-for="option in periods"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </NativeSelect>
                    <PrintButton :href="printUrl" />
                </div>
            </template>
        </PageHeader>

        <section
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 print:grid-cols-4 print:gap-2"
            aria-label="Summary"
        >
            <StatCard
                title="Won revenue"
                :value="money(summary.wonValue)"
                :hint="`${summary.wonCount} deal${summary.wonCount === 1 ? '' : 's'} won`"
                :icon="Trophy"
            />
            <StatCard
                title="Open pipeline"
                :value="money(summary.pipelineValue)"
                :hint="`${summary.openDeals} open deal${summary.openDeals === 1 ? '' : 's'}`"
                :icon="Briefcase"
            />
            <StatCard
                title="Win rate"
                :value="summary.winRate === null ? '—' : `${summary.winRate}%`"
                :hint="`${summary.wonCount} won · ${summary.lostCount} lost`"
                :icon="Percent"
            />
            <StatCard
                title="Open tickets"
                :value="summary.openTickets"
                :hint="
                    summary.breachedTickets
                        ? `${summary.breachedTickets} past SLA`
                        : 'All within SLA'
                "
                :icon="Headset"
            />
            <StatCard
                title="Avg. first response"
                :value="duration(support.avgFirstResponseHours)"
                :icon="Clock"
            />
            <StatCard
                title="Avg. resolution"
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
                :icon="ShieldCheck"
            />
            <StatCard
                title="Customer satisfaction"
                :value="support.csat ? `${support.csat.average} / 5` : '—'"
                :hint="
                    support.csat
                        ? `${support.csat.responses} rating${support.csat.responses === 1 ? '' : 's'}`
                        : 'No ratings'
                "
                :icon="Star"
            />
        </section>

        <Card>
            <CardHeader>
                <CardTitle>Deals</CardTitle>
                <CardDescription>
                    Open deals, plus deals won or lost in the period
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Table v-if="deals.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Deal</TableHead>
                            <TableHead v-if="isCompany">Contact</TableHead>
                            <TableHead>Stage</TableHead>
                            <TableHead class="text-right">Value</TableHead>
                            <TableHead>Expected / closed</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="deal in deals" :key="deal.deal_id">
                            <TableCell class="font-medium whitespace-normal">
                                <Link
                                    :href="dealsRoutes.show(deal.hash_id)"
                                    class="hover:underline"
                                >
                                    {{ deal.title }}
                                </Link>
                            </TableCell>
                            <TableCell v-if="isCompany">
                                {{ deal.contact?.full_name }}
                            </TableCell>
                            <TableCell
                                ><StageBadge :stage="deal.stage"
                            /></TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{ money(deal.value) }}
                            </TableCell>
                            <TableCell>
                                {{
                                    deal.closed_at
                                        ? date(deal.closed_at)
                                        : date(deal.expected_close_date)
                                }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState v-else title="No deals in this period" />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Support tickets</CardTitle>
                <CardDescription>
                    Opened in the period, plus any still active
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Table v-if="tickets.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Ticket</TableHead>
                            <TableHead v-if="isCompany">Contact</TableHead>
                            <TableHead>Priority</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Opened</TableHead>
                            <TableHead>Resolved</TableHead>
                            <TableHead>SLA</TableHead>
                            <TableHead class="text-right">CSAT</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="ticket in tickets"
                            :key="ticket.ticket_id"
                        >
                            <TableCell class="max-w-xs whitespace-normal">
                                <Link
                                    :href="ticketsRoutes.show(ticket.hash_id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ ticket.subject }}
                                </Link>
                                <p
                                    class="text-xs text-muted-foreground tabular-nums"
                                >
                                    {{ ticket.reference }}
                                </p>
                            </TableCell>
                            <TableCell v-if="isCompany">
                                {{ ticket.contact?.full_name }}
                            </TableCell>
                            <TableCell>
                                <TicketPriorityBadge
                                    :priority="ticket.priority"
                                />
                            </TableCell>
                            <TableCell>
                                <TicketStatusBadge :status="ticket.status" />
                            </TableCell>
                            <TableCell>{{ date(ticket.created_at) }}</TableCell>
                            <TableCell>{{
                                date(ticket.resolved_at)
                            }}</TableCell>
                            <TableCell>
                                <SlaBadge v-if="ticket.sla" :sla="ticket.sla" />
                            </TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{
                                    ticket.satisfaction
                                        ? `${ticket.satisfaction}/5`
                                        : '—'
                                }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState v-else title="No support tickets in this period" />
            </CardContent>
        </Card>

        <Card v-if="isCompany">
            <CardHeader>
                <CardTitle>Contacts</CardTitle>
            </CardHeader>
            <CardContent>
                <Table v-if="contacts.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Title</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead class="print:hidden">
                                <span class="sr-only">Report</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="person in contacts"
                            :key="person.contact_id"
                        >
                            <TableCell class="font-medium">
                                {{ person.full_name }}
                            </TableCell>
                            <TableCell>{{ person.title ?? '—' }}</TableCell>
                            <TableCell>{{ person.email ?? '—' }}</TableCell>
                            <TableCell>{{ person.phone ?? '—' }}</TableCell>
                            <TableCell class="text-right print:hidden">
                                <Link
                                    :href="reports.contact(person.hash_id)"
                                    class="text-sm text-primary hover:underline"
                                >
                                    Customer report
                                </Link>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState v-else title="No contacts" />
            </CardContent>
        </Card>

        <Card v-else>
            <CardHeader>
                <CardTitle>Contact details</CardTitle>
            </CardHeader>
            <CardContent>
                <dl class="grid gap-3 text-sm sm:grid-cols-2 print:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Email</dt>
                        <dd>{{ contact.email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Phone</dt>
                        <dd>{{ contact.phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Company</dt>
                        <dd>
                            <Link
                                v-if="contact.company"
                                :href="reports.company(contact.company.hash_id)"
                                class="hover:underline"
                            >
                                {{ contact.company.name }}
                            </Link>
                            <span v-else>—</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Source</dt>
                        <dd>{{ contact.source ?? '—' }}</dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Open follow-ups</CardTitle>
                <CardDescription
                    >Pending tasks for this account</CardDescription
                >
            </CardHeader>
            <CardContent>
                <Table v-if="followUps.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Task</TableHead>
                            <TableHead>Related to</TableHead>
                            <TableHead>Assigned to</TableHead>
                            <TableHead>Due</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="task in followUps" :key="task.task_id">
                            <TableCell class="max-w-md whitespace-normal">
                                {{ task.description }}
                            </TableCell>
                            <TableCell>
                                {{
                                    task.deal?.title ??
                                    task.ticket?.reference ??
                                    '—'
                                }}
                            </TableCell>
                            <TableCell>
                                {{ task.responsible?.name ?? 'Unassigned' }}
                            </TableCell>
                            <TableCell>{{ date(task.due_date) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState v-else title="No open follow-ups" />
            </CardContent>
        </Card>
    </div>
</template>
