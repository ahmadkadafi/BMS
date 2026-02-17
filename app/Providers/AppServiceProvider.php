<?php

namespace App\Providers;

use App\Models\BatteryMonitoring;
use App\Models\Logger;
use App\Models\Resor;
use App\Observers\BatteryMonitoringObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        BatteryMonitoring::observe(BatteryMonitoringObserver::class);

        View::composer('partials.sidebar', function ($view) {
            $query = Resor::query()->orderBy('nama');
            $authUser = session('auth_user');

            if (($authUser['role'] ?? null) !== 'admin' && isset($authUser['allowed_resor_id'])) {
                $query->where('id', $authUser['allowed_resor_id']);
            }

            $resors = $query->get();
            $view->with('resors', $resors);
        });

        View::composer('partials.header', function ($view) {
            $authUser = session('auth_user', []);
            $isAdmin = ($authUser['role'] ?? null) === 'admin';
            $allowedResorId = $authUser['allowed_resor_id'] ?? null;

            $baseQuery = Logger::query()
                ->whereIn('status', ['warning', 'alarm'])
                ->where('action', 'coming');

            if (! $isAdmin && $allowedResorId) {
                $baseQuery->where('resor_id', $allowedResorId);
            }

            $warningCount = (clone $baseQuery)->where('status', 'warning')->count();
            $alarmCount = (clone $baseQuery)->where('status', 'alarm')->count();
            $notificationCount = $warningCount + $alarmCount;

            $latestNotifications = (clone $baseQuery)
                ->with(['resor:id,nama', 'gardu:id,nama'])
                ->orderByDesc('occurred_at')
                ->limit(8)
                ->get();

            $targetResorId = $allowedResorId ?: $latestNotifications->first()?->resor_id;

            $view->with([
                'notificationCount' => $notificationCount,
                'warningCount' => $warningCount,
                'alarmCount' => $alarmCount,
                'latestNotifications' => $latestNotifications,
                'notificationResorId' => $targetResorId,
            ]);
        });
        
        Paginator::useBootstrapFive();
    }
}
