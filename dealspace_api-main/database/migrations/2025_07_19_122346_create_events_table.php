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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('source')->nullable();
            $table->string('system')->nullable();
            $table->string('type');
            $table->text('message')->nullable();
            $table->text('description')->nullable();
            $table->json('person')->nullable();
            $table->json('property')->nullable();
            $table->json('property_search')->nullable();
            $table->json('campaign')->nullable();
            $table->string('page_title')->nullable();
            $table->string('page_url')->nullable();
            $table->string('page_referrer')->nullable();
            $table->integer('page_duration')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
