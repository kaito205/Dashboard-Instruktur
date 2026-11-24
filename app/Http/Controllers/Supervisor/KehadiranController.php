<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Admin\KehadiranController as BaseKehadiranController;

class KehadiranController extends BaseKehadiranController
{
    protected string $viewName    = 'pages.supervisor.kehadiran.index';
    protected string $routePrefix = 'supervisor.kehadiran';
}
