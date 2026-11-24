<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Admin\ObservasiController as BaseObservasiController;

class ObservasiController extends BaseObservasiController
{
    protected string $viewPrefix  = 'pages.supervisor.observasi';
    protected string $routePrefix = 'supervisor.observasi';
}
