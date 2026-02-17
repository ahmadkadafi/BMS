<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResorDashboardController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\GraphicController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\BatterySettingController;
use App\Http\Controllers\ChargerSettingController;
use App\Http\Controllers\LoggerController;
use App\Http\Controllers\AnalyticController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\LineController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\InputController;


Route::get('/', function () {
    if (session()->has('auth_user')) {
        $authUser = session('auth_user', []);
        if (($authUser['role'] ?? null) === 'admin') {
            return redirect()->route('dashboard');
        }
        if (isset($authUser['allowed_resor_id'])) {
            return redirect()->route('dashboard.resor', ['resor' => $authUser['allowed_resor_id']]);
        }
        $firstResorId = \App\Models\Resor::query()->orderBy('id')->value('id');
        if ($firstResorId) {
            return redirect()->route('dashboard.resor', ['resor' => $firstResorId]);
        }
        return redirect()->route('login');
    }

    return redirect()->route('login');
});

Route::middleware('guest.session')->group(function () {
    Route::get('/login', [LoginController::class, 'login'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth.session')
    ->name('logout');

Route::middleware(['auth.session', 'admin.only'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
});

Route::middleware(['auth.session', 'resor.access'])->group(function () {
    Route::get('/dashboard/resor/{resor}', [ResorDashboardController::class, 'index'])->name('dashboard.resor');
    Route::get('/about', [AboutController::class, 'about']);
    Route::get('/monitoring/resor/{resor}', [MonitoringController::class, 'index'])
        ->name('monitoring.resor');
    Route::get('/graphic/resor/{resor}', [GraphicController::class, 'index'])
        ->name('graphic.resor');
    Route::get('/logger/resor/{resor}', [LoggerController::class, 'index'])
        ->name('logger.resor');
    Route::patch('/logger/resor/{resor}/submit/{logger}', [LoggerController::class, 'submit'])
        ->name('logger.submit');
    Route::get('/analytic', [AnalyticController::class, 'analytic']);
    Route::get('/report', [ReportController::class, 'report'])->name('report');
    Route::get('/report/resor/{resor}', [ReportController::class, 'index'])
        ->name('report.resor');
    Route::get('/test', [TestController::class, 'test']);
    Route::get('/line/resor/{resor}', [LineController::class, 'index'])
        ->name('line.resor');
    Route::get('/table/resor/{resor}', [TableController::class, 'index'])
        ->name('table.resor');
    Route::prefix('batterysetting')->group(function () {
        Route::get('/resor/{resor}',
            [BatterySettingController::class, 'index']
        )->name('batterysetting.index');
        Route::post('/store',
            [BatterySettingController::class, 'store']
        )->name('batterysetting.store');
        Route::delete('/delete/{gardu}',
            [BatterySettingController::class, 'destroy']
        )->name('batterysetting.destroy');
    });
    Route::prefix('chargersetting')->group(function () {
        Route::get('/resor/{resor}',
            [ChargerSettingController::class, 'index']
        )->name('chargersetting.index');
        Route::post('/store',
            [ChargerSettingController::class, 'store']
        )->name('chargersetting.store');
        Route::delete('/delete/{charger}',
            [ChargerSettingController::class, 'destroy']
        )->name('chargersetting.destroy');
    });
});

Route::middleware(['auth.session', 'admin.only'])->prefix('input')->group(function () {
    Route::get('/', [InputController::class, 'index'])->name('input.index');
    Route::post('/users', [InputController::class, 'storeUser'])->name('input.users.store');
    Route::patch('/users/{user}', [InputController::class, 'updateUser'])->name('input.users.update');
    Route::delete('/users/{user}', [InputController::class, 'destroyUser'])->name('input.users.destroy');

    Route::post('/daop', [InputController::class, 'storeDaop'])->name('input.daop.store');
    Route::patch('/daop/{daop}', [InputController::class, 'updateDaop'])->name('input.daop.update');
    Route::delete('/daop/{daop}', [InputController::class, 'destroyDaop'])->name('input.daop.destroy');

    Route::post('/resor', [InputController::class, 'storeResor'])->name('input.resor.store');
    Route::patch('/resor/{resor}', [InputController::class, 'updateResor'])->name('input.resor.update');
    Route::delete('/resor/{resor}', [InputController::class, 'destroyResor'])->name('input.resor.destroy');

    Route::post('/gardu', [InputController::class, 'storeGardu'])->name('input.gardu.store');
    Route::patch('/gardu/{gardu}', [InputController::class, 'updateGardu'])->name('input.gardu.update');
    Route::delete('/gardu/{gardu}', [InputController::class, 'destroyGardu'])->name('input.gardu.destroy');

    Route::post('/charger', [InputController::class, 'storeCharger'])->name('input.charger.store');
    Route::patch('/charger/{charger}', [InputController::class, 'updateCharger'])->name('input.charger.update');
    Route::delete('/charger/{charger}', [InputController::class, 'destroyCharger'])->name('input.charger.destroy');

    Route::post('/battery', [InputController::class, 'storeBattery'])->name('input.battery.store');
    Route::patch('/battery/{battery}', [InputController::class, 'updateBattery'])->name('input.battery.update');
    Route::delete('/battery/{battery}', [InputController::class, 'destroyBattery'])->name('input.battery.destroy');
});
