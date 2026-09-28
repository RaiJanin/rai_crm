<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { CheckSquare, Plus, Search } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import Pagination from '@/components/crm/Pagination.vue';
import TaskFormDialog from '@/components/crm/TaskFormDialog.vue';
import TaskTable from '@/components/crm/TaskTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { useFilters } from '@/composables/useFilters';
import tasksRoutes from '@/routes/tasks';
import type {
    Contact,
    Deal,
    Option,
    Paginated,
    Task,
    TaskStatus,
    UserOption,
} from '@/types';

const props = defineProps<{
    scope: 'all' | 'mine';
    tasks: Paginated<Task>;
    filters: { search: string; status: string };
    taskStatuses: Option<TaskStatus>[];
    users: UserOption[];
    deals: Pick<Deal, 'deal_id' | 'title'>[];
    contacts: Pick<Contact, 'contact_id' | 'full_name'>[];
}>();

const title = computed(() =>
    props.scope === 'mine' ? 'My Tasks' : 'All Tasks',
);
const route = () =>
    props.scope === 'mine' ? tasksRoutes.myTasks() : tasksRoutes.allTasks();

setLayoutProps({
    breadcrumbs: [{ title: title.value, href: route() }],
});

const filters = useFilters(
    { search: props.filters.search, status: props.filters.status },
    route,
);
</script>

<template>
    <Head :title="title" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            :title="title"
            :description="
                scope === 'mine'
                    ? 'Action items assigned to you.'
                    : 'Every action item across deals and contacts.'
            "
        >
            <template #actions>
                <TaskFormDialog
                    :users="users"
                    :statuses="taskStatuses"
                    :deals="deals"
                    :contacts="contacts"
                >
                    <Button><Plus /> New task</Button>
                </TaskFormDialog>
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
                    placeholder="Search tasks…"
                    aria-label="Search tasks"
                    class="pl-9"
                />
            </div>
            <NativeSelect
                v-model="filters.status"
                aria-label="Filter by status"
                class="w-44"
            >
                <option value="">All statuses</option>
                <option
                    v-for="status in taskStatuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </NativeSelect>
        </div>

        <Card class="py-2">
            <CardContent class="px-2">
                <TaskTable
                    v-if="tasks.data.length"
                    :tasks="tasks.data"
                    :users="users"
                    :statuses="taskStatuses"
                    :deal-options="deals"
                    :contact-options="contacts"
                />
                <EmptyState
                    v-else
                    :icon="CheckSquare"
                    :title="
                        filters.search || filters.status
                            ? 'No tasks match your filters'
                            : 'No tasks yet'
                    "
                    class="m-2"
                />
            </CardContent>
        </Card>

        <Pagination :paginator="tasks" />
    </div>
</template>
