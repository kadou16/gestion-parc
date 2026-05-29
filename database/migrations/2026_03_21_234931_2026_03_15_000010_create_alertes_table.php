<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('alertes', function (Blueprint $table) {
            $table->id('idAlerte');
            $table->foreignId('document_id')
                ->nullable()
                ->constrained('documents', 'idDocument')
                ->onDelete('cascade');
            $table->foreignId('maintenance_id')
                ->nullable()
                ->constrained('maintenances', 'idMaintenance')
                ->onDelete('cascade');
            $table->string('typeAlerte');
            $table->date('dateAlerte');
            $table->string('statut');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes');
    }
};
