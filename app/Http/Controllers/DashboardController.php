<?php

namespace App\Http\Controllers;

use App\Enums\Page;
use App\Services\Crm\DashboardService;
use Illuminate\Http\Request;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): Response
    {
        return $this->inertia(Page::Dashboard, [
            'stats' => $dashboard->stats(),
            'recentDeals' => $dashboard->recentDeals(),
            'myTasks' => $dashboard->tasksFor($request->user()),
            'recentActivity' => $dashboard->activity(),
        ]);
    }
}
