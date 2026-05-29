<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Administrateur extends Model
{
    protected $table = 'administrateurs';
    protected $primaryKey = 'idAdministrateur';

    protected $fillable = ['utilisateur_id', 'motdePasse'];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function vehicules()
    {
        return $this->hasMany(Vehicule::class, 'administrateur_id', 'idAdministrateur');
    }

    public function gererUtilisateurs()
    {
        return Utilisateur::query();
    }

    public function ajouterVehicule(array $donnees): Vehicule
    {
        return $this->vehicules()->create($donnees);
    }

    public function modifierVehicule(Vehicule $vehicule, array $donnees): bool
    {
        return $vehicule->update($donnees);
    }

    public function supprimerVehicule(Vehicule $vehicule): ?bool
    {
        return $vehicule->delete();
    }
}
