@extends('layouts.app')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Firebase Notification Test
            <small>Test push notifications</small>
        </h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Firebase Configuration</h3>
                    </div>
                    <div class="box-body">
                        <div id="firebase-config-status">
                            <div class="text-center">
                                <i class="fa fa-spinner fa-spin"></i> Loading...
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Service Worker Status</h3>
                    </div>
                    <div class="box-body">
                        <div id="sw-status">
                            <div class="text-center">
                                <i class="fa fa-spinner fa-spin"></i> Checking...
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">FCM Token</h3>
                    </div>
                    <div class="box-body">
                        <div id="token-status">
                            <button id="get-token-btn" class="btn btn-primary">
                                <i class="fa fa-key"></i> Get FCM Token
                            </button>
                        </div>
                        <div id="token-display" style="display:none; margin-top:10px;">
                            <div class="alert alert-success">
                                <strong>Token obtained:</strong><br>
                                <code id="token-value" style="word-break:break-all;"></code>
                            </div>
                            <button id="send-token-btn" class="btn btn-success">
                                <i class="fa fa-upload"></i> Send Token to Server
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">Send Test Notification</h3>
                    </div>
                    <div class="box-body">
                        <button id="send-test-btn" class="btn btn-warning btn-block">
                            <i class="fa fa-paper-plane"></i> Send Test Notification
                        </button>
                        <div id="test-notification-status" style="margin-top:10px;"></div>
                    </div>
                </div>

                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">Console Log</h3>
                    </div>
                    <div class="box-body">
                        <div id="console-log" style="background:#f5f5f5; padding:10px; height:300px; overflow-y:auto; font-family:monospace; font-size:12px;">
                            <div style="color:#999;">Waiting for events...</div>
                        </div>
                        <button id="clear-console-btn" class="btn btn-xs btn-default" style="margin-top:10px;">
                            <i class="fa fa-trash"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">Real Booking Test</h3>
                    </div>
                    <div class="box-body">
                        <p>Create a real booking to test the full notification flow:</p>
                        <a href="{{ url('/restaurant/booking/create') }}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Create New Booking
                        </a>
                        <div id="booking-test-status" style="margin-top:10px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.alert-info.notification-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    max-width: 400px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

#console-log div {
    margin: 2px 0;
    padding: 2px 5px;
    border-left: 3px solid #ddd;
}

#console-log .log-info { border-left-color: #3c8dbc; }
#console-log .log-success { border-left-color: #00a65a; background: #e8f5e9; }
#console-log .log-error { border-left-color: #dd4b39; background: #ffebee; }
#console-log .log-warning { border-left-color: #f39c12; background: #fff3e0; }
</style>

@endsection

@push('scripts')
<script>
// Console logging function
function logToConsole(message, type = 'info') {
    const consoleDiv = document.getElementById('console-log');
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = document.createElement('div');
    logEntry.className = 'log-' + type;
    logEntry.innerHTML = `<span style="color:#999;">[${timestamp}]</span> ${message}`;
    consoleDiv.appendChild(logEntry);
    consoleDiv.scrollTop = consoleDiv.scrollHeight;
}

// Clear console
document.getElementById('clear-console-btn').addEventListener('click', function() {
    document.getElementById('console-log').innerHTML = '';
});

// Check Firebase config
async function checkFirebaseConfig() {
    try {
        const response = await fetch('/api/firebase/config');
        const data = await response.json();
        
        let html = '<ul>';
        html += `<li><strong>Enabled:</strong> ${data.enabled ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'}</li>`;
        html += `<li><strong>Project ID:</strong> ${data.project_id}</li>`;
        html += `<li><strong>Has Client Email:</strong> ${data.has_client_email ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'}</li>`;
        html += `<li><strong>Has Private Key:</strong> ${data.has_private_key ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'}</li>`;
        html += `<li><strong>Has VAPID Key:</strong> ${data.has_vapid_key ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'}</li>`;
        html += '</ul>';
        
        document.getElementById('firebase-config-status').innerHTML = html;
        logToConsole('Firebase config checked: ' + (data.enabled ? 'OK' : 'Disabled'), data.enabled ? 'success' : 'error');
    } catch (error) {
        document.getElementById('firebase-config-status').innerHTML = '<div class="alert alert-danger">Error loading config</div>';
        logToConsole('Error checking Firebase config: ' + error.message, 'error');
    }
}

