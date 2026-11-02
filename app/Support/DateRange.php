<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Rentang tanggal filter (from/to, format Y-m-d) dengan batas jumlah hari.
 * Tanggal tidak valid diganti hari ini, tanggal terbalik ditukar, dan rentang
 * yang terlalu panjang dipotong dari sisi "from" disertai pesan.
 */
class DateRange
{
    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly int $maxDays,
        public readonly ?string $notice = null,
    ) {
    }

    public static function fromRequest(Request $request, int $maxDays): self
    {
        $today = now(config('app.timezone'))->startOfDay();
        $to = self::parse($request->input('to')) ?? $today->copy();
        $from = self::parse($request->input('from')) ?? $to->copy();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $notice = null;
        if ($from->diffInDays($to) >= $maxDays) {
            $from = $to->copy()->subDays($maxDays - 1);
            $notice = 'Rentang tanggal maksimal '.$maxDays.' hari. Filter disesuaikan menjadi '
                .$from->format('d/m/Y').' s/d '.$to->format('d/m/Y').'.';
        }

        return new self($from, $to, $maxDays, $notice);
    }

    public function end(): Carbon
    {
        return $this->to->copy()->endOfDay();
    }

    public function fromDate(): string
    {
        return $this->from->toDateString();
    }

    public function toDate(): string
    {
        return $this->to->toDateString();
    }

    public static function maxDate(): string
    {
        return now(config('app.timezone'))->toDateString();
    }

    private static function parse(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value, config('app.timezone'));

            return $date->toDateString() === $value ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
