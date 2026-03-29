@extends('layouts.app')

@section('header')
    <h3>Contact Devices</h3>
@endsection

@section('content')
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Contact Devices</h3>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="contact_devices_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Contact Name</th>
                        <th>Device Name</th>
                        <th>Model</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#contact_devices_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("bookings.contact_devices.data") }}',
        columns: [
            { data: 'id', name: 'contact_device.id' },
            { data: 'contact_name', name: 'contacts.name' },
            { data: 'device_name', name: 'categories.name' },
            { data: 'model_name', name: 'repair_device_models.name' },
            { 
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ]
    });
});
</script>
@endpush
