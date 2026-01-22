@extends('layouts.master')
@section('title')
    Graphic
@endsection
@section('content')
@include('partials.location')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
            <div class="card-title">Battery Voltage</div>
            </div>
            <div class="card-body">
            <div class="chart-container">
                <canvas id="BattVoltChart"></canvas>
            </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
            <div class="card-title">Battery Temperature</div>
            </div>
            <div class="card-body">
            <div class="chart-container">
                <canvas id="BattTempChart"></canvas>
            </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
            <div class="card-title">Battery Internal Resistance</div>
            </div>
            <div class="card-body">
            <div class="chart-container">
                <canvas id="b"></canvas>
            </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
            <div class="card-title">Battery State of Healt</div>
            </div>
            <div class="card-body">
            <div class="chart-container">
                <canvas id="c"></canvas>
            </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
            <div class="card-title">Voltage Charging</div>
            </div>
            <div class="card-body">
            <div class="chart-container">
                <canvas id="d"></canvas>
            </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
            <div class="card-title">Current Charging</div>
            </div>
            <div class="card-body">
            <div class="chart-container">
                <canvas id="CurrentChart"></canvas>
            </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  var ctx1 = document.getElementById('BattVoltChart');
  new Chart(ctx1, {
    type: "bar",
    data: {
        labels: [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
        ],
        datasets: [
        {
            label: "Batt 1",
            borderColor: "#1d7af3",
            pointBorderColor: "#FFF",
            pointBackgroundColor: "#1d7af3",
            pointBorderWidth: 2,
            pointHoverRadius: 4,
            pointHoverBorderWidth: 1,
            pointRadius: 4,
            backgroundColor: "transparent",
            fill: true,
            borderWidth: 2,
            data: [30],
        },
        {
            label: "Batt 2",
            borderColor: "#59d05d",
            pointBorderColor: "#FFF",
            pointBackgroundColor: "#59d05d",
            pointBorderWidth: 2,
            pointHoverRadius: 4,
            pointHoverBorderWidth: 1,
            pointRadius: 4,
            backgroundColor: "transparent",
            fill: true,
            borderWidth: 2,
            data: [10],
        },
        {
            label: "Batt 2",
            borderColor: "#f3545d",
            pointBorderColor: "#FFF",
            pointBackgroundColor: "#f3545d",
            pointBorderWidth: 2,
            pointHoverRadius: 4,
            pointHoverBorderWidth: 1,
            pointRadius: 4,
            backgroundColor: "transparent",
            fill: true,
            borderWidth: 2,
            data: [58 ],
        },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
        position: "top",
        },
        tooltips: {
        bodySpacing: 4,
        mode: "nearest",
        intersect: 0,
        position: "nearest",
        xPadding: 10,
        yPadding: 10,
        caretPadding: 10,
        },
        layout: {
        padding: { left: 15, right: 15, top: 15, bottom: 15 },
        },
    },
  });
</script>

<script>
  var ctx2 = document.getElementById('BattTempChart');
  new Chart(ctx2, {
    type: "bar",
    data: {
        labels: [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
        ],
        datasets: [
        {
            label: "Batt 1",
            borderColor: "#1d7af3",
            pointBorderColor: "#FFF",
            pointBackgroundColor: "#1d7af3",
            pointBorderWidth: 2,
            pointHoverRadius: 4,
            pointHoverBorderWidth: 1,
            pointRadius: 4,
            backgroundColor: "transparent",
            fill: true,
            borderWidth: 2,
            data: [30, 45, 45, 68, 69, 90, 100, 158, 177, 200, 245, 256],
        },
        {
            label: "Batt 2",
            borderColor: "#59d05d",
            pointBorderColor: "#FFF",
            pointBackgroundColor: "#59d05d",
            pointBorderWidth: 2,
            pointHoverRadius: 4,
            pointHoverBorderWidth: 1,
            pointRadius: 4,
            backgroundColor: "transparent",
            fill: true,
            borderWidth: 2,
            data: [10, 20, 55, 75, 80, 48, 59, 55, 23, 107, 60, 87],
        },
        {
            label: "Batt 3",
            borderColor: "#f3545d",
            pointBorderColor: "#FFF",
            pointBackgroundColor: "#f3545d",
            pointBorderWidth: 2,
            pointHoverRadius: 4,
            pointHoverBorderWidth: 1,
            pointRadius: 4,
            backgroundColor: "transparent",
            fill: true,
            borderWidth: 2,
            data: [10, 30, 58, 79, 90, 105, 117, 160, 185, 210, 185, 194],
        },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
        position: "top",
        },
        tooltips: {
        bodySpacing: 4,
        mode: "nearest",
        intersect: 0,
        position: "nearest",
        xPadding: 10,
        yPadding: 10,
        caretPadding: 10,
        },
        layout: {
        padding: { left: 15, right: 15, top: 15, bottom: 15 },
        },
    },
  });
</script>
@endsection

