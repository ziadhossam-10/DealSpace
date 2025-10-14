<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PersonStageEnum;

return new class extends Migration
{
    public function up()
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->integer('stage_id')->nullable();
            $table->string('source')->default('<unspecified>');
            $table->string('source_url')->nullable();
            $table->integer('contacted')->default(0);
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedBigInteger('assigned_lender_id')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->string('picture')->nullable();
            $table->text('background')->nullable();
            $table->unsignedBigInteger('timeframe_id')->nullable();
            $table->string('created_via')->default('API');
            $table->timestamps();
            $table->timestamp('last_activity')->nullable();
            $table->foreign('assigned_user_id')->references('id')->on('users');
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('people');
    }
};
