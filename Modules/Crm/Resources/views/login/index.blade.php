<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page with Background Image</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                             url('https://gaugemagazine.com/wp-content/uploads/2019/12/Auto-Repair-.jpg');
            /* Replace with your image URL */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.9);
            /* Semi-transparent white for readability */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-container input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .login-container button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Welcome</h2>
        <p style="text-align: center;">Please enter your details to login</p>
        <form method="POST" action="{{ route('login') }}" id="login-form">
            {{ csrf_field() }}
            <div class="input-group">
                <label for="username">Mobile</label>
                <input type="text" id="username" name="username" placeholder="Enter your username or email"
                    value="{{ old('username') }}" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="login-btn">Login</button>
            
            <div class="forgot-password" style="text-align: center;">
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>
        </form>
    </div>
</body>

</html>
