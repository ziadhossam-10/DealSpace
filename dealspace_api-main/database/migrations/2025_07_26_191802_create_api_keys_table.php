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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Key name/description
            $table->string('key', 64)->unique(); // The actual API key
            $table->json('allowed_domains')->nullable(); // Restricted domains
            $table->json('allowed_endpoints')->nullable(); // Restricted API endpoints
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->timestamps();

            $table->index(['key', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
