<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('target_amount', 12, 2)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('target_currency_amount', 12, 2)->nullable();
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('current_amount', 12, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status')->default('active');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_goals');
    }
};
