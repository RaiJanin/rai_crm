<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Circle, CircleCheck, Pencil, Trash2 } from '@lucide/vue';
import {
    destroy,
    update,
} from '@/actions/App/Http/Controllers/Crm/TaskController';
import ConfirmAction from '@/components/crm/ConfirmAction.vue';
import TaskFormDialog from '@/components/crm/TaskFormDialog.vue';
import TaskStatusBadge from '@/components/crm/TaskStatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFormatters } from '@/composables/useFormatters';
import contacts from '@/routes/contacts';
import deals from '@/routes/deals';
import tickets from '@/routes/tickets';
import type {
    Contact,
    Deal,
    Option,
    Task,
    TaskStatus,
    UserOption,
} from '@/types';

withDefaults(
    defineProps<{
        tasks: Task[];
        users: UserOption[];
        statuses: Option<TaskStatus>[];
        dealOptions?: Pick<Deal, 'deal_id' | 'title'>[];
        contactOptions?: Pick<Contact, 'contact_id' | 'full_name'>[];
        showDeal?: boolean;
        showContact?: boolean;
    }>(),
    {
        dealOptions: undefined,
        contactOptions: undefined,
        showDeal: true,
        showContact: true,
    },
);

const { date, isOverdue } = useFormatters();

function toggleDone(task: Task) {
    router.visit(update(task), {
        data: {
            description: task.description,
            deal_id: task.deal_id,
            ticket_id: task.ticket_id,
            contact_id: task.contact_id,
            responsible_person_id: task.responsible_person_id,
            due_date: task.due_date,
            status: task.status === 'done' ? 'pending' : 'done',
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead class="w-10"
                    ><span class="sr-only">Done</span></TableHead
                >
                <TableHead>Task</TableHead>
                <TableHead v-if="showDeal">Related to</TableHead>
                <TableHead v-if="showContact">Contact</TableHead>
                <TableHead>Assigned to</TableHead>
                <TableHead>Due</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="w-24"
                    ><span class="sr-only">Actions</span></TableHead
                >
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-for="task in tasks" :key="task.task_id">
                <TableCell>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        :aria-label="
                            task.status === 'done'
                                ? 'Reopen task'
                                : 'Mark task done'
                        "
                        @click="toggleDone(task)"
                    >
                        <CircleCheck
                            v-if="task.status === 'done'"
                            class="text-success"
                        />
                        <Circle v-else class="text-muted-foreground" />
                    </Button>
                </TableCell>
                <TableCell class="max-w-sm whitespace-normal">
                    <span
                        :class="{
                            'text-muted-foreground line-through':
                                task.status === 'done',
                        }"
                    >
                        {{ task.description }}
                    </span>
                </TableCell>
                <TableCell v-if="showDeal">
                    <Link
                        v-if="task.deal"
                        :href="deals.show(task.deal.hash_id)"
                        class="hover:underline"
                    >
                        {{ task.deal.title }}
                    </Link>
                    <Link
                        v-else-if="task.ticket"
                        :href="tickets.show(task.ticket.hash_id)"
                        class="hover:underline"
                    >
                        {{ task.ticket.reference }}
                    </Link>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell v-if="showContact">
                    <Link
                        v-if="task.contact"
                        :href="contacts.show(task.contact.hash_id)"
                        class="hover:underline"
                    >
                        {{ task.contact.full_name }}
                    </Link>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell>
                    {{ task.responsible?.name ?? 'Unassigned' }}
                </TableCell>
                <TableCell
                    :class="{
                        'font-medium text-danger':
                            task.status !== 'done' && isOverdue(task.due_date),
                    }"
                >
                    {{ date(task.due_date) }}
                </TableCell>
                <TableCell>
                    <TaskStatusBadge :status="task.status" />
                </TableCell>
                <TableCell>
                    <div class="flex justify-end gap-1">
                        <TaskFormDialog
                            :task="task"
                            :users="users"
                            :statuses="statuses"
                            :deals="dealOptions"
                            :contacts="contactOptions"
                        >
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Edit task"
                            >
                                <Pencil />
                            </Button>
                        </TaskFormDialog>
                        <ConfirmAction
                            :action="destroy(task)"
                            title="Delete this task?"
                            description="The task will be removed from all lists."
                        >
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Delete task"
                            >
                                <Trash2 />
                            </Button>
                        </ConfirmAction>
                    </div>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
