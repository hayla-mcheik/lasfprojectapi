<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: DejaVu Sans;
            text-align: center;
        }

        .card {
            border: 2px solid #000;
            padding: 20px;
            width: 350px;
            margin: auto;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .section {
            margin-top: 10px;
            font-size: 12px;
        }

        .qr {
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="card">

    <div class="title">
        CROSS COUNTRY FLIGHT
    </div>

    <div class="section">
        <strong>Pilot:</strong> {{ $request->pilot->name }}
    </div>

    <div class="section">
        <strong>License:</strong>
        {{ optional($request->pilot->pilotProfile)->license_number }}
    </div>

    <div class="section">
        <strong>Date:</strong>
        {{ $request->flight_date->format('d/m/Y') }}
    </div>

    <div class="section">
        <strong>Takeoff:</strong>
        {{ $request->takeoff_time }}
    </div>

    <div class="section">
        <strong>Landing:</strong>
        {{ $request->estimated_landing_time }}
    </div>

    <div class="section">
        <strong>Route:</strong><br>
        @foreach($request->locations as $loc)
            • {{ $loc->location->name }} <br>
        @endforeach
    </div>

    <div class="qr">
        <img src="data:image/png;base64,{{ $qrImage }}">
    </div>

</div>

</body>
</html>