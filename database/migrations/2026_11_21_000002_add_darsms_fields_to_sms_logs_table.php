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
            $table->string('provider', 30)->default('beem');
            $table->string('batch_id')->nullable();
            $table->unsignedInteger('queued_count')->default(0);
            $table->unsignedInteger('excluded_count')->default(0);
            $table->json('provider_response')->nullable();
        });

        // Existing logs predate provider tracking and were sent through Beem.
        DB::table('sms_logs')->whereNull('provider')->update(['provider' => 'beem']);
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropColumn(['provider', 'batch_id', 'queued_count', 'excluded_count', 'provider_response']);
        });
    }
};
