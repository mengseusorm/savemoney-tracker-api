<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\IncomeSourceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingGoalController;
use App\Http\Controllers\SavingTransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    // User endpoint
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('income-sources', IncomeSourceController::class);
    Route::apiResource('currencies', CurrencyController::class);
    Route::apiResource('incomes', IncomeController::class);
    Route::apiResource('expense-categories', ExpenseCategoryController::class);
    Route::apiResource('expenses', ExpenseController::class);
    Route::apiResource('saving-goals', SavingGoalController::class);
    Route::get('/saving-goals/{savingGoal}/transactions', [SavingTransactionController::class, 'index']);
    Route::post('/saving-goals/{savingGoal}/transactions', [SavingTransactionController::class, 'store']);
    Route::apiResource('saving-transactions', SavingTransactionController::class)->except(['index', 'store']);
    Route::get('/reports/summary', [ReportController::class, 'summary']);
});

Route::get('/test', function () {
    return response()->json([
        'message' => 'Test endpoint is working!',
    ]);
});
