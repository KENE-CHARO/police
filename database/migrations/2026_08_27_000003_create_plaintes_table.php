<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plaintes', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('plaignant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('commissariat_id')->nullable()->constrained('commissariats')->nullOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('statut')->default('nouveau');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plaintes');
    }
};
