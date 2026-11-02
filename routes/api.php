<?php

use App\Http\Controllers\MonitoringIngestController;
use Illuminate\Support\Facades\Route;

Route::post('/ingest/monitoring', [MonitoringIngestController::class, 'store'])
    ->middleware('ingest.token')
    ->name('ingest.monitoring');
