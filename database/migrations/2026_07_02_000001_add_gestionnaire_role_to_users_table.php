<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('Admin', 'Gestionnaire', 'Conducteur') NOT NULL DEFAULT 'Conducteur'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('Admin', 'Conducteur') NOT NULL DEFAULT 'Conducteur'");
        }
    }
};
