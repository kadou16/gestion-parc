<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('fichier_path')->nullable()->after('statut');
        });
    }

   
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('fichier_path');
        });
    }
};
