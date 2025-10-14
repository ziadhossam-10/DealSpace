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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('stage_id')->nullable()->constrained('deal_stages')->onDelete('set null');
            $table->foreignId('type_id')->nullable()->constrained('deal_types')->onDelete('set null');
            $table->text('description')->nullable();
            $table->integer('price')->nullable();
            $table->date('projected_close_date')->nullable();
            $table->integer('order_weight')->default(0);
            $table->integer('commission_value')->nullable();
            $table->integer('agent_commission')->nullable();
            $table->integer('team_commission')->nullable();
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('deal_person', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
        Schema::create('deal_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
