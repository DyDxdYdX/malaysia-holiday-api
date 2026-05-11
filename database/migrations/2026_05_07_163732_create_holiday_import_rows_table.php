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
        Schema::create('holiday_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_import_batch_id')->constrained('holiday_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw_payload')->nullable();
            $table->json('normalized_payload')->nullable();
            $table->string('status', 30)->default('invalid');
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->timestamps();

            $table->index(['holiday_import_batch_id', 'status'], 'idx_import_rows_batch_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holiday_import_rows');
    }
};
