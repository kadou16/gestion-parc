<?php
namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehiculeController extends Controller
{
    public function index()
    {
        return response()->json(Vehicule::with('administrateur.utilisateur')->get());
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'administrateur_id' => 'required|exists:administrateurs,idAdministrateur',
            'immatriculation' => 'required|string|unique:vehicules,immatriculation',
            'marque' => 'required|string',
            'modele' => 'required|string',
            'annee' => 'required|integer|min:1900|max:2100',
            'kilometrage' => 'required|numeric|min:0',
            'statut' => 'required|in:Disponible,Affecté,Maintenance',
            'etat' => 'required|in:Bon,Moyen,Endommagé',
            'conducteur_id' => 'nullable|exists:conducteurs,idConducteur',
        ]);

        if ($donnees['statut'] === 'Affecté' && empty($donnees['conducteur_id'])) {
            return response()->json([
                'message' => 'Veuillez sélectionner un conducteur lorsque le véhicule est affecté.'
            ], 422);
        }

        $vehicule = DB::transaction(function () use ($donnees) {
            $vehicule = Vehicule::create([
                'administrateur_id' => $donnees['administrateur_id'],
                'immatriculation' => $donnees['immatriculation'],
                'marque' => $donnees['marque'],
                'modele' => $donnees['modele'],
                'annee' => $donnees['annee'],
                'kilometrage' => $donnees['kilometrage'],
                'statut' => $donnees['statut'],
                'etat' => $donnees['etat'],
            ]);

            if ($donnees['statut'] === 'Affecté') {
                Affectation::create([
                    'vehicule_id' => $vehicule->idVehicule,
                    'conducteur_id' => $donnees['conducteur_id'],
                    'dateDebut' => now()->toDateString(),
                    'dateFin' => null,
                    'heure_depart' => now()->format('H:i'),
                    'heure_retour' => null,
                    'etatDepart' => $donnees['etat'],
                    'etatRetour' => null,
                    'mission' => 'Affectation initiale',
                    'coutGenere' => 0,
                ]);
            }

            return $vehicule;
        });

        return response()->json($vehicule, 201);
    }

    public function show($id)
    {
        $vehicule = Vehicule::with('administrateur.utilisateur')->findOrFail($id);

        return response()->json($vehicule);
    }

    public function update(Request $request, $id)
    {
        $vehicule = Vehicule::findOrFail($id);

        $donnees = $request->validate([
            'administrateur_id' => 'sometimes|exists:administrateurs,idAdministrateur',
            'immatriculation' => 'sometimes|string|unique:vehicules,immatriculation,' . $vehicule->idVehicule . ',idVehicule',
            'marque' => 'sometimes|string',
            'modele' => 'sometimes|string',
            'annee' => 'sometimes|integer|min:1900|max:2100',
            'kilometrage' => 'sometimes|numeric|min:0',
            'statut' => 'sometimes|in:Disponible,Affecté,Maintenance',
            'etat' => 'sometimes|in:Bon,Moyen,Endommagé',
        ]);

        $vehicule->update($donnees);

        return response()->json($vehicule);
    }

    public function destroy($id)
    {
        Vehicule::findOrFail($id)->delete();
        return response()->json(['message' => 'Véhicule supprimé']);
    }
}
