<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerte extends Model
{
    protected $table = 'alertes';
    protected $primaryKey = 'idAlerte';

    protected $fillable = [
        'document_id', 'maintenance_id',
        'typeAlerte', 'dateAlerte', 'statut'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'idDocument');
    }

    public function maintenance()
    {
        return $this->belongsTo(Maintenance::class, 'maintenance_id', 'idMaintenance');
    }

    public function genreAlerte(): bool
    {
        if (empty($this->dateAlerte)) {
            $this->dateAlerte = now()->toDateString();
        }

        return $this->save();
    }

    public function envoyerNotification(): string
    {
        return "Notification d'alerte envoyée: {$this->typeAlerte}";
    }
}
