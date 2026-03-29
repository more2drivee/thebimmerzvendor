
<?php

$data = DB::table('contact_device')->where('contact_device.contact_id', $id)->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')->join('categories', 'categories.id', '=', 'contact_device.device_id')->select('contact_device.color', 'contact_device.chassis_number', 'contact_device.plate_number', 'contact_device.manufacturing_year', 'repair_device_models.name AS modelName', 'categories.name AS categoryName')->get();

?>

<table class="table table-condensed">
    <thead>
        <tr>
            <th>@lang('car.device')</th>
            <th>@lang('car.model')</th>
            <th>@lang('car.manufacturing')</th>
            <th>@lang('car.color')</th>
            <th>@lang('car.chassis')</th>
            <th>@lang('car.plate')</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $car)
            <tr>
                <td><?= $car->categoryName ?></td>
                <td><?= $car->modelName ?></td>
                <td><?= $car->manufacturing_year ?></td>
                <td><?= $car->color ?></td>
                <td><?= $car->chassis_number ?></td>
                <td><?= $car->plate_number ?></td>
            </tr>
        @endforeach
    </tbody>

</table>
