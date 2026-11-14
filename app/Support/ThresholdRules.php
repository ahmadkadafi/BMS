<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\Validator;

/**
 * Validasi urutan batas warning/alarm. Untuk batas bawah alarm harus di bawah warning,
 * untuk batas atas alarm harus di atas warning; jika tidak, level warning tidak pernah
 * tercapai karena alarm dicek lebih dulu.
 */
class ThresholdRules
{
    /**
     * @param  array  $rules  aturan dasar Laravel
     * @param  array  $pairs  [warn, alarm, label, 'min'|'max']
     * @param  array  $ranges  [lower, upper, label]: batas min harus di bawah batas max
     */
    public static function validate(Request $request, array $rules, array $pairs, array $ranges = []): array
    {
        $attributes = collect($pairs)->flatMap(fn ($pair) => [
            $pair[0] => "{$pair[2]} warning",
            $pair[1] => "{$pair[2]} alarm",
        ])->all();

        return ValidatorFactory::make($request->all(), $rules, [], $attributes)
            ->after(function (Validator $validator) use ($pairs, $ranges) {
                $data = $validator->getData();
                $value = fn (string $key) => isset($data[$key]) && is_numeric($data[$key]) ? (float) $data[$key] : null;

                foreach ($pairs as [$warn, $alarm, $label, $mode]) {
                    $w = $value($warn);
                    $a = $value($alarm);

                    if (is_null($w) || is_null($a)) {
                        continue;
                    }
                    if ($mode === 'min' && $a >= $w) {
                        $validator->errors()->add($alarm, "{$label}: batas alarm harus lebih kecil dari batas warning.");
                    }
                    if ($mode === 'max' && $a <= $w) {
                        $validator->errors()->add($alarm, "{$label}: batas alarm harus lebih besar dari batas warning.");
                    }
                }

                foreach ($ranges as [$lower, $upper, $label]) {
                    $l = $value($lower);
                    $u = $value($upper);

                    if (! is_null($l) && ! is_null($u) && $l >= $u) {
                        $validator->errors()->add($upper, "{$label}: batas max harus lebih besar dari batas min.");
                    }
                }
            })
            ->validate();
    }
}
