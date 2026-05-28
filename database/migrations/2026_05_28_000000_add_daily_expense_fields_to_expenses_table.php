<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->boolean('is_daily_expense')->default(false)->after('exchange_rate');
            $table->decimal('daily_amount', 12, 2)->nullable()->after('is_daily_expense');
            $table->decimal('daily_currency_amount', 12, 2)->nullable()->after('daily_amount');
            $table->unsignedSmallInteger('daily_days')->nullable()->after('daily_currency_amount');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn([
                'is_daily_expense',
                'daily_amount',
                'daily_currency_amount',
                'daily_days',
            ]);
        });
    }
};
