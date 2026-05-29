<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('evaluation_conducteurs', 'date_retour')) {
            Schema::table('evaluation_conducteurs', function (Blueprint $table) {
                $table->date('date_retour')->nullable()->after('retards');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('evaluation_conducteurs', 'date_retour')) {
            Schema::table('evaluation_conducteurs', function (Blueprint $table) {
                $table->dropColumn('date_retour');
            });
        }
    }
};
