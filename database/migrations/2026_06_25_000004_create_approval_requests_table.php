<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('APPRO', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_id');
            $table->string('business_unit', 100);
            $table->string('module', 100);
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedInteger('current_level')->nullable();
            $table->unsignedBigInteger('mapping_version_id')->nullable();
            $table->unsignedBigInteger('approval_mapping_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('mapping_version_id')->references('id')->on('AMVPM')->nullOnDelete();
            $table->foreign('approval_mapping_id')->references('id')->on('AMPMA')->nullOnDelete();
            $table->index(['status', 'module'], 'appro_status_module_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('APPRO');
    }
};
