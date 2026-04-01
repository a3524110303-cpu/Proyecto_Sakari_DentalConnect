<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class ClinicaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Solo aplicar en contexto web (no en API con Sanctum)
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();

        // El super admin ve todo
        if ($user->rol === 'administrador' || $user->rol === 'admin') {
            return;
        }

        $idClinica = $user->id_clinica;

        if ($idClinica) {
            $builder->where($model->getTable() . '.id_clinica', $idClinica);
        }
    }
}
