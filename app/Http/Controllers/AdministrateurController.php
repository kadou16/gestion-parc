<?php

namespace App\Http\Controllers;

use App\Models\Administrateur;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdministrateurController extends Controller
{
    public function index()
    {
        return response()->json(
            Administrateur::with('utilisateur')->get()
        );
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'motdePasse' => 'required|string|min:6',
        ]);

        $utilisateur = Utilisateur::create([
            'nom' => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'email' => $donnees['email'],
            'role' => 'Admin',
            'motdePasse' => Hash::make($donnees['motdePasse']),
        ]);

        $administrateur = Administrateur::create([
            'utilisateur_id' => $utilisateur->id,
            'motdePasse' => $utilisateur->motdePasse,
        ]);

        return response()->json($administrateur->load('utilisateur'), 201);
    }

    public function show($id)
    {
        return response()->json(
            Administrateur::with('utilisateur')->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $administrateur = Administrateur::with('utilisateur')->findOrFail($id);

        $donnees = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $administrateur->utilisateur->id,
        ]);

        $administrateur->utilisateur->update($donnees);

        return response()->json($administrateur->load('utilisateur'));
    }

    public function destroy($id)
    {
        $administrateur = Administrateur::with('utilisateur')->findOrFail($id);
        $administrateur->utilisateur?->delete();

        return response()->json(['message' => 'Administrateur supprimé']);
    }
}
