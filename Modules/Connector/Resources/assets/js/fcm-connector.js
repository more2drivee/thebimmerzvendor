/**
 * FCM Token Management for Connector API
 * This file handles Firebase Cloud Messaging token registration for mobile apps and web clients
 * using the Connector API endpoints.
 */

class FcmConnectorManager {
    constructor(apiBaseUrl, authToken) {
        this.apiBaseUrl = apiBaseUrl.replace(/\/$/, ''); // Remove trailing slash
        this.authToken = authToken;
        this.fcmTokenEndpoint = `${this.apiBaseUrl}/connector/api/fcm-tokens`;
    }

    /**
     * Register FCM token with the server
     * @param {string} fcmToken - The FCM token from Firebase
     * @param {string} deviceInfo - Optional device information
     * @returns {Promise<Object>} - API response
     */
    async registerToken(fcmToken, deviceInfo = null) {
        try {
            console.log('Registering FCM token with Connector API...');
            
            const response = await fetch(this.fcmTokenEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    fcm_token: fcmToken,
                    device_info: deviceInfo
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                console.log('FCM token registered successfully:', data);
                return { success: true, data: data.data };
            } else {
                console.error('Failed to register FCM token:', data);
                return { success: false, error: data.message || 'Unknown error' };
            }
        } catch (error) {
            console.error('Error registering FCM token:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Get user's FCM tokens
     * @returns {Promise<Object>} - API response with tokens
     */
    async getTokens() {
        try {
            const response = await fetch(this.fcmTokenEndpoint, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${this.authToken}`,
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                return { success: true, data: data.data };
            } else {
                return { success: false, error: data.message || 'Unknown error' };
            }
        } catch (error) {
            console.error('Error getting FCM tokens:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Delete FCM token
     * @param {number} tokenId - The token ID to delete
     * @returns {Promise<Object>} - API response
     */
    async deleteToken(tokenId) {
        try {
            const response = await fetch(`${this.fcmTokenEndpoint}/${tokenId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${this.authToken}`,
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                console.log('FCM token deleted successfully');
                return { success: true, data: data.data };
            } else {
                return { success: false, error: data.message || 'Unknown error' };
            }
        } catch (error) {
            console.error('Error deleting FCM token:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Update FCM token status (activate/deactivate)
     * @param {number} tokenId - The token ID to update
     * @param {boolean} isActive - Whether the token should be active
     * @returns {Promise<Object>} - API response
     */
    async updateTokenStatus(tokenId, isActive) {
        try {
            const response = await fetch(`${this.fcmTokenEndpoint}/${tokenId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.authToken}`,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    is_active: isActive
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                console.log('FCM token status updated successfully');
                return { success: true, data: data.data };
            } else {
                return { success: false, error: data.message || 'Unknown error' };
            }
        } catch (error) {
            console.error('Error updating FCM token status:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Send test notification
     * @returns {Promise<Object>} - API response
     */
    async sendTestNotification() {
        try {
            const response = await fetch(`${this.fcmTokenEndpoint}/test-notification`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.authToken}`,
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                console.log('Test notification sent successfully');
                return { success: true, data: data.data };
            } else {
                return { success: false, error: data.message || 'Unknown error' };
            }
        } catch (error) {
            console.error('Error sending test notification:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Initialize FCM for web applications
     * @param {Object} firebaseConfig - Firebase configuration
     * @param {string} vapidKey - VAPID public key
     * @returns {Promise<Object>} - Initialization result
     */
    async initializeWebFCM(firebaseConfig, vapidKey) {
        try {
            // Check if Firebase is available
            if (typeof firebase === 'undefined') {
                throw new Error('Firebase SDK not loaded');
            }

            // Initialize Firebase if not already initialized
            if (!firebase.apps.length) {
                firebase.initializeApp(firebaseConfig);
            }

            // Check for service worker support
            if (!('serviceWorker' in navigator)) {
                throw new Error('Service Worker not supported');
            }

            // Register service worker
            const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
            console.log('Service worker registered successfully');

            // Initialize messaging
            const messaging = firebase.messaging();
            messaging.usePublicVapidKey(vapidKey);
            messaging.useServiceWorker(registration);

            // Request permission
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                throw new Error('Notification permission denied');
            }

            // Get FCM token
            const token = await messaging.getToken();
            if (!token) {
                throw new Error('Failed to get FCM token');
            }

            console.log('FCM token obtained:', token.substring(0, 20) + '...');

            // Register token with server
            const deviceInfo = `${navigator.userAgent} - ${new Date().toISOString()}`;
            const result = await this.registerToken(token, deviceInfo);

            if (result.success) {
                // Set up foreground message handler
                messaging.onMessage((payload) => {
                    console.log('Foreground message received:', payload);
                    this.handleForegroundMessage(payload);
                });

                // Set up token refresh handler
                messaging.onTokenRefresh(async () => {
                    try {
                        const newToken = await messaging.getToken();
                        console.log('Token refreshed:', newToken.substring(0, 20) + '...');
                        await this.registerToken(newToken, deviceInfo);
                    } catch (error) {
                        console.error('Token refresh error:', error);
                    }
                });

                return { success: true, token, data: result.data };
            } else {
                throw new Error(result.error);
            }

        } catch (error) {
            console.error('FCM initialization error:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Handle foreground messages
     * @param {Object} payload - Message payload
     */
    handleForegroundMessage(payload) {
        const notification = payload.notification;
        
        if (document.visibilityState === 'visible') {
            // Show custom notification or toast
            this.showCustomNotification(notification.title, notification.body, payload.data);
        } else {
            // Show browser notification
            new Notification(notification.title, {
                body: notification.body,
                icon: notification.icon || '/img/default.png',
                data: payload.data
            });
        }
    }

    /**
     * Show custom notification (override this method for custom UI)
     * @param {string} title - Notification title
     * @param {string} body - Notification body
     * @param {Object} data - Additional data
     */
    showCustomNotification(title, body, data) {
        // Default implementation - create a simple toast
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #333;
            color: white;
            padding: 15px;
            border-radius: 5px;
            z-index: 10000;
            max-width: 300px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        `;
        toast.innerHTML = `<strong>${title}</strong><br>${body}`;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    }
}

// Export for use in different environments
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FcmConnectorManager;
} else if (typeof window !== 'undefined') {
    window.FcmConnectorManager = FcmConnectorManager;
}

// Usage example:
/*
// Initialize the manager
const fcmManager = new FcmConnectorManager('https://your-api-url.com', 'your-auth-token');

// For web applications
const firebaseConfig = {
    // your firebase config
};
const vapidKey = 'your-vapid-key';

fcmManager.initializeWebFCM(firebaseConfig, vapidKey)
    .then(result => {
        if (result.success) {
            console.log('FCM initialized successfully');
        } else {
            console.error('FCM initialization failed:', result.error);
        }
    });

// For mobile applications (React Native, Flutter, etc.)
// Just use the registerToken method after getting the token from the platform
fcmManager.registerToken('fcm-token-from-platform', 'device-info')
    .then(result => {
        if (result.success) {
            console.log('Token registered successfully');
        }
    });
*/