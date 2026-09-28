<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Search, Users } from '@lucide/vue';
import ContactFormDialog from '@/components/crm/ContactFormDialog.vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import Pagination from '@/components/crm/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFilters } from '@/composables/useFilters';
import companiesRoutes from '@/routes/companies';
import contactsRoutes from '@/routes/contacts';
import type { Company, Contact, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Contacts', href: contactsRoutes.index() }],
    },
});

const props = defineProps<{
    contacts: Paginated<Contact>;
    companies: Pick<Company, 'company_id' | 'name'>[];
    filters: { search: string };
}>();

const filters = useFilters({ search: props.filters.search ?? '' }, () =>
    contactsRoutes.index(),
);
</script>

<template>
    <Head title="Contacts" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            title="Contacts"
            description="People you're working with across all deals."
        >
            <template #actions>
                <ContactFormDialog :companies="companies">
                    <Button><Plus /> New contact</Button>
                </ContactFormDialog>
            </template>
        </PageHeader>

        <div class="relative max-w-sm">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="filters.search"
                type="search"
                placeholder="Search by name or email…"
                aria-label="Search contacts"
                class="pl-9"
            />
        </div>

        <Card class="py-2">
            <CardContent class="px-2">
                <Table v-if="contacts.data.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Company</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead class="text-right">Deals</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="contact in contacts.data"
                            :key="contact.contact_id"
                        >
                            <TableCell>
                                <Link
                                    :href="contactsRoutes.show(contact.hash_id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ contact.full_name }}
                                </Link>
                                <p
                                    v-if="contact.title"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ contact.title }}
                                </p>
                            </TableCell>
                            <TableCell>
                                <Link
                                    v-if="contact.company"
                                    :href="
                                        companiesRoutes.show(
                                            contact.company.hash_id,
                                        )
                                    "
                                    class="hover:underline"
                                >
                                    {{ contact.company.name }}
                                </Link>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                            <TableCell>
                                <a
                                    v-if="contact.email"
                                    :href="`mailto:${contact.email}`"
                                    class="hover:underline"
                                >
                                    {{ contact.email }}
                                </a>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                            <TableCell>{{ contact.phone ?? '—' }}</TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{ contact.deals_count }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else
                    :icon="Users"
                    :title="
                        filters.search
                            ? 'No contacts match your search'
                            : 'No contacts yet'
                    "
                    class="m-2"
                />
            </CardContent>
        </Card>

        <Pagination :paginator="contacts" />
    </div>
</template>
