<?php
namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Vehicule;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index()
    {
        return response()->json(
            Maintenance::with('vehicule')
                ->where('is_deleted', false)
                ->get()
        );
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'vehicule_id' => 'required|exists:vehicules,idVehicule',
            'type' => 'required|string',
            'dateDebut' => 'required|date',
            'dateFin' => 'required|date|after_or_equal:dateDebut',
            'description' => 'required|string',
            'cout' => 'nullable|numeric|min:0',
            'statut' => 'required|string',
            'prestataire' => 'required|string',
        ]);

        Vehicule::findOrFail($donnees['vehicule_id'])
            ->changerStatus('Maintenance');

        $maintenance = Maintenance::create($donnees);

        return response()->json($maintenance, 201);
    }

    public function show($id)
    {
        return response()->json(
            Maintenance::with('vehicule', 'alertes')
                ->where('is_deleted', false)
                ->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $maintenance = Maintenance::where('is_deleted', false)->findOrFail($id);

        $donnees = $request->validate([
            'type' => 'sometimes|string',
            'dateDebut' => 'sometimes|date',
            'dateFin' => 'sometimes|date',
            'description' => 'sometimes|string',
            'cout' => 'sometimes|numeric|min:0',
            'statut' => 'sometimes|string',
            'prestataire' => 'sometimes|string',
        ]);

        $maintenance->update($donnees);

        if (isset($donnees['statut']) && $donnees['statut'] === 'Terminée') {
            Vehicule::findOrFail($maintenance->vehicule_id)
                ->changerStatus('Disponible');
                
            // Générer une alerte lorsque la maintenance est terminée
            \App\Models\Alerte::create([
                'maintenance_id' => $maintenance->idMaintenance,
                'vehicule_id' => $maintenance->vehicule_id,
                'typeAlerte' => 'Fin de maintenance',
                'dateAlerte' => now(),
                'statut' => 'Non lue',
            ]);
        } elseif (isset($donnees['statut']) && $donnees['statut'] === 'En cours') {
            Vehicule::findOrFail($maintenance->vehicule_id)
                ->changerStatus('Maintenance');
        }

        return response()->json($maintenance);
    }

    public function destroy($id)
    {
        $maintenance = Maintenance::where('is_deleted', false)->findOrFail($id);

        $maintenance->update([
            'is_deleted' => true,
        ]);

        Vehicule::findOrFail($maintenance->vehicule_id)
            ->changerStatus('Disponible');

        return response()->json(['message' => 'Maintenance supprimée']);
    }
}
