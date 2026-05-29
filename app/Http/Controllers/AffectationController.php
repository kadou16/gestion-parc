<?php
namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Vehicule;
use Illuminate\Http\Request;

class AffectationController extends Controller
{
    public function index()
    {
        return response()->json(
            Affectation::with('vehicule', 'conducteur.utilisateur')->get()
        );
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'vehicule_id' => 'required|exists:vehicules,idVehicule',
            'conducteur_id' => 'required|exists:conducteurs,idConducteur',
            'dateDebut' => 'required|date',
            'dateFin' => 'nullable|date|after_or_equal:dateDebut',
            'heure_depart' => 'required|date_format:H:i',
            'heure_retour' => 'nullable|date_format:H:i',
            'etatDepart' => 'required|string',
            'etatRetour' => 'nullable|string',
            'mission' => 'required|string',
            'coutGenere' => 'nullable|numeric|min:0',
        ]);

        $vehicule = Vehicule::findOrFail($donnees['vehicule_id']);
        
        // Empêcher l'affectation si le véhicule n'est pas disponible
        if ($vehicule->statut !== 'Disponible') {
            return response()->json([
                'message' => 'Ce véhicule est actuellement '.$vehicule->statut.' et ne peut être affecté.'
            ], 422);
        }

        $vehicule->changerStatus('Affecté');

        $affectation = Affectation::create($donnees);

        return response()->json($affectation, 201);
    }

    public function show($id)
    {
        return response()->json(
            Affectation::with('vehicule', 'conducteur.utilisateur', 'evaluationConducteurs')
                       ->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $affectation = Affectation::findOrFail($id);

        $donnees = $request->validate([
            'dateDebut' => 'sometimes|date',
            'dateFin' => 'nullable|date',
            'heure_depart' => 'sometimes|date_format:H:i',
            'heure_retour' => 'nullable|date_format:H:i',
            'etatDepart' => 'sometimes|string',
            'etatRetour' => 'nullable|string',
            'mission' => 'sometimes|string',
            'coutGenere' => 'sometimes|numeric|min:0',
        ]);

        $affectation->update($donnees);

        if (!empty($donnees['dateFin'])) {
            Vehicule::findOrFail($affectation->vehicule_id)
                ->changerStatus('Disponible');
        }

        return response()->json($affectation);
    }

    public function destroy($id)
    {
        Affectation::findOrFail($id)->delete();
        return responses()->json(['message' => 'Affectation supprimée']);
    }
}
