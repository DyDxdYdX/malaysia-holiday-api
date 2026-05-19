<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_id')->constrained('holidays')->cascadeOnDelete();
            $table->string('state_code', 10);
            $table->timestamps();

            $table->unique(['holiday_id', 'state_code'], 'unique_holiday_state');
            $table->index(['state_code', 'holiday_id'], 'idx_holiday_states_state_holiday');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_states');
    }
};
