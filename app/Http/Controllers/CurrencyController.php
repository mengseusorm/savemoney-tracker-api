<?php

namespace App\Http\Controllers;

use App\Http\Requests\CurrencyRequest;
use App\Models\Currency;

class CurrencyController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Currency::orderByDesc('is_active')->orderBy('code')->get(),
        ]);
    }

    public function store(CurrencyRequest $request)
    {
        $currency = Currency::create($request->validated());

        return response()->json([
            'message' => 'Currency created successfully',
            'data' => $currency,
        ], 201);
    }

    public function show(Currency $currency)
    {
        return response()->json([
            'data' => $currency,
        ]);
    }

    public function update(CurrencyRequest $request, Currency $currency)
    {
        $currency->update($request->validated());

        return response()->json([
            'message' => 'Currency updated successfully',
            'data' => $currency,
        ]);
    }

    public function destroy(Currency $currency)
    {
        $currency->delete();

        return response()->json([
            'message' => 'Currency deleted successfully',
        ]);
    }
}
