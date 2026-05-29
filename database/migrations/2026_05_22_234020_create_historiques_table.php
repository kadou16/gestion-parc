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
        Schema::create('historiques', function (Blueprint $table) {
            $table->id('idHistorique');
            $table->string('type_entite'); // 'Conducteur', 'Vehicule', 'Affectation'
            $table->unsignedBigInteger('id_entite');
            $table->string('action'); // 'update', 'delete'
            $table->json('anciennes_valeurs')->nullable();
            $table->json('nouvelles_valeurs')->nullable();
            $table->unsignedBigInteger('utilisateur_id')->nullable(); // Admin user making the change
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historiques');
    }
};
