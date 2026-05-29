<?php
namespace App\Http\Controllers;

use App\Models\EvaluationConducteur;
use Illuminate\Http\Request;

class EvaluationConducteurController extends Controller
{
    public function index()
    {
        return response()->json(
            EvaluationConducteur::with('conducteur.utilisateur')->get()
        );
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'conducteur_id' => 'required|exists:conducteurs,idConducteur',
            'nombreSinistres' => 'nullable|integer|min:0',
            'retards' => 'nullable|integer|min:0',
            'date_retour' => 'nullable|date',
            'affectation_ids' => 'nullable|array',
            'affectation_ids.*' => 'integer|exists:affectations,idAffectation',
        ]);

        $eval = EvaluationConducteur::create([
            'conducteur_id' => $donnees['conducteur_id'],
            'nombreSinistres' => $donnees['nombreSinistres'] ?? 0,
            'retards' => $donnees['retards'] ?? 0,
            'date_retour' => $donnees['date_retour'] ?? null,
            'coutTotalGenere' => 0,
            'scoreCalcule' => 0,
        ]);

        if (!empty($donnees['affectation_ids'])) {
            $eval->affectations()->sync($donnees['affectation_ids']);
        }

        $eval->calculerScore();

        return response()->json($eval, 201);
    }

    public function show($id)
    {
        return response()->json(
            EvaluationConducteur::with('conducteur.utilisateur', 'affectations')->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $evaluation = EvaluationConducteur::findOrFail($id);

        $donnees = $request->validate([
            'nombreSinistres' => 'sometimes|integer|min:0',
            'retards' => 'sometimes|integer|min:0',
            'date_retour' => 'nullable|date',
            'message' => 'nullable|string',
            'affectation_ids' => 'nullable|array',
            'affectation_ids.*' => 'integer|exists:affectations,idAffectation',
        ]);

        $evaluation->update($donnees);

        if (array_key_exists('affectation_ids', $donnees)) {
            $evaluation->affectations()->sync($donnees['affectation_ids'] ?? []);
        }

        $evaluation->calculerScore();

        return response()->json($evaluation->load('affectations'));
    }

    public function destroy($id)
    {
        $evaluation = EvaluationConducteur::findOrFail($id);
        $evaluation->delete();

        return response()->json(['message' => 'Évaluation supprimée']);
    }
}
