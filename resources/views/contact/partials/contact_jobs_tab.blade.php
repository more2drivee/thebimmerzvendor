
<?php

$data = DB::table('repair_job_sheets')->where('repair_job_sheets.contact_id', $id)
->join('business_locations', 'business_locations.id', '=', 'repair_job_sheets.location_id')
->join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
->join('contact_device', 'contact_device.id', '=', 'bookings.device_id')
->join('categories', 'categories.id', '=', 'contact_device.device_id')
->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
->select('repair_job_sheets.job_sheet_no', 'business_locations.name', 'repair_device_models.name AS model', 'categories.name AS brand')
->get();

?>

<table class="table table-condensed">
    <thead>
        <tr>
            <th>@lang('car.jobNo')</th>
            <th>@lang('car.location')</th>
            <th>@lang('car.model')</th>
            <th>@lang('car.brand')</th>
            <th>@lang('car.status')</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $car)
            <tr>
                <td><?= $car->job_sheet_no ?></td>
                <td><?= $car->name ?></td>
                <td><?= $car->model ?></td>
                <td><?= $car->brand ?></td>
                <td>In Progress</td>
            </tr>
        @endforeach
    </tbody>

</table>
