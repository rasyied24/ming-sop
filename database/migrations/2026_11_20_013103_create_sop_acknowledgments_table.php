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
        Schema::create('sop_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sop_version');
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->unique(['sop_id', 'user_id', 'sop_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sop_acknowledgments');
    }
};
