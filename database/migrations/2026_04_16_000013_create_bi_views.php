<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('DROP VIEW IF EXISTS `Vue_Generale_Parc`');
        DB::statement(<<<'SQL'
            CREATE VIEW `Vue_Generale_Parc` AS
            SELECT
                v.statut,
                v.etat,
                COUNT(*) AS nbVehicules
            FROM vehicules v
            GROUP BY v.statut, v.etat
        SQL);

        DB::statement('DROP VIEW IF EXISTS `Vue_Analyse_Couts`');
        DB::statement(<<<'SQL'
            CREATE VIEW `Vue_Analyse_Couts` AS
            SELECT
                v.idVehicule,
                v.immatriculation,
                v.marque,
                v.modele,
                COALESCE(m.totalMaintenance, 0) AS totalMaintenance,
                COALESCE(a.totalAffectation, 0) AS totalAffectation,
                COALESCE(m.totalMaintenance, 0) + COALESCE(a.totalAffectation, 0) AS coutTotalGlobal
            FROM vehicules v
            LEFT JOIN (
                SELECT
                    vehicule_id,
                    SUM(cout) AS totalMaintenance
                FROM maintenances
                GROUP BY vehicule_id
            ) m ON m.vehicule_id = v.idVehicule
            LEFT JOIN (
                SELECT
                    vehicule_id,
                    SUM(coutGenere) AS totalAffectation
                FROM affectations
                GROUP BY vehicule_id
            ) a ON a.vehicule_id = v.idVehicule
        SQL);

        DB::statement('DROP VIEW IF EXISTS `Vue_Evaluation_Conducteurs`');
        DB::statement(<<<'SQL'
            CREATE VIEW `Vue_Evaluation_Conducteurs` AS
            SELECT
                s.idEvaluation,
                s.conducteur_id,
                s.scoreCalcule,
                s.moyenneScoreParConducteur,
                DENSE_RANK() OVER (
                    ORDER BY s.moyenneScoreParConducteur DESC, s.idEvaluation ASC
                ) AS classement
            FROM (
                SELECT
                    ec.idEvaluation,
                    ec.conducteur_id,
                    ec.scoreCalcule,
                    AVG(ec.scoreCalcule) OVER (PARTITION BY ec.conducteur_id) AS moyenneScoreParConducteur
                FROM evaluation_conducteurs ec
            ) s
        SQL);

        DB::statement('DROP VIEW IF EXISTS `Vue_Maintenances_Alertes`');
        DB::statement(<<<'SQL'
            CREATE VIEW `Vue_Maintenances_Alertes` AS
            SELECT
                d.idDocument,
                d.vehicule_id AS idVehicule,
                d.type AS typeDocument,
                d.dateExpiration,
                d.statut AS statutDocument,
                CASE
                    WHEN d.dateExpiration < CURDATE() THEN 'Expire'
                    WHEN d.dateExpiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expire sous 30 jours'
                    ELSE 'Valide'
                END AS niveauAlerteDocument,
                m.type AS typeMaintenance,
                COUNT(m.idMaintenance) AS nbMaintenancesType,
                COALESCE(SUM(m.cout), 0) AS coutMaintenancesType
            FROM documents d
            LEFT JOIN maintenances m
                ON m.vehicule_id = d.vehicule_id
            GROUP BY
                d.idDocument,
                d.vehicule_id,
                d.type,
                d.dateExpiration,
                d.statut,
                m.type
        SQL);
    }

    
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('DROP VIEW IF EXISTS `Vue_Maintenances_Alertes`');
        DB::statement('DROP VIEW IF EXISTS `Vue_Evaluation_Conducteurs`');
        DB::statement('DROP VIEW IF EXISTS `Vue_Analyse_Couts`');
        DB::statement('DROP VIEW IF EXISTS `Vue_Generale_Parc`');
    }
};
