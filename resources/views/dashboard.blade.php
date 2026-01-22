@extends('layouts.master')

@section('title', 'Dashboard')

@section('content')
<div class="row">

    {{-- MAP --}}
    <div class="col-md-12">
        <div id="map"></div>
    </div>

    {{-- BATTERY PERFORMANCE --}}
    <div class="col-md-12 mt-4">
        <h4 class="card-title">Battery Performance</h4>

        <div class="row row-card-no-pd mt-2">
            @foreach ($gardu as $g)
                @php
                    $soh = $g->avg_soh ?? 0;

                    $color = $soh >= 80
                        ? 'success'
                        : ($soh >= 70 ? 'warning' : 'danger');
                @endphp

                <div class="col-12 col-sm-6 col-md-6 col-xl-2 mb-3">
                    <div class="card">
                        <div class="card-body">

                            <div class="d-flex justify-content-between">
                                <h6><b>GT {{ $g->nama }}</b></h6>
                                <h4 class="text-{{ $color }} fw-bold">
                                    {{ number_format($soh, 1) }}%
                                </h4>
                            </div>

                            <div class="progress progress-sm">
                                <div
                                    class="progress-bar bg-{{ $color }}"
                                    style="width: {{ $soh }}%"
                                    role="progressbar">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-2">
                                <p class="text-muted mb-0">State of Health</p>
                                <p class="text-muted mb-0">SOH</p>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection

{{-- ================== EXTRA CSS & JS ================== --}}
@section('adding')

{{-- Leaflet CSS --}}
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<style>
    #map {
        height: 60vh;
        border-radius: 8px;
    }
</style>

{{-- Kirim data PHP → JS --}}
<script>
    const garduData = @json($gardu);
    console.log('Gardu:', garduData);
</script>

{{-- Leaflet JS --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const map = L.map('map').setView([-6.35266, 106.25153], 9);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const garduIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
        iconSize: [30, 30],
        iconAnchor: [15, 30],
        popupAnchor: [0, -28],
    });

    const bounds = [];

    garduData.forEach((g, i) => {
        if (g.latitude && g.longitude) {

            // offset biar marker tidak numpuk
            const offset = i * 0.0003;

            const lat = parseFloat(g.latitude) + offset;
            const lng = parseFloat(g.longitude) + offset;

            bounds.push([lat, lng]);

            L.marker([lat, lng], { icon: garduIcon })
                .addTo(map)
                .bindPopup(`
                    <b>GT ${g.nama}</b><br>
                    SOH Rata-rata: <b>${g.avg_soh ?? 0}%</b><br>
                    Battery: ${g.n_batt}
                `);
        }
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds);
    }
});
</script>
@endsection