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
        Schema::create('conducteurs', function (Blueprint $table) {
            $table->id('idConducteur');
            $table->foreignId('utilisateur_id')
                ->unique()
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('numPermis')->unique();
            $table->date('DateExpPermis');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conducteurs');
    }
};
