<?php
namespace App\Http\Controllers;

use App\Models\Alerte;
use Illuminate\Http\Request;

class AlerteController extends Controller
{
    public function index()
    {
        return response()->json(
            Alerte::with('document.vehicule', 'maintenance.vehicule')->get()
        );
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'document_id' => 'nullable|exists:documents,idDocument',
            'maintenance_id' => 'nullable|exists:maintenances,idMaintenance',
            'typeAlerte' => 'required|string',
            'dateAlerte' => 'required|date',
            'statut' => 'required|string',
        ]);

        $alerte = Alerte::create($donnees);
        return response()->json($alerte, 201);
    }

    public function show($id)
    {
        return response()->json(Alerte::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $alerte = Alerte::findOrFail($id);

        $donnees = $request->validate([
            'typeAlerte' => 'sometimes|string',
            'dateAlerte' => 'sometimes|date',
            'statut' => 'sometimes|string',
        ]);

        $alerte->update($donnees);
        return response()->json($alerte);
    }

    public function destroy($id)
    {
        Alerte::findOrFail($id)->delete();
        return response()->json(['message' => 'Alerte supprimée']);
    }
}
