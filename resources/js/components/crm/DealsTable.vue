<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StageBadge from '@/components/crm/StageBadge.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFormatters } from '@/composables/useFormatters';
import companies from '@/routes/companies';
import contacts from '@/routes/contacts';
import dealsRoutes from '@/routes/deals';
import type { Deal } from '@/types';

withDefaults(
    defineProps<{
        deals: Deal[];
        showContact?: boolean;
        showCompany?: boolean;
    }>(),
    {
        showContact: true,
        showCompany: true,
    },
);

const { money, date } = useFormatters();
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>Deal</TableHead>
                <TableHead v-if="showContact">Contact</TableHead>
                <TableHead v-if="showCompany">Company</TableHead>
                <TableHead>Stage</TableHead>
                <TableHead class="text-right">Value</TableHead>
                <TableHead>Expected close</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-for="deal in deals" :key="deal.deal_id">
                <TableCell class="font-medium">
                    <Link
                        :href="dealsRoutes.show(deal.hash_id)"
                        class="hover:underline"
                    >
                        {{ deal.title }}
                    </Link>
                </TableCell>
                <TableCell v-if="showContact">
                    <Link
                        v-if="deal.contact"
                        :href="contacts.show(deal.contact.hash_id)"
                        class="hover:underline"
                    >
                        {{ deal.contact.full_name }}
                    </Link>
                </TableCell>
                <TableCell v-if="showCompany">
                    <Link
                        v-if="deal.company"
                        :href="companies.show(deal.company.hash_id)"
                        class="hover:underline"
                    >
                        {{ deal.company.name }}
                    </Link>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell><StageBadge :stage="deal.stage" /></TableCell>
                <TableCell class="text-right tabular-nums">
                    {{ money(deal.value) }}
                </TableCell>
                <TableCell>{{ date(deal.expected_close_date) }}</TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
