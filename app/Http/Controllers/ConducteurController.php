<?php
namespace App\Http\Controllers;

use App\Models\Conducteur;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ConducteurController extends Controller
{
    public function index()
    {
        return response()->json(
            Conducteur::with('utilisateur', 'affectations', 'evaluationConducteurs')->get()
        );
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'motdePasse' => 'required|min:6',
            'numPermis' => 'required|string|unique:conducteurs,numPermis',
            'DateExpPermis' => 'required|date',
        ]);

        $utilisateur = Utilisateur::create([
            'nom' => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'email' => $donnees['email'],
            'role' => 'Conducteur',
            'motdePasse' => Hash::make($donnees['motdePasse']),
        ]);

        $conducteur = Conducteur::create([
            'utilisateur_id' => $utilisateur->id,
            'numPermis' => $donnees['numPermis'],
            'DateExpPermis' => $donnees['DateExpPermis'],
        ]);

        return response()->json($conducteur->load('utilisateur'), 201);
    }

    public function show($id)
    {
        return response()->json(
            Conducteur::with('utilisateur', 'affectations', 'evaluationConducteurs')->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $conducteur = Conducteur::findOrFail($id);
        $utilisateur = $conducteur->utilisateur;

        $donnees = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $utilisateur->id,
            'motdePasse' => 'nullable|min:6',
            'numPermis' => 'sometimes|string|unique:conducteurs,numPermis,' . $conducteur->idConducteur . ',idConducteur',
            'DateExpPermis' => 'sometimes|date',
        ]);

        if (isset($donnees['nom']) || isset($donnees['prenom']) || isset($donnees['email']) || isset($donnees['motdePasse'])) {
            $userData = [];
            if (isset($donnees['nom'])) $userData['nom'] = $donnees['nom'];
            if (isset($donnees['prenom'])) $userData['prenom'] = $donnees['prenom'];
            if (isset($donnees['email'])) $userData['email'] = $donnees['email'];
            if (!empty($donnees['motdePasse'])) $userData['motdePasse'] = Hash::make($donnees['motdePasse']);
            
            $utilisateur->update($userData);
        }

        $conducteurData = [];
        if (isset($donnees['numPermis'])) $conducteurData['numPermis'] = $donnees['numPermis'];
        if (isset($donnees['DateExpPermis'])) $conducteurData['DateExpPermis'] = $donnees['DateExpPermis'];

        if (!empty($conducteurData)) {
            $conducteur->update($conducteurData);
        }

        return response()->json($conducteur->load('utilisateur'));
    }

    public function destroy($id)
    {
        Conducteur::findOrFail($id)->delete();
        return response()->json(['message' => 'Conducteur supprimé']);
    }
}
