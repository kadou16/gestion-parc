<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationConducteur extends Model
{
    protected $table = 'evaluation_conducteurs';
    protected $primaryKey = 'idEvaluation';

    protected $fillable = [
        'conducteur_id', 'nombreSinistres', 'retards', 'date_retour',
        'coutTotalGenere', 'scoreCalcule', 'message'
    ];

    public function conducteur()
    {
        return $this->belongsTo(Conducteur::class, 'conducteur_id', 'idConducteur');
    }

    public function affectations()
    {
        return $this->belongsToMany(
            Affectation::class,
            'affectation_evaluation_conducteur',
            'evaluation_conducteur_id',
            'affectation_id',
            'idEvaluation',
            'idAffectation'
        );
    }

    public function calculerScore(): float
    {
        $coutTotal = (float) $this->affectations()->sum('coutGenere');
        $this->coutTotalGenere = $coutTotal;

        $penaliteSinistres = ((int) $this->nombreSinistres) * 15;
        $penaliteRetards = ((int) $this->retards) * 5;
        $penaliteCout = min($coutTotal / 1000, 30);

        $score = max(0, 100 - $penaliteSinistres - $penaliteRetards - $penaliteCout);
        $this->scoreCalcule = round($score, 2);
        $this->save();

        return $this->scoreCalcule;
    }
}
