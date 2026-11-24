<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Admin\NotifikasiController as BaseNotifikasiController;

class NotifikasiController extends BaseNotifikasiController
{
    protected string $viewName = 'pages.supervisor.notifikasi.index';
    protected string $roleName = 'supervisor';
}
