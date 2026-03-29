# FCM Token Management API Documentation

## Overview
The FCM Token Management API allows authenticated users to register, manage, and test Firebase Cloud Messaging (FCM) tokens for push notifications. This API is part of the Connector module and provides endpoints for mobile apps and web clients to handle notification tokens.

## Base URL
```
{your-domain}/connector/api/fcm-tokens
```

## Authentication
All endpoints require authentication using Bearer token:
```
Authorization: Bearer {your-auth-token}
```

## Endpoints

### 1. Register/Update FCM Token
**POST** `/connector/api/fcm-tokens`

Register a new FCM token or update an existing one for the authenticated user.

#### Request Body
```json
{
    "fcm_token": "string (required) - The FCM token from Firebase",
    "device_info": "string (optional) - Device information"
}
```

#### Response
**Success (200)**
```json
{
    "success": true,
    "data": {
        "message": "FCM token registered successfully",
        "token_id": 123,
        "user_id": 456
    }
}
```

**Error (400/500)**
```json
{
    "success": false,
    "message": "Error message"
}
```

#### Example
```javascript
const response = await fetch('/connector/api/fcm-tokens', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer your-auth-token',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        fcm_token: 'fBNOV6BD0t3M_lUFygtFVR:APA91bHgwwat2BGe5X5OqvLhtJc...',
        device_info: 'iPhone 13 Pro - iOS 15.0'
    })
});
```

---

### 2. Get User's FCM Tokens
**GET** `/connector/api/fcm-tokens`

Retrieve all FCM tokens for the authenticated user.

#### Response
**Success (200)**
```json
{
    "success": true,
    "data": {
        "tokens": [
            {
                "id": 123,
                "token_preview": "fBNOV6BD0t3M_lUFygtF...",
                "device_info": "iPhone 13 Pro - iOS 15.0",
                "is_active": true,
                "last_used_at": "2026-01-26T17:30:00.000000Z",
                "created_at": "2026-01-26T10:00:00.000000Z"
            }
        ],
        "total_count": 1
    }
}
```

#### Example
```javascript
const response = await fetch('/connector/api/fcm-tokens', {
    headers: {
        'Authorization': 'Bearer your-auth-token',
        'Accept': 'application/json'
    }
});
```

---

### 3. Delete FCM Token
**DELETE** `/connector/api/fcm-tokens/{id}`

Delete a specific FCM token.

#### Parameters
- `id` (integer, required) - The token ID to delete

#### Response
**Success (200)**
```json
{
    "success": true,
    "data": {
        "message": "FCM token deleted successfully"
    }
}
```

**Error (404)**
```json
{
    "success": false,
    "message": "FCM token not found"
}
```

#### Example
```javascript
const response = await fetch('/connector/api/fcm-tokens/123', {
    method: 'DELETE',
    headers: {
        'Authorization': 'Bearer your-auth-token',
        'Accept': 'application/json'
    }
});
```

---

### 4. Update FCM Token Status
**PATCH** `/connector/api/fcm-tokens/{id}/status`

Activate or deactivate a specific FCM token.

#### Parameters
- `id` (integer, required) - The token ID to update

#### Request Body
```json
{
    "is_active": true
}
```

#### Response
**Success (200)**
```json
{
    "success": true,
    "data": {
        "message": "FCM token status updated successfully",
        "is_active": true
    }
}
```

#### Example
```javascript
const response = await fetch('/connector/api/fcm-tokens/123/status', {
    method: 'PATCH',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer your-auth-token',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        is_active: false
    })
});
```

---

### 5. Send Test Notification
**POST** `/connector/api/fcm-tokens/test-notification`

Send a test notification to the authenticated user's devices.

#### Response
**Success (200)**
```json
{
    "success": true,
    "data": {
        "message": "Test notification sent successfully",
        "success": true
    }
}
```

#### Example
```javascript
const response = await fetch('/connector/api/fcm-tokens/test-notification', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer your-auth-token',
        'Accept': 'application/json'
    }
});
```

---

## Integration Examples

