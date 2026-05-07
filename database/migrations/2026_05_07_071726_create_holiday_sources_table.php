<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_sources', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year')->unsigned();
            $table->string('source_name');
            $table->string('source_type', 50); // federal_pdf|state_page|gazette|admin_csv|manual_entry|third_party_reference
            $table->text('source_url')->nullable();
            $table->text('file_path')->nullable();
            $table->string('checksum', 128)->nullable();
            $table->string('status', 30)->default('draft'); // draft|active
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_sources');
    }
};
