<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckMaintenanceEcheance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-maintenance-echeance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les échéances de maintenance et crée une alerte';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = \Carbon\Carbon::today();
        $maintenances = \App\Models\Maintenance::where('statut', 'En cours')
            ->where('is_deleted', false)
            ->whereDate('dateFin', '=', $today)
            ->get();

        foreach ($maintenances as $maintenance) {
            \App\Models\Alerte::firstOrCreate([
                'maintenance_id' => $maintenance->idMaintenance,
                'typeAlerte' => 'Fin de maintenance',
                'dateAlerte' => $today,
                'statut' => 'Non lue'
            ]);
        }
        
        $this->info("Échéances de maintenance vérifiées.");
    }
}
