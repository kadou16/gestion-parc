<?php

namespace Database\Seeders;

use App\Models\Administrateur;
use App\Models\Affectation;
use App\Models\Alerte;
use App\Models\Conducteur;
use App\Models\Document;
use App\Models\EvaluationConducteur;
use App\Models\Maintenance;
use App\Models\Utilisateur;
use App\Models\Vehicule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    
    public function run(): void
    {
        $utilisateur = Utilisateur::create([
            'nom' => 'Test',
            'prenom' => 'User',
            'email' => 'test@example.com',
            'role' => 'Admin',
            'motdePasse' => Hash::make('password'),
        ]);

        $administrateur = Administrateur::create([
            'utilisateur_id' => $utilisateur->id,
            'motdePasse' => $utilisateur->motdePasse,
        ]);

        $userConducteur = Utilisateur::create([
            'nom' => 'Ali',
            'prenom' => 'Karim',
            'email' => 'conducteur@example.com',
            'role' => 'Conducteur',
            'motdePasse' => Hash::make('password'),
        ]);

        $conducteur = Conducteur::create([
            'utilisateur_id' => $userConducteur->id,
            'numPermis' => 'PERMIS-ALG-001',
            'DateExpPermis' => now()->addYears(2)->toDateString(),
        ]);

        $vehicule = Vehicule::create([
            'administrateur_id' => $administrateur->idAdministrateur,
            'immatriculation' => '12345-116-16',
            'marque' => 'Toyota',
            'modele' => 'Hilux',
            'annee' => 2022,
            'kilometrage' => 45200,
            'statut' => 'Disponible',
            'etat' => 'Bon',
        ]);

        $maintenance = Maintenance::create([
            'vehicule_id' => $vehicule->idVehicule,
            'type' => 'Préventive',
            'dateDebut' => now()->subDays(30)->toDateString(),
            'dateFin' => now()->subDays(27)->toDateString(),
            'description' => 'Vidange + filtres',
            'cout' => 7500,
            'statut' => 'Clôturée',
            'prestataire' => 'Garage Central',
        ]);

        $document = Document::create([
            'vehicule_id' => $vehicule->idVehicule,
            'type' => 'Assurance',
            'dateDebut' => now()->subMonths(3)->toDateString(),
            'dateExpiration' => now()->addMonths(9)->toDateString(),
            'statut' => 'Valide',
        ]);

        $affectation = Affectation::create([
            'vehicule_id' => $vehicule->idVehicule,
            'conducteur_id' => $conducteur->idConducteur,
            'dateDebut' => now()->subDays(10)->toDateString(),
            'dateFin' => now()->subDays(8)->toDateString(),
            'etatDepart' => 'Bon',
            'etatRetour' => 'Bon',
            'mission' => 'Transport inter-sites',
            'coutGenere' => 12000,
        ]);

        $evaluation = EvaluationConducteur::create([
            'conducteur_id' => $conducteur->idConducteur,
            'nombreSinistres' => 0,
            'retards' => 1,
            'coutTotalGenere' => 0,
            'scoreCalcule' => 0,
        ]);

        $evaluation->affectations()->sync([$affectation->idAffectation]);
        $evaluation->calculerScore();

        Alerte::create([
            'document_id' => $document->idDocument,
            'maintenance_id' => null,
            'typeAlerte' => 'Expiration prochaine assurance',
            'dateAlerte' => now()->toDateString(),
            'statut' => 'Nouvelle',
        ]);

        Alerte::create([
            'document_id' => null,
            'maintenance_id' => $maintenance->idMaintenance,
            'typeAlerte' => 'Contrôle maintenance',
            'dateAlerte' => now()->toDateString(),
            'statut' => 'Nouvelle',
        ]);
    }
}
