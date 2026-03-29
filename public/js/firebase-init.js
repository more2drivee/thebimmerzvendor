 const firebaseConfig = {
    apiKey: "AIzaSyCdGoJva76iD5GsNKHxmFYksbKBpjSI_Jo",
    authDomain: "gmotor-fa1ff.firebaseapp.com",
    projectId: "gmotor-fa1ff",
    storageBucket: "gmotor-fa1ff.firebasestorage.app",
    messagingSenderId: "424907866933",
    appId: "1:424907866933:web:917943a47140fe134dc27c",
    measurementId: "G-Y8E17W02LR"
  };
// Initialize Firebase with error handling for Brave browser
try {
    if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
        console.log('Firebase initialized successfully');
    } else {
        console.log('Firebase already initialized');
    }
} catch (error) {
    console.error('Firebase initialization error:', error);
}

// Function to update navbar notification count
function updateNotificationCount() {
    console.log('Updating notification count...');
    fetch('/get-total-unread', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
    .then(response => {
        console.log('Notification count response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Notification count data:', data);
        
        // Try multiple selectors for the notification badge
        const possibleSelectors = [
            '.notifications-menu .notifications_count',
            '.notifications-menu .label-warning',
            '.notifications-menu .badge',
            '.notifications-menu .label',
            '.navbar-nav .notifications-menu .notifications_count',
            '.navbar-nav .notifications-menu .label-warning',
            '.navbar-nav .notifications-menu .badge',
            '[data-toggle="dropdown"] .notifications_count',
            '[data-toggle="dropdown"] .label-warning',
            '[data-toggle="dropdown"] .badge'
        ];
        
        let badge = null;
        for (const selector of possibleSelectors) {
            badge = document.querySelector(selector);
            if (badge) {
                console.log('Found notification badge with selector:', selector);
                break;
            }
        }
        
        if (badge && data.total_unread !== undefined) {
            badge.textContent = data.total_unread;
            badge.style.display = data.total_unread > 0 ? 'inline' : 'none';
            console.log('Updated notification badge to:', data.total_unread);
        } else {
            console.log('Badge element not found. Available selectors tried:', possibleSelectors);
            console.log('Available notification elements:', 
                document.querySelectorAll('.navbar-nav *[class*="notification"], .navbar-nav *[class*="badge"], .navbar-nav *[class*="label"]'));
        }
    })
    .catch(error => {
        console.error('Error updating notification count:', error);
    });
}

async function initFirebaseMessaging() {
    // Check for browser compatibility
    if (!('serviceWorker' in navigator)) {
        console.log('Service Worker not supported in this browser');
        return;
    }

    if (!('Notification' in window)) {
        console.log('Notifications not supported in this browser');
        return;
    }

    // Check if Firebase is available
    if (typeof firebase === 'undefined' || !firebase.messaging) {
        console.error('Firebase messaging not available');
        return;
    }

    try {
        // Register service worker with explicit scope for Brave browser
        const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js', {
            scope: '/'
        });
        console.log('Service worker registered:', registration.scope);

        // Wait for service worker to be ready
        await navigator.serviceWorker.ready;
        console.log('Service worker is ready');

        // Enhanced message listener for Brave browser
        navigator.serviceWorker.addEventListener('message', function(event) {
            console.log('Message from service worker:', event.data);
            
            if (event.data && event.data.type === 'NOTIFICATION_RECEIVED') {
                console.log('Notification received, updating navbar count');
                // Update notification count when new notification received
                updateNotificationCount();
                
                // Show toast notification if page is visible
                if (document.visibilityState === 'visible') {
                    showToastNotification(event.data.payload);
                }
            }
        });

        // Initialize Firebase messaging with better error handling
        let messaging;
        try {
            messaging = firebase.messaging();
        } catch (messagingError) {
            console.error('Failed to initialize Firebase messaging:', messagingError);
            return;
        }

        // Request permission with better handling for Brave
        let permission;
        try {
            permission = await Notification.requestPermission();
        } catch (permissionError) {
            console.error('Failed to request notification permission:', permissionError);
            return;
        }
        
        console.log('Notification permission:', permission);

        if (permission === 'granted') {
            try {
                const vapidKey = window.FIREBASE_VAPID_PUBLIC_KEY || "BKbzaDKLcIvyUUvvLzZoZ02EI0_7sIgEE5ZlwXRpguGYtmHqx5CqblNOyMRYwq26-yE2d-9iIN9nVRIfQ8TZ6Gc";
                if (!vapidKey) {
                    console.error('Missing FIREBASE_VAPID_PUBLIC_KEY');
                    return;
                }

                messaging.usePublicVapidKey(vapidKey);
                messaging.useServiceWorker(registration);

                const token = await messaging.getToken();
                if (!token) {
                    console.error('Failed to get FCM token');
                    return;
                }
                
                console.log('FCM token obtained:', token.substring(0, 20) + '...');

                // Send token to server with retry logic for Brave
                let retryCount = 0;
                const maxRetries = 3;
                
                while (retryCount < maxRetries) {
                    try {
                        const response = await fetch('/fcm-token/update', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ fcm_token: token })
                        });

                        if (response.ok) {
                            console.log('Token sent to server: success');
                            break;
                        } else {
                            throw new Error(`Server responded with status: ${response.status}`);
                        }
                    } catch (fetchError) {
                        retryCount++;
                        console.error(`Failed to send token to server (attempt ${retryCount}):`, fetchError);
                        
                        if (retryCount < maxRetries) {
                            // Wait before retry
                            await new Promise(resolve => setTimeout(resolve, 1000 * retryCount));
                        } else {
                            console.error('Failed to send token after all retries');
                        }
                    }
                }
            } catch (tokenError) {
                console.error('Failed to get FCM token:', tokenError);
            }
        } else {
            console.log('Notification permission denied');
        }
    } catch (error) {
        console.error('Firebase messaging initialization error:', error.message, error);
    }
}

// Handle foreground messages
firebase.messaging().onMessage((payload) => {
    console.log('Foreground message received:', payload);
    
    const notification = payload.notification;
    
    // Show browser notification if page is not visible
    if (document.visibilityState !== 'visible') {
        new Notification(notification.title, {
            body: notification.body,
            icon: notification.icon || '/img/default.png',
            data: payload.data
        });
    } else {
        // Show toast notification if page is visible
        showToastNotification(payload);
    }
    
    // Update notification count immediately
    console.log('Foreground message: updating notification count');
    updateNotificationCount();
});

// Handle token refresh
firebase.messaging().onTokenRefresh(async () => {
    try {
        const newToken = await firebase.messaging().getToken();
        console.log('Token refreshed:', newToken.substring(0, 20) + '...');
        
        await fetch('/fcm-token/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ fcm_token: newToken })
        });
        
        console.log('Refreshed token sent to server');
    } catch (error) {
        console.error('Token refresh error:', error);
    }
});

// Function to show toast notification
function showToastNotification(payload) {
    const notification = payload.notification;
    
    // Create toast notification element
    const toast = document.createElement('div');
    toast.className = 'alert alert-info alert-dismissible notification-toast';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 400px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    `;
    
    toast.innerHTML = `
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <strong>${notification.title}</strong><br>
        ${notification.body}
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
    
    // Handle close button
    toast.querySelector('.close').addEventListener('click', () => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    });
}

document.addEventListener('DOMContentLoaded', initFirebaseMessaging);
