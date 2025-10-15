<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subscription_usage');
        
        Schema::create('subscription_usage', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('feature'); // e.g., 'users', 'deals', 'contacts', 'campaigns'
            $table->integer('used')->default(0);
            $table->integer('limit')->nullable(); // null = unlimited
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'feature', 'period_start']);
            $table->unique(['tenant_id', 'feature', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usage');
    }
};