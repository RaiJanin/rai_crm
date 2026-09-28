<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { RotateCcw, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import {
    destroy,
    restore,
} from '@/actions/App/Http/Controllers/Crm/TrashController';
import ConfirmAction from '@/components/crm/ConfirmAction.vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import Pagination from '@/components/crm/Pagination.vue';
import StageBadge from '@/components/crm/StageBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useFormatters } from '@/composables/useFormatters';
import { cn } from '@/lib/utils';
import trash from '@/routes/trash';
import type { Company, Contact, Deal, Paginated, Ticket } from '@/types';

type TrashType = 'deals' | 'contacts' | 'companies' | 'tickets';
type TrashItem = Deal | Contact | Company | Ticket;

const props = defineProps<{
    type: TrashType;
    items: Paginated<TrashItem>;
}>();

const tabs: {
    type: TrashType;
    label: string;
    route: () => ReturnType<typeof trash.deals>;
}[] = [
    { type: 'deals', label: 'Deals', route: trash.deals },
    { type: 'contacts', label: 'Contacts', route: trash.contacts },
    { type: 'companies', label: 'Companies', route: trash.companies },
    { type: 'tickets', label: 'Tickets', route: trash.tickets },
];

const current = computed(() => tabs.find((tab) => tab.type === props.type)!);

setLayoutProps({
    breadcrumbs: [
        { title: 'Trash', href: trash.deals() },
        { title: current.value.label, href: current.value.route() },
    ],
});

const { isCurrentUrl } = useCurrentUrl();
const { date, money } = useFormatters();

// Contacts carry deal/company ids and a job `title`, so branch on the page
// type rather than on which keys a record happens to have.
function idOf(item: TrashItem): number {
    switch (props.type) {
        case 'deals':
            return (item as Deal).deal_id;
        case 'contacts':
            return (item as Contact).contact_id;
        case 'tickets':
            return (item as Ticket).ticket_id;
        default:
            return (item as Company).company_id;
    }
}

function nameOf(item: TrashItem): string {
    switch (props.type) {
        case 'deals':
            return (item as Deal).title;
        case 'contacts':
            return (item as Contact).full_name;
        case 'tickets':
            return `${(item as Ticket).reference} · ${(item as Ticket).subject}`;
        default:
            return (item as Company).name;
    }
}

function detailOf(item: TrashItem): string {
    switch (props.type) {
        case 'deals':
            return (item as Deal).contact?.full_name ?? '';
        case 'contacts':
            return (item as Contact).company?.name ?? '';
        case 'tickets':
            return (item as Ticket).contact?.full_name ?? '';
        default:
            return (item as Company).industry ?? '';
    }
}

const cascadeNote: Record<TrashType, string> = {
    deals: "The deal's tasks and files are deleted with it.",
    contacts:
        "The contact's deals, tickets, tasks and files are deleted with it.",
    companies: 'Contacts, deals and tickets keep existing without a company.',
    tickets: "The ticket's tasks, conversation and files are deleted with it.",
};
</script>

<template>
    <Head :title="`Trash · ${current.label}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            title="Trash"
            description="Restore deleted records or remove them permanently."
        />

        <nav
            class="inline-flex w-fit gap-1 rounded-lg bg-muted p-1"
            aria-label="Trash type"
        >
            <Link
                v-for="tab in tabs"
                :key="tab.type"
                :href="tab.route()"
                :class="
                    cn(
                        'rounded-md px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground',
                        isCurrentUrl(tab.route()) &&
                            'bg-background text-foreground shadow-xs',
                    )
                "
            >
                {{ tab.label }}
            </Link>
        </nav>

        <Card class="py-2">
            <CardContent class="px-2">
                <Table v-if="items.data.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>
                                {{
                                    type === 'deals' || type === 'tickets'
                                        ? 'Contact'
                                        : type === 'contacts'
                                          ? 'Company'
                                          : 'Industry'
                                }}
                            </TableHead>
                            <TableHead v-if="type === 'deals'">Stage</TableHead>
                            <TableHead
                                v-if="type === 'deals'"
                                class="text-right"
                            >
                                Value
                            </TableHead>
                            <TableHead>Deleted</TableHead>
                            <TableHead class="w-48">
                                <span class="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in items.data" :key="idOf(item)">
                            <TableCell class="font-medium">
                                {{ nameOf(item) }}
                            </TableCell>
                            <TableCell>{{ detailOf(item) || '—' }}</TableCell>
                            <template v-if="type === 'deals'">
                                <TableCell>
                                    <StageBadge :stage="(item as Deal).stage" />
                                </TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ money((item as Deal).value) }}
                                </TableCell>
                            </template>
                            <TableCell>{{ date(item.deleted_at) }}</TableCell>
                            <TableCell>
                                <div class="flex justify-end gap-2">
                                    <ConfirmAction
                                        :action="
                                            restore({
                                                type,
                                                hash: item.hash_id,
                                            })
                                        "
                                        :title="`Restore ${nameOf(item)}?`"
                                        confirm-label="Restore"
                                        :destructive="false"
                                    >
                                        <Button size="sm" variant="outline">
                                            <RotateCcw /> Restore
                                        </Button>
                                    </ConfirmAction>
                                    <ConfirmAction
                                        :action="
                                            destroy({
                                                type,
                                                hash: item.hash_id,
                                            })
                                        "
                                        :title="`Permanently delete ${nameOf(item)}?`"
                                        :description="`This cannot be undone. ${cascadeNote[type]}`"
                                        confirm-label="Delete forever"
                                    >
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            aria-label="Delete forever"
                                        >
                                            <Trash2 />
                                        </Button>
                                    </ConfirmAction>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else
                    :icon="Trash2"
                    :title="`No deleted ${current.label.toLowerCase()}`"
                    class="m-2"
                />
            </CardContent>
        </Card>

        <Pagination :paginator="items" />
    </div>
</template>
