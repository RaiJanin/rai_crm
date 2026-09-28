<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SlaBadge from '@/components/crm/SlaBadge.vue';
import TicketPriorityBadge from '@/components/crm/TicketPriorityBadge.vue';
import TicketStatusBadge from '@/components/crm/TicketStatusBadge.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFormatters } from '@/composables/useFormatters';
import contactsRoutes from '@/routes/contacts';
import ticketsRoutes from '@/routes/tickets';
import type { Ticket } from '@/types';

withDefaults(
    defineProps<{
        tickets: Ticket[];
        showContact?: boolean;
    }>(),
    {
        showContact: true,
    },
);

const { relative } = useFormatters();
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>Ticket</TableHead>
                <TableHead v-if="showContact">Customer</TableHead>
                <TableHead>Priority</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>SLA</TableHead>
                <TableHead>Assignee</TableHead>
                <TableHead>Opened</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-for="ticket in tickets" :key="ticket.ticket_id">
                <TableCell class="max-w-md whitespace-normal">
                    <Link
                        :href="ticketsRoutes.show(ticket.hash_id)"
                        class="font-medium hover:underline"
                    >
                        {{ ticket.subject }}
                    </Link>
                    <p class="text-xs text-muted-foreground tabular-nums">
                        {{ ticket.reference }}
                    </p>
                </TableCell>
                <TableCell v-if="showContact">
                    <Link
                        v-if="ticket.contact"
                        :href="contactsRoutes.show(ticket.contact.hash_id)"
                        class="hover:underline"
                    >
                        {{ ticket.contact.full_name }}
                    </Link>
                    <p
                        v-if="ticket.company"
                        class="text-xs text-muted-foreground"
                    >
                        {{ ticket.company.name }}
                    </p>
                </TableCell>
                <TableCell>
                    <TicketPriorityBadge :priority="ticket.priority" />
                </TableCell>
                <TableCell>
                    <TicketStatusBadge :status="ticket.status" />
                </TableCell>
                <TableCell>
                    <SlaBadge v-if="ticket.sla" :sla="ticket.sla" />
                </TableCell>
                <TableCell>
                    <span v-if="ticket.assignee">{{
                        ticket.assignee.name
                    }}</span>
                    <span v-else class="font-medium text-highlight-foreground">
                        Unassigned
                    </span>
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ relative(ticket.created_at) }}
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
