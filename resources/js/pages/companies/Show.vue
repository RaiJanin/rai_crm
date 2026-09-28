<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import {
    FileBarChart,
    Globe,
    MapPin,
    Pencil,
    Phone,
    Plus,
    Trash2,
} from '@lucide/vue';
import { destroy } from '@/actions/App/Http/Controllers/Crm/CompanyController';
import AttachmentsPanel from '@/components/crm/AttachmentsPanel.vue';
import CommentsPanel from '@/components/crm/CommentsPanel.vue';
import CompanyFormDialog from '@/components/crm/CompanyFormDialog.vue';
import ConfirmAction from '@/components/crm/ConfirmAction.vue';
import DealsTable from '@/components/crm/DealsTable.vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
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
import companies from '@/routes/companies';
import contacts from '@/routes/contacts';
import deals from '@/routes/deals';
import ticketsRoutes from '@/routes/tickets';
import reports from '@/routes/reports';
import type { Company, Ticket } from '@/types';

const props = defineProps<{
    company: Company;
    tickets: Ticket[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Companies', href: companies.index() },
        {
            title: props.company.name,
            href: companies.show(props.company.hash_id),
        },
    ],
});
</script>

<template>
    <Head :title="company.name" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            :title="company.name"
            :description="company.industry ?? undefined"
        >
            <template #meta>
                <div
                    class="flex flex-wrap gap-x-4 gap-y-1 pt-1 text-sm text-muted-foreground"
                >
                    <a
                        v-if="company.website"
                        :href="company.website"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 hover:underline"
                    >
                        <Globe class="size-3.5" /> {{ company.website }}
                    </a>
                    <span
                        v-if="company.phone"
                        class="inline-flex items-center gap-1.5"
                    >
                        <Phone class="size-3.5" /> {{ company.phone }}
                    </span>
                    <span
                        v-if="company.address"
                        class="inline-flex items-center gap-1.5"
                    >
                        <MapPin class="size-3.5" /> {{ company.address }}
                    </span>
                </div>
            </template>
            <template #actions>
                <Button variant="outline" as-child>
                    <Link
                        :href="
                            deals.create({
                                query: { company: company.hash_id },
                            })
                        "
                    >
                        <Plus /> New deal
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="reports.company(company.hash_id)">
                        <FileBarChart /> Report
                    </Link>
                </Button>
                <CompanyFormDialog :company="company">
                    <Button variant="outline"><Pencil /> Edit</Button>
                </CompanyFormDialog>
                <ConfirmAction
                    :action="destroy(company)"
                    title="Move this company to trash?"
                    description="Its contacts and deals are kept. An admin can restore the company from the trash."
                >
                    <Button variant="outline"><Trash2 /> Delete</Button>
                </ConfirmAction>
            </template>
        </PageHeader>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="flex flex-col gap-4 lg:col-span-2">
                <Card>
                    <CardHeader><CardTitle>Deals</CardTitle></CardHeader>
                    <CardContent>
                        <DealsTable
                            v-if="company.deals?.length"
                            :deals="company.deals"
                            :show-company="false"
                        />
                        <EmptyState v-else title="No deals with this company" />
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
                                            query: { company: company.hash_id },
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
                        />
                        <EmptyState v-else title="No support tickets" />
                    </CardContent>
                </Card>

                <Card>
                    <CardContent>
                        <Tabs default-value="notes">
                            <TabsList>
                                <TabsTrigger value="notes">
                                    Notes ({{ company.comments?.length ?? 0 }})
                                </TabsTrigger>
                                <TabsTrigger value="files">
                                    Files ({{
                                        company.attachments?.length ?? 0
                                    }})
                                </TabsTrigger>
                            </TabsList>
                            <TabsContent value="notes" class="pt-4">
                                <CommentsPanel
                                    type="company"
                                    :id="company.company_id"
                                    :comments="company.comments ?? []"
                                />
                            </TabsContent>
                            <TabsContent value="files" class="pt-4">
                                <AttachmentsPanel
                                    type="company"
                                    :id="company.company_id"
                                    :attachments="company.attachments ?? []"
                                />
                            </TabsContent>
                        </Tabs>
                    </CardContent>
                </Card>
            </div>

            <Card class="self-start">
                <CardHeader><CardTitle>Contacts</CardTitle></CardHeader>
                <CardContent>
                    <ul v-if="company.contacts?.length" class="divide-y">
                        <li
                            v-for="contact in company.contacts"
                            :key="contact.contact_id"
                            class="py-2.5 first:pt-0 last:pb-0"
                        >
                            <Link
                                :href="contacts.show(contact.hash_id)"
                                class="text-sm font-medium hover:underline"
                            >
                                {{ contact.full_name }}
                            </Link>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    [contact.title, contact.email]
                                        .filter(Boolean)
                                        .join(' · ') || '—'
                                }}
                            </p>
                        </li>
                    </ul>
                    <EmptyState v-else title="No contacts yet" />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
