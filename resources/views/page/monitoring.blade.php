@extends('layouts.master')
@section('title')
    Data Monitoring
@endsection
@section('content')
@include('partials.location')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Gardu Traksi A</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg">
                        <div class="card card-stats card-round">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div
                                    class="icon-big text-center icon-success bubble-shadow-small"
                                    >
                                    <i class="fas fa-thermometer-half"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Temperature</p>
                                        <h4 class="card-title">24&degC</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg">
                        <div class="card card-stats card-round">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div
                                    class="icon-big text-center icon-success bubble-shadow-small"
                                    >
                                    <i class="fas fa-battery-three-quarters"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Voltage</p>
                                        <h4 class="card-title">13.8 VDC</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg">
                        <div class="card card-stats card-round">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div
                                    class="icon-big text-center icon-success bubble-shadow-small"
                                    >
                                    <i class="fas fa-code-branch"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Internal Resistance</p>
                                        <h4 class="card-title">90%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg">
                        <div class="card card-stats card-round">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div
                                    class="icon-big text-center icon-success bubble-shadow-small"
                                    >
                                    <i class="fas fa-car-battery"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">State of Health</p>
                                        <h4 class="card-title">90%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg">
                        <div class="card card-stats card-round">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div
                                    class="icon-big text-center icon-success bubble-shadow-small"
                                    >
                                    <i class="fas fa-car-battery"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">State of Charge</p>
                                        <h4 class="card-title">90%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg">
                        <div class="card card-stats card-round">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div
                                    class="icon-big text-center icon-primary bubble-shadow-small"
                                    >
                                    <i class="fas fa-car-battery"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Voltage Charging</p>
                                        <h4 class="card-title">110.3 VDC</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg">
                        <div class="card card-stats card-round">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div
                                    class="icon-big text-center icon-warning bubble-shadow-small"
                                    >
                                    <i class="fas fa-plug"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Current Charging</p>
                                        <h4 class="card-title">0.02 A</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>  
            </div>
        </div>
    </div>
</div>
@endsection
@section('adding')
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""
    />
  <style>
      /* Pastikan kontainer peta punya tinggi agar peta terlihat */
      html, body { height: 100%; margin: 0; padding: 0; }
      #map { height: 40vh; } /* penuh layar; bisa diubah sesuai kebutuhan */
  </style>
  <!-- Leaflet JS (CDN) -->
  <script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
  ></script>

  <script>
    // 1) Inisialisasi peta: setView(lat, lng, zoom)
    const map = L.map('map').setView([-6.200000, 106.816666], 14); // contoh: Jakarta

    // 2) Tambahkan tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
      maxZoom: 19
    }).addTo(map);

    // 3) Tambahkan marker dan popup
    const marker = L.marker([-6.200000, 106.816666]).addTo(map);
    marker.bindPopup('<b>Jakarta</b><br>Contoh marker Leaflet.').openPopup();

    // 5) Event klik peta: tampilkan koordinat
    map.on('click', function(e) {
      const lat = e.latlng.lat.toFixed(6);
      const lng = e.latlng.lng.toFixed(6);
      L.popup()
        .setLatLng(e.latlng)
        .setContent('Koordinat: ' + lat + ', ' + lng)
        .openOn(map);
    });

    // 6) Mencoba geolocation pengguna (jika diizinkan)
    function locateMe() {
      map.locate({ setView: true, maxZoom: 16 });
    }
    map.on('locationfound', function(e) {
      const radius = e.accuracy;
      L.marker(e.latlng).addTo(map).bindPopup('Anda di sini — akurasi: ' + Math.round(radius) + ' m').openPopup();
      L.circle(e.latlng, radius).addTo(map);
    });
    map.on('locationerror', function() {
      console.warn('Lokasi tidak tersedia / izin ditolak.');
    });

    // Panggil fungsi locate (opsional) — hapus atau panggil dari tombol sesuai kebutuhan
    // locateMe();
  </script>
@endsection
