<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Admin\JadwalController as BaseJadwalController;

class JadwalController extends BaseJadwalController
{
    protected string $viewPrefix  = 'pages.supervisor.jadwal';
    protected string $routePrefix = 'supervisor.jadwal';
}
