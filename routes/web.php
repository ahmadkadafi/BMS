<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\GraphicController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\BatterySettingController;
use App\Http\Controllers\LoggerController;
use App\Http\Controllers\AnalyticController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\LineController;
use App\Http\Controllers\TableController;


Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');
Route::get('/about', [AboutController::class, 'about']);
Route::get('/monitoring/resor/{resor}', [MonitoringController::class, 'index'])
    ->name('monitoring.resor');
Route::get('/graphic/resor/{resor}', [GraphicController::class, 'index'])
    ->name('graphic.resor');
Route::get('/batterysetting', [BatterySettingController::class, 'batterysetting']);
Route::get('/logger', [LoggerController::class, 'logger']);
Route::get('/analytic', [AnalyticController::class, 'analytic']);
Route::get('/report', [ReportController::class, 'report']);
Route::get('/test', [TestController::class, 'test']);
Route::get('/line/resor/{resor}', [LineController::class, 'index'])
    ->name('line.resor');
Route::get('/table/resor/{resor}', [TableController::class, 'index'])
    ->name('table.resor');


