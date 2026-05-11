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
        Schema::table('holiday_import_batches', function (Blueprint $table) {
            $table->string('import_method', 30)->default('csv')->after('status');
            $table->string('provider', 50)->nullable()->after('import_method');
            $table->string('model')->nullable()->after('provider');
            $table->timestamp('started_at')->nullable()->after('model');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->timestamp('failed_at')->nullable()->after('completed_at');
            $table->text('failure_reason')->nullable()->after('failed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('holiday_import_batches', function (Blueprint $table) {
            $table->dropColumn([
                'import_method',
                'provider',
                'model',
                'started_at',
                'completed_at',
                'failed_at',
                'failure_reason',
            ]);
        });
    }
};
