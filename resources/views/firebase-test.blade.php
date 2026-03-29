<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Firebase Notification Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; margin: 5px; }
        .button:hover { background: #005a87; }
        .log { background: #f5f5f5; padding: 10px; border-radius: 3px; margin: 10px 0; font-family: monospace; white-space: pre-wrap; max-height: 300px; overflow-y: auto; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Firebase Notification Test</h1>
        
        <div class="section">
            <h2>Authentication Status</h2>
            <p><strong>User:</strong> {{ auth()->check() ? auth()->user()->username : 'Not authenticated' }}</p>
            <p><strong>Contact ID:</strong> {{ auth()->check() && auth()->user()->contact_id ? auth()->user()->contact_id : 'No contact ID' }}</p>
        </div>

        <div class="section">
            <h2>Firebase Configuration</h2>
            <button class="button" onclick="checkConfig()">Check Firebase Config</button>
            <div id="config-result" class="log"></div>
        </div>

        <div class="section">
            <h2>Service Worker Status</h2>
            <button class="button" onclick="checkServiceWorker()">Check Service Worker</button>
            <div id="sw-result" class="log"></div>
        </div>

        <div class="section">
            <h2>FCM Token</h2>
            <button class="button" onclick="getToken()">Get FCM Token</button>
            <button class="button" onclick="sendTokenToServer()">Send Token to Server</button>
            <div id="token-result" class="log"></div>
        </div>

        <div class="section">
            <h2>Test Notification</h2>
            <button class="button" onclick="sendTestNotification()">Send Test Notification</button>
            <div id="test-result" class="log"></div>
        </div>

        <div class="section">
            <h2>Console Log</h2>
            <div id="console-log" class="log"></div>
        </div>
    </div>

    <!-- Firebase Scripts -->
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js"></script>

    <script>
        const firebaseConfig = {
            apiKey: "AIzaSyCvYpRWvq6gF19inPp8UlRmqKu41f86izI",
            authDomain: "flash-asset-370312.firebaseapp.com",
            projectId: "flash-asset-370312",
            storageBucket: "flash-asset-370312.firebasestorage.app",
            messagingSenderId: "439874870346",
            appId: "1:439874870346:web:37dda94d5b73ce2f3ddab3",
            measurementId: "G-Y22S6E753G"
        };

        firebase.initializeApp(firebaseConfig);
        let messaging;
        let currentToken = null;

        // Override console.log to show in page
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;

        function logToPage(message, type = 'info') {
            const logDiv = document.getElementById('console-log');
            const timestamp = new Date().toLocaleTimeString();
            logDiv.innerHTML += `<span class="${type}">[${timestamp}] ${message}</span>\n`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        console.log = function(...args) {
            originalLog.apply(console, args);
            logToPage(args.join(' '), 'info');
        };

        console.error = function(...args) {
            originalError.apply(console, args);
            logToPage(args.join(' '), 'error');
        };

        console.warn = function(...args) {
            originalWarn.apply(console, args);
            logToPage(args.join(' '), 'error');
        };

        async function checkConfig() {
            try {
                const response = await fetch('/api/firebase/config');
                const config = await response.json();
                document.getElementById('config-result').innerHTML = JSON.stringify(config, null, 2);
                console.log('Firebase config:', config);
            } catch (error) {
                console.error('Error checking config:', error);
                document.getElementById('config-result').innerHTML = 'Error: ' + error.message;
            }
        }

        async function checkServiceWorker() {
            const resultDiv = document.getElementById('sw-result');
            
            if (!('serviceWorker' in navigator)) {
                resultDiv.innerHTML = 'Service Worker not supported';
                console.error('Service Worker not supported');
                return;
            }

            try {
                const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
                console.log('Service worker registered:', registration.scope);
                
                await navigator.serviceWorker.ready;
                console.log('Service worker is ready');
                
                messaging = firebase.messaging();
                messaging.useServiceWorker(registration);
                
                resultDiv.innerHTML = `Service Worker registered successfully\nScope: ${registration.scope}`;
            } catch (error) {
                console.error('Service worker registration failed:', error);
                resultDiv.innerHTML = 'Error: ' + error.message;
            }
        }

        async function getToken() {
            const resultDiv = document.getElementById('token-result');
            
            if (!messaging) {
                await checkServiceWorker();
            }

            try {
                const permission = await Notification.requestPermission();
                console.log('Notification permission:', permission);
                
                if (permission !== 'granted') {
                    resultDiv.innerHTML = 'Notification permission denied';
                    return;
                }

                const vapidKey = "BKbzaDKLcIvyUUvvLzZoZ02EI0_7sIgEE5ZlwXRpguGYtmHqx5CqblNOyMRYwq26-yE2d-9iIN9nVRIfQ8TZ6Gc";
                messaging.usePublicVapidKey(vapidKey);

                currentToken = await messaging.getToken();
                console.log('FCM token obtained:', currentToken.substring(0, 20) + '...');
                
                resultDiv.innerHTML = `Token obtained successfully:\n${currentToken.substring(0, 50)}...`;
            } catch (error) {
                console.error('Failed to get FCM token:', error);
                resultDiv.innerHTML = 'Error: ' + error.message;
            }
        }

        async function sendTokenToServer() {
            const resultDiv = document.getElementById('token-result');
            
            if (!currentToken) {
                resultDiv.innerHTML = 'No token available. Get token first.';
                return;
            }

            try {
                const response = await fetch('/fcm-token/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ fcm_token: currentToken })
                });

                const result = await response.json();
                console.log('Token sent to server:', response.ok ? 'success' : 'failed');
                console.log('Server response:', result);
                
                resultDiv.innerHTML += `\n\nServer response (${response.status}): ${JSON.stringify(result, null, 2)}`;
            } catch (error) {
                console.error('Error sending token to server:', error);
                resultDiv.innerHTML += '\n\nError: ' + error.message;
            }
        }

        async function sendTestNotification() {
            const resultDiv = document.getElementById('test-result');
            
            try {
                const response = await fetch('/api/firebase/test', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();
                console.log('Test notification result:', result);
                
                resultDiv.innerHTML = JSON.stringify(result, null, 2);
            } catch (error) {
                console.error('Error sending test notification:', error);
                resultDiv.innerHTML = 'Error: ' + error.message;
            }
        }

        // Handle foreground messages
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Firebase test page loaded');
            
            // Set up message handler when messaging is available
            setTimeout(() => {
                if (firebase.messaging.isSupported()) {
                    const messaging = firebase.messaging();
                    
                    messaging.onMessage((payload) => {
                        console.log('Foreground message received:', payload);
                        logToPage('Foreground message received: ' + JSON.stringify(payload), 'success');
                        
                        // Show notification
                        if (Notification.permission === 'granted') {
                            new Notification(payload.notification.title, {
                                body: payload.notification.body,
                                icon: payload.notification.icon || '/img/default.png'
                            });
                        }
                    });
                }
            }, 1000);
        });
    </script>
</body>
</html>