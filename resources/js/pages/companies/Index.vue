<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Plus, Search } from '@lucide/vue';
import CompanyFormDialog from '@/components/crm/CompanyFormDialog.vue';
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
import type { Company, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Companies', href: companiesRoutes.index() }],
    },
});

const props = defineProps<{
    companies: Paginated<Company>;
    filters: { search: string };
}>();

const filters = useFilters({ search: props.filters.search ?? '' }, () =>
    companiesRoutes.index(),
);
</script>

<template>
    <Head title="Companies" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            title="Companies"
            description="Organizations your contacts and deals belong to."
        >
            <template #actions>
                <CompanyFormDialog>
                    <Button><Plus /> New company</Button>
                </CompanyFormDialog>
            </template>
        </PageHeader>

        <div class="relative max-w-sm">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="filters.search"
                type="search"
                placeholder="Search companies…"
                aria-label="Search companies"
                class="pl-9"
            />
        </div>

        <Card class="py-2">
            <CardContent class="px-2">
                <Table v-if="props.companies.data.length">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Industry</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead class="text-right">Contacts</TableHead>
                            <TableHead class="text-right">Deals</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="company in props.companies.data"
                            :key="company.company_id"
                        >
                            <TableCell class="font-medium">
                                <Link
                                    :href="
                                        companiesRoutes.show(company.hash_id)
                                    "
                                    class="hover:underline"
                                >
                                    {{ company.name }}
                                </Link>
                            </TableCell>
                            <TableCell>{{ company.industry ?? '—' }}</TableCell>
                            <TableCell>{{ company.phone ?? '—' }}</TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{ company.contacts_count }}
                            </TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{ company.deals_count }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else
                    :icon="Building2"
                    :title="
                        filters.search
                            ? 'No companies match your search'
                            : 'No companies yet'
                    "
                    class="m-2"
                />
            </CardContent>
        </Card>

        <Pagination :paginator="props.companies" />
    </div>
</template>
