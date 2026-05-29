<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'documents';
    protected $primaryKey = 'idDocument';

    protected $fillable = [
        'vehicule_id', 'type', 'dateDebut',
        'dateExpiration', 'statut', 'fichier_path'
    ];

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class, 'vehicule_id', 'idVehicule');
    }

    public function alertes()
    {
        return $this->hasMany(Alerte::class, 'document_id', 'idDocument');
    }

    public function verifierExipiration(): bool
    {
        return Carbon::parse($this->dateExpiration)->isPast();
    }

    public function renouvelerDocument(?string $nouvelleDateExpiration = null): bool
    {
        return $this->update([
            'dateDebut' => now()->toDateString(),
            'dateExpiration' => $nouvelleDateExpiration ?? now()->addYear()->toDateString(),
            'statut' => 'Valide',
        ]);
    }
}
