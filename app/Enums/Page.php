<?php

namespace App\Enums;

/**
 * Inertia page components (resources/js/pages/*.vue) rendered by CRM controllers.
 */
enum Page: string
{
    case Dashboard = 'Dashboard';

    case CompaniesIndex = 'companies/Index';
    case CompaniesShow = 'companies/Show';

    case ContactsIndex = 'contacts/Index';
    case ContactsShow = 'contacts/Show';

    case DealsIndex = 'deals/Index';
    case DealsPipeline = 'deals/Pipeline';
    case DealsCreate = 'deals/Create';
    case DealsShow = 'deals/Show';

    case TasksIndex = 'tasks/Index';

    case TicketsIndex = 'tickets/Index';
    case TicketsCreate = 'tickets/Create';
    case TicketsShow = 'tickets/Show';

    case ReportsIndex = 'reports/Index';
    case ReportsAccount = 'reports/Account';

    case TrashIndex = 'trash/Index';
}
