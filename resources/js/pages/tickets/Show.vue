<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import {
    Building2,
    Briefcase,
    CheckCircle2,
    CircleAlert,
    Clock,
    Mail,
    Pencil,
    Phone,
    Plus,
    RotateCcw,
    Star,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    destroy,
    triage,
} from '@/actions/App/Http/Controllers/Crm/TicketController';
import AttachmentsPanel from '@/components/crm/AttachmentsPanel.vue';
import CommentsPanel from '@/components/crm/CommentsPanel.vue';
import ConfirmAction from '@/components/crm/ConfirmAction.vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import ResolveTicketDialog from '@/components/crm/ResolveTicketDialog.vue';
import SlaBadge from '@/components/crm/SlaBadge.vue';
import TaskFormDialog from '@/components/crm/TaskFormDialog.vue';
import TaskTable from '@/components/crm/TaskTable.vue';
import TicketForm from '@/components/crm/TicketForm.vue';
import TicketPriorityBadge from '@/components/crm/TicketPriorityBadge.vue';
import TicketStatusBadge from '@/components/crm/TicketStatusBadge.vue';
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
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useFormatters } from '@/composables/useFormatters';
import companiesRoutes from '@/routes/companies';
import contactsRoutes from '@/routes/contacts';
import dealsRoutes from '@/routes/deals';
import ticketsRoutes from '@/routes/tickets';
import type {
    Company,
    Contact,
    Deal,
    Option,
    TaskStatus,
    Ticket,
    TicketChannel,
    TicketPriority,
    TicketStatus,
    TicketType,
    UserOption,
} from '@/types';

const props = defineProps<{
    ticket: Ticket;
    history: Pick<
        Ticket,
        | 'ticket_id'
        | 'hash_id'
        | 'reference'
        | 'subject'
        | 'status'
        | 'created_at'
    >[];
    contacts: Pick<Contact, 'contact_id' | 'full_name' | 'company_id'>[];
    companies: Pick<Company, 'company_id' | 'name'>[];
    deals: Pick<Deal, 'deal_id' | 'title' | 'contact_id'>[];
    users: UserOption[];
    types: Option<TicketType>[];
    priorities: Option<TicketPriority>[];
    statuses: Option<TicketStatus>[];
    channels: Option<TicketChannel>[];
    taskStatuses: Option<TaskStatus>[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'All Tickets', href: ticketsRoutes.allTickets() },
        {
            title: props.ticket.reference,
            href: ticketsRoutes.show(props.ticket.hash_id),
        },
    ],
});

const { dateTime, relative } = useFormatters();
const editOpen = ref(false);

const isFinished = computed(
    () =>
        props.ticket.status === 'resolved' || props.ticket.status === 'closed',
);

const channelLabel = computed(
    () =>
        props.channels.find((c) => c.value === props.ticket.channel)?.label ??
        props.ticket.channel,
);

const typeLabel = computed(
    () =>
        props.types.find((t) => t.value === props.ticket.type)?.label ??
        props.ticket.type,
);

function applyTriage(
    field: 'status' | 'priority' | 'assignee_id',
    value: string | number | null,
) {
    router.visit(triage(props.ticket), {
        data: { [field]: value === '' ? null : value },
        preserveScroll: true,
    });
}

/** Met/breached state for one SLA target. */
function targetState(dueAt: string | null, doneAt: string | null) {
    if (doneAt) {
        return dueAt && doneAt <= dueAt ? 'met' : 'missed';
    }

    if (isFinished.value) {
        return 'none';
    }

    return dueAt && new Date(dueAt) < new Date() ? 'breached' : 'pending';
}

const slaTargets = computed(() => [
    {
        label: 'First response',
        dueAt: props.ticket.first_response_due_at,
        doneAt: props.ticket.first_responded_at,
        state: targetState(
            props.ticket.first_response_due_at,
            props.ticket.first_responded_at,
        ),
    },
    {
        label: 'Resolution',
        dueAt: props.ticket.resolution_due_at,
        doneAt: props.ticket.resolved_at,
        state: targetState(
            props.ticket.resolution_due_at,
            props.ticket.resolved_at,
        ),
    },
]);
</script>

