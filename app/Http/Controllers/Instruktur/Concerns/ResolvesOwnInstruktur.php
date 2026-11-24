<?php

namespace App\Http\Controllers\Instruktur\Concerns;

use App\Models\Instruktur;
use Illuminate\Support\Facades\Gate;

trait ResolvesOwnInstruktur
{
    protected function resolveOwnInstruktur(): ?Instruktur
    {
        $user = auth()->user();
        abort_unless($user && $user->hasRole('instruktur'), 403);

        $instruktur = Instruktur::where('user_id', $user->id)->first();
        if ($instruktur) {
            Gate::authorize('view', $instruktur);
        }

        return $instruktur;
    }
}
