<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Admin\DashboardController as BaseDashboardController;

class DashboardController extends BaseDashboardController
{
    protected string $viewName = 'pages.supervisor.dashboard';
}
