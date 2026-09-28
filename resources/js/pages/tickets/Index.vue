<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { Headset, Plus, Search } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import Pagination from '@/components/crm/Pagination.vue';
import TicketsTable from '@/components/crm/TicketsTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { useFilters } from '@/composables/useFilters';
import { cn } from '@/lib/utils';
import ticketsRoutes from '@/routes/tickets';
import type {
    Option,
    Paginated,
    Ticket,
    TicketPriority,
    TicketStatus,
} from '@/types';

const props = defineProps<{
    scope: 'all' | 'mine';
    tickets: Paginated<Ticket>;
    filters: { search: string; status: string; priority: string };
    counts: { active: number; breached: number; unassigned: number };
    statuses: Option<TicketStatus>[];
    priorities: Option<TicketPriority>[];
}>();

const title = computed(() =>
    props.scope === 'mine' ? 'My Tickets' : 'All Tickets',
);
const route = () =>
    props.scope === 'mine'
        ? ticketsRoutes.myTickets()
        : ticketsRoutes.allTickets();

setLayoutProps({
    breadcrumbs: [{ title: title.value, href: route() }],
});

const filters = useFilters(
    {
        search: props.filters.search,
        status: props.filters.status,
        priority: props.filters.priority,
    },
    route,
);

const views = computed(() => [
    { value: 'active', label: 'Active', count: props.counts.active },
    { value: 'breached', label: 'SLA breached', count: props.counts.breached },
]);
</script>

<template>
    <Head :title="title" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            :title="title"
            :description="
                scope === 'mine'
                    ? 'Customer concerns assigned to you.'
                    : 'Every customer concern, most urgent first.'
            "
        >
            <template #meta>
                <p
                    v-if="counts.unassigned && scope === 'all'"
                    class="pt-1 text-sm font-medium text-highlight-foreground"
                >
                    {{ counts.unassigned }} active ticket{{
                        counts.unassigned === 1 ? '' : 's'
                    }}
                    waiting for an assignee
                </p>
            </template>
            <template #actions>
                <Button variant="cta" as-child>
                    <Link :href="ticketsRoutes.create()">
                        <Plus /> New ticket
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-center gap-2">
            <div
                class="inline-flex gap-1 rounded-lg bg-muted p-1"
                role="radiogroup"
                aria-label="Ticket view"
            >
                <button
                    v-for="view in views"
                    :key="view.value"
                    type="button"
                    role="radio"
                    :aria-checked="filters.status === view.value"
                    :class="
                        cn(
                            'inline-flex items-center gap-1.5 rounded-md px-3 py-1 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground',
                            filters.status === view.value &&
                                'bg-card text-foreground shadow-xs',
                        )
                    "
                    @click="filters.status = view.value"
                >
                    {{ view.label }}
                    <span
                        class="rounded-full px-1.5 text-xs tabular-nums"
                        :class="
                            view.value === 'breached' && view.count
                                ? 'bg-danger-soft text-danger-foreground'
                                : 'bg-secondary text-secondary-foreground'
                        "
                    >
                        {{ view.count }}
                    </span>
                </button>
            </div>

            <NativeSelect
                v-model="filters.status"
                aria-label="Filter by status"
                class="w-44"
            >
                <option value="active">All active</option>
                <option value="breached">SLA breached</option>
                <option
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </NativeSelect>

            <NativeSelect
                v-model="filters.priority"
                aria-label="Filter by priority"
                class="w-40"
            >
                <option value="">Any priority</option>
                <option
                    v-for="priority in priorities"
                    :key="priority.value"
                    :value="priority.value"
                >
                    {{ priority.label }}
                </option>
            </NativeSelect>

            <div class="relative w-full max-w-xs">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search tickets…"
                    aria-label="Search tickets"
                    class="pl-9"
                />
            </div>
        </div>

        <Card class="py-2">
            <CardContent class="px-2">
                <TicketsTable
                    v-if="tickets.data.length"
                    :tickets="tickets.data"
                />
                <EmptyState
                    v-else
                    :icon="Headset"
                    :title="
                        filters.status === 'breached'
                            ? 'No SLA breaches — nice work'
                            : 'No tickets match these filters'
                    "
                    class="m-2"
                />
            </CardContent>
        </Card>

        <Pagination :paginator="tickets" />
    </div>
</template>
