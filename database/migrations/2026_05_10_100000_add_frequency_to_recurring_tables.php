<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_expenses', function (Blueprint $table) {
            $table->string('frequency', 16)->default('monthly')->after('category');
            $table->unsignedTinyInteger('day_of_week')->nullable()->after('day_of_month');
        });

        Schema::table('recurring_incomes', function (Blueprint $table) {
            $table->string('frequency', 16)->default('monthly')->after('amount');
            $table->unsignedTinyInteger('day_of_week')->nullable()->after('day_of_month');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_expenses', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'day_of_week']);
        });

        Schema::table('recurring_incomes', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'day_of_week']);
        });
    }
};
