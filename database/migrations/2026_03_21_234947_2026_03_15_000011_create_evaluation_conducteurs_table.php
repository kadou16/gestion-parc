<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_conducteurs', function (Blueprint $table) {
            $table->id('idEvaluation');
            $table->foreignId('conducteur_id')
                ->constrained('conducteurs', 'idConducteur')
                ->onDelete('cascade');
            $table->integer('nombreSinistres')->default(0);
            $table->integer('retards')->default(0);
            $table->decimal('coutTotalGenere', 12, 2)->default(0);
            $table->decimal('scoreCalcule', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('evaluation_conducteurs');
    }
};
