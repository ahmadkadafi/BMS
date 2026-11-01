<?php

namespace App\Http\Controllers;

use App\Models\Gardu;
use App\Models\Logger;
use App\Models\Resor;
use App\Services\ReportStatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private ReportStatsService $stats)
    {
    }

    public function report(Request $request): RedirectResponse
    {
        $authUser = $request->session()->get('auth_user', []);

        $targetResorId = $authUser['allowed_resor_id'] ?? Resor::query()->orderBy('id')->value('id');

        if (! $targetResorId) {
            abort(404, 'Data resor belum tersedia.');
        }

        return redirect()->route('report.resor', ['resor' => $targetResorId]);
    }

    public function index(Request $request, ?Resor $resor = null): View
    {
        $month = max(1, min(12, (int) $request->input('month', now()->month)));
        $year = max(2000, min(2100, (int) $request->input('year', now()->year)));
        $referenceDate = Carbon::create($year, $month, 1)->endOfMonth();

        $gardus = Gardu::query()
            ->when($resor, fn ($q) => $q->where('resor_id', $resor->id))
            ->with(['batteries:id,serial_no,pemasangan,gardu_id', 'chargers:id,gardu_id'])
            ->orderBy('nama')
            ->get();

        $monthlyStats = $this->stats->forMonth($year, $month);

        $alertCounts = Logger::query()
            ->whereBetween('occurred_at', [
                $referenceDate->copy()->startOfMonth()->toDateTimeString(),
                $referenceDate->toDateTimeString(),
            ])
            ->selectRaw('gardu_id')
            ->selectRaw("SUM(CASE WHEN status='warning' THEN 1 ELSE 0 END) as warning_count")
            ->selectRaw("SUM(CASE WHEN status='alarm' THEN 1 ELSE 0 END) as alarm_count")
            ->groupBy('gardu_id')
            ->get()
            ->keyBy('gardu_id');

        $reportCards = $gardus->map(function ($gardu) use ($monthlyStats, $alertCounts, $referenceDate) {
            $days = $monthlyStats->get($gardu->id, collect());
            $alerts = $alertCounts->get($gardu->id);

            $remainingDays = $gardu->batteries
                ->filter(fn ($b) => ! empty($b->pemasangan))
                ->map(function ($battery) use ($referenceDate) {
                    $expiredAt = Carbon::parse($battery->pemasangan)->addYears(2);
                    return $referenceDate->diffInDays($expiredAt, false);
                })
                ->min();

            $card = [
                'gardu' => $gardu,
                'remaining_days' => $remainingDays,
                'warning_count' => (int) ($alerts->warning_count ?? 0),
                'alarm_count' => (int) ($alerts->alarm_count ?? 0),
            ];

            foreach (ReportStatsService::METRICS as $metric) {
                $min = $this->extreme($days, $metric.'_min', 'min');
                $max = $this->extreme($days, $metric.'_max', 'max');

                $card['min_'.$metric] = $min['value'];
                $card['min_'.$metric.'_at'] = $min['at'];
                $card['max_'.$metric] = $max['value'];
                $card['max_'.$metric.'_at'] = $max['at'];
            }

            return $card;
        });

        return view('page.report', [
            'resor' => $resor,
            'month' => $month,
            'year' => $year,
            'years' => range(now()->year - 5, now()->year + 1),
            'reportCards' => $reportCards,
        ]);
    }

    /**
     * Nilai ekstrem satu bulan beserta tanggalnya. Jika seri, minimum memakai hari
     * paling awal dan maksimum memakai hari paling akhir.
     */
    private function extreme(Collection $days, string $column, string $mode): array
    {
        $candidates = $days->filter(fn ($day) => ! is_null($day->{$column}));

        if ($candidates->isEmpty()) {
            return ['value' => null, 'at' => null];
        }

        $picked = $candidates
            ->sort(function ($a, $b) use ($column, $mode) {
                $order = $mode === 'min'
                    ? [(float) $a->{$column}, $a->date] <=> [(float) $b->{$column}, $b->date]
                    : [(float) $b->{$column}, $b->date] <=> [(float) $a->{$column}, $a->date];

                return $order;
            })
            ->first();

        return ['value' => $picked->{$column}, 'at' => $picked->date];
    }
}
