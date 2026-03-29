// Firebase Service Worker for background notifications - Enhanced for Brave browser
importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js');

// Initialize Firebase with error handling
try {
    firebase.initializeApp({
 apiKey: "AIzaSyAsirsHNlPr3AKeztkgMWJCkXX5Qqgk85k",
  authDomain: "carservpro-a89ac.firebaseapp.com",
  projectId: "carservpro-a89ac",
  storageBucket: "carservpro-a89ac.firebasestorage.app",
  messagingSenderId: "808421807717",
  appId: "1:808421807717:web:488a39c4c7b7d18c0cd88c",
  measurementId: "G-YKW8X39H81"
    });
    console.log('Service Worker: Firebase initialized successfully');
} catch (error) {
    console.error('Service Worker: Firebase initialization error:', error);
}

const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function(payload) {
    console.log('Background message received:', payload);
    
    const notificationTitle = (payload.notification && payload.notification.title) ? payload.notification.title : 'New Notification';
    const notificationOptions = {
        body: payload.notification && payload.notification.body ? payload.notification.body : '',
        icon: payload.notification && payload.notification.icon ? payload.notification.icon : '/img/default.png',
        badge: '/img/notification-badge.png',
        data: payload.data || {},
        tag: 'booking-notification',
        requireInteraction: true,
        silent: false,
        vibrate: [200, 100, 200],
        actions: [
            {
                action: 'view',
                title: 'View',
                icon: '/img/view-icon.png'
            },
            {
                action: 'dismiss',
                title: 'Dismiss',
                icon: '/img/dismiss-icon.png'
            }
        ]
    };

    // Log analytics event
    if (typeof gtag !== 'undefined') {
        gtag('event', 'notification_received', {
            'notification_title': notificationTitle,
            'notification_type': payload.data && payload.data.type ? payload.data.type : 'unknown'
        });
    }

    // Notify all clients to update navbar - Enhanced for Brave browser
    self.clients.matchAll({ 
        includeUncontrolled: true, 
        type: 'window' 
    }).then(clients => {
        console.log('Notifying clients about new notification, client count:', clients.length);
        clients.forEach(client => {
            try {
                console.log('Sending message to client:', client.url);
                client.postMessage({
                    type: 'NOTIFICATION_RECEIVED',
                    payload: payload,
                    timestamp: Date.now()
                });
            } catch (error) {
                console.error('Error sending message to client:', error);
            }
        });
    }).catch(error => {
        console.error('Error getting clients:', error);
    });

    return self.registration.showNotification(notificationTitle, notificationOptions);
});

self.addEventListener('notificationclick', function(event) {
    console.log('Notification clicked:', event.notification);
    event.notification.close();
    
    // Log analytics event
    if (typeof gtag !== 'undefined') {
        gtag('event', 'notification_clicked', {
            'notification_title': event.notification.title,
            'notification_type': event.notification.data && event.notification.data.type ? event.notification.data.type : 'unknown'
        });
    }
    
    // Handle action buttons
    if (event.action === 'dismiss') {
        return; // Just close the notification
    }
    
    const urlToOpen = event.notification.data?.url || '/restaurant/booking';
    
    event.waitUntil(
        clients.matchAll({ 
            type: 'window', 
            includeUnaffected: true 
        }).then(function(clientList) {
            // Check if there's already a window open with the target URL
            for (let i = 0; i < clientList.length; i++) {
                let client = clientList[i];
                if (client.url.includes(urlToOpen.split('?')[0]) && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // If no matching window is open, check for any window from the same origin
            for (let i = 0; i < clientList.length; i++) {
                let client = clientList[i];
                if (client.url.includes(self.location.origin) && 'navigate' in client) {
                    client.navigate(urlToOpen);
                    return client.focus();
                }
            }
            
            // If no window is open, open a new one
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        }).catch(error => {
            console.error('Error handling notification click:', error);
            // Fallback: try to open new window
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

// Handle messages from main thread - Enhanced for Brave
self.addEventListener('message', function(event) {
    console.log('Service worker received message:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    // Handle client requests
    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({
            type: 'VERSION_RESPONSE',
            version: '1.0.0',
            timestamp: Date.now()
        });
    }
});

// Handle service worker activation
self.addEventListener('activate', function(event) {
    console.log('Service worker activated');
    event.waitUntil(self.clients.claim());
});

// Handle service worker installation
self.addEventListener('install', function(event) {
    console.log('Service worker installed');
    self.skipWaiting();
});