<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You!</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
            text-align: center;
        }

        .thank-you-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
        }

        .thank-you-container i {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 10px;
        }

        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        p {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s ease-in-out;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <div class="thank-you-container">
        <i class="fa fa-check-circle"></i>
        <h1>Thank You!</h1>
        <p>Your Reservation has been received successfully.</p>
    </div>

</body>
</html>
