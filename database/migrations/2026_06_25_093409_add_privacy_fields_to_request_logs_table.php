<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('request_logs', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->change();
            $table->string('visitor_hash', 64)->nullable()->after('ip_address')->index();
            $table->string('network_prefix')->nullable()->after('visitor_hash');
            $table->timestamp('expires_at')->nullable()->after('created_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('request_logs')
            ->whereNull('ip_address')
            ->update(['ip_address' => '0.0.0.0']);

        Schema::table('request_logs', function (Blueprint $table) {
            $table->dropIndex(['visitor_hash']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['visitor_hash', 'network_prefix', 'expires_at']);
            $table->string('ip_address', 45)->nullable(false)->change();
        });
    }
};
