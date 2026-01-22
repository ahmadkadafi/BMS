@extends('layouts.master')
@section('title')
    Dashboard
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div id="map"></div>
        </div>
        <div class="col-md-12 mt-4">
          <h4 class="card-title">Battery Performance</h4>
          <div class="row row-card-no-pd mt-2">
            <div class="col-12 col-sm-6 col-md-6 col-xl-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <h6><b>GT Rangkas</b></h6>                   
                    <h4 class="text-info fw-bold">90%</h4>
                  </div>
                  <div class="progress progress-sm">
                    <div
                      class="progress-bar bg-info w-75"
                      role="progressbar"
                      aria-valuenow="75"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    ></div>
                  </div>
                  <div class="d-flex justify-content-between mt-2">
                    <p class="text-muted mb-0">State of Healt</p>
                    <p class="text-muted mb-0">SOH</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-xl-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <h6><b>GT Lebak</b></h6>                   
                    <h4 class="text-info fw-bold">95%</h4>
                  </div>
                  <div class="progress progress-sm">
                    <div
                      class="progress-bar bg-success w-75"
                      role="progressbar"
                      aria-valuenow="95"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    ></div>
                  </div>
                  <div class="d-flex justify-content-between mt-2">
                    <p class="text-muted mb-0">State of Healt</p>
                    <p class="text-muted mb-0">SOH</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-xl-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <h6><b>GT Lebak</b></h6>                   
                    <h4 class="text-info fw-bold">95%</h4>
                  </div>
                  <div class="progress progress-sm">
                    <div
                      class="progress-bar bg-success w-75"
                      role="progressbar"
                      aria-valuenow="95"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    ></div>
                  </div>
                  <div class="d-flex justify-content-between mt-2">
                    <p class="text-muted mb-0">State of Healt</p>
                    <p class="text-muted mb-0">SOH</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-xl-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <h6><b>GT Lebak</b></h6>                   
                    <h4 class="text-info fw-bold">95%</h4>
                  </div>
                  <div class="progress progress-sm">
                    <div
                      class="progress-bar bg-success w-75"
                      role="progressbar"
                      aria-valuenow="95"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    ></div>
                  </div>
                  <div class="d-flex justify-content-between mt-2">
                    <p class="text-muted mb-0">State of Healt</p>
                    <p class="text-muted mb-0">SOH</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-xl-3">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <h6><b>GT Lebak</b></h6>                   
                    <h4 class="text-info fw-bold">95%</h4>
                  </div>
                  <div class="progress progress-sm">
                    <div
                      class="progress-bar bg-success w-75"
                      role="progressbar"
                      aria-valuenow="95"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    ></div>
                  </div>
                  <div class="d-flex justify-content-between mt-3">
                    <p class="text-muted mb-0">State of Healt</p>
                    <p class="text-muted mb-0">SOH</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      
    </div>
    

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
      #map { height: 60vh; } /* penuh layar; bisa diubah sesuai kebutuhan */
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

@endsection