<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DealForm from '@/components/crm/DealForm.vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import contactsRoutes from '@/routes/contacts';
import dealsRoutes from '@/routes/deals';
import type { Company, Contact, DealStage, Option } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'All Deals', href: dealsRoutes.index() },
            { title: 'New deal', href: dealsRoutes.create() },
        ],
    },
});

defineProps<{
    contacts: Pick<Contact, 'contact_id' | 'full_name' | 'company_id'>[];
    companies: Pick<Company, 'company_id' | 'name'>[];
    stages: Option<DealStage>[];
    defaults: { contact_id: number | null; company_id: number | null };
}>();
</script>

<template>
    <Head title="New deal" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            title="New deal"
            description="Track a new engagement with a contact."
        />

        <Card class="max-w-3xl">
            <CardContent>
                <DealForm
                    v-if="contacts.length"
                    :contacts="contacts"
                    :companies="companies"
                    :stages="stages"
                    :defaults="defaults"
                    submit-label="Create deal"
                >
                    <template #cancel>
                        <Button variant="secondary" as-child>
                            <Link :href="dealsRoutes.index()">Cancel</Link>
                        </Button>
                    </template>
                </DealForm>
                <EmptyState
                    v-else
                    title="Add a contact first"
                    description="Every deal belongs to a contact."
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
