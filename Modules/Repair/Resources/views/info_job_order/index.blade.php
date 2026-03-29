<!doctype html>
<html lang="en">

<head>
    <title>Info Job Order</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {

            font-family: Arial, sans-serif;
        }

        .form-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }

        h1 {
            font-size: 28px;
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
        }

        input,
        select {
            width: 100%;
            padding: 8px 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
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


        .form-check {
            margin-bottom: 10px;
        }

        .star-rating .fa-star {
            font-size: 20px;
            color: #ddd;
            cursor: pointer;
            margin-right: 5px;
        }

        .star-rating .fa-star.checked {
            color: #f39c12;
        }

        .choice {
            font-size: 17px;
            margin-top: 0px;
            margin-left: 10px;

        }

        p {
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <form action="{{ route('test.check.info.job.order', ['id' => $id]) }}" method="POST">
            @csrf

            <label for="phone">Enter Last 4 Digit from your phone</label>
            <input type="text" id="phone" name="phone" placeholder="Enter your Last 4 Digit" required>


            <button type="submit">Submit</button>

        </form>
    </div>

</body>

</html>
