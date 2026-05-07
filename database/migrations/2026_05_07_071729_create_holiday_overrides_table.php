<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_id')->nullable()->constrained('holidays')->nullOnDelete();
            $table->smallInteger('year')->unsigned();
            $table->string('state_code', 10);
            $table->string('name');
            $table->date('date');
            $table->string('action', 30); // add|remove|replace|rename|mark_subject_to_change
            $table->text('reason')->nullable();
            $table->text('source_url')->nullable();
            $table->text('source_file_path')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['year', 'state_code'], 'idx_overrides_year_state');
            $table->index(['date', 'state_code'], 'idx_overrides_date_state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_overrides');
    }
};
