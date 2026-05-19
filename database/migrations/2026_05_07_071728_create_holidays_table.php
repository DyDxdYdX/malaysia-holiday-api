<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_source_id')->nullable()->constrained('holiday_sources')->nullOnDelete();
            $table->foreignId('holiday_import_batch_id')->nullable()->constrained('holiday_import_batches')->nullOnDelete();
            $table->smallInteger('year')->unsigned();
            $table->string('name');
            $table->date('date');
            $table->string('day_name', 20)->nullable();
            $table->string('scope', 30); // federal|state|custom
            $table->string('type', 50); // federal|state|replacement|additional|custom
            $table->boolean('is_subject_to_change')->default(false);
            $table->string('status', 30)->default('draft'); // draft|confirmed|published|overridden|cancelled
            $table->text('source_note')->nullable();
            $table->timestamps();

            $table->index(['year'], 'idx_holidays_year');
            $table->index(['date'], 'idx_holidays_date');
            $table->unique(['year', 'date', 'name'], 'unique_holiday_record');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
