<?php

namespace App\Http\Controllers\Instruktur;

use App\Http\Controllers\Admin\NotifikasiController as BaseNotifikasiController;

class NotifikasiController extends BaseNotifikasiController
{
    protected string $viewName = 'pages.instruktur.notifikasi';
    protected string $roleName = 'instruktur';
}
