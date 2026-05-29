<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Historique extends Model
{
    protected $table = 'historiques';
    protected $primaryKey = 'idHistorique';

    protected $fillable = [
        'type_entite',
        'id_entite',
        'action',
        'anciennes_valeurs',
        'nouvelles_valeurs',
        'utilisateur_id',
    ];

    protected $casts = [
        'anciennes_valeurs' => 'array',
        'nouvelles_valeurs' => 'array',
    ];
}
