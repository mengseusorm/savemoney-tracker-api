<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('income_source_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('currency_amount', 12, 2)->nullable();
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->date('income_date');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
