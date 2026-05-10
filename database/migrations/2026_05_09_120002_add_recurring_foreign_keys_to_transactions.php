<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('recurring_expense_id')
                ->nullable()
                ->after('user_id')
                ->constrained('recurring_expenses')
                ->nullOnDelete();
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->foreignId('recurring_income_id')
                ->nullable()
                ->after('user_id')
                ->constrained('recurring_incomes')
                ->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->unique(['recurring_expense_id', 'date']);
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->unique(['recurring_income_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique(['recurring_expense_id', 'date']);
            $table->dropForeign(['recurring_expense_id']);
            $table->dropColumn('recurring_expense_id');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropUnique(['recurring_income_id', 'date']);
            $table->dropForeign(['recurring_income_id']);
            $table->dropColumn('recurring_income_id');
        });
    }
};
