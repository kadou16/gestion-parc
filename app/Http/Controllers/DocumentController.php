<?php
namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Maintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index()
    {
        return response()->json(
            Document::with('vehicule', 'maintenance')->get()
        );
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'vehicule_id' => 'required|exists:vehicules,idVehicule',
            'maintenance_id' => 'required_if:type,Visite technique|nullable|exists:maintenances,idMaintenance',
            'type' => 'required|in:Assurance,Visite technique',
            'dateDebut' => 'required|date',
            'dateExpiration' => 'required|date|after_or_equal:dateDebut',
            'statut' => 'required|string',
            'fichier' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($donnees['type'] === 'Visite technique') {
            $maintenance = Maintenance::withCount('documents')->findOrFail($donnees['maintenance_id']);

            if ($maintenance->type !== 'Preventive') {
                return response()->json([
                    'message' => 'Seules les maintenances préventives peuvent avoir un document.'
                ], 422);
            }

            if ($maintenance->documents_count > 0) {
                return response()->json([
                    'message' => 'Cette maintenance possède déjà un document.'
                ], 422);
            }

            $donnees['vehicule_id'] = $maintenance->vehicule_id;
        } else {
            $donnees['maintenance_id'] = null;
        }

        if ($request->hasFile('fichier')) {
            $donnees['fichier_path'] = $request->file('fichier')->store('documents', 'public');
        }

        $document = Document::create($donnees);

        return response()->json($document, 201);
    }

    public function show($id)
    {
        return response()->json(
            Document::with('vehicule', 'maintenance', 'alertes')->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        $donnees = $request->validate([
            'type' => 'sometimes|in:Assurance,Visite technique',
            'dateDebut' => 'sometimes|date',
            'dateExpiration' => 'sometimes|date',
            'statut' => 'sometimes|string',
            'fichier' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($request->hasFile('fichier')) {
            if (!empty($document->fichier_path) && Storage::disk('public')->exists($document->fichier_path)) {
                Storage::disk('public')->delete($document->fichier_path);
            }
            $donnees['fichier_path'] = $request->file('fichier')->store('documents', 'public');
        }

        $document->update($donnees);

        return response()->json($document);
    }

    public function destroy($id)
    {
        $document = Document::findOrFail($id);

        if (!empty($document->fichier_path) && Storage::disk('public')->exists($document->fichier_path)) {
            Storage::disk('public')->delete($document->fichier_path);
        }

        $document->delete();
        return response()->json(['message' => 'Document supprimé']);
    }

    public function visualiser($id)
    {
        $document = Document::findOrFail($id);

        if (empty($document->fichier_path) || !Storage::disk('public')->exists($document->fichier_path)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        return response()->file(Storage::disk('public')->path($document->fichier_path));
    }

    public function telecharger($id)
    {
        $document = Document::findOrFail($id);

        if (empty($document->fichier_path) || !Storage::disk('public')->exists($document->fichier_path)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        $extension = pathinfo($document->fichier_path, PATHINFO_EXTENSION);
        $nom = sprintf('document_%s_%s.%s', $document->idDocument, str_replace(' ', '_', strtolower($document->type)), $extension ?: 'pdf');

        return Storage::disk('public')->download($document->fichier_path, $nom);
    }
}
