<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('affectations', function (Blueprint $table) {
            $table->id('idAffectation');
            $table->foreignId('vehicule_id')
                ->constrained('vehicules', 'idVehicule')
                ->onDelete('cascade');
            $table->foreignId('conducteur_id')
                ->constrained('conducteurs', 'idConducteur')
                ->onDelete('cascade');
            $table->date('dateDebut');
            $table->date('dateFin')->nullable();
            $table->string('etatDepart');
            $table->string('etatRetour')->nullable();
            $table->string('mission');
            $table->decimal('coutGenere', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectations');
    }
};
