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
        Schema::create('analytics_daily_route_stats', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');
            $table->string('route_type', 10);
            $table->unsignedInteger('request_count')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedBigInteger('duration_total_ms')->default(0);
            $table->unsignedInteger('duration_samples')->default(0);

            $table->unique(['stat_date', 'route_type']);
            $table->index('stat_date');
        });

        Schema::create('analytics_daily_path_stats', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');
            $table->string('route_type', 10);
            $table->string('method', 10);
            $table->string('path');
            $table->unsignedInteger('request_count')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedBigInteger('duration_total_ms')->default(0);
            $table->unsignedInteger('duration_samples')->default(0);

            $table->unique(['stat_date', 'route_type', 'method', 'path'], 'analytics_daily_path_stats_unique');
            $table->index(['stat_date', 'route_type']);
        });

        Schema::create('analytics_daily_visitor_stats', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');
            $table->string('visitor_hash', 64);
            $table->unsignedInteger('request_count')->default(0);
            $table->timestamp('last_active');
            $table->text('user_agent')->nullable();

            $table->unique(['stat_date', 'visitor_hash']);
            $table->index('stat_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_visitor_stats');
        Schema::dropIfExists('analytics_daily_path_stats');
        Schema::dropIfExists('analytics_daily_route_stats');
    }
};
