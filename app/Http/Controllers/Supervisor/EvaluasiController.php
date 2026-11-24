<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Admin\EvaluasiController as BaseEvaluasiController;

class EvaluasiController extends BaseEvaluasiController
{
    protected string $viewPrefix  = 'pages.supervisor.evaluasi';
    protected string $routePrefix = 'supervisor.evaluasi';
}
