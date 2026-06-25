<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AMPMA', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('version_id');
            $table->unsignedBigInteger('business_unit_id')->nullable();
            $table->unsignedBigInteger('cost_range_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('department')->nullable();
            $table->string('module', 100);
            $table->enum('type', ['direct', 'agency'])->default('direct');
            $table->boolean('is_sequential')->default(false);
            $table->decimal('auto_approve_threshold', 12, 2)->nullable();
            $table->unsignedInteger('escalation_days')->nullable();
            $table->timestamps();

            $table->foreign('version_id')->references('id')->on('AMVPM')->cascadeOnDelete();
            $table->index(['version_id', 'module', 'business_unit_id'], 'ampma_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AMPMA');
    }
};
