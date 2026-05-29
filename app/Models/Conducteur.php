<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conducteur extends Model
{
    use Traits\HasHistorique;

    protected $table = 'conducteurs';
    protected $primaryKey = 'idConducteur';

    protected $fillable = [
        'utilisateur_id', 'numPermis', 'DateExpPermis',
    ];

    protected function casts(): array
    {
        return [
            'DateExpPermis' => 'date',
        ];
    }

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function affectations()
    {
        return $this->hasMany(Affectation::class, 'conducteur_id', 'idConducteur');
    }

    public function evaluationConducteurs()
    {
        return $this->hasMany(EvaluationConducteur::class, 'conducteur_id', 'idConducteur');
    }

    public function consulterScore(): float
    {
        return (float) ($this->evaluationConducteurs()->latest('idEvaluation')->value('scoreCalcule') ?? 0);
    }

    public function envoyerMessage(string $message): string
    {
        return "Message envoyé au conducteur {$this->idConducteur}: {$message}";
    }
}
