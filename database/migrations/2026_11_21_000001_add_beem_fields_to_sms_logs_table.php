<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->integer('sent_count')->default(0)->after('recipients_count');
            $table->integer('failed_count')->default(0)->after('sent_count');
            $table->json('beem_response')->nullable()->after('status');
            $table->text('error_message')->nullable()->after('beem_response');
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropColumn(['sent_count', 'failed_count', 'beem_response', 'error_message']);
        });
    }
};
