<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1],
            ['code' => 'KHR', 'name' => 'Cambodian Riel', 'symbol' => 'KHR', 'exchange_rate' => 0.00025],
            ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => 'THB', 'exchange_rate' => 0.027],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => 'EUR', 'exchange_rate' => 1.08],
        ])->each(fn (array $currency) => Currency::updateOrCreate(
            ['code' => $currency['code']],
            $currency
        ));
    }
}
