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
        // Expand ENUM to include 'admin' alongside existing values
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'data_admin', 'admin'])->default('admin')->change();
        });

        // Migrate all existing roles to the single 'admin' role
        DB::table('users')->update(['role' => 'admin']);

        // Restrict ENUM to only 'admin'
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin'])->default('admin')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->update(['role' => 'super_admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'data_admin'])->default('data_admin')->change();
        });
    }
};
