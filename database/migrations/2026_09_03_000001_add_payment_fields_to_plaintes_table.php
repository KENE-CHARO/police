<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plaintes', function (Blueprint $table) {
            $table->string('payment_phone')->nullable()->after('paid');
            $table->string('payment_operator')->nullable()->after('payment_phone');
            $table->string('payment_txn_id')->nullable()->after('payment_operator');
            $table->string('payment_status')->nullable()->default('pending')->after('payment_txn_id');
            $table->integer('payment_amount')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('plaintes', function (Blueprint $table) {
            $table->dropColumn(['payment_phone', 'payment_operator', 'payment_txn_id', 'payment_status', 'payment_amount']);
        });
    }
};
