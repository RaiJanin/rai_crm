<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import {
    destroy,
    updateStage,
} from '@/actions/App/Http/Controllers/Crm/DealController';
import AttachmentsPanel from '@/components/crm/AttachmentsPanel.vue';
import CommentsPanel from '@/components/crm/CommentsPanel.vue';
import ConfirmAction from '@/components/crm/ConfirmAction.vue';
import DealForm from '@/components/crm/DealForm.vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import StageBadge from '@/components/crm/StageBadge.vue';
import TaskFormDialog from '@/components/crm/TaskFormDialog.vue';
import TaskTable from '@/components/crm/TaskTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { NativeSelect } from '@/components/ui/native-select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useFormatters } from '@/composables/useFormatters';
import companiesRoutes from '@/routes/companies';
import contactsRoutes from '@/routes/contacts';
import dealsRoutes from '@/routes/deals';
import type {
    Company,
    Contact,
    Deal,
    DealStage,
    Option,
    TaskStatus,
    UserOption,
} from '@/types';

const props = defineProps<{
    deal: Deal;
    contacts: Pick<Contact, 'contact_id' | 'full_name' | 'company_id'>[];
    companies: Pick<Company, 'company_id' | 'name'>[];
    stages: Option<DealStage>[];
    users: UserOption[];
    taskStatuses: Option<TaskStatus>[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'All Deals', href: dealsRoutes.index() },
        { title: props.deal.title, href: dealsRoutes.show(props.deal.hash_id) },
    ],
});

const { money, date } = useFormatters();
const editOpen = ref(false);

function changeStage(stage: string | number | null) {
    router.visit(updateStage(props.deal), {
        data: { stage },
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="deal.title" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader :title="deal.title">
            <template #meta>
                <div
                    class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 text-sm text-muted-foreground"
                >
                    <StageBadge :stage="deal.stage" />
                    <span class="font-medium text-foreground tabular-nums">
                        {{ money(deal.value) }}
                    </span>
                    <span v-if="deal.contact">
                        with
                        <Link
                            :href="contactsRoutes.show(deal.contact.hash_id)"
                            class="text-foreground hover:underline"
                        >
                            {{ deal.contact.full_name }}
                        </Link>
                    </span>
                    <span v-if="deal.company">
                        at
                        <Link
                            :href="companiesRoutes.show(deal.company.hash_id)"
                            class="text-foreground hover:underline"
                        >
                            {{ deal.company.name }}
                        </Link>
                    </span>
                </div>
            </template>
            <template #actions>
                <NativeSelect
                    :model-value="deal.stage"
                    aria-label="Change stage"
                    class="w-36"
                    @update:model-value="changeStage"
                >
                    <option
                        v-for="stage in stages"
                        :key="stage.value"
                        :value="stage.value"
                    >
                        {{ stage.label }}
                    </option>
                </NativeSelect>
                <Dialog v-model:open="editOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline"><Pencil /> Edit</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Edit deal</DialogTitle>
                        </DialogHeader>
                        <DealForm
                            :deal="deal"
                            :contacts="contacts"
                            :companies="companies"
                            :stages="stages"
                            @success="editOpen = false"
                        >
                            <template #cancel>
                                <DialogClose as-child>
                                    <Button variant="secondary" type="button">
                                        Cancel
                                    </Button>
                                </DialogClose>
                            </template>
                        </DealForm>
                    </DialogContent>
                </Dialog>
                <ConfirmAction
                    :action="destroy(deal)"
                    title="Move this deal to trash?"
                    description="Its tasks and files are moved to the trash too. An admin can restore them."
                >
                    <Button variant="outline"><Trash2 /> Delete</Button>
                </ConfirmAction>
            </template>
        </PageHeader>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="flex flex-col gap-4 lg:col-span-2">
                <Card>
                    <CardContent>
                        <Tabs default-value="tasks">
                            <TabsList>
                                <TabsTrigger value="tasks">
                                    Tasks ({{ deal.tasks?.length ?? 0 }})
                                </TabsTrigger>
                                <TabsTrigger value="notes">
                                    Notes ({{ deal.comments?.length ?? 0 }})
                                </TabsTrigger>
                                <TabsTrigger value="files">
                                    Files ({{ deal.attachments?.length ?? 0 }})
                                </TabsTrigger>
                            </TabsList>

                            <TabsContent value="tasks" class="space-y-4 pt-4">
                                <div class="flex justify-end">
                                    <TaskFormDialog
                                        :users="users"
                                        :statuses="taskStatuses"
                                        :deal-id="deal.deal_id"
                                        :contact-id="deal.contact_id"
                                    >
                                        <Button size="sm" variant="outline">
                                            <Plus /> Add task
                                        </Button>
                                    </TaskFormDialog>
                                </div>
                                <TaskTable
                                    v-if="deal.tasks?.length"
                                    :tasks="deal.tasks"
                                    :users="users"
                                    :statuses="taskStatuses"
                                    :show-deal="false"
                                    :show-contact="false"
                                />
                                <EmptyState
                                    v-else
                                    title="No tasks yet"
                                    description="Add follow-ups, calls and other action items for this deal."
                                />
                            </TabsContent>

                            <TabsContent value="notes" class="pt-4">
                                <CommentsPanel
                                    type="deal"
                                    :id="deal.deal_id"
                                    :comments="deal.comments ?? []"
                                />
                            </TabsContent>

                            <TabsContent value="files" class="pt-4">
                                <AttachmentsPanel
                                    type="deal"
                                    :id="deal.deal_id"
                                    :attachments="deal.attachments ?? []"
                                />
                            </TabsContent>
                        </Tabs>
                    </CardContent>
                </Card>
            </div>

            <Card class="self-start">
                <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                <CardContent>
                    <dl class="grid gap-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Value</dt>
                            <dd class="font-medium tabular-nums">
                                {{ money(deal.value) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">
                                Expected close
                            </dt>
                            <dd>{{ date(deal.expected_close_date) }}</dd>
                        </div>
                        <div
                            v-if="deal.closed_at"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">Closed</dt>
                            <dd>{{ date(deal.closed_at) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Owner</dt>
                            <dd>{{ deal.creator?.name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Created</dt>
                            <dd>{{ date(deal.created_at) }}</dd>
                        </div>
                        <div
                            v-if="deal.contact?.email"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">Email</dt>
                            <dd class="truncate">
                                <a
                                    :href="`mailto:${deal.contact.email}`"
                                    class="hover:underline"
                                >
                                    {{ deal.contact.email }}
                                </a>
                            </dd>
                        </div>
                        <div
                            v-if="deal.contact?.phone"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted-foreground">Phone</dt>
                            <dd>{{ deal.contact.phone }}</dd>
                        </div>
                    </dl>
                    <div v-if="deal.notes" class="mt-4 border-t pt-4">
                        <p class="mb-1 text-sm text-muted-foreground">Notes</p>
                        <p class="text-sm whitespace-pre-line">
                            {{ deal.notes }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
