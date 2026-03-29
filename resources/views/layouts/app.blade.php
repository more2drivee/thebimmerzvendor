@inject('request', 'Illuminate\Http\Request')

@if (
    $request->segment(1) == 'pos' &&
        ($request->segment(2) == 'create' || $request->segment(3) == 'edit' || $request->segment(2) == 'payment'))
    @php
        $pos_layout = true;
    @endphp
@else
    @php
        $pos_layout = false;
    @endphp
@endif

@php
    $whitelist = ['127.0.0.1', '::1'];
@endphp

<!DOCTYPE html>
<html class="tw-bg-white tw-scroll-smooth" lang="{{ app()->getLocale() }}"
    dir="{{ in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) ? 'rtl' : 'ltr' }}">
<head>
    <!-- Tell the browser to be responsive to screen width -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ Session::get('business.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet">


    @include('layouts.partials.css')


    @include('layouts.partials.extracss')

    @yield('css')

</head>
<style>
    html[dir="rtl"] .o_web_client > .o_action_manager {
    direction: rtl;
}

html[dir="ltr"] .o_web_client > .o_action_manager {
    direction: ltr;
}
    .responsive-img {
        width: 100%;
        max-width: 150px; /* You can adjust this max-width based on your preference */
        height: auto;
    }


    .content_icons {
        /* Default font settings */
        font-family: var(--body-font-family);
        font-size: var(--body-font-size);
        font-weight: var(--body-font-weight);
        line-height: var(--body-line-height);
        color: var(--body-color);
        text-align: var(--body-text-align);
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
    }

    /* Apply Cairo font to Arabic text specifically */
    .content_icons:lang(ar) {
        font-family: 'Cairo', sans-serif;
    }


    .default-image {
        opacity: 1 !important;
        transition: opacity 0.3s ease !important;
        position:absolute !important;
    }

    .hover-image {
        opacity: 0 !important;
        transition: opacity 0.3s ease !important;
        position:absolute !important;
    }

    a:hover .default-image {
        opacity: 0 !important;
    }

    a:hover .hover-image {
        opacity: 1 !important;
    }


    .data_handel_view{

        width: auto;

    }




    @media (min-width: 900px) {
        .custom-class {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3rem; /* 48px */
        }
        /*.data_handel_view{*/
        /*    width: 100%;*/
        /*}*/
    }
    /* Loading spinner overlay */
    .overlay {
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        position: fixed;
        top: 0;
        left: 0;
        display: none; /* Hidden by default */
        z-index: 9999;
        justify-content: center;
        align-items: center;
        display: flex;
    }

    .spinner {
        border: 4px solid rgba(255, 255, 255, 0.3); /* Light color border */
        border-top: 4px solid #fff; /* White color on top */
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    @media print {
        #scrollable-container {
            overflow: visible !important;
            height: auto !important;
        }
    }
    @media print {
        #scrollable-container {
            overflow: visible !important;
            height: auto !important;
        }
    }


    .small-view-side-active {
        display: grid !important;
        z-index: 1000;
        position: absolute;
    }
    .overlay {
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.8);
        position: fixed;
        top: 0;
        left: 0;
        display: none;
        z-index: 20;
    }

    .tw-dw-btn.tw-dw-btn-xs.tw-dw-btn-outline {
        width: max-content;
        margin: 2px;
    }

    #scrollable-container{
        position:relative;
    }


    .o_app_icon {
        width: 70px;
        aspect-ratio: 1;
        padding: 10px;
        background-color: var(--AppSwitcherIcon-background, white);
        object-fit: cover;
        transform-origin: center bottom;
        transition: box-shadow ease-in 0.2s, transform ease-in 0.2s, background-color ease-in 0.2s;
        box-shadow: var(--AppSwitcherIcon-inset-shadow, inset 0 0 0 1px rgba(0, 0, 0, 0.2)),
        0 1px 1px rgba(0, 0, 0, 0.02),
        0 2px 2px rgba(0, 0, 0, 0.02),
        0 4px 4px rgba(0, 0, 0, 0.02),
        0 8px 8px rgba(0, 0, 0, 0.02),
        0 16px 16px rgba(0, 0, 0, 0.02);
    }
    .o_app_icon:hover {
        background-color: var(--AppSwitcherIcon-hover-background, #f0f0f0);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1),
        0 8px 12px rgba(0, 0, 0, 0.08),
        0 16px 24px rgba(0, 0, 0, 0.06);
        transform: translateY(-10px) scale(1.1);
        cursor: pointer;
    }
    .rounded-3 {
        border-radius: var(--border-radius-lg) !important;
    }

    img {
        overflow-clip-margin: content-box;
        overflow: clip;
    }
    img, svg {
        vertical-align: middle;
    }
    *, *::before, *::after {
        box-sizing: border-box;
    }
    a {
        color: rgba(var(--link-color-rgb), var(--link-opacity, 1));
        text-decoration: none;
    }
    .o_caption {
        color: var(--homeMenuCaption-color, #374151);
        text-shadow: none;
    }
    .container, .o_container_small, .container-fluid, .container-xxl, .container-xl, .container-lg, .container-md, .container-sm {
        --gutter-x: 32px;
        --gutter-y: 0;
        width: 100%;
        padding-right: calc(var(--gutter-x)* .5);
        padding-left: calc(var(--gutter-x)* .5);
        margin-right: auto;
        margin-left: auto;
    }
    @media (min-width: 768px) {
        .o_home_menu .container, .o_home_menu .o_container_small {
            max-width: 850px !important;
        }
    }
    @media (min-width: 1400px) {
        .container-xxl, .container-xl, .container-lg, .container-md, .container-sm, .container, .o_container_small {
            max-width: 1320px;
        }
    }
    @media (min-width: 1200px) {
        .container-xl, .container-lg, .container-md, .container-sm, .container, .o_container_small {
            max-width: 1140px;
        }
    }
    @media (min-width: 992px) {
        .container-lg, .container-md, .container-sm, .container, .o_container_small {
            max-width: 960px;
        }
    }
    @media (min-width: 768px) {
        .container-md, .container-sm, .container, .o_container_small {
            max-width: 720px;
        }
    }
    @media (min-width: 576px) {
        .container-sm, .container, .o_container_small {
            max-width: 540px;
        }
    }
    html .o_web_client > .o_action_manager {
       
        -webkit-box-flex: 1;
        -webkit-flex: 1 1 auto;
        flex: 1 1 auto;
        height: 100%;
        overflow: hidden;
    }
    html[dir="rtl"] .o_web_client > .o_action_manager {
    direction: rtl;
}

html[dir="ltr"] .o_web_client > .o_action_manager {
    direction: ltr;
}
    .o_home_menu {
        font-size: 0.875rem;
    }
    html .o_web_client {
        height: 100%;
        display: -webkit-box;
        display: -webkit-flex;
        display: flex
    ;
        -webkit-flex-flow: column nowrap;
        flex-flow: column nowrap;
        overflow: hidden;
    }
    .o_web_client {
        direction: ltr;
        position: relative;
        /* background-color: #F9FAFB; */
        color-scheme: bright;
    }


    .header_top{
        padding-top: 7%;
    }
</style>

<body
    class=" content_icons tw-font-sans tw-antialiased tw-text-gray-900  @if ($pos_layout) hold-transition lockscreen @else hold-transition skin-@if (!empty(session('business.theme_color'))){{ session('business.theme_color') }}@else{{ 'blue-light' }} @endif sidebar-mini @endif" >
    <div class="tw-flex">
        <script type="text/javascript">
            if (localStorage.getItem("upos_sidebar_collapse") == 'true') {
                var body = document.getElementsByTagName("body")[0];
                body.className += " sidebar-collapse";
            }

        </script>
        @if (!$pos_layout)
{{--            @include('layouts.partials.sidebar')--}}


        @endif

        @if (in_array($_SERVER['REMOTE_ADDR'], $whitelist))
            <input type="hidden" id="__is_localhost" value="true">
        @endif

        <!-- Add currency related field-->
        <input type="hidden" id="__code" value="{{ session('currency')['code'] }}">
        <input type="hidden" id="__symbol" value="{{ session('currency')['symbol'] }}">
        <input type="hidden" id="__thousand" value="{{ session('currency')['thousand_separator'] }}">
        <input type="hidden" id="__decimal" value="{{ session('currency')['decimal_separator'] }}">
        <input type="hidden" id="__symbol_placement" value="{{ session('business.currency_symbol_placement') }}">
        <input type="hidden" id="__precision" value="{{ session('business.currency_precision', 2) }}">
        <input type="hidden" id="__quantity_precision" value="{{ session('business.quantity_precision', 2) }}">
        
        <!-- End of currency related field-->
        @can('view_export_buttons')
            <input type="hidden" id="view_export_buttons">
        @endcan
        @if (isMobile())
            <input type="hidden" id="__is_mobile">
        @endif
        @if (session('status'))
            <input type="hidden" id="status_span" data-status="{{ session('status.success') }}"
                data-msg="{{ session('status.msg') }}">
        @endif
        <main class="tw-flex tw-flex-col tw-flex-1 tw-h-full tw-min-w-0">

            @if (!$pos_layout)
                @include('layouts.partials.header')
            @else
                @include('layouts.partials.header-pos')
            @endif
            <!-- empty div for vuejs -->
            <div id="app">
                @yield('vue')
            </div>
            <div class=" tw-flex-1 tw-h-screen" id="scrollable-container">
                @inject('request', 'Illuminate\Http\Request')

                @yield('content')
                @if (!$pos_layout)

{{--                    @include('layouts.partials.footer')--}}
                @else
                    @include('layouts.partials.footer_pos')
                @endif
            </div>
            <div class='scrolltop no-print'>
                <div class='scroll icon'><i class="fas fa-angle-up"></i></div>
            </div>

            @if (config('constants.iraqi_selling_price_adjustment'))
                <input type="hidden" id="iraqi_selling_price_adjustment">
            @endif

            <!-- This will be printed -->
            <section class="invoice print_section" id="receipt_section">
            </section>
        </main>

        @include('home.todays_profit_modal')
        <!-- /.content-wrapper -->



        <audio id="success-audio">
            <source src="{{ asset('/audio/success.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/success.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>
        <audio id="error-audio">
            <source src="{{ asset('/audio/error.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/error.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>
        <audio id="warning-audio">
            <source src="{{ asset('/audio/warning.ogg?v=' . $asset_v) }}" type="audio/ogg">
            <source src="{{ asset('/audio/warning.mp3?v=' . $asset_v) }}" type="audio/mpeg">
        </audio>

        @if (!empty($__additional_html))
            {!! $__additional_html !!}
        @endif

        @include('layouts.partials.javascripts')

        <!-- Firebase Scripts -->
        <!-- <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
        <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js"></script>
        <script>
            window.FIREBASE_VAPID_PUBLIC_KEY = "{{ config('services.firebase.vapid_public_key') }}";
        </script>
        <script src="{{ asset('js/firebase-init.js') }}"></script> -->

        <div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

        @if (!empty($__additional_views) && is_array($__additional_views))
            @foreach ($__additional_views as $additional_view)
                @includeIf($additional_view)
            @endforeach
        @endif
        <div>

            <div class="overlay tw-hidden"></div>


</body>
<script>
    $(document).ready(function() {
        // When the button is clicked, show the modal and overlay
        $('#triggerFormButton').on('click', function() {
            $('#overlayContainer').fadeIn();
            $('#formModal').fadeIn();
        });

        // Close the modal when the overlay or cancel button is clicked
        $('#overlayContainer, #closeModalButton').on('click', function() {
            $('#overlayContainer').fadeOut();
            $('#formModal').fadeOut();
        });

        // Optional: Prevent closing the modal when clicking inside the modal
        $('#formModal').on('click', function(event) {
            event.stopPropagation();
        });
    });

</script>
<script type="module">
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.8.0/firebase-app.js";
import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/12.8.0/firebase-messaging.js";

const firebaseConfig = {
  apiKey: "{{ session('notification_settings.firebase_api_key') }}",
  authDomain: "{{ session('notification_settings.firebase_auth_domain') }}",
  projectId: "{{ session('notification_settings.firebase_project_id') }}",
  storageBucket: "{{ session('notification_settings.firebase_storage_bucket') }}",
  messagingSenderId: "{{ session('notification_settings.firebase_messaging_sender_id') }}",
  appId: "{{ session('notification_settings.firebase_app_id') }}",
  measurementId: "{{ session('notification_settings.firebase_measurement_id') }}"
};

@if(session('notification_settings.enabled') == 1)

const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/firebase-messaging-sw.js')
        .then((registration) => {
            console.log('Service Worker registered:', registration);

            Notification.requestPermission().then((permission) => {

                if (permission !== "granted") {
                    console.warn("Notification permission denied.");
                    return;
                }

                console.log("Notification permission granted.");

                getToken(messaging, {
                    vapidKey: "{{ session('notification_settings.firebase_vapid_key') }}",
                    serviceWorkerRegistration: registration
                })
                .then((currentToken) => {

                    if (!currentToken) {
                        console.warn("No FCM token received.");
                        return;
                    }

                    console.log("FCM Token:", currentToken);

                    fetch("{{ url('/fcm-token/update') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            fcm_token: currentToken,
                            device_info: navigator.userAgent
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        console.log("FCM token response:", data);
                    })
                    .catch(err => console.error("Token save error:", err));

                })
                .catch((err) => {
                    console.error("Error retrieving FCM token:", err);
                });

            });

        })
        .catch((err) => {
            console.error('Service Worker registration failed:', err);
        });
}

onMessage(messaging, (payload) => {
    console.log('Message received in foreground:', payload);

    if (payload.notification) {
        new Notification(payload.notification.title, {
            body: payload.notification.body,
            icon: payload.notification.icon ?? '/favicon.ico'
        });
    }
});

@endif
</script>
</html>
