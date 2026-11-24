<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Admin\LaporanController as BaseLaporanController;

class LaporanController extends BaseLaporanController
{
    protected string $viewName    = 'pages.supervisor.laporan.index';
    protected string $routePrefix = 'supervisor';
}
