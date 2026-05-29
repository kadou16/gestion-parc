<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicule extends Model
{
    use Traits\HasHistorique;

    protected $table = 'vehicules';
    protected $primaryKey = 'idVehicule';

    protected $fillable = [
        'administrateur_id',
        'immatriculation', 'marque', 'modele',
        'annee', 'kilometrage', 'statut', 'etat'
    ];

    public function administrateur()
    {
        return $this->belongsTo(Administrateur::class, 'administrateur_id', 'idAdministrateur');
    }

    public function affectations()
    {
        return $this->hasMany(Affectation::class, 'vehicule_id', 'idVehicule');
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class, 'vehicule_id', 'idVehicule');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'vehicule_id', 'idVehicule');
    }

    public function changerStatus(string $nouveauStatut): bool
    {
        return $this->update(['statut' => $nouveauStatut]);
    }

    public function verifierDisponibilite(): bool
    {
        return $this->statut === 'Disponible';
    }

    public function calculerCoutTotal(): float
    {
        $coutMaintenances = (float) $this->maintenances()->sum('cout');
        $coutAffectations = (float) $this->affectations()->sum('coutGenere');

        return $coutMaintenances + $coutAffectations;
    }
}
