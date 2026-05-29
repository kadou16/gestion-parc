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
        Schema::create('affectation_evaluation_conducteur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affectation_id');
            $table->unsignedBigInteger('evaluation_conducteur_id');
            $table->timestamps();

            $table->foreign('affectation_id', 'fk_affect_eval_affect')
                ->references('idAffectation')
                ->on('affectations')
                ->onDelete('cascade');

            $table->foreign('evaluation_conducteur_id', 'fk_affect_eval_eval')
                ->references('idEvaluation')
                ->on('evaluation_conducteurs')
                ->onDelete('cascade');

            $table->unique(['affectation_id', 'evaluation_conducteur_id'], 'uq_affect_eval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectation_evaluation_conducteur');
    }
};
