<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Car Service Pro - Login</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family: 'Arial', sans-serif; }

.HomePageContainer{
    width: 100vw;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;

    background-color: {{ $settings['login_bg_color'] ?? '#fff' }};
    
}

   .left-half {
    flex: 1;
    position: relative;
    height: 95vh;
    background-color: #d3411d;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    min-height: 400px;
    border-radius: 20px;
}
    .img-container {
        position: absolute;
        top: 0; left:0;
        width: 100%; height: 100%;
        overflow: hidden;
    }

    .img-container img {
        width: 100%; height: 100%;
        object-fit: cover;
        filter: brightness(0.6);
        transition: transform 10s ease;
    }

    .overlay-text {
        position: relative;
        z-index: 10;
        text-align: center;
    }

    .logo {
        width: 250px;
        max-width: 80%;
        filter: drop-shadow(2px 4px 10px rgba(0,0,0,0.5));
        transition: transform 0.3s ease;
    }

    .logo:hover {
        transform: scale(1.05);
    }


    .right-half {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        
    }

    .login-container {
        width: 90%;
        max-width: 420px;
        animation: slideIn 0.8s ease-out;
    }

    @keyframes slideIn {
        from { opacity:0; transform: translateX(30px); }
        to { opacity:1; transform: translateX(0); }
    }

    .login-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .login-header h2 {
        color: #1a3b5d;
        font-size: 1.8rem;
        font-weight: 700;
    }

    .login-header p {
        color: #6c757d;
        font-size: 1rem;
    }

    .form-container {
        background-color: #ffffff;
        padding: 2.5rem;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .form-container:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    }

    .input-group {
        margin-bottom: 1.8rem;
        position: relative;
    }

    .input-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #495057;
        font-size: 0.95rem;
    }

    .input-group input {
        width: 100%;
        padding: 0.85rem 1rem;
        border: 1.5px solid #e0e0e0;
        border-radius: 10px;
        font-size: 1rem;
        background-color: #f9f9f9;
        transition: all 0.3s ease;
    }

    .input-group input:focus {
        outline: none;
        border-color: {{ $settings['login_button_color'] ?? '#1a3b5d' }};
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(26,59,93,0.15);
    }

    .login-btn {
        width: 100%;
        padding: 0.95rem;
        background-color: {{ $settings['login_button_color'] ?? '#1a3b5d' }};
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .login-btn:hover {
        background-color: darken({{ $settings['login_button_color'] ?? '#1a3b5d' }}, 10%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    }

    .alert {
        padding: 0.8rem 1rem;
        margin-bottom: 1.5rem;
        border-radius: 8px;
        font-size: 0.9rem;
        display: none;
    }

    .alert-danger { display: block; background-color: #f8d7da; color: #721c24; border:1px solid #f5c6cb; }


    @media (max-width: 992px) { .login-container { width: 95%; } }
    @media (max-width: 768px) {
        .HomePageContainer { flex-direction: column; min-height: 100vh; }
        .left-half { height: 25vh; min-height: 180px; width: 100%; }
        .right-half { padding: 0; width: 100%;  }
    }
</style>
</head>
<body>

<div class="HomePageContainer">

   
   <div class="left-half">
 @php
    $selectedTheme = $settings['login_canvas'] ?? null;
@endphp

@if(!empty($settings['login_image']))
    <div class="img-container">
        <img src="{{ asset('storage/'.$settings['login_image']) }}" alt="Background">
    </div>

@else

    @php
        $selectedTheme = $selectedTheme ?: 'SoftAuroraLines';
    @endphp

    <div class="darkveil-wrapper"
         style="position:absolute;top:0;left:0;right:0;bottom:0;width:100%;height:100%;z-index:1;">

        @if($selectedTheme === 'FloatingGradientWaves')
            @include('Animations.FloatingGradientWaves')

        @elseif($selectedTheme === 'SoftParticleGlow')
            @include('Animations.SoftParticleGlow')

        @elseif($selectedTheme === 'SoftAuroraLines')
            @include('Animations.SoftAuroraLines')
        @endif

    </div>

@endif

    <div class="overlay-text" style="z-index: 20; position: relative;">
        @if(!empty($settings['login_logo']))
            <img src="{{ asset('storage/' . $settings['login_logo']) }}" alt="Logo" class="logo">
        @else
            <img src="{{ asset('/uploads/images/white_logo.png') }}" alt="Logo" class="logo">
        @endif

    </div>
    <div class="careserv-logo" style="position:absolute;bottom:15px;right:20px;z-index: 20;">
@if(!empty($settings['carserv_logo']))
<img src="{{ asset('storage/'.$settings['carserv_logo']) }}" alt="Car Service Pro Logo" style="width:120px; opacity:0.8; object-fit: contain;">
@endif
</div>
</div>

    <div class="right-half">
        <div class="login-container">
            <div class="login-header">
                <h2>{{ $settings['login_title'] ?? 'Welcome Back' }}</h2>
                <p>{{ $settings['login_subtitle'] ?? 'Please enter your credentials' }}</p>
            </div>

            <div class="form-container">
                @if($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin:0; padding-left:1rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="input-group">
                        <label for="username">Username or Email</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username or email" value="{{ old('username') }}" required autofocus>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="login-btn">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
