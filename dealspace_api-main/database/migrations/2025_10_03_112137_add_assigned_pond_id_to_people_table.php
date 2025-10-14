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
        Schema::table('people', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_pond_id')->nullable()->after('assigned_user_id');
            $table->foreign('assigned_pond_id')->references('id')->on('ponds')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropForeign(['assigned_pond_id']);
            $table->dropColumn('assigned_pond_id');
        });
    }
};
