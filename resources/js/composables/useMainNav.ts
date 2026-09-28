import { usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Briefcase,
    Building2,
    CheckSquare,
    Headset,
    LayoutGrid,
    Settings,
    Trash2,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';
import { dashboard } from '@/routes';
import appearance from '@/routes/appearance';
import companies from '@/routes/companies';
import contacts from '@/routes/contacts';
import deals from '@/routes/deals';
import profile from '@/routes/profile';
import reports from '@/routes/reports';
import security from '@/routes/security';
import tasks from '@/routes/tasks';
import tickets from '@/routes/tickets';
import trash from '@/routes/trash';
import type { NavItem } from '@/types';

/**
 * Primary CRM navigation. Parent items link to their first child so they
 * also work in the flat header layout.
 */
export function useMainNav(): ComputedRef<NavItem[]> {
    const page = usePage();

    return computed(() => {
        const items: NavItem[] = [
            { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
            {
                title: 'Contacts',
                href: contacts.index(),
                icon: Users,
                items: [
                    { title: 'All Contacts', href: contacts.index() },
                    {
                        title: 'Companies',
                        href: companies.index(),
                        icon: Building2,
                    },
                ],
            },
            {
                title: 'Deals',
                href: deals.pipeline(),
                icon: Briefcase,
                items: [
                    { title: 'Pipeline', href: deals.pipeline() },
                    { title: 'All Deals', href: deals.index() },
                ],
            },
            {
                title: 'Support',
                href: tickets.allTickets(),
                icon: Headset,
                items: [
                    { title: 'All Tickets', href: tickets.allTickets() },
                    { title: 'My Tickets', href: tickets.myTickets() },
                ],
            },
            {
                title: 'Tasks',
                href: tasks.allTasks(),
                icon: CheckSquare,
                items: [
                    { title: 'All Tasks', href: tasks.allTasks() },
                    { title: 'My Tasks', href: tasks.myTasks() },
                ],
            },
            { title: 'Reports', href: reports.index(), icon: BarChart3 },
        ];

        if (page.props.auth.isAdmin) {
            items.push({
                title: 'Trash',
                href: trash.deals(),
                icon: Trash2,
                items: [
                    { title: 'Deals', href: trash.deals() },
                    { title: 'Contacts', href: trash.contacts() },
                    { title: 'Companies', href: trash.companies() },
                    { title: 'Tickets', href: trash.tickets() },
                ],
            });
        }

        items.push({
            title: 'Settings',
            href: profile.edit(),
            icon: Settings,
            items: [
                { title: 'Profile', href: profile.edit() },
                { title: 'Security', href: security.edit() },
                { title: 'Appearance', href: appearance.edit() },
            ],
        });

        return items;
    });
}
