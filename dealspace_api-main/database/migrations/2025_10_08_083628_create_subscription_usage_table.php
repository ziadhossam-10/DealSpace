<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('feature'); // e.g., 'deals', 'contacts', 'campaigns'
            $table->integer('used')->default(0);
            $table->integer('limit')->nullable(); // null = unlimited
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->index(['user_id', 'feature', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usage');
    }
};