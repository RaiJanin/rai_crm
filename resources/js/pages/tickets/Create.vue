<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import TicketForm from '@/components/crm/TicketForm.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import contactsRoutes from '@/routes/contacts';
import ticketsRoutes from '@/routes/tickets';
import type {
    Company,
    Contact,
    Deal,
    Option,
    TicketChannel,
    TicketPriority,
    TicketType,
    UserOption,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'All Tickets', href: ticketsRoutes.allTickets() },
            { title: 'New ticket', href: ticketsRoutes.create() },
        ],
    },
});

defineProps<{
    contacts: Pick<Contact, 'contact_id' | 'full_name' | 'company_id'>[];
    companies: Pick<Company, 'company_id' | 'name'>[];
    deals: Pick<Deal, 'deal_id' | 'title' | 'contact_id'>[];
    users: UserOption[];
    types: Option<TicketType>[];
    priorities: Option<TicketPriority>[];
    channels: Option<TicketChannel>[];
    defaults: {
        contact_id: number | null;
        company_id: number | null;
        deal_id: number | null;
    };
}>();
</script>

<template>
    <Head title="New ticket" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            title="New ticket"
            description="Log a customer concern. Priority sets the response and resolution targets."
        />

        <Card class="max-w-4xl">
            <CardContent>
                <TicketForm
                    v-if="contacts.length"
                    :contacts="contacts"
                    :companies="companies"
                    :deals="deals"
                    :users="users"
                    :types="types"
                    :priorities="priorities"
                    :channels="channels"
                    :defaults="defaults"
                    submit-label="Create ticket"
                >
                    <template #cancel>
                        <Button variant="secondary" as-child>
                            <Link :href="ticketsRoutes.allTickets()"
                                >Cancel</Link
                            >
                        </Button>
                    </template>
                </TicketForm>
                <EmptyState
                    v-else
                    title="Add a contact first"
                    description="Every ticket belongs to a customer contact."
                >
                    <Button as-child>
                        <Link :href="contactsRoutes.index()"
                            >Go to contacts</Link
                        >
                    </Button>
                </EmptyState>
            </CardContent>
        </Card>
    </div>
</template>
