<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('plaintes', function (Blueprint $table) {
            $table->boolean('recevable')->nullable()->after('statut');
        });
    }

    public function down()
    {
        Schema::table('plaintes', function (Blueprint $table) {
            $table->dropColumn('recevable');
        });
    }
};
