<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AMVPM', function (Blueprint $table) {
            $table->id();
            $table->string('version', 100);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('business_unit_id')->nullable();
            $table->unsignedBigInteger('module_id')->nullable();
            $table->string('module_reference')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_unit_id', 'module_reference', 'is_active'], 'amvpm_scope_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AMVPM');
    }
};
