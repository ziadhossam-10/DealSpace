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
        Schema::create('tracking_scripts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('script_key', 50)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('domain')->nullable(); // Can store single domain or array of domains
            $table->boolean('is_active')->default(true);

            // Form tracking settings
            $table->boolean('track_all_forms')->default(false);
            $table->json('form_selectors')->nullable(); // Array of CSS selectors
            $table->json('field_mappings')->nullable(); // Field mapping configuration
            $table->boolean('auto_lead_capture')->default(true);

            // Page tracking settings
            $table->boolean('track_page_views')->default(true);
            $table->boolean('track_utm_parameters')->default(true);

            // Custom events
            $table->json('custom_events')->nullable(); // Array of allowed custom event types

            // Additional settings
            $table->json('settings')->nullable(); // For future extensibility

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['script_key', 'is_active']);
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->index('source'); // Add index on source for better performance
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['source']);
        });

        Schema::dropIfExists('tracking_scripts');
    }
};
