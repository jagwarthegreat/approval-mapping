<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ARLPE', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_request_id');
            $table->string('reference')->nullable();
            $table->unsignedInteger('cycle_no')->default(1);
            $table->unsignedInteger('level');
            $table->unsignedBigInteger('approver_user_id');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->enum('action', ['approved', 'rejected', 'returned']);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('approval_request_id')->references('id')->on('APPRO')->cascadeOnDelete();
            $table->index(['approval_request_id', 'cycle_no', 'level'], 'arlpe_request_cycle_level_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ARLPE');
    }
};
