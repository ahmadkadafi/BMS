{{-- $eta dari AlarmEtaService, $target nama level yang dituju (Warning/Alarm) --}}
@php
    $state = $eta['state'] ?? null;
    $r2 = isset($eta['r2']) ? ' (R² '.number_format($eta['r2'], 2).')' : '';
@endphp
@switch($state)
    @case('eta')
        <span class="text-nowrap" title="Perkiraan dari tren{{ $r2 }}{{ ! empty($eta['extrapolated']) ? ', diekstrapolasi melewati horizon terjauh' : '' }}">
            {{ $target }} ± {{ $eta['at']->format(isset($eta['days']) ? 'd/m/Y' : 'd/m/Y H:i') }}
        </span>
        <br><small class="text-muted">
            @if (isset($eta['days']))
                {{ $eta['days'] }} hari dari anchor{{ ! empty($eta['extrapolated']) ? ', perkiraan kasar' : '' }}
            @else
                {{ $eta['at']->isPast() ? 'seharusnya sudah tercapai' : $eta['at']->locale('id')->diffForHumans(['parts' => 2]) }}
            @endif
        </small>
        @break
    @case('reached')
        <span class="text-danger">{{ $target }} sudah tercapai</span>
        @break
    @case('beyond')
        <span class="text-muted">{{ $target }} &gt; {{ config('alarm.eta.max_days') }} hari</span>
        @break
    @case('stable')
        <span class="text-success" title="Tren tidak bergerak menuju batas{{ $r2 }}">Tren menjauh/stabil</span>
        @break
    @case('uncertain')
        <span class="text-muted" title="Data terlalu sedikit atau tren terlalu acak{{ $r2 }}">Tren belum jelas</span>
        @break
    @default
        -
@endswitch
