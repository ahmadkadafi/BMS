<?php

namespace App\Http\Controllers;

use App\Models\Gardu;
use App\Models\Resor;
use App\Support\DateRange;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TableController extends Controller
{
    public const MAX_RANGE_DAYS = 30;

    private const PER_PAGE = 10;

    // Jumlah snapshot yang diproses sekali jalan saat export
    private const EXPORT_CHUNK = 500;

    private const TABLES = ['volt', 'temp', 'thd', 'soc', 'soh', 'charger'];

    public function index(Request $request, ?Resor $resor = null)
    {
        $gardu = Gardu::query()
            ->when($resor, fn ($q) => $q->where('resor_id', $resor->id))
            ->with([
                'batteries' => fn ($q) => $q->select('id', 'gardu_id')->orderBy('id'),
                'chargers' => fn ($q) => $q->select('id', 'gardu_id')->orderBy('id'),
            ])
            ->orderBy('id')
            ->get(['id', 'nama']);

        // Tanpa parameter: gardu default; gardu_id kosong dari dropdown: semua gardu
        if (! $request->has('gardu_id')) {
            $selectedGarduId = $gardu->contains('id', 1) ? 1 : $gardu->min('id');
        } else {
            $selectedGarduId = $gardu->contains('id', (int) $request->gardu_id) ? (int) $request->gardu_id : null;
        }

        $garduData = $selectedGarduId ? $gardu->where('id', $selectedGarduId)->values() : $gardu;

        $selectedTable = in_array($request->input('table'), self::TABLES, true) ? $request->input('table') : 'volt';
        $isCharger = $selectedTable === 'charger';

        $range = DateRange::fromRequest($request, self::MAX_RANGE_DAYS);
        $fromDate = $range->fromDate();
        $toDate = $range->toDate();
        $maxDate = DateRange::maxDate();
        $rangeNotice = $range->notice;

        $unit = ['volt' => 'V', 'temp' => '°C', 'thd' => 'mΩ', 'soc' => '%', 'soh' => '%'][$selectedTable] ?? '';
        $valueColumns = $isCharger
            ? ['Volt Charge (V)', 'Current Charge (A)']
            : collect(range(1, max(1, (int) $garduData->max(fn ($g) => $g->batteries->count()))))
                ->map(fn ($i) => 'Batt '.$i.' ('.$unit.')')
                ->all();

        $snapshots = $this->snapshotQuery($garduData, $isCharger, $range);

        if ($request->boolean('export')) {
            return $this->exportCsv($snapshots, $garduData, $selectedTable, $valueColumns, $range);
        }

        $total = DB::query()->fromSub((clone $snapshots)->reorder(), 'snapshots')->count();
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $rows = new LengthAwarePaginator(
            $this->buildRows((clone $snapshots)->forPage($currentPage, self::PER_PAGE)->get(), $garduData, $selectedTable, count($valueColumns)),
            $total,
            self::PER_PAGE,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('page.table', compact(
            'resor',
            'gardu',
            'selectedGarduId',
            'rows',
            'selectedTable',
            'valueColumns',
            'fromDate',
            'toDate',
            'maxDate',
            'rangeNotice'
        ));
    }

    /**
     * Satu baris tabel = satu waktu pengukuran pada satu gardu, terbaru di atas.
     */
    private function snapshotQuery(Collection $garduData, bool $isCharger, DateRange $range): Builder
    {
        [$table, $foreignKey, $devices] = $this->source($garduData, $isCharger);

        return DB::table($table.' as m')
            ->join(($isCharger ? 'charger' : 'battery').' as d', 'd.id', '=', 'm.'.$foreignKey)
            ->whereIn('m.'.$foreignKey, $devices->keys())
            ->whereBetween('m.measured_at', [$range->from, $range->end()])
            ->select('d.gardu_id', 'm.measured_at')
            ->distinct()
            ->orderByDesc('m.measured_at')
            ->orderBy('d.gardu_id');
    }

    private function buildRows(Collection $snapshots, Collection $garduData, string $selectedTable, int $valueCount): Collection
    {
        if ($snapshots->isEmpty()) {
            return collect();
        }

        $isCharger = $selectedTable === 'charger';
        [$table, $foreignKey, $devices] = $this->source($garduData, $isCharger);
        $columns = $isCharger ? ['voltage', 'current'] : [$selectedTable];

        $readings = DB::table($table)
            ->whereIn($foreignKey, $devices->keys())
            ->whereIn('measured_at', $snapshots->pluck('measured_at')->unique()->values())
            ->orderBy($foreignKey)
            ->get([$foreignKey, 'measured_at', ...$columns])
            ->groupBy(fn ($row) => $devices[$row->{$foreignKey}]['gardu_id'].'|'.$row->measured_at);

        $garduNames = $garduData->pluck('nama', 'id');
        $timezone = config('app.timezone');

        return $snapshots->map(function ($snapshot) use ($readings, $devices, $foreignKey, $isCharger, $selectedTable, $valueCount, $garduNames, $timezone) {
            $group = $readings->get($snapshot->gardu_id.'|'.$snapshot->measured_at, collect());

            if ($isCharger) {
                $join = fn (string $column) => $group->pluck($column)->filter(fn ($v) => $v !== null)->implode(', ') ?: null;
                $values = [$join('voltage'), $join('current')];
            } else {
                // Nilai ditempatkan sesuai urutan baterai di gardu, sehingga kolom tidak bergeser saat ada data kosong
                $values = array_fill(0, $valueCount, null);
                foreach ($group as $reading) {
                    $values[$devices[$reading->{$foreignKey}]['position']] = $reading->{$selectedTable};
                }
            }

            return [
                'time' => Carbon::parse($snapshot->measured_at, $timezone),
                'gardu' => $garduNames[$snapshot->gardu_id] ?? '-',
                'values' => $values,
            ];
        });
    }

    /**
     * [tabel monitoring, foreign key, device_id => {gardu_id, position}]
     */
    private function source(Collection $garduData, bool $isCharger): array
    {
        $devices = $garduData->flatMap(fn ($g) => ($isCharger ? $g->chargers : $g->batteries)
            ->values()
            ->map(fn ($device, $position) => ['id' => $device->id, 'gardu_id' => $g->id, 'position' => $position])
        )->keyBy('id');

        return $isCharger
            ? ['charger_monitoring', 'charger_id', $devices]
            : ['battery_monitoring', 'battery_id', $devices];
    }

    private function exportCsv(Builder $snapshots, Collection $garduData, string $selectedTable, array $valueColumns, DateRange $range): StreamedResponse
    {
        $filename = sprintf(
            'bms-%s-%s-%s_%s.csv',
            $garduData->count() === 1 ? Str::slug($garduData->first()->nama) : 'semua-gardu',
            $selectedTable === 'thd' ? 'ir' : $selectedTable,
            $range->from->format('Ymd'),
            $range->to->format('Ymd')
        );

        return response()->streamDownload(function () use ($snapshots, $garduData, $selectedTable, $valueColumns) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['No', 'Date', 'Time', 'Lokasi', ...$valueColumns]);

            $number = 0;
            for ($page = 1; ; $page++) {
                $chunk = (clone $snapshots)->forPage($page, self::EXPORT_CHUNK)->get();

                foreach ($this->buildRows($chunk, $garduData, $selectedTable, count($valueColumns)) as $row) {
                    fputcsv($handle, [
                        ++$number,
                        $row['time']->format('d/m/Y'),
                        $row['time']->format('H:i:s'),
                        $row['gardu'],
                        ...array_map(fn ($value) => $value ?? '-', $row['values']),
                    ]);
                }

                if ($chunk->count() < self::EXPORT_CHUNK) {
                    break;
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