### Web Application Integration

```javascript
// Initialize FCM Manager
const fcmManager = new FcmConnectorManager('https://your-api-url.com', 'your-auth-token');

// Firebase configuration
const firebaseConfig = {
    apiKey: "your-api-key",
    authDomain: "your-project.firebaseapp.com",
    projectId: "your-project-id",
    storageBucket: "your-project.firebasestorage.app",
    messagingSenderId: "123456789",
    appId: "your-app-id"
};

const vapidKey = "your-vapid-public-key";

// Initialize FCM for web
fcmManager.initializeWebFCM(firebaseConfig, vapidKey)
    .then(result => {
        if (result.success) {
            console.log('FCM initialized successfully');
            console.log('Token registered:', result.token);
        } else {
            console.error('FCM initialization failed:', result.error);
        }
    });
```

### Mobile Application Integration

```javascript
// For React Native or other mobile frameworks
// After getting FCM token from the platform

const fcmManager = new FcmConnectorManager('https://your-api-url.com', 'your-auth-token');

// Register token
fcmManager.registerToken(fcmTokenFromPlatform, deviceInfo)
    .then(result => {
        if (result.success) {
            console.log('Token registered successfully');
        } else {
            console.error('Token registration failed:', result.error);
        }
    });
```

### Flutter Integration

```dart
// In your Flutter app
import 'package:http/http.dart' as http;
import 'dart:convert';

class FcmTokenService {
    final String baseUrl;
    final String authToken;
    
    FcmTokenService(this.baseUrl, this.authToken);
    
    Future<Map<String, dynamic>> registerToken(String fcmToken, String deviceInfo) async {
        final response = await http.post(
            Uri.parse('$baseUrl/connector/api/fcm-tokens'),
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer $authToken',
                'Accept': 'application/json',
            },
            body: jsonEncode({
                'fcm_token': fcmToken,
                'device_info': deviceInfo,
            }),
        );
        
        return jsonDecode(response.body);
    }
}

// Usage
final fcmService = FcmTokenService('https://your-api-url.com', 'your-auth-token');
final result = await fcmService.registerToken(fcmToken, 'Flutter App - Android 12');
```

## Error Handling

### Common Error Responses

**401 Unauthorized**
```json
{
    "success": false,
    "message": "Not authenticated"
}
```

**422 Validation Error**
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "fcm_token": ["The fcm token field is required."]
    }
}
```

**500 Internal Server Error**
```json
{
    "success": false,
    "message": "Failed to register FCM token"
}
```

## Best Practices

1. **Token Management**: Always register the FCM token when the user logs in or when the app starts.

2. **Token Refresh**: Handle token refresh events and update the server with the new token.

3. **Error Handling**: Implement proper error handling for network failures and API errors.

4. **Device Info**: Include meaningful device information to help with debugging and analytics.

5. **Token Cleanup**: Delete tokens when the user logs out or uninstalls the app.

6. **Testing**: Use the test notification endpoint to verify that notifications are working correctly.

## Security Considerations

1. **Authentication**: All endpoints require valid authentication tokens.

2. **Token Privacy**: FCM tokens are sensitive and should be handled securely.

3. **Rate Limiting**: Implement rate limiting on the client side to avoid excessive API calls.

4. **HTTPS**: Always use HTTPS for API communications.

## Troubleshooting

### Common Issues

1. **Token Not Received**: Check if the FCM token is being generated correctly on the client side.

2. **Notifications Not Delivered**: Verify that the token is active and the user has granted notification permissions.

3. **Authentication Errors**: Ensure the Bearer token is valid and not expired.

4. **Duplicate Token Errors**: The API handles token reassignment automatically, but check logs for any issues.

### Debugging

1. Check the Laravel logs for detailed error messages:
   ```bash
   tail -f storage/logs/laravel.log | grep "Connector API"
   ```

2. Use the test notification endpoint to verify the setup.

3. Check the FCM token status using the GET endpoint.

## Support

For additional support or questions about the FCM Token API, please refer to the main application documentation or contact the development team.