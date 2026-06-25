<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AMLPM', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_mapping_id');
            $table->unsignedInteger('level_number')->default(1);
            $table->json('level_groups')->nullable();
            $table->timestamps();

            $table->foreign('approval_mapping_id')->references('id')->on('AMPMA')->cascadeOnDelete();
            $table->unique('approval_mapping_id', 'amlpm_mapping_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AMLPM');
    }
};
