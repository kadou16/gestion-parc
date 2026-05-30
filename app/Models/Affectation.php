<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affectation extends Model
{
    use Traits\HasHistorique;

    protected $table = 'affectations';
    protected $primaryKey = 'idAffectation';

    protected $fillable = [
        'vehicule_id', 'conducteur_id', 'dateDebut',
        'dateFin', 'etatDepart', 'etatRetour',
        'mission', 'coutGenere', 'heure_depart', 'heure_retour',
        'kilometrage_depart', 'kilometrage_retour'
    ];

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class, 'vehicule_id', 'idVehicule');
    }

    public function conducteur()
    {
        return $this->belongsTo(Conducteur::class, 'conducteur_id', 'idConducteur');
    }

    public function evaluationConducteurs()
    {
        return $this->belongsToMany(
            EvaluationConducteur::class,
            'affectation_evaluation_conducteur',
            'affectation_id',
            'evaluation_conducteur_id',
            'idAffectation',
            'idEvaluation'
        );
    }

    public function enregistrerAffectation(): bool
    {
        return $this->save();
    }

    public function cloturerAffectation(?string $etatRetour = null): bool
    {
        return $this->update([
            'dateFin' => now()->toDateString(),
            'etatRetour' => $etatRetour ?? $this->etatRetour,
        ]);
    }
}
