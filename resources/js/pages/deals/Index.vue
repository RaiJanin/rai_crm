<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Briefcase, Kanban, Plus, Search } from '@lucide/vue';
import DealsTable from '@/components/crm/DealsTable.vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import Pagination from '@/components/crm/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { useFilters } from '@/composables/useFilters';
import dealsRoutes from '@/routes/deals';
import type { Deal, DealStage, Option, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'All Deals', href: dealsRoutes.index() }],
    },
});

const props = defineProps<{
    deals: Paginated<Deal>;
    stages: Option<DealStage>[];
    filters: { search: string; stage: string };
}>();

const filters = useFilters(
    { search: props.filters.search, stage: props.filters.stage },
    () => dealsRoutes.index(),
);
</script>

<template>
    <Head title="All Deals" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            title="All Deals"
            description="Every engagement you're tracking, newest first."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="dealsRoutes.pipeline()">
                        <Kanban /> Pipeline
                    </Link>
                </Button>
                <Button variant="cta" as-child>
                    <Link :href="dealsRoutes.create()"><Plus /> New deal</Link>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-wrap gap-2">
            <div class="relative w-full max-w-sm">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search deals…"
                    aria-label="Search deals"
                    class="pl-9"
                />
            </div>
            <NativeSelect
                v-model="filters.stage"
                aria-label="Filter by stage"
                class="w-44"
            >
                <option value="">All stages</option>
                <option
                    v-for="stage in stages"
                    :key="stage.value"
                    :value="stage.value"
                >
                    {{ stage.label }}
                </option>
            </NativeSelect>
        </div>

        <Card class="py-2">
            <CardContent class="px-2">
                <DealsTable v-if="deals.data.length" :deals="deals.data" />
                <EmptyState
                    v-else
                    :icon="Briefcase"
                    :title="
                        filters.search || filters.stage
                            ? 'No deals match your filters'
                            : 'No deals yet'
                    "
                    class="m-2"
                />
            </CardContent>
        </Card>

        <Pagination :paginator="deals" />
    </div>
</template>
