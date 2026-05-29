<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Models\Alerte;
use Carbon\Carbon;

class CheckDocumentExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-document-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier les expirations de documents (assurance, visite technique) et générer des alertes automatiquement.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Chercher les documents qui expirent dans 7 jours ou moins
        $dateLimite = Carbon::now()->addDays(7)->toDateString();

        $documentsExpiring = Document::where('dateExpiration', '<=', $dateLimite)
            ->where('statut', '!=', 'Expiré')
            ->get();

        $this->info("Vérification commencée. " . $documentsExpiring->count() . " document(s) proche(s) de l'expiration.");

        foreach ($documentsExpiring as $doc) {
            // Vérifier si l'alerte n'existe pas déjà pour ce document
            $alerteExistante = Alerte::where('document_id', $doc->idDocument)
                ->where('statut', 'Non lue')
                ->first();

            if (!$alerteExistante) {
                Alerte::create([
                    'document_id' => $doc->idDocument,
                    'typeAlerte' => 'Expiration proche : ' . $doc->type,
                    'dateAlerte' => Carbon::now()->toDateString(),
                    'statut' => 'Non lue',
                ]);
                $this->info("Alerte créée pour le document ID: {$doc->idDocument} (Véhicule ID: {$doc->vehicule_id}).");
            }
        }
        
        $this->info("Vérification terminée.");
    }
}
