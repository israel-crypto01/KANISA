<?php

use App\Models\Department;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Department::ensureDefaultsExist();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't delete departments on rollback to protect user data
    }
};
