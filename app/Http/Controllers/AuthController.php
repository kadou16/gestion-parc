<?php
namespace App\Http\Controllers;

use App\Models\Administrateur;
use App\Models\Conducteur;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'motdePasse' => 'required|min:6',
            'role' => 'required|in:Admin,Conducteur',
            'numPermis' => 'required_if:role,Conducteur|nullable|string|max:100',
            'DateExpPermis' => 'required_if:role,Conducteur|nullable|date',
        ]);

        $utilisateur = Utilisateur::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'role' => $request->role,
            'motdePasse' => Hash::make($request->motdePasse),
        ]);

        if ($request->role === 'Admin') {
            Administrateur::create([
                'utilisateur_id' => $utilisateur->id,
                'motdePasse' => $utilisateur->motdePasse,
            ]);
        } else {
            Conducteur::create([
                'utilisateur_id' => $utilisateur->id,
                'numPermis' => $request->numPermis,
                'DateExpPermis' => $request->DateExpPermis,
            ]);
        }

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'utilisateur' => $utilisateur,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'motdePasse' => 'required',
        ]);

        $utilisateur = Utilisateur::where('email', $request->email)->first();

        if (!$utilisateur || !Hash::check($request->motdePasse, $utilisateur->motdePasse)) {
            return response()->json([
                'message' => 'Email ou mot de passe incorrect',
            ], 401);
        }

        $token = $utilisateur->seConnecter('auth_token');

        return response()->json([
            'message' => 'Connexion réussie',
            'token' => $token,
            'role' => $utilisateur->role,
            'user' => $utilisateur,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function me(Request $request)
    {
        $utilisateur = $request->user();

        return response()->json([
            'user' => $utilisateur,
            'role' => $utilisateur?->role,
        ]);
    }

    public function updateMe(Request $request)
    {
        $utilisateur = $request->user();

        $donnees = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($utilisateur->id)],
            'motdePasse' => 'nullable|string|min:6',
        ]);

        $payload = [
            'nom' => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'email' => $donnees['email'],
        ];

        if (!empty($donnees['motdePasse'])) {
            $payload['motdePasse'] = Hash::make($donnees['motdePasse']);
        }

        $utilisateur->update($payload);

        if (!empty($donnees['motdePasse']) && $utilisateur->role === 'Admin') {
            Administrateur::where('utilisateur_id', $utilisateur->id)
                ->update(['motdePasse' => $payload['motdePasse']]);
        }

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $utilisateur->fresh(),
        ]);
    }
}
