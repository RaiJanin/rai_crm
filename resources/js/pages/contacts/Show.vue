<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import {
    Building2,
    FileBarChart,
    Mail,
    Pencil,
    Phone,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed } from 'vue';
import { destroy } from '@/actions/App/Http/Controllers/Crm/ContactController';
import AttachmentsPanel from '@/components/crm/AttachmentsPanel.vue';
import CommentsPanel from '@/components/crm/CommentsPanel.vue';
import ConfirmAction from '@/components/crm/ConfirmAction.vue';
import ContactFormDialog from '@/components/crm/ContactFormDialog.vue';
import DealsTable from '@/components/crm/DealsTable.vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import TaskFormDialog from '@/components/crm/TaskFormDialog.vue';
import TaskTable from '@/components/crm/TaskTable.vue';
import TicketsTable from '@/components/crm/TicketsTable.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import companiesRoutes from '@/routes/companies';
import contactsRoutes from '@/routes/contacts';
import deals from '@/routes/deals';
import ticketsRoutes from '@/routes/tickets';
import reports from '@/routes/reports';
import type {
    Company,
    Contact,
    Option,
    TaskStatus,
    Ticket,
    UserOption,
} from '@/types';

const props = defineProps<{
    contact: Contact;
    companies: Pick<Company, 'company_id' | 'name'>[];
    users: UserOption[];
    taskStatuses: Option<TaskStatus>[];
    tickets: Ticket[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Contacts', href: contactsRoutes.index() },
        {
            title: props.contact.full_name,
            href: contactsRoutes.show(props.contact.hash_id),
        },
    ],
});

const dealOptions = computed(() => props.contact.deals ?? []);
</script>

<template>
    <Head :title="contact.full_name" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            :title="contact.full_name"
            :description="contact.title ?? undefined"
        >
            <template #meta>
                <div
                    class="flex flex-wrap gap-x-4 gap-y-1 pt-1 text-sm text-muted-foreground"
                >
                    <Link
                        v-if="contact.company"
                        :href="companiesRoutes.show(contact.company.hash_id)"
                        class="inline-flex items-center gap-1.5 hover:underline"
                    >
                        <Building2 class="size-3.5" />
                        {{ contact.company.name }}
                    </Link>
                    <a
                        v-if="contact.email"
                        :href="`mailto:${contact.email}`"
                        class="inline-flex items-center gap-1.5 hover:underline"
                    >
                        <Mail class="size-3.5" /> {{ contact.email }}
                    </a>
                    <a
                        v-if="contact.phone"
                        :href="`tel:${contact.phone}`"
                        class="inline-flex items-center gap-1.5 hover:underline"
                    >
                        <Phone class="size-3.5" /> {{ contact.phone }}
                    </a>
                </div>
            </template>
            <template #actions>
                <Button variant="outline" as-child>
                    <Link
                        :href="
                            deals.create({
                                query: { contact: contact.hash_id },
                            })
                        "
                    >
                        <Plus /> New deal
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="reports.contact(contact.hash_id)">
                        <FileBarChart /> Report
                    </Link>
                </Button>
                <ContactFormDialog :contact="contact" :companies="companies">
                    <Button variant="outline"><Pencil /> Edit</Button>
                </ContactFormDialog>
                <ConfirmAction
                    :action="destroy(contact)"
                    title="Move this contact to trash?"
                    description="Their deals, tickets and tasks are moved to the trash too. An admin can restore them."
                >
                    <Button variant="outline"><Trash2 /> Delete</Button>
                </ConfirmAction>
            </template>
        </PageHeader>

        <Card>
            <CardHeader><CardTitle>Deals</CardTitle></CardHeader>
            <CardContent>
                <DealsTable
                    v-if="contact.deals?.length"
                    :deals="contact.deals"
                    :show-contact="false"
                    :show-company="false"
                />
                <EmptyState v-else title="No deals with this contact yet" />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Support tickets</CardTitle>
                <CardAction>
                    <Button size="sm" variant="outline" as-child>
                        <Link
                            :href="
                                ticketsRoutes.create({
                                    query: { contact: contact.hash_id },
                                })
                            "
                        >
                            <Plus /> New ticket
                        </Link>
                    </Button>
                </CardAction>
            </CardHeader>
            <CardContent>
                <TicketsTable
                    v-if="tickets.length"
                    :tickets="tickets"
                    :show-contact="false"
                />
                <EmptyState
                    v-else
                    title="No support tickets"
                    description="Concerns this customer raises will be listed here."
                />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Tasks</CardTitle>
                <CardAction>
                    <TaskFormDialog
                        :users="users"
                        :statuses="taskStatuses"
                        :deals="dealOptions"
                        :contact-id="contact.contact_id"
                    >
                        <Button size="sm" variant="outline">
                            <Plus /> Add task
                        </Button>
                    </TaskFormDialog>
                </CardAction>
            </CardHeader>
            <CardContent>
                <TaskTable
                    v-if="contact.tasks?.length"
                    :tasks="contact.tasks"
                    :users="users"
                    :statuses="taskStatuses"
                    :deal-options="dealOptions"
                    :show-contact="false"
                />
                <EmptyState v-else title="No tasks for this contact" />
            </CardContent>
        </Card>

        <Card>
            <CardContent>
                <Tabs default-value="notes">
                    <TabsList>
                        <TabsTrigger value="notes">
                            Notes ({{ contact.comments?.length ?? 0 }})
                        </TabsTrigger>
                        <TabsTrigger value="files">
                            Files ({{ contact.attachments?.length ?? 0 }})
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent value="notes" class="pt-4">
                        <CommentsPanel
                            type="contact"
                            :id="contact.contact_id"
                            :comments="contact.comments ?? []"
                        />
                    </TabsContent>
                    <TabsContent value="files" class="pt-4">
                        <AttachmentsPanel
                            type="contact"
                            :id="contact.contact_id"
                            :attachments="contact.attachments ?? []"
                        />
                    </TabsContent>
                </Tabs>
            </CardContent>
        </Card>
    </div>
</template>
