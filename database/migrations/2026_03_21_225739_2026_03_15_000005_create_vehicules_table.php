<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicules', function (Blueprint $table) {
            $table->id('idVehicule');
            $table->foreignId('administrateur_id')
                ->constrained('administrateurs', 'idAdministrateur')
                ->onDelete('cascade');
            $table->string('immatriculation')->unique();
            $table->string('marque');
            $table->string('modele');
            $table->integer('annee');
            $table->double('kilometrage');
            $table->enum('statut', ['Disponible', 'Affecté', 'Maintenance'])
                ->default('Disponible');
            $table->enum('etat', ['Bon', 'Moyen', 'Endommagé'])
                ->default('Bon');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicules');
    }
};
