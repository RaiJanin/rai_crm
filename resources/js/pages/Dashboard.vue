<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Headset,
    Briefcase,
    CheckSquare,
    MessageSquare,
    Plus,
    Trophy,
    Users,
} from '@lucide/vue';
import DealsTable from '@/components/crm/DealsTable.vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import StatCard from '@/components/crm/StatCard.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFormatters } from '@/composables/useFormatters';
import { dashboard } from '@/routes';
import companies from '@/routes/companies';
import contacts from '@/routes/contacts';
import deals from '@/routes/deals';
import tasks from '@/routes/tasks';
import tickets from '@/routes/tickets';
import type { ActivityItem, Deal, Task } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

defineProps<{
    stats: {
        openDeals: number;
        openDealsValue: number;
        pendingTasks: number;
        overdueTasks: number;
        wonThisMonth: number;
        wonThisMonthValue: number;
        contacts: number;
        openTickets: number;
        breachedTickets: number;
    };
    recentDeals: Deal[];
    myTasks: Task[];
    recentActivity: ActivityItem[];
}>();

const { money, date, relative, isOverdue } = useFormatters();

const subjectRoutes = {
    deal: deals.show,
    contact: contacts.show,
    company: companies.show,
    ticket: tickets.show,
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <PageHeader
            title="Dashboard"
            :description="`Welcome back, ${$page.props.auth.user.name}.`"
        >
            <template #actions>
                <Button variant="cta" as-child>
                    <Link :href="deals.create()"><Plus /> New deal</Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <StatCard
                title="Open deals"
                :value="stats.openDeals"
                :hint="`${money(stats.openDealsValue)} in pipeline`"
                :icon="Briefcase"
            />
            <StatCard
                title="Won this month"
                :value="money(stats.wonThisMonthValue)"
                :hint="`${stats.wonThisMonth} deal${stats.wonThisMonth === 1 ? '' : 's'} closed`"
                :icon="Trophy"
            />
            <StatCard
                title="Pending tasks"
                :value="stats.pendingTasks"
                :hint="
                    stats.overdueTasks
                        ? `${stats.overdueTasks} overdue`
                        : 'Nothing overdue'
                "
                :icon="stats.overdueTasks ? AlertTriangle : CheckSquare"
            />
            <StatCard
                title="Open tickets"
                :value="stats.openTickets"
                :hint="
                    stats.breachedTickets
                        ? `${stats.breachedTickets} past SLA`
                        : 'All within SLA'
                "
                :icon="stats.breachedTickets ? AlertTriangle : Headset"
            />
            <StatCard title="Contacts" :value="stats.contacts" :icon="Users" />
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Recent deals</CardTitle>
                    <CardAction>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="deals.index()">View all</Link>
                        </Button>
                    </CardAction>
                </CardHeader>
                <CardContent>
                    <DealsTable
                        v-if="recentDeals.length"
                        :deals="recentDeals"
                        :show-company="false"
                    />
                    <EmptyState
                        v-else
                        :icon="Briefcase"
                        title="No deals yet"
                        description="Create your first deal to start tracking your pipeline."
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>My tasks</CardTitle>
                    <CardAction>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="tasks.myTasks()">View all</Link>
                        </Button>
                    </CardAction>
                </CardHeader>
                <CardContent>
                    <ul v-if="myTasks.length" class="space-y-3">
                        <li
                            v-for="task in myTasks"
                            :key="task.task_id"
                            class="space-y-0.5"
                        >
                            <p class="text-sm leading-snug">
                                {{ task.description }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                <span
                                    :class="{
                                        'font-medium text-danger': isOverdue(
                                            task.due_date,
                                        ),
                                    }"
                                >
                                    {{
                                        task.due_date
                                            ? `Due ${date(task.due_date)}`
                                            : 'No due date'
                                    }}
                                </span>
                                <template v-if="task.deal">
                                    ·
                                    <Link
                                        :href="deals.show(task.deal.hash_id)"
                                        class="hover:underline"
                                    >
                                        {{ task.deal.title }}
                                    </Link>
                                </template>
                            </p>
                        </li>
                    </ul>
                    <EmptyState
                        v-else
                        :icon="CheckSquare"
                        title="You're all caught up"
                    />
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Recent activity</CardTitle>
            </CardHeader>
            <CardContent>
                <ul v-if="recentActivity.length" class="space-y-4">
                    <li
                        v-for="item in recentActivity"
                        :key="item.key"
                        class="flex gap-3"
                    >
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary-soft text-primary"
                        >
                            <MessageSquare
                                v-if="item.kind === 'comment'"
                                class="size-4"
                            />
                            <Briefcase v-else class="size-4" />
                        </div>
                        <div class="min-w-0 flex-1 text-sm">
                            <p>
                                <span class="font-medium">
                                    {{ item.user ?? 'Someone' }}
                                </span>
                                {{
                                    item.kind === 'comment'
                                        ? 'added a note on'
                                        : item.text
                                }}
                                <Link
                                    v-if="item.subject"
                                    :href="
                                        subjectRoutes[item.subject.type](
                                            item.subject.id,
                                        )
                                    "
                                    class="font-medium hover:underline"
                                >
                                    {{ item.subject.label }}
                                </Link>
                            </p>
                            <p
                                v-if="item.kind === 'comment'"
                                class="truncate text-muted-foreground"
                            >
                                {{ item.text }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ relative(item.at) }}
                            </p>
                        </div>
                    </li>
                </ul>
                <EmptyState
                    v-else
                    :icon="MessageSquare"
                    title="No activity yet"
                />
            </CardContent>
        </Card>
    </div>
</template>
