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
        Schema::create('documents', function (Blueprint $table) {
            $table->id('idDocument');
            $table->foreignId('vehicule_id')
                ->constrained('vehicules', 'idVehicule')
                ->onDelete('cascade');
            $table->enum('type', ['Assurance', 'Visite technique']);
            $table->date('dateDebut');
            $table->date('dateExpiration');
            $table->string('statut');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
