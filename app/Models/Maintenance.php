<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $table = 'maintenances';
    protected $primaryKey = 'idMaintenance';

    protected $fillable = [
        'vehicule_id', 'type', 'dateDebut', 'dateFin',
        'description', 'cout', 'statut', 'prestataire', 'is_deleted'
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class, 'vehicule_id', 'idVehicule');
    }

    public function alertes()
    {
        return $this->hasMany(Alerte::class, 'maintenance_id', 'idMaintenance');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'maintenance_id', 'idMaintenance');
    }

    public function planifierMaintenance(array $donnees): bool
    {
        return $this->update($donnees);
    }

    public function cloturerMaintenance(): bool
    {
        return $this->update([
            'dateFin' => now()->toDateString(),
            'statut' => 'Clôturée',
        ]);
    }
}
