<?php

namespace Database\Seeders;

use App\Models\Administrateur;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        $gestionnaire = Utilisateur::updateOrCreate(
            ['email' => 'gestionnaire@example.com'],
            [
                'nom' => 'Gestionnaire',
                'prenom' => 'Parc',
                'role' => 'Gestionnaire',
                'motdePasse' => Hash::make('password'),
            ]
        );

        Administrateur::updateOrCreate(
            ['utilisateur_id' => $gestionnaire->id],
            ['motdePasse' => $gestionnaire->motdePasse]
        );
    }
}
