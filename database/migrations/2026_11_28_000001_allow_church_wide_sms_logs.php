<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->change();
            $table->string('recipient_group', 30)->default('department')->after('message');
        });
    }

    public function down(): void
    {
        $departmentId = DB::table('departments')->value('id');
        if ($departmentId) {
            DB::table('sms_logs')->whereNull('department_id')->update(['department_id' => $departmentId]);
        }

        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropColumn('recipient_group');
            $table->foreignId('department_id')->nullable(false)->change();
        });
    }
};
