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
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('person_id')->nullable()->after('tenant_id');
            $table->foreign('person_id')->references('id')->on('people')->onDelete('set null');
            $table->index('person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropIndex(['person_id']);
            $table->dropColumn('person_id');
        });
    }
};
