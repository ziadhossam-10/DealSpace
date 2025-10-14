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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('type');
            $table->tinyInteger('distribution');
            $table->unsignedBigInteger('default_user_id')->nullable();
            $table->unsignedBigInteger('default_pond_id')->nullable();
            $table->unsignedBigInteger('default_group_id')->nullable();
            $table->integer('claim_window')->default(900); // Default 15 minutes (900 seconds)
            $table->unsignedBigInteger('next_round_robin_user_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('default_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('default_group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('default_pond_id')->references('id')->on('ponds')->nullOnDelete();
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
