<?php

namespace App\Models\Traits;

use App\Models\Historique;
use Illuminate\Support\Facades\Auth;

trait HasHistorique
{
    public static function bootHasHistorique()
    {
        static::updated(function ($model) {
            Historique::create([
                'type_entite' => class_basename($model),
                'id_entite' => $model->getKey(),
                'action' => 'update',
                'anciennes_valeurs' => $model->getOriginal(),
                'nouvelles_valeurs' => $model->getChanges(),
                'utilisateur_id' => Auth::id(),
            ]);
        });

        static::deleted(function ($model) {
            Historique::create([
                'type_entite' => class_basename($model),
                'id_entite' => $model->getKey(),
                'action' => 'delete',
                'anciennes_valeurs' => $model->getOriginal(),
                'nouvelles_valeurs' => null,
                'utilisateur_id' => Auth::id(),
            ]);
        });
    }
}

