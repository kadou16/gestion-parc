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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id('idMaintenance');
            $table->foreignId('vehicule_id')
                ->constrained('vehicules', 'idVehicule')
                ->onDelete('cascade');
            $table->string('type');
            $table->date('dateDebut');
            $table->date('dateFin')->nullable();
            $table->text('description');
            $table->decimal('cout', 12, 2)->default(0);
            $table->string('statut');
            $table->string('prestataire');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
