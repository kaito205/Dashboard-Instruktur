<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Admin\MonitoringController as BaseMonitoringController;

class MonitoringController extends BaseMonitoringController
{
    protected string $viewName = 'pages.supervisor.kinerja.index';
}