// Check service worker
async function checkServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.getRegistration();
            if (registration) {
                document.getElementById('sw-status').innerHTML = 
                    '<div class="alert alert-success"><i class="fa fa-check"></i> Service worker registered<br><small>Scope: ' + registration.scope + '</small></div>';
                logToConsole('Service worker registered: ' + registration.scope, 'success');
            } else {
                document.getElementById('sw-status').innerHTML = 
                    '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> No service worker registered</div>';
                logToConsole('No service worker registered', 'warning');
            }
        } catch (error) {
            document.getElementById('sw-status').innerHTML = 
                '<div class="alert alert-danger"><i class="fa fa-times"></i> Error: ' + error.message + '</div>';
            logToConsole('Service worker error: ' + error.message, 'error');
        }
    } else {
        document.getElementById('sw-status').innerHTML = 
            '<div class="alert alert-danger"><i class="fa fa-times"></i> Service workers not supported</div>';
        logToConsole('Service workers not supported', 'error');
    }
}

// Get FCM token
document.getElementById('get-token-btn').addEventListener('click', async function() {
    logToConsole('Requesting notification permission...', 'info');
    
    try {
        const permission = await Notification.requestPermission();
        logToConsole('Notification permission: ' + permission, permission === 'granted' ? 'success' : 'warning');
        
        if (permission === 'granted') {
            const messaging = firebase.messaging();
            const vapidKey = window.FIREBASE_VAPID_PUBLIC_KEY || "BKbzaDKLcIvyUUvvLzZoZ02EI0_7sIgEE5ZlwXRpguGYtmHqx5CqblNOyMRYwq26-yE2d-9iIN9nVRIfQ8TZ6Gc";
            
            messaging.usePublicVapidKey(vapidKey);
            
            const token = await messaging.getToken();
            logToConsole('FCM token obtained: ' + token.substring(0, 20) + '...', 'success');
            
            document.getElementById('token-display').style.display = 'block';
            document.getElementById('token-value').textContent = token;
            document.getElementById('get-token-btn').style.display = 'none';
        }
    } catch (error) {
        logToConsole('Error getting FCM token: ' + error.message, 'error');
    }
});

// Send token to server
document.getElementById('send-token-btn').addEventListener('click', async function() {
    const token = document.getElementById('token-value').textContent;
    
    logToConsole('Sending token to server...', 'info');
    
    try {
        const response = await fetch('/api/fcm-token/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ fcm_token: token })
        });
        
        const data = await response.json();
        logToConsole('Token sent to server: ' + (data.success ? 'Success' : 'Failed'), data.success ? 'success' : 'error');
        
        if (data.success) {
            document.getElementById('send-token-btn').textContent = '✓ Token Sent';
            document.getElementById('send-token-btn').disabled = true;
        }
    } catch (error) {
        logToConsole('Error sending token: ' + error.message, 'error');
    }
});

// Send test notification
document.getElementById('send-test-btn').addEventListener('click', async function() {
    logToConsole('Sending test notification...', 'info');
    
    try {
        const response = await fetch('/api/firebase/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        logToConsole('Test notification sent: ' + (data.success ? 'Success' : 'Failed'), data.success ? 'success' : 'error');
        
        document.getElementById('test-notification-status').innerHTML = 
            '<div class="alert ' + (data.success ? 'alert-success' : 'alert-danger') + '">' + data.message + '</div>';
    } catch (error) {
        logToConsole('Error sending test notification: ' + error.message, 'error');
        document.getElementById('test-notification-status').innerHTML = 
            '<div class="alert alert-danger">Error: ' + error.message + '</div>';
    }
});

// Listen for service worker messages
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', function(event) {
        logToConsole('Message from service worker: ' + JSON.stringify(event.data), 'info');
        
        if (event.data && event.data.type === 'NOTIFICATION_RECEIVED') {
            logToConsole('✓ Notification received!', 'success');
            showToastNotification(event.data.payload);
        }
    });
}

// Listen for foreground messages
firebase.messaging().onMessage(function(payload) {
    logToConsole('Foreground message received: ' + payload.notification.title, 'success');
    showToastNotification(payload);
});

// Show toast notification
function showToastNotification(payload) {
    const notification = payload.notification;
    
    const toast = document.createElement('div');
    toast.className = 'alert alert-info alert-dismissible notification-toast';
    toast.innerHTML = `
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <strong>${notification.title}</strong><br>
        ${notification.body}
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
    
    toast.querySelector('.close').addEventListener('click', () => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    checkFirebaseConfig();
    checkServiceWorker();
    logToConsole('Firebase test page loaded', 'info');
});
</script>
@endpush
