<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });

        Schema::table('recurring_expenses', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });

        Schema::table('recurring_incomes', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });

        Schema::table('recurring_expenses', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });

        Schema::table('recurring_incomes', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });
    }
};
