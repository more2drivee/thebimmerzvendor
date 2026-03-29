<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>Info Job</title>
    @livewireStyles

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add Cairo Font from Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0px;
        }

        th,
        td {
            padding: 5px;
            //text-align: center;
        }

        th {
            background-color: #f4f4f4;
        }

        .total {
            margin-top: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .form-container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px 10px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            text-align: right;
        }

        h1 {
            font-size: 28px;
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .title-job {
            text-align: center;
            font-family: 'Cairo', sans-serif;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            text-align: right;
        }

        .form-check {
            margin-bottom: 10px;
        }

        button {
            width: 100%;
            background-color: rgb(37, 126, 161);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
        }

        button:hover {
            background-color: rgb(36, 94, 121);
        }

        .form-check-label {
            font-size: 14px;
        }

        p {
            margin: 0;
            font-weight: bold;
        }

        .progress-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin: 30px 0;
            position: relative;
            padding: 20px 0;
        }

        .progress-container::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: #ddd;
            z-index: -1;
            transform: translateY(-50%);
        }

        .step {
            text-align: center;
            width: 25%;
            position: relative;
        }

        .step .icon {
            width: 40px;
            height: 40px;
            line-height: 40px;
            background: #ddd;
            border-radius: 50%;
            display: inline-block;
            font-size: 18px;
            color: white;
        }

        .step.active .icon {
            background: #0d6efd;
        }

        .step.completed .icon {
            background: #28a745;
        }

        // .step.completed .icon::after {
        //    content: "\f00c";
        //   font-family: "Font Awesome 6 Free";
        // font-weight: 900;
        //}

        .step p {
            margin-top: 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .plate {
            width: 130px;
            height: 60px;
            border: solid black;
            border-radius: 5px;
            //overflow: hidden;
            //box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.5);
            flex-shrink: 0;
        }

        .info-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .info {
            flex: 1;
        }

        .top {
            background-color: #0077c8;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            //padding: 10px;
            font-family: Arial, sans-serif;
            font-size: 15px;
            font-weight: bold;
            text-align: center;
            position: relative;
        }

        .top .left {
            width: 50%;
            text-align: center;
        }

        .top .right {
            width: 50%;
            text-align: center;
            font-family: 'Cairo', sans-serif;
        }

        .bottom {
            background-color: white;
            color: black;
            display: flex;
            justify-content: center;
            align-items: center;
            //padding: 10px;
            font-family: 'Cairo', sans-serif;
            font-size: 20px;
            font-weight: bold;
            position: relative;
        }

        .separator {
            width: 3px;
            height: 32px;
            //margin-top: 6px;
            background-color: black;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .numbers,
        .letters {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50%;
            flex-direction: column;
        }

        .countdown-container {
            display: flex;
            justify-content: center;
            //gap: 30px;
        }

        .countdown-box {
            width: 70px;
            height: 90px;
            border-radius: 50%;
            //border: 6px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            position: relative;
        }

        .countdown-box span {
            font-size: 25px;
            //font-weight: bold;
            color: black;
        }

        .countdown-box:after {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            //border: 6px solid transparent;
            //border-top-color: #ffcc00;
            //animation: spin 5s linear infinite;
        }

        //@keyframes spin {
        //    0% {
        //       transform: rotate(0deg);
        //    }

        //    100% {
        //       transform: rotate(360deg);
        //    }
        //}
    </style>
</head>

<body>
    <div class="form-container">
        <div class="title-job">
            <h3>معلومات الطلب</h3>
        </div>
        <hr>
        <form action="{{ route('save.job.order',['id' => $id]) }}" method="POST">
            @csrf

            <input type="hidden" name="job_order_id" id="job_order_id" value="<?= $id ?>">

            <div class="info-container">
                <div class="info">
                    <?php $productName = DB::table('products')
                        ->select('name')
                        ->where('id', $job_order->first()->product_id)
                        ->first();
                    $info = DB::table('repair_job_sheets')
                        ->join('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
                        ->join('contact_device', 'contact_device.id', '=', 'bookings.device_id')
                        ->join('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
                        ->join('types_of_services', 'types_of_services.id', '=', 'bookings.service_type_id')
                        ->join('contacts', 'contacts.id', '=', 'bookings.contact_id')
                        ->join('categories', 'categories.id', '=', 'contact_device.device_id')
                        ->where('repair_job_sheets.id', $job_order->first()->job_order_id)
                        ->select('contacts.name AS name', 'contact_device.color AS color', 'contact_device.plate_number AS number', 'repair_device_models.name AS model', 'types_of_services.name AS service', 'categories.name AS catname', 'repair_job_sheets.status_id AS status')
                        ->first();
                    ?>
                    <p>اسم العميل : <?= $info->name ?> </p>
                    <p>ماركة السيارة : <?= $info->catname ?></p>
                    <p>موديل السيارة : <?= $info->model ?></p>
                    <p>لون السيارة : <?= $info->color ?></p>
                    <p>نوع الخدمة : <?= $info->service ?></p>
                </div>

                <div class="plate">
                    <?php preg_match('/([\p{L}\s]+)(\d+)/u',  $info->number, $parts); ?>
                    <div class="top">
                        <div class="left">EGYPT</div>
                        <div class="right">مصر</div>
                    </div>
                    <div class="bottom">
                        <div class="numbers" style="font-family: 'Cairo', sans-serif;">
                            <div><?= $parts[1] ?></div>
                        </div>
                        <div class="separator"></div>
                        <div class="letters" style="font-family: 'Cairo', sans-serif;">
                            <div><?= $parts[2] ?></div>
                        </div>
                    </div>
                </div>
            </div>


            <hr>


            @livewire('progress-tracker', ['jobOrderId' => $job_order->first()->job_order_id])



            {{-- <?php
            //$statuses = DB::table('repair_statuses')->select('name', 'id')->where('status_category', 'status')->get();
            //$statusid = DB::table('repair_job_sheets')
              //  ->select('status_id')
                //->where('id', $job_order->first()->job_order_id)
                //->first();
            //dd($status);
            ?>

            <div class="progress-container">
                <?php //foreach($statuses as $status) {  ?>
                <div class="<?= //$statusid->status_id == $status->id ? 'step completed' : 'step' ?>"
                    id="pro<?= //$status->id ?> ">
                    <span class="icon"><i
                            class="<?= //$statusid->status_id == $status->id ? 'fa-solid fa-check' : 'fas fa-hourglass-half' ?>"></i></span>
                    <p> <?= //$status->name ?></p>
                    </p>
                </div>




                <?php //} ?> --}}

            {{-- </div> --}}









            <hr>
            <div class="title-job">
                <h3>طلب موافقه العميل علي قطع الغيار</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>اسم القطعة</th>
                        <th>السعر</th>
                        <th>الكمية</th>
                        <th>اختيار</th>
                    </tr>
                </thead>
                <tbody id="partsTable">

                    <?php foreach($job_order as $order){ 
                        $productName = DB::table('products')->select('name')->where('id', $order->product_id)->first();
                        //echo $order->product_id;
                        ?>
                    <tr>
                        <td> <?= $productName->name ?> </td>
                        <td class = "price"><?= $order->price ?></td>
                        <td class = "quantity"><?= $order->quantity ?></td>
                        <td><input type="checkbox" class="part-checkbox" name="product_ids[<?= $order->id ?>]"
                                id="approval_status" value="1" <?= $order->client_approval == 1 ? 'checked disabled' : '' ?>
                                onchange="updateHiddenInput()"></td>
                    </tr>

                    <?php } ?>

                </tbody>
            </table>

            <div class="title-job">
                <p id = "okay"><?= $okay ?></p>
            </div>

            <button type="sumitb">الموافقة</button>
            <hr>

            <div class="total">
                العدد الكلي: <span id="totalCount">0</span>
                <div>السعر الكلي: <span id="totalPrice">0</span> EGP</div>
            </div>

            <hr>


            <div class="title-job">
                <h3 id = "finish">وقت انتهاء العمل</h3>
            </div>
            <div class="countdown-container">
                <div class="countdown-box">
                    <span id="days">00</span>
                    يوم
                </div>
                <div class="countdown-box">
                    <span id="hours">00</span>
                    ساعة
                </div>
                <div class="countdown-box">
                    <span id="minutes">00</span>
                    دقيقة
                </div>
                <div class="countdown-box">
                    <span id="seconds">00</span>
                    ثانية
                </div>
            </div>

        </form>
    </div>
    @livewireScripts

    <script>
        

        document.addEventListener('statusUpdated', function() {
            Livewire.emit('statusUpdated');
        });
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.part-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateTotal);
            });

            function updateTotal() {
                let totalCount = 0;
                let totalPrice = 0;

                document.querySelectorAll('.part-checkbox:checked').forEach(checkbox => {
                    let row = checkbox.closest('tr');
                    let price = parseFloat(row.querySelector('.price').textContent);
                    let quantity = parseInt(row.querySelector('.quantity').textContent);

                    totalCount += quantity;
                    totalPrice += (price * quantity);
                });

                document.getElementById('totalCount').textContent = totalCount;
                document.getElementById('totalPrice').textContent = totalPrice;
            }

            updateTotal();
        });
        

        function updateHiddenInput() {
            let checkboxes = document.querySelectorAll('.part-checkbox');
            let isChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
            document.getElementById('approval_status').value = isChecked ? '1' : '0';
        }


        let days = parseInt("{{ $days }}");
        let hours = parseInt("{{ $hours }}");
        let minutes = parseInt("{{ $minutes }}");
        let seconds = parseInt("{{ $seconds }}");

        function updateCountdown() {
            if (days === 0 && hours === 0 && minutes === 0 && seconds === 0) {
                document.getElementById("finish").innerText = "تم انتهاء العمل";
                clearInterval(countdownInterval);
                return;
            }

            document.getElementById("days").innerText = days.toString().padStart(2, '0');
            document.getElementById("hours").innerText = hours.toString().padStart(2, '0');
            document.getElementById("minutes").innerText = minutes.toString().padStart(2, '0');
            document.getElementById("seconds").innerText = seconds.toString().padStart(2, '0');

            if (seconds > 0) {
                seconds--;
            } else {
                if (minutes > 0) {
                    minutes--;
                    seconds = 59;
                } else {
                    if (hours > 0) {
                        hours--;
                        minutes = 59;
                        seconds = 59;
                    } else {
                        if (days > 0) {
                            days--;
                            hours = 23;
                            minutes = 59;
                            seconds = 59;
                        }
                    }
                }
            }
        }

        let countdownInterval = setInterval(updateCountdown, 1000);

        updateCountdown();
    </script>
</body>

</html>
