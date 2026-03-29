@extends('layouts.app')
@section('title', __('lang_v1.business_location_map'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black"> @lang('lang_v1.business_location_map')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-solid'])
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <div id="map" style="height: 600px;"></div>
    @endcomponent

</section>
<!-- /.content -->
@stop
@section('javascript')
    <script type="text/javascript">
        var map;
        var locations = @json($locations);

        function initMap() {
            map = L.map('map').setView([30.0444, 31.2357], 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            var bounds = L.latLngBounds();

            locations.forEach(function(location) {
                if (location.latitude && location.longitude) {
                    var lat = parseFloat(location.latitude);
                    var lng = parseFloat(location.longitude);
                    var marker = L.marker([lat, lng]).addTo(map);

                    marker.bindPopup('<strong>' + location.name + '</strong><br>' + (location.landmark || ''));

                    bounds.extend([lat, lng]);
                }
            });

            if (bounds.isValid()) {
                map.fitBounds(bounds);
            }
        }

        $(document).ready(function() {
            initMap();
        });
    </script>
@endsection