<template>
    <Head :title="`${ticket.reference} · ${ticket.subject}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader :title="ticket.subject">
            <template #meta>
                <div
                    class="flex flex-wrap items-center gap-x-3 gap-y-2 pt-1 text-sm text-muted-foreground"
                >
                    <span class="font-medium text-foreground tabular-nums">
                        {{ ticket.reference }}
                    </span>
                    <TicketStatusBadge :status="ticket.status" />
                    <TicketPriorityBadge :priority="ticket.priority" />
                    <SlaBadge v-if="ticket.sla" :sla="ticket.sla" />
                    <span>
                        {{ typeLabel }} via {{ channelLabel }} · opened
                        {{ relative(ticket.created_at) }}
                        <template v-if="ticket.creator">
                            by {{ ticket.creator.name }}
                        </template>
                    </span>
                </div>
            </template>
            <template #actions>
                <ResolveTicketDialog v-if="!isFinished" :ticket="ticket">
                    <Button><CheckCircle2 /> Resolve</Button>
                </ResolveTicketDialog>
                <template v-else>
                    <Button
                        v-if="ticket.status === 'resolved'"
                        variant="outline"
                        @click="applyTriage('status', 'closed')"
                    >
                        Close ticket
                    </Button>
                    <Button
                        variant="outline"
                        @click="applyTriage('status', 'open')"
                    >
                        <RotateCcw /> Reopen
                    </Button>
                </template>
                <Dialog v-model:open="editOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline"><Pencil /> Edit</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-3xl">
                        <DialogHeader>
                            <DialogTitle
                                >Edit {{ ticket.reference }}</DialogTitle
                            >
                        </DialogHeader>
                        <TicketForm
                            :ticket="ticket"
                            :contacts="contacts"
                            :companies="companies"
                            :deals="deals"
                            :users="users"
                            :types="types"
                            :priorities="priorities"
                            :channels="channels"
                            @success="editOpen = false"
                        >
                            <template #cancel>
                                <DialogClose as-child>
                                    <Button variant="secondary" type="button">
                                        Cancel
                                    </Button>
                                </DialogClose>
                            </template>
                        </TicketForm>
                    </DialogContent>
                </Dialog>
                <ConfirmAction
                    :action="destroy(ticket)"
                    title="Move this ticket to trash?"
                    description="Its tasks and files are moved to the trash too. An admin can restore them."
                >
                    <Button variant="outline" aria-label="Delete ticket">
                        <Trash2 />
                    </Button>
                </ConfirmAction>
            </template>
        </PageHeader>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="flex flex-col gap-4 lg:col-span-2">
                <Card>
                    <CardHeader
                        ><CardTitle>Customer's concern</CardTitle></CardHeader
                    >
                    <CardContent>
                        <p class="text-sm leading-relaxed whitespace-pre-line">
                            {{ ticket.description }}
                        </p>
                    </CardContent>
                </Card>

                <Card v-if="isFinished" class="border-success/30">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <CheckCircle2 class="size-4 text-success" />
                            Resolution
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <p
                            v-if="ticket.resolution"
                            class="text-sm whitespace-pre-line"
                        >
                            {{ ticket.resolution }}
                        </p>
                        <p v-else class="text-sm text-muted-foreground">
                            No resolution summary recorded.
                        </p>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="text-muted-foreground">
                                Customer satisfaction
                            </span>
                            <span
                                v-if="ticket.satisfaction"
                                class="inline-flex items-center gap-0.5"
                                :aria-label="`${ticket.satisfaction} out of 5`"
                            >
                                <Star
                                    v-for="n in 5"
                                    :key="n"
                                    class="size-4 fill-current"
                                    :class="
                                        n <= ticket.satisfaction
                                            ? 'text-cta'
                                            : 'text-muted-foreground/30'
                                    "
                                />
                            </span>
                            <span v-else class="text-muted-foreground">
                                Not rated
                            </span>
                            <ResolveTicketDialog :ticket="ticket">
                                <Button
                                    variant="link"
                                    size="sm"
                                    class="h-auto p-0"
                                >
                                    Edit
                                </Button>
                            </ResolveTicketDialog>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent>
                        <Tabs default-value="conversation">
                            <TabsList>
                                <TabsTrigger value="conversation">
                                    Conversation ({{
                                        ticket.comments?.length ?? 0
                                    }})
                                </TabsTrigger>
                                <TabsTrigger value="tasks">
                                    Tasks ({{ ticket.tasks?.length ?? 0 }})
                                </TabsTrigger>
                                <TabsTrigger value="files">
                                    Files ({{
                                        ticket.attachments?.length ?? 0
                                    }})
                                </TabsTrigger>
                            </TabsList>

                            <TabsContent value="conversation" class="pt-4">
                                <CommentsPanel
                                    type="ticket"
                                    :id="ticket.ticket_id"
                                    :comments="ticket.comments ?? []"
                                    allow-replies
                                />
                            </TabsContent>

                            <TabsContent value="tasks" class="space-y-4 pt-4">
                                <div class="flex justify-end">
                                    <TaskFormDialog
                                        :users="users"
                                        :statuses="taskStatuses"
                                        :ticket-id="ticket.ticket_id"
                                        :contact-id="ticket.contact_id"
                                        :deal-id="ticket.deal_id ?? undefined"
                                    >
                                        <Button size="sm" variant="outline">
                                            <Plus /> Add task
                                        </Button>
                                    </TaskFormDialog>
                                </div>
                                <TaskTable
                                    v-if="ticket.tasks?.length"
                                    :tasks="ticket.tasks"
                                    :users="users"
                                    :statuses="taskStatuses"
                                    :show-deal="false"
                                    :show-contact="false"
                                />
                                <EmptyState
                                    v-else
                                    title="No follow-up tasks"
                                    description="Add call-backs, site visits or anything the team needs to do to resolve this."
                                />
                            </TabsContent>

                            <TabsContent value="files" class="pt-4">
                                <AttachmentsPanel
                                    type="ticket"
                                    :id="ticket.ticket_id"
                                    :attachments="ticket.attachments ?? []"
                                />
                            </TabsContent>
                        </Tabs>
                    </CardContent>
                </Card>
            </div>

            <div class="flex flex-col gap-4">
                <Card>
                    <CardHeader><CardTitle>Properties</CardTitle></CardHeader>
                    <CardContent class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="triage-status">Status</Label>
                            <NativeSelect
                                id="triage-status"
                                :model-value="ticket.status"
                                @update:model-value="
                                    (v) => applyTriage('status', v)
                                "
                            >
                                <option
                                    v-for="status in statuses"
                                    :key="status.value"
                                    :value="status.value"
                                >
                                    {{ status.label }}
                                </option>
                            </NativeSelect>
                        </div>
                        <div class="grid gap-2">
                            <Label for="triage-priority">Priority</Label>
                            <NativeSelect
                                id="triage-priority"
                                :model-value="ticket.priority"
                                @update:model-value="
                                    (v) => applyTriage('priority', v)
                                "
                            >
                                <option
                                    v-for="priority in priorities"
                                    :key="priority.value"
                                    :value="priority.value"
                                >
                                    {{ priority.label }}
                                </option>
                            </NativeSelect>
                        </div>
                        <div class="grid gap-2">
                            <Label for="triage-assignee">Assignee</Label>
                            <NativeSelect
                                id="triage-assignee"
                                :model-value="ticket.assignee_id ?? ''"
                                @update:model-value="
                                    (v) => applyTriage('assignee_id', v)
                                "
                            >
                                <option value="">Unassigned</option>
                                <option
                                    v-for="user in users"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }}
                                </option>
                            </NativeSelect>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Clock class="size-4 text-primary" />
                            Service level
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul class="divide-y text-sm">
                            <li
                                v-for="target in slaTargets"
                                :key="target.label"
                                class="flex items-start justify-between gap-3 py-2.5 first:pt-0 last:pb-0"
                            >
                                <div>
                                    <p class="font-medium">
                                        {{ target.label }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Due {{ dateTime(target.dueAt) }}
                                    </p>
                                </div>
                                <div class="text-right text-xs">
                                    <p
                                        v-if="target.state === 'met'"
                                        class="inline-flex items-center gap-1 font-medium text-success"
                                    >
                                        <CheckCircle2 class="size-3.5" /> Met
                                    </p>
                                    <p
                                        v-else-if="
                                            target.state === 'missed' ||
                                            target.state === 'breached'
                                        "
                                        class="inline-flex items-center gap-1 font-medium text-danger"
                                    >
                                        <CircleAlert class="size-3.5" />
                                        {{
                                            target.state === 'missed'
                                                ? 'Missed'
                                                : 'Breached'
                                        }}
                                    </p>
                                    <p
                                        v-else-if="target.state === 'pending'"
                                        class="font-medium text-foreground"
                                    >
                                        {{ relative(target.dueAt) }}
                                    </p>
                                    <p
                                        v-if="target.doneAt"
                                        class="text-muted-foreground"
                                    >
                                        {{ dateTime(target.doneAt) }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Customer</CardTitle></CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div v-if="ticket.contact">
                            <Link
                                :href="
                                    contactsRoutes.show(ticket.contact.hash_id)
                                "
                                class="font-medium hover:underline"
                            >
                                {{ ticket.contact.full_name }}
                            </Link>
                            <p
                                v-if="ticket.contact.title"
                                class="text-xs text-muted-foreground"
                            >
                                {{ ticket.contact.title }}
                            </p>
                        </div>
                        <ul class="space-y-1.5 text-muted-foreground">
                            <li v-if="ticket.company">
                                <Link
                                    :href="
                                        companiesRoutes.show(
                                            ticket.company.hash_id,
                                        )
                                    "
                                    class="inline-flex items-center gap-2 hover:underline"
                                >
                                    <Building2 class="size-3.5" />
                                    {{ ticket.company.name }}
                                </Link>
                            </li>
                            <li v-if="ticket.contact?.email">
                                <a
                                    :href="`mailto:${ticket.contact.email}`"
                                    class="inline-flex items-center gap-2 hover:underline"
                                >
                                    <Mail class="size-3.5" />
                                    {{ ticket.contact.email }}
                                </a>
                            </li>
                            <li v-if="ticket.contact?.phone">
                                <a
                                    :href="`tel:${ticket.contact.phone}`"
                                    class="inline-flex items-center gap-2 hover:underline"
                                >
                                    <Phone class="size-3.5" />
                                    {{ ticket.contact.phone }}
                                </a>
                            </li>
                            <li v-if="ticket.deal">
                                <Link
                                    :href="
                                        dealsRoutes.show(ticket.deal.hash_id)
                                    "
                                    class="inline-flex items-center gap-2 hover:underline"
                                >
                                    <Briefcase class="size-3.5" />
                                    {{ ticket.deal.title }}
                                </Link>
                            </li>
                        </ul>

                        <div v-if="history.length" class="border-t pt-3">
                            <p
                                class="mb-2 text-xs font-medium text-muted-foreground"
                            >
                                Other tickets from this customer
                            </p>
                            <ul class="space-y-2">
                                <li
                                    v-for="item in history"
                                    :key="item.ticket_id"
                                    class="flex items-start justify-between gap-2"
                                >
                                    <Link
                                        :href="ticketsRoutes.show(item.hash_id)"
                                        class="min-w-0 truncate hover:underline"
                                        :title="item.subject"
                                    >
                                        {{ item.subject }}
                                    </Link>
                                    <TicketStatusBadge :status="item.status" />
                                </li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
