<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquetes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plainte_id')->constrained('plaintes')->cascadeOnDelete();
            $table->foreignId('enqueteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rapport')->nullable();
            $table->string('statut')->default('ouverte');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquetes');
    }
};
